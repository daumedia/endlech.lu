<?php

namespace App\Repository;

use App\Entity\FinanceEntry;
use App\Enum\FinanceCategory;
use App\Enum\FinanceType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FinanceEntry>
 */
class FinanceEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FinanceEntry::class);
    }

    /**
     * @return FinanceEntry[]
     */
    public function findForAdmin(?FinanceType $type = null): array
    {
        $qb = $this->createQueryBuilder('f')
            ->orderBy('f.date', 'DESC')
            ->addOrderBy('f.id', 'DESC');

        if ($type) {
            $qb->andWhere('f.type = :type')->setParameter('type', $type);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Summen je Kategorie – die einzige Auflösung, in der Finanzdaten die
     * Anwendung verlassen.
     *
     * @return array<string, array{category: FinanceCategory, total: float, count: int, quantity: int}>
     */
    public function sumByCategory(FinanceType $type, ?\DateTimeImmutable $until = null): array
    {
        $qb = $this->createQueryBuilder('f')
            ->select('f.category AS category', 'SUM(f.amount) AS total', 'COUNT(f.id) AS entries', 'SUM(f.quantity) AS quantity')
            ->andWhere('f.type = :type')
            ->setParameter('type', $type)
            ->groupBy('f.category');

        if ($until) {
            $qb->andWhere('f.date <= :until')->setParameter('until', $until);
        }

        $result = [];

        foreach ($qb->getQuery()->getArrayResult() as $row) {
            $category = $row['category'] instanceof FinanceCategory
                ? $row['category']
                : FinanceCategory::from((string) $row['category']);

            $result[$category->value] = [
                'category' => $category,
                'total' => (float) $row['total'],
                'count' => (int) $row['entries'],
                'quantity' => (int) $row['quantity'],
            ];
        }

        return $result;
    }

    public function sumByType(FinanceType $type, ?\DateTimeImmutable $until = null): float
    {
        $qb = $this->createQueryBuilder('f')
            ->select('SUM(f.amount)')
            ->andWhere('f.type = :type')
            ->setParameter('type', $type);

        if ($until) {
            $qb->andWhere('f.date <= :until')->setParameter('until', $until);
        }

        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Gesamtstückzahl einer mengenführenden Kategorie (gelieferte Inclusion
     * Boxes).
     */
    public function sumQuantity(FinanceCategory $category, ?\DateTimeImmutable $until = null): int
    {
        $qb = $this->createQueryBuilder('f')
            ->select('SUM(f.quantity)')
            ->andWhere('f.category = :category')
            ->setParameter('category', $category);

        if ($until) {
            $qb->andWhere('f.date <= :until')->setParameter('until', $until);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Frühestes Belegdatum einer Richtung – Grundlage für die Quartalsregel,
     * die Einnahmen bis zum ersten vollständigen Quartal zurückhält.
     */
    public function findEarliestDate(FinanceType $type): ?\DateTimeImmutable
    {
        $result = $this->createQueryBuilder('f')
            ->select('MIN(f.date)')
            ->andWhere('f.type = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? new \DateTimeImmutable((string) $result) : null;
    }

    /**
     * Zeitpunkt der letzten Pflege – erscheint als "Stand vom …" in der
     * Finanzsektion. Ein Dashboard, dem man das Alter nicht ansieht, richtet
     * mehr Schaden an als gar keines.
     */
    public function findLastUpdatedAt(): ?\DateTimeImmutable
    {
        $result = $this->createQueryBuilder('f')
            ->select('MAX(f.updatedAt)')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? new \DateTimeImmutable((string) $result) : null;
    }
}
