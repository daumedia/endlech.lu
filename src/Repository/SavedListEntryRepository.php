<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Restaurant;
use App\Entity\SavedList;
use App\Entity\SavedListEntry;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Einträge werden nur über eine Liste gelesen, die der Aufrufer zuvor über
 * `SavedListRepository::findOneOwned()` geholt hat — oder über das Konto selbst.
 *
 * @extends ServiceEntityRepository<SavedListEntry>
 */
class SavedListEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SavedListEntry::class);
    }

    public function findOneByListAndRestaurant(SavedList $list, Restaurant $restaurant): ?SavedListEntry
    {
        return $this->findOneBy(['list' => $list, 'restaurant' => $restaurant]);
    }

    public function countByList(SavedList $list): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.list = :list')
            ->setParameter('list', $list)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Eine Seite der Liste als Lokalnummern, zuletzt hinzugefügt zuerst (AK-25).
     * Ohne Joins aus demselben Grund wie `FavoriteRepository::findRestaurantIdsPage()`.
     *
     * @return int[]
     */
    public function findRestaurantIdsPage(SavedList $list, int $offset, int $limit): array
    {
        $zeilen = $this->createQueryBuilder('e')
            ->select('IDENTITY(e.restaurant) AS restaurantId')
            ->andWhere('e.list = :list')
            ->setParameter('list', $list)
            ->orderBy('e.addedAt', 'DESC')
            ->addOrderBy('e.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn (array $z): int => (int) $z['restaurantId'], $zeilen);
    }

    /**
     * In welchen eigenen Listen steht dieses Lokal? Für die Häkchen der Detailseite (AK-21).
     *
     * @return int[] Listennummern
     */
    public function findListIdsContaining(User $user, Restaurant $restaurant): array
    {
        $zeilen = $this->createQueryBuilder('e')
            ->select('IDENTITY(e.list) AS listId')
            ->innerJoin('e.list', 'l')
            ->andWhere('l.user = :user')
            ->andWhere('e.restaurant = :restaurant')
            ->setParameter('user', $user)
            ->setParameter('restaurant', $restaurant)
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn (array $z): int => (int) $z['listId'], $zeilen);
    }

    /**
     * Für den Datenexport (AK-60): alle Einträge einer Liste samt Lokal.
     *
     * @return SavedListEntry[]
     */
    public function findByListNewestFirst(SavedList $list): array
    {
        return $this->createQueryBuilder('e')
            ->addSelect('r')
            ->innerJoin('e.restaurant', 'r')
            ->andWhere('e.list = :list')
            ->setParameter('list', $list)
            ->orderBy('e.addedAt', 'DESC')
            ->addOrderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
