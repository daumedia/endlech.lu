<?php

namespace App\Repository;

use App\Entity\Restaurant;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Restaurant>
 */
class RestaurantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Restaurant::class);
    }

    /**
     * Die bestbewerteten Häuser für die Startseite.
     *
     * ⚠️ **`Paginator` ist hier Pflicht, nicht Bequemlichkeit.** Die beiden
     * `addSelect()` holen Öffnungszeiten und Küchen mit (gegen N+1) — dadurch
     * erzeugt jedes Restaurant so viele SQL-Zeilen, wie es Kombinationen aus
     * beidem hat. `setMaxResults()` begrenzt aber die **Zeilen**, nicht die
     * Entities: Das bestbewertete Haus allein brachte 14 Zeilen mit, und
     * `findTopRated(6)` lieferte dadurch **ein** Restaurant statt sechs
     * (QA B12, BF-64). Der zweite Parameter `$fetchJoinCollection` ist genau
     * für diesen Fall da.
     *
     * @return Restaurant[]
     */
    public function findTopRated(int $limit = 6): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.openingHours', 'oh')
            ->addSelect('oh')
            ->leftJoin('r.cuisines', 'c')
            ->addSelect('c')
            ->orderBy('r.rating', 'DESC')
            ->addOrderBy('r.name', 'ASC')
            ->setMaxResults($limit);

        return iterator_to_array(new Paginator($qb->getQuery(), true), false);
    }

    public function findPaginated(string $sort = 'rating', int $page = 1, int $limit = 6, array $filters = []): Paginator
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.openingHours', 'oh')
            ->addSelect('oh')
            ->leftJoin('r.cuisines', 'c')
            ->addSelect('c');

        if (!empty($filters['verified'])) {
            $qb->andWhere('r.isVerified = true');
        }
        if (!empty($filters['wheelchair'])) {
            $qb->andWhere('r.isWheelchairAccessible = true');
        }
        if (!empty($filters['toilet'])) {
            $qb->andWhere('r.hasAccessibleToilet = true');
        }
        if (!empty($filters['dogs'])) {
            $qb->andWhere('r.allowsAssistanceDogs = true');
        }
        if (!empty($filters['lighting'])) {
            $qb->andWhere('r.hasBrightLighting = true');
        }
        if (!empty($filters['changing_table'])) {
            $qb->andWhere('r.hasChangingTable = true');
        }
        if (!empty($filters['disabled_parking'])) {
            $qb->andWhere('r.hasDisabledParking = true');
        }
        if (!empty($filters['open'])) {
            $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Luxembourg'));
            $currentTime = $now->format('H:i:s');
            $currentDay = (int) $now->format('N');
            $previousDay = $currentDay === 1 ? 7 : $currentDay - 1;

            $qb->leftJoin('r.openingHours', 'oh_today', 'WITH', 'oh_today.dayOfWeek = :currentDay')
                ->leftJoin('r.openingHours', 'oh_yesterday', 'WITH', 'oh_yesterday.dayOfWeek = :previousDay')
                ->andWhere(
                    '(oh_today.openTime <= oh_today.closeTime AND oh_today.openTime <= :currentTime AND oh_today.closeTime > :currentTime)' .
                    ' OR ' .
                    '(oh_today.openTime > oh_today.closeTime AND oh_today.openTime <= :currentTime)' .
                    ' OR ' .
                    '(oh_yesterday.openTime > oh_yesterday.closeTime AND oh_yesterday.closeTime > :currentTime)'
                )
                ->distinct()
                ->setParameter('currentDay', $currentDay)
                ->setParameter('previousDay', $previousDay)
                ->setParameter('currentTime', $currentTime);
        }
        if (!empty($filters['vegan'])) {
            $qb->andWhere('r.isVegan = true');
        }
        if (!empty($filters['vegetarian'])) {
            $qb->andWhere('r.isVegetarian = true');
        }
        if (!empty($filters['halal'])) {
            $qb->andWhere('r.isHalal = true');
        }
        if (!empty($filters['city'])) {
            $qb->andWhere('r.city LIKE :city')->setParameter('city', '%'.$filters['city'].'%');
        }
        if (!empty($filters['cuisine'])) {
            $qb->innerJoin('r.cuisines', 'c_filter')
                ->andWhere('c_filter.id IN (:cuisineIds)')
                ->setParameter('cuisineIds', $filters['cuisine']);
        }
        if (!empty($filters['lang'])) {
            foreach ($filters['lang'] as $i => $langValue) {
                $param = 'lang'.$i;
                $qb->andWhere("JSON_CONTAINS(r.spokenLanguages, :$param) = 1")
                    ->setParameter($param, json_encode($langValue));
            }
        }

        match ($sort) {
            'name' => $qb->orderBy('r.name', 'ASC'),
            'newest' => $qb->orderBy('r.createdAt', 'DESC'),
            default => $qb->orderBy('r.rating', 'DESC')->addOrderBy('r.name', 'ASC'),
        };

        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return new Paginator($qb);
    }

    public function countVerified(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.isVerified = :verified')
            ->setParameter('verified', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countCreatedSince(\DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.createdAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Restaurant[]
     */
    public function findRecent(int $limit = 5): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Restaurant[]
     */
    public function findBySubmitter(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.submittedBy = :user')
            ->setParameter('user', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Rohdaten für die Open-Startup-Auswertung: nur die Spalten, die in
     * Abdeckung, Punktzahl und Impact einfließen, als Array statt als Entity.
     *
     * Bewusst keine hydrierten Objekte – die Auswertung liest jede Zeile genau
     * einmal, ein UnitOfWork mit hunderten Entities wäre reiner Ballast. Der
     * Zeitfilter macht die Methode zugleich für nachträglich erzeugte
     * Monats-Snapshots brauchbar.
     *
     * @return list<array{
     *     city: string,
     *     isVerified: bool,
     *     isWheelchairAccessible: bool,
     *     hasAccessibleToilet: bool,
     *     allowsAssistanceDogs: bool,
     *     hasBrightLighting: bool,
     *     hasChangingTable: bool,
     *     hasDisabledParking: bool,
     *     doorWidthCm: int|null,
     *     tableSpacingCm: int|null
     * }>
     */
    public function findMetricRows(?\DateTimeImmutable $createdUntil = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select(
                'r.city AS city',
                'r.isVerified AS isVerified',
                'r.isWheelchairAccessible AS isWheelchairAccessible',
                'r.hasAccessibleToilet AS hasAccessibleToilet',
                'r.allowsAssistanceDogs AS allowsAssistanceDogs',
                'r.hasBrightLighting AS hasBrightLighting',
                'r.hasChangingTable AS hasChangingTable',
                'r.hasDisabledParking AS hasDisabledParking',
                'r.doorWidthCm AS doorWidthCm',
                'r.tableSpacingCm AS tableSpacingCm',
            );

        if ($createdUntil) {
            $qb->andWhere('r.createdAt <= :until')->setParameter('until', $createdUntil);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Vollständiger Datensatz für den offenen CSV-/JSON-Export unter CC-BY.
     *
     * @return Restaurant[]
     */
    public function findAllForExport(): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.cuisines', 'c')
            ->addSelect('c')
            ->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
