<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\BodyRendererInterface;

/**
 * Meldet, wenn der Messenger-Worker steht.
 *
 * ⚠ **Dieser Ausfall ist der lautloseste des Projekts.** Läuft der Worker nicht,
 * stapeln sich die Nachrichten in `messenger_messages`, während die Anwendung
 * weiter „erfolgreich" meldet: Keine Bestätigungsmail geht mehr hinaus —
 * Registrierung, alle **drei** Wartelisten, E-Mail-Wechsel —, kein
 * Monats-Snapshot entsteht, kein Brevo-Abgleich läuft. Für den Nutzer sieht das
 * aus wie ein kaputtes Formular; er trägt sich ein, liest „bitte bestätige den
 * Link" und bekommt nichts.
 *
 * Bis heute stand der Prüfweg als Handgriff in `CLAUDE.md` (`messenger:stats`).
 * Ein Handgriff, den niemand täglich ausführt, ist keine Überwachung — er ist
 * die Anleitung, die man liest, wenn sich schon jemand beschwert hat.
 *
 * ⚠ **Der Alarm geht per Mail — also über denselben Weg, der ausgefallen sein
 * könnte.** Deshalb versendet dieser Befehl über `TransportInterface` **direkt**,
 * am Messenger vorbei. `MailerInterface` täte das nicht: Er schiebt jede Mail
 * über den Bus, und die Warnung landete in genau der Warteschlange, vor der sie
 * warnt. Beim Einrichten gemessen — der Stand stieg von 30 auf 31, und die 31.
 * war die Warnung selbst.
 *
 * Wenn schon der Mailversand als solcher tot ist, hilft auch das nicht mehr;
 * dann greift nur die Uptime-Prüfung von außen. Sie ist die zweite Hälfte
 * dieser Überwachung, kein Ersatz.
 */
#[AsCommand(
    name: 'app:messenger:watch',
    description: 'Meldet einen Rückstau in der Messenger-Warteschlange per E-Mail',
)]
final class MessengerWatchCommand extends Command
{
    use LockableTrait;

    /**
     * Ab dieser Zahl unbearbeiteter Nachrichten gilt die Lage als Ausfall.
     *
     * Im Normalbetrieb ist die Warteschlange innerhalb von Sekunden leer; ein
     * zweistelliger Stand entsteht nur, wenn niemand sie abarbeitet. 25 statt
     * 5, damit ein Versandstoß (etwa ein Kampagnenlauf) keinen Fehlalarm
     * auslöst — ein Alarm, der einmal grundlos kam, wird beim zweiten Mal
     * weggeklickt.
     */
    public const int SCHWELLE = 25;

    /** Wie lange eine Nachricht liegen darf, bevor sie als hängend gilt. */
    public const string ALTER = '-30 minutes';

    public function __construct(
        private readonly Connection $connection,
        // ⚠ **`TransportInterface`, NICHT `MailerInterface`.** Letzterer schiebt
        // jede Mail über den Messenger-Bus (`SendEmailMessage: async` in
        // `messenger.yaml`) — die Warnung läge damit in genau der
        // Warteschlange, vor der sie warnt, und ginge erst hinaus, wenn der
        // Worker wieder läuft. Also nie, wenn es darauf ankommt.
        //
        // Beim Einrichten gemessen: Mit `MailerInterface` stieg der Stand von
        // 30 auf 31 unbearbeitete Nachrichten, und die 31. war die Warnung.
        private readonly TransportInterface $transport,
        private readonly BodyRendererInterface $bodyRenderer,
        #[Autowire('%app.contact_email%')]
        private readonly string $contactEmail,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Nur berichten, keine Mail versenden',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock()) {
            return Command::SUCCESS;
        }

        try {
            return $this->pruefe($input, $output);
        } finally {
            $this->release();
        }
    }

    private function pruefe(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // ⚠ Rohes SQL statt `messenger:stats`: Jener Befehl schreibt für
        // Menschen und lässt sich nicht auswerten. Die Tabelle liegt seit
        // Version20260113160019 fest.
        try {
            $offen = (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM messenger_messages WHERE delivered_at IS NULL',
            );
            $haengend = (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM messenger_messages WHERE delivered_at IS NOT NULL AND delivered_at < :grenze',
                ['grenze' => (new \DateTimeImmutable(self::ALTER))->format('Y-m-d H:i:s')],
            );
        } catch (\Throwable $e) {
            // Kommt die Datenbank nicht, ist das ein größeres Problem als ein
            // Rückstau — und eines, das die Uptime-Prüfung sieht.
            $io->error(sprintf('Warteschlange nicht lesbar: %s', $e::class));

            return Command::FAILURE;
        }

        $io->writeln(sprintf(
            'Unbearbeitet: %d · seit über 30 Minuten in Zustellung: %d · Schwelle: %d',
            $offen,
            $haengend,
            self::SCHWELLE,
        ));

        if ($offen < self::SCHWELLE && 0 === $haengend) {
            $io->success('Die Warteschlange läuft.');

            return Command::SUCCESS;
        }

        $io->warning('Rückstau erkannt.');

        if ($input->getOption('dry-run')) {
            $io->note('--dry-run: keine Mail versendet.');

            return Command::SUCCESS;
        }

        $this->melde($io, $offen, $haengend);

        // ⚠ SUCCESS, nicht FAILURE: Der Befehl hat getan, was er soll — er hat
        // gemeldet. Bei FAILURE würfe `RunCommandMessage` eine Ausnahme, die
        // Nachricht liefe in den `failed`-Transport, und ausgerechnet die
        // Überwachung füllte die Warteschlange weiter, die sie beobachtet.
        return Command::SUCCESS;
    }

    private function melde(SymfonyStyle $io, int $offen, int $haengend): void
    {
        $mail = (new TemplatedEmail())
            ->to($this->contactEmail)
            ->subject(sprintf('⚠ Endlech.lu: %d Nachrichten in der Warteschlange', $offen))
            ->htmlTemplate('email/ops/messenger_backlog.html.twig')
            ->context([
                'offen' => $offen,
                'haengend' => $haengend,
                'schwelle' => self::SCHWELLE,
            ]);

        try {
            // Die Vorlage muss von Hand gerendert werden: Das erledigt sonst
            // der Messenger-Handler, den wir hier bewusst umgehen.
            $this->bodyRenderer->render($mail);
            $this->transport->send($mail);
            $io->note(sprintf('Meldung an %s versendet — direkt über den Transport.', $this->contactEmail));
        } catch (TransportExceptionInterface $e) {
            // Der Fall, in dem die Überwachung selbst nichts mehr ausrichten
            // kann. Er gehört ins Protokoll — dort liest ihn Sentry mit, weil
            // `error` über der `fingers_crossed`-Schwelle liegt.
            $io->error(sprintf(
                'Meldung konnte NICHT versendet werden (%s). Der Mailversand ist selbst betroffen.',
                $e::class,
            ));
        }
    }
}
