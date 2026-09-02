<?php

namespace App\Command;

use App\Open\MetricSnapshotService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Schreibt den Monats-Snapshot der Open-Startup-Kennzahlen.
 *
 * Der reguläre Auslöser ist seit dem 2026-09-02 der Zeitplan
 * {@see \App\Scheduler\MetricsScheduleProvider} — er schickt am Ersten jedes
 * Monats eine `CaptureMetricSnapshot` los, die im Dienst dasselbe tut wie dieser
 * Befehl. Der Befehl bleibt für Nachläufe und Prüfungen von Hand.
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
    // Verhindert, dass sich zwei Durchgänge überlappen – der Scheduler alle
    // fünf Minuten, ein Nachlauf von Hand, ein zweiter Worker.
    use LockableTrait;

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
