<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SavedList;
use App\Entity\SavedListEntry;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ⚠ **Es gibt keine Methode, die eine Liste nur über ihre Nummer liefert.** Jeder Zugriff
 * geht über „Nummer **und** Konto“ (`findOneOwned`). Eine fremde Nummer findet damit schlicht
 * nichts und endet in 404 — identisch zu einer Nummer, die es nicht gibt (AK-53, AK-54).
 * `find()` aus der Basisklasse wird in Feature 17 nicht benutzt.
 *
 * @extends ServiceEntityRepository<SavedList>
 */
class SavedListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SavedList::class);
    }

    public function findOneOwned(int $id, User $user): ?SavedList
    {
        return $this->findOneBy(['id' => $id, 'user' => $user]);
    }

    /**
     * Alle Listen des Kontos, zuletzt angelegte zuerst (AK-11). Höchstens 25.
     *
     * @return SavedList[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.user = :user')
            ->setParameter('user', $user)
            ->orderBy('l.createdAt', 'DESC')
            ->addOrderBy('l.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findOneByUserAndKey(User $user, string $nameKey): ?SavedList
    {
        return $this->findOneBy(['user' => $user, 'nameKey' => $nameKey]);
    }

    /**
     * Zahl der Lokale je eigener Liste, in einer Abfrage (AK-11).
     *
     * @return array<int, int> Listennummer => Zahl der Lokale; leere Listen fehlen
     */
    public function countEntriesPerList(User $user): array
    {
        $zeilen = $this->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(e.list) AS listId', 'COUNT(e.id) AS anzahl')
            ->from(SavedListEntry::class, 'e')
            ->innerJoin('e.list', 'l')
            ->andWhere('l.user = :user')
            ->setParameter('user', $user)
            ->groupBy('e.list')
            ->getQuery()
            ->getScalarResult();

        $ergebnis = [];
        foreach ($zeilen as $zeile) {
            $ergebnis[(int) $zeile['listId']] = (int) $zeile['anzahl'];
        }

        return $ergebnis;
    }
}
