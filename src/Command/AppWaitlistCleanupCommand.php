<?php

declare(strict_types=1);

namespace App\Command;

use App\Waitlist\StaleAppWaitlistCleaner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Räumt nie bestätigte Vormerkungen der App-Warteliste ab (AK-47, AK-49).
 *
 * Der reguläre Auslöser ist der Zeitplan `marketing`
 * ({@see \App\Scheduler\MarketingScheduleProvider}) — er schickt diesen Befehl
 * einmal täglich los. Er bleibt daneben von Hand aufrufbar, und dieselbe
 * Aufräumung stößt die Wartelisten-Verwaltung beim Öffnen an, höchstens einmal
 * je Tag. Zwei Wege, weil auf Produktion schon zweimal ein geplanter Lauf
 * ausblieb — siehe {@see StaleAppWaitlistCleaner}.
 */
#[AsCommand(
    name: 'app:app-waitlist:cleanup',
    description: 'Löscht nie bestätigte App-Vormerkungen älter als 30 Tage',
)]
final class AppWaitlistCleanupCommand extends Command
{
    // Verhindert, dass sich zwei Durchgänge überlappen – der Zeitplan und ein
    // Aufruf von Hand können zusammenfallen.
    use LockableTrait;

    public function __construct(private readonly StaleAppWaitlistCleaner $cleaner)
    {
        parent::__construct();
    }

    /**
     * Nimmt die Sperre und gibt sie in jedem Fall wieder frei.
     *
     * ⚠ **Das `finally` ist Pflicht, nicht Stil.** `LockableTrait::lock()` wirft
     * beim zweiten Aufruf auf derselben Instanz „A lock is already in place." —
     * und genau das tut ein Prüflauf, der `execute()` zweimal hintereinander auf
     * demselben Objekt ruft.
     *
     * ⚠ **Ein belegtes Schloss ist SUCCESS, kein FAILURE.** Bei FAILURE würfe
     * `RunCommandMessage` eine Ausnahme, die Nachricht liefe in den
     * `failed`-Transport, und dort stapelte sich Rauschen statt echter Befunde.
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
            $io = new SymfonyStyle($input, $output);
            $geloescht = $this->cleaner->sweep();

            if (0 === $geloescht) {
                $io->success('Nichts abzuräumen — keine unbestätigte Vormerkung ist älter als 30 Tage.');
            } else {
                $io->success(sprintf('%d nie bestätigte Vormerkung(en) gelöscht.', $geloescht));
            }

            return Command::SUCCESS;
        } finally {
            $this->release();
        }
    }
}
