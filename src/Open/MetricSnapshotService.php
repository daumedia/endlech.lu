<?php

namespace App\Open;

use App\Entity\MetricSnapshot;
use App\Enum\FinanceType;
use App\Repository\FinanceEntryRepository;
use App\Repository\MetricSnapshotRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Friert die aktuellen Kennzahlen als Monatswert ein.
 *
 * Ausdrücklich idempotent: Der Aufruf läuft aus zwei Richtungen (Scheduler und
 * Konsolenbefehl) und darf einen bereits geschriebenen Monat nicht ein zweites
 * Mal anlegen – sonst stünde derselbe Punkt doppelt in der Verlaufsgrafik.
 */
final class MetricSnapshotService
{
    public function __construct(
        private readonly OpenStatsService $stats,
        private readonly MetricSnapshotRepository $repository,
        private readonly FinanceEntryRepository $financeRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Der Monat, den ein Lauf ohne Argument festhält: der abgeschlossene
     * Vormonat.
     *
     * Der Job läuft am Ersten. Würde er den laufenden Monat schreiben, stünde
     * in der Grafik ein Punkt für einen Monat, der noch nicht stattgefunden
     * hat – und jeder Verlauf endete mit einem künstlichen Einbruch.
     */
    public function defaultMonth(?\DateTimeImmutable $now = null): \DateTimeImmutable
    {
        return ($now ?? new \DateTimeImmutable())
            ->modify('first day of last month')
            ->setTime(0, 0);
    }

    /**
     * @return array{snapshot: MetricSnapshot, created: bool}
     */
    public function capture(?\DateTimeImmutable $month = null, bool $force = false): array
    {
        $month = ($month ?? $this->defaultMonth())->modify('first day of this month')->setTime(0, 0);
        $existing = $this->repository->findForMonth($month);

        if ($existing && !$force) {
            return ['snapshot' => $existing, 'created' => false];
        }

        $snapshot = $existing ?? new MetricSnapshot();
        $snapshot->setCapturedFor($month);

        $this->fill($snapshot, $this->stats->computeAll());

        if (!$existing) {
            $this->entityManager->persist($snapshot);
        }

        $this->entityManager->flush();

        return ['snapshot' => $snapshot, 'created' => null === $existing];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function fill(MetricSnapshot $snapshot, array $data): void
    {
        /** @var array<string, mixed> $platform */
        $platform = $data['platform'];
        /** @var array<string, mixed> $impact */
        $impact = $data['impact'];
        /** @var array<string, mixed> $finance */
        $finance = $data['finance'];

        $snapshot
            ->setRestaurantCount((int) $platform['restaurants'])
            ->setVerifiedCount((int) $platform['verified'])
            ->setCommunesCovered((int) $platform['communesCovered'])
            ->setCantonsCovered((int) $platform['cantonsCovered'])
            ->setAverageAccessibilityScore(number_format((float) $platform['averageScore'], 2, '.', ''))
            ->setStepFreeEntrances((int) $impact['stepFreeEntrances'])
            ->setAccessibleRestrooms((int) $impact['accessibleRestrooms'])
            ->setWideDoors((int) $impact['wideDoors'])
            ->setWheelchairTableSpacing((int) $impact['wheelchairTableSpacing'])
            ->setInclusionBoxesDelivered((int) $impact['inclusionBoxesDelivered'])
            ->setTotalExpenses(number_format((float) $finance['totalExpenses'], 2, '.', ''))
            // Direkt aus dem Repository statt aus $finance: Die Quartalssperre
            // ist eine Anzeigeregel und leert dort die Einnahmenseite. Würde
            // der Snapshot sie übernehmen, stünde für die Anfangsmonate
            // dauerhaft eine 0 in der Historie – und ließe sich später nicht
            // mehr korrigieren, weil genau das der Zweck eines Snapshots ist.
            ->setTotalIncome(number_format($this->financeRepository->sumByType(FinanceType::INCOME), 2, '.', ''))
            ->setPayload([
                'platform' => $platform,
                'impact' => $impact,
                'finance' => $finance,
            ]);
    }
}
