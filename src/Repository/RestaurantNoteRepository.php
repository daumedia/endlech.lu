<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Restaurant;
use App\Entity\RestaurantNote;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ⚠ Notizen sind privat (AK-52). Jede Methode nimmt das Konto als Pflichtargument.
 *
 * @extends ServiceEntityRepository<RestaurantNote>
 */
class RestaurantNoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RestaurantNote::class);
    }

    public function findOneByUserAndRestaurant(User $user, Restaurant $restaurant): ?RestaurantNote
    {
        return $this->findOneBy(['user' => $user, 'restaurant' => $restaurant]);
    }

    /**
     * Die Notizen des Kontos zu diesen Lokalen, in einer Abfrage (AK-34).
     *
     * @param int[] $restaurantIds
     *
     * @return array<int, string> Lokalnummer => Notiztext
     */
    public function findTextsFor(User $user, array $restaurantIds): array
    {
        if ([] === $restaurantIds) {
            return [];
        }

        $zeilen = $this->createQueryBuilder('n')
            ->select('IDENTITY(n.restaurant) AS restaurantId', 'n.text AS text')
            ->andWhere('n.user = :user')
            ->andWhere('n.restaurant IN (:ids)')
            ->setParameter('user', $user)
            ->setParameter('ids', $restaurantIds)
            ->getQuery()
            ->getScalarResult();

        $ergebnis = [];
        foreach ($zeilen as $zeile) {
            $ergebnis[(int) $zeile['restaurantId']] = (string) $zeile['text'];
        }

        return $ergebnis;
    }

    /**
     * Für den Datenexport (AK-60).
     *
     * @return RestaurantNote[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('n')
            ->addSelect('r')
            ->innerJoin('n.restaurant', 'r')
            ->andWhere('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.updatedAt', 'DESC')
            ->addOrderBy('n.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
