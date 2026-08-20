<?php

namespace App\Command;

use App\Open\MetricSnapshotService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Schreibt den Monats-Snapshot der Open-Startup-Kennzahlen.
 *
 * Für Production der eigentliche Auslöser: Dort läuft kein Messenger-Worker,
 * also feuert der Zeitplan aus App\Schedule nicht. Der Cron-Eintrag ruft
 * diesen Befehl am Ersten jedes Monats auf.
 *
 * `--month` holt einzelne Monate nach – etwa nach einem ausgefallenen Cron
 * oder beim erstmaligen Befüllen der Historie. Die Werte sind dann allerdings
 * der *heutige* Stand, nicht der damalige; ein Snapshot kann nur festhalten,
 * was zum Zeitpunkt seines Laufs in der Datenbank steht.
 */
#[AsCommand(
    name: 'app:metrics:snapshot',
    description: 'Friert die Kennzahlen der Open-Startup-Seite als Monatswert ein',
)]
final class CaptureMetricSnapshotCommand extends Command
{
    public function __construct(private readonly MetricSnapshotService $snapshots)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'month',
                'm',
                InputOption::VALUE_REQUIRED,
                'Monat im Format YYYY-MM (Standard: der abgeschlossene Vormonat)',
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Vorhandenen Snapshot des Monats überschreiben',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $monthOption = $input->getOption('month');

        try {
            $month = null === $monthOption ? null : $this->parseMonth((string) $monthOption);
        } catch (\InvalidArgumentException $exception) {
            $io->error($exception->getMessage());

            return Command::INVALID;
        }

        $result = $this->snapshots->capture($month, (bool) $input->getOption('force'));
        $snapshot = $result['snapshot'];

        if (!$result['created'] && !$input->getOption('force')) {
            $io->note(sprintf(
                'Für %s existiert bereits ein Snapshot (#%d). Mit --force überschreiben.',
                $snapshot->getMonthKey(),
                $snapshot->getId(),
            ));

            return Command::SUCCESS;
        }

        $io->success(sprintf('Snapshot für %s geschrieben.', $snapshot->getMonthKey()));
        $io->definitionList(
            ['Restaurants' => (string) $snapshot->getRestaurantCount()],
            ['Verifiziert' => (string) $snapshot->getVerifiedCount()],
            ['Gemeinden' => (string) $snapshot->getCommunesCovered()],
            ['Kantone' => (string) $snapshot->getCantonsCovered()],
            ['Ø Punktzahl' => $snapshot->getAverageAccessibilityScore()],
            ['Ausgaben' => $snapshot->getTotalExpenses() . ' €'],
            ['Einnahmen' => $snapshot->getTotalIncome() . ' €'],
        );

        return Command::SUCCESS;
    }

    private function parseMonth(string $value): \DateTimeImmutable
    {
        if (1 !== preg_match('/^\d{4}-\d{2}$/', $value)) {
            throw new \InvalidArgumentException(sprintf('"%s" ist kein Monat im Format YYYY-MM.', $value));
        }

        $month = \DateTimeImmutable::createFromFormat('!Y-m-d', $value . '-01');

        if (false === $month) {
            throw new \InvalidArgumentException(sprintf('"%s" lässt sich nicht als Monat lesen.', $value));
        }

        return $month;
    }
}
