<?php

declare(strict_types=1);

namespace App\Command;

use App\Board\StaleIdeaCleaner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Räumt nie freigegebene Einreichungen des Community-Boards ab (AK-74).
 *
 * Für einen Cron-Eintrag gedacht, aber nicht darauf angewiesen: Dieselbe
 * Aufräumung stößt die Moderationsschlange beim Öffnen an, höchstens einmal je
 * Tag. Siehe `StaleIdeaCleaner`.
 */
#[AsCommand(
    name: 'app:board:cleanup',
    description: 'Löscht nie freigegebene Board-Einreichungen älter als zwölf Monate',
)]
final class BoardCleanupCommand extends Command
{
    public function __construct(private readonly StaleIdeaCleaner $cleaner)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $geloescht = $this->cleaner->sweep();

        if (0 === $geloescht) {
            $io->success('Nichts abzuräumen — keine wartende Einreichung ist älter als zwölf Monate.');
        } else {
            $io->success(sprintf('%d nie freigegebene Einreichung(en) gelöscht.', $geloescht));
        }

        return Command::SUCCESS;
    }
}
