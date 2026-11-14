<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Favorite;
use App\Entity\Restaurant;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ⚠ **Jede Methode nimmt das Konto als Pflichtargument** (Feature 17, `design.md`,
 * Zugriffsregeln). Es gibt keinen Weg, Favoriten ohne Konto zu lesen — eine vergessene
 * Besitzprüfung ist damit keine Möglichkeit, sondern ein Kompilierfehler.
 *
 * @extends ServiceEntityRepository<Favorite>
 */
class FavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favorite::class);
    }

    public function findOneByUserAndRestaurant(User $user, Restaurant $restaurant): ?Favorite
    {
        return $this->findOneBy(['user' => $user, 'restaurant' => $restaurant]);
    }

    /**
     * Welche dieser Lokale hat das Konto gemerkt? Eine Abfrage für die ganze Seite.
     *
     * @param int[] $restaurantIds
     *
     * @return int[]
     */
    public function findFavoriteRestaurantIds(User $user, array $restaurantIds): array
    {
        if ([] === $restaurantIds) {
            return [];
        }

        $zeilen = $this->createQueryBuilder('f')
            ->select('IDENTITY(f.restaurant) AS restaurantId')
            ->andWhere('f.user = :user')
            ->andWhere('f.restaurant IN (:ids)')
            ->setParameter('user', $user)
            ->setParameter('ids', $restaurantIds)
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn (array $z): int => (int) $z['restaurantId'], $zeilen);
    }

    public function countByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Eine Seite gemerkter Lokale als Nummern, zuletzt gemerkt zuerst (AK-12).
     *
     * Bewusst ohne Join auf die Kartendaten: `setMaxResults()` auf einer Abfrage mit
     * `addSelect()`-Joins begrenzt Zeilen statt Lokale (BF-64). Die Karten lädt danach
     * `RestaurantRepository::findForCards()` über diese Nummern.
     *
     * @return int[]
     */
    public function findRestaurantIdsPage(User $user, int $offset, int $limit): array
    {
        $zeilen = $this->createQueryBuilder('f')
            ->select('IDENTITY(f.restaurant) AS restaurantId')
            ->andWhere('f.user = :user')
            ->setParameter('user', $user)
            ->orderBy('f.createdAt', 'DESC')
            ->addOrderBy('f.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn (array $z): int => (int) $z['restaurantId'], $zeilen);
    }

    /**
     * Für den Datenexport (AK-60): alle Favoriten samt Lokal, zuletzt gemerkt zuerst.
     *
     * @return Favorite[]
     */
    public function findByUserNewestFirst(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->addSelect('r')
            ->innerJoin('f.restaurant', 'r')
            ->andWhere('f.user = :user')
            ->setParameter('user', $user)
            ->orderBy('f.createdAt', 'DESC')
            ->addOrderBy('f.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
