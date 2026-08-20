<?php

namespace App\Repository;

use App\Entity\PartnerWaitlistEntry;
use App\Enum\WaitlistStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PartnerWaitlistEntry>
 */
class PartnerWaitlistEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PartnerWaitlistEntry::class);
    }

    public function findOneByConfirmationToken(string $token): ?PartnerWaitlistEntry
    {
        return $this->findOneBy(['confirmationToken' => $token]);
    }

    /**
     * Unbestätigte Anmeldungen, die älter als $date sind – Grundlage für ein
     * späteres Aufräumen verwaister Double-Opt-Ins.
     *
     * @return PartnerWaitlistEntry[]
     */
    public function findPendingOlderThan(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->andWhere('p.createdAt < :date')
            ->setParameter('status', WaitlistStatus::PENDING)
            ->setParameter('date', $date)
            ->orderBy('p.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Admin-Liste: optional nach Status gefiltert, nach Eingangsdatum sortiert.
     *
     * @return PartnerWaitlistEntry[]
     */
    public function findFiltered(?WaitlistStatus $status = null, string $direction = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.restaurant', 'r')
            ->addSelect('r');

        if (null !== $status) {
            $qb->andWhere('p.status = :status')
                ->setParameter('status', $status);
        }

        $qb->orderBy('p.createdAt', 'ASC' === strtoupper($direction) ? 'ASC' : 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Anzahl je Status, für die Filter-Pills im Admin.
     *
     * @return array<string, int>
     */
    public function countByStatus(): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('p.status AS status, COUNT(p.id) AS total')
            ->groupBy('p.status')
            ->getQuery()
            ->getArrayResult();

        $counts = [];

        foreach ($rows as $row) {
            $status = $row['status'];
            $counts[$status instanceof WaitlistStatus ? $status->value : (string) $status] = (int) $row['total'];
        }

        return $counts;
    }

    public function countPending(): int
    {
        return $this->count(['status' => WaitlistStatus::PENDING]);
    }
}
