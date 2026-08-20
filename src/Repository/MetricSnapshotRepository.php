<?php

namespace App\Repository;

use App\Entity\MetricSnapshot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MetricSnapshot>
 */
class MetricSnapshotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MetricSnapshot::class);
    }

    public function findForMonth(\DateTimeImmutable $month): ?MetricSnapshot
    {
        return $this->findOneBy([
            'capturedFor' => $month->modify('first day of this month')->setTime(0, 0),
        ]);
    }

    /**
     * Chronologisch aufsteigend – die Verlaufsgrafik liest von links nach
     * rechts, ein DESC-Ergebnis müsste jede Ansicht selbst umdrehen.
     *
     * @return MetricSnapshot[]
     */
    public function findTrend(int $months = 12): array
    {
        $snapshots = $this->createQueryBuilder('s')
            ->orderBy('s.capturedFor', 'DESC')
            ->setMaxResults($months)
            ->getQuery()
            ->getResult();

        return array_reverse($snapshots);
    }

    public function findLatest(): ?MetricSnapshot
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.capturedFor', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
