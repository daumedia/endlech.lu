<?php

declare(strict_types=1);

namespace App\Command;

use App\Marketing\MarketingSyncService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Arbeitet das Auftragsbuch des Brevo-Kontaktabgleichs ab (Feature 04).
 *
 * Der einzige reguläre Auslöser von {@see MarketingSyncService::run()}: Der
 * Dienst läuft bewusst nie in einer Anfrage, damit keine Anmeldung an der
 * Erreichbarkeit von Brevo hängt (AK-17). Ausgelöst wird der Befehl **alle 5
 * Minuten** vom Zeitplan {@see \App\Scheduler\MarketingScheduleProvider} — die
 * Frist von 15 Minuten aus AK-10 ist damit dreifach unterschritten.
 *
 * ⚠ Der Zeitplan ruft diesen Befehl über `RunCommandMessage`, geht also exakt
 * denselben Weg wie ein Aufruf von Hand. Ein eigener Messenger-Handler müsste die
 * Prüfung von `--limit`, die Unterscheidung von Fehlversuch und fehlendem
 * Schlüssel sowie AK-31 nachbauen — und liefe irgendwann auseinander.
 *
 * ⚠ **Fehlversuche färben den Lauf nicht rot** (AK-19). Sie sind der eingeplante
 * Normalfall — der nächste Durchgang greift sie von allein wieder auf. Ein
 * Cron-Job, der alle 5 Minuten wegen eines einzelnen 429ers scheitert, ist ein
 * Alarm, den nach zwei Tagen niemand mehr liest. Rot wird der Lauf ausschließlich
 * bei fehlendem API-Schlüssel: Das ist ein Konfigurationsfehler und bleibt
 * bestehen, bis jemand eingreift.
 *
 * ⚠ **Keine E-Mail-Adressen in der Ausgabe** (AK-31). Sie landet in Logdateien
 * und in der Cron-Mail an den Systembenutzer. Das Ergebnisobjekt trägt deshalb
 * nur Zahlen — dabei bleibt es auch hier.
 */
#[AsCommand(
    name: 'app:marketing:sync',
    description: 'Überträgt offene Marketing-Kontakte an Brevo und führt Löschaufträge aus',
)]
final class MarketingSyncCommand extends Command
{
    // Verhindert, dass sich zwei Durchgänge überlappen – der Scheduler alle
    // fünf Minuten, ein Nachlauf von Hand, ein zweiter Worker.
    use LockableTrait;

    public function __construct(private readonly MarketingSyncService $sync)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'limit',
            'l',
            InputOption::VALUE_REQUIRED,
            'Höchstzahl der Einträge in diesem Lauf (Standard: app.brevo_sync_batch, 200)',
        );
    }

    /**
     * Nimmt die Sperre und gibt sie in jedem Fall wieder frei.
     *
     * ⚠ **Das `finally` ist Pflicht, nicht Stil.** `LockableTrait::lock()` wirft
     * beim zweiten Aufruf auf derselben Instanz „A lock is already in place." —
     * und genau das tut `CaptureMetricSnapshotCommandTest`, der `execute()`
     * zweimal hintereinander auf demselben Objekt ruft, um die Idempotenz zu
     * prüfen. Ohne die Freigabe wäre der Prüflauf rot.
     *
     * ⚠ **Ein belegtes Schloss ist SUCCESS, kein FAILURE.** Der Lauf wurde
     * übersprungen, weil bereits einer unterwegs ist — das ist der eingeplante
     * Normalfall und kein Fehler. Bei FAILURE würde `RunCommandMessage` eine
     * Ausnahme werfen, die Nachricht liefe in den `failed`-Transport, und bei
     * einem Fünf-Minuten-Takt stapelte sich dort Rauschen.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock()) {
            (new SymfonyStyle($input, $output))->warning(
                'Es läuft bereits ein Durchgang – dieser Aufruf endet ohne Wirkung.',
            );

            return Command::SUCCESS;
        }

        try {
            return $this->fuehreAus($input, $output);
        } finally {
            $this->release();
        }
    }

    private function fuehreAus(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limitOption = $input->getOption('limit');

        // Ohne Prüfung würde aus `--limit=abc` per Cast eine 0, und der Lauf
        // täte still gar nichts – der teuerste aller Ausgänge, weil er wie ein
        // leeres Auftragsbuch aussieht.
        if (null !== $limitOption && 1 !== preg_match('/^[1-9]\d*$/', (string) $limitOption)) {
            $io->error(sprintf('"%s" ist keine positive Ganzzahl.', (string) $limitOption));

            return Command::INVALID;
        }

        $result = $this->sync->run(null === $limitOption ? null : (int) $limitOption);

        if (!$result->configured) {
            $io->error('Kein Brevo-API-Schlüssel gesetzt – es wurde nichts übertragen.');
            $io->writeln('Der Schlüssel gehört als <info>BREVO_API_KEY</info> in die ungetrackte <info>.env.local</info> auf dem Server.');
            $io->writeln('Die Aufträge bleiben stehen und gehen beim ersten Lauf mit gesetztem Schlüssel hinaus.');

            return Command::FAILURE;
        }

        if (!$result->hasWork()) {
            $io->writeln('Nichts zu tun.');

            return Command::SUCCESS;
        }

        $io->writeln(sprintf(
            'Übertragen: %d · Entfernt: %d · Fehlgeschlagen: %d · Übersprungen: %d',
            $result->synced,
            $result->removed,
            $result->failed,
            $result->skipped,
        ));

        if ($result->failed > 0) {
            $io->writeln(sprintf(
                '%d Fehlversuch(e) – der nächste Lauf greift sie erneut auf; den Grund zeigt die Verwaltung.',
                $result->failed,
            ));
        }

        return Command::SUCCESS;
    }
}
