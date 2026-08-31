<?php

namespace App\Repository;

use App\Entity\BoardIdea;
use App\Entity\User;
use App\Enum\BoardIdeaStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BoardIdea>
 */
class BoardIdeaRepository extends ServiceEntityRepository
{
    public const int PER_PAGE = 20;

    /** Ab hier gilt eine wartende Einreichung als bald fällig (AK-73). */
    public const int DUE_SOON_WORKDAYS = 3;

    /** Ab hier ist die Zusage aus AK-72 erreicht (AK-79). */
    public const int OVERDUE_WORKDAYS = 5;

    /** Nie freigegebene Einreichungen werden nach dieser Zeit gelöscht (AK-74). */
    public const string STALE_AFTER = '-12 months';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BoardIdea::class);
    }

    /**
     * Die öffentliche Hauptliste des Boards.
     *
     * ⚠ **`publishedAt IS NOT NULL`, `duplicateOf IS NULL` und `status != done`
     * sind fest verdrahtet und nicht abschaltbar.** Damit ist AK-71 („kein
     * Beitrag war je ohne Freigabe öffentlich") an einer einzigen Stelle
     * prüfbar statt an fünf. Umgesetzte Ideen stehen in einem eigenen Abschnitt
     * (`findPublishedDone()`, AK-75), nicht in dieser Liste.
     *
     * ⚠ **Gezählt wird über `COUNT(...) AS HIDDEN` mit `GROUP BY` — ohne
     * `addSelect` einer Collection.** Das ist der Unterschied zu BF-64: Ein
     * fetch-join vervielfacht die SQL-Zeilen je Entität, und `setMaxResults()`
     * begrenzt Zeilen, nicht Objekte. Nach `GROUP BY` liefert die Abfrage genau
     * eine Zeile je Idee, und die Begrenzung wirkt auf Ideen.
     *
     * @param 'votes'|'newest' $sort
     */
    public function findPublishedPaginated(string $sort = 'votes', int $page = 1, ?BoardIdeaStatus $status = null): Paginator
    {
        $qb = $this->createQueryBuilder('i')
            ->leftJoin('i.votes', 'v')
            ->andWhere('i.publishedAt IS NOT NULL')
            ->andWhere('i.duplicateOf IS NULL')
            ->andWhere('i.status != :done')
            ->setParameter('done', BoardIdeaStatus::DONE)
            ->groupBy('i.id');

        if (null !== $status && BoardIdeaStatus::DONE !== $status) {
            $qb->andWhere('i.status = :status')->setParameter('status', $status);
        }

        if ('newest' === $sort) {
            $qb->orderBy('i.publishedAt', 'DESC');
        } else {
            // Zweitschlüssel bei Gleichstand: die neuere Idee steht oben (AK-05).
            $qb->addSelect('COUNT(v.id) AS HIDDEN stimmen')
                ->orderBy('stimmen', 'DESC')
                ->addOrderBy('i.publishedAt', 'DESC');
        }

        $qb->setFirstResult(($page - 1) * self::PER_PAGE)->setMaxResults(self::PER_PAGE);

        return new Paginator($qb->getQuery(), false);
    }

    /**
     * Der Abschnitt „Schon umgesetzt" unter der Hauptliste (AK-75).
     *
     * @return BoardIdea[]
     */
    public function findPublishedDone(int $limit = 20): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.publishedAt IS NOT NULL')
            ->andWhere('i.duplicateOf IS NULL')
            ->andWhere('i.status = :done')
            ->setParameter('done', BoardIdeaStatus::DONE)
            ->orderBy('i.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Die Zustimmungszahlen für genau die angezeigten Ideen.
     *
     * Eine Abfrage für die ganze Seite. Die Zahl kommt bewusst nicht aus einem
     * Feld an der Entität: Ein Zählerfeld liefe auseinander, sobald die
     * Fremdschlüssel-Kaskade beim Kontolöschen Stimmen entfernt (AK-66).
     *
     * @param int[] $ideaIds
     *
     * @return array<int, int> Kennung → Zahl
     */
    public function countVotesFor(array $ideaIds): array
    {
        if ([] === $ideaIds) {
            return [];
        }

        $zeilen = $this->createQueryBuilder('i')
            ->select('i.id AS id, COUNT(v.id) AS anzahl')
            ->leftJoin('i.votes', 'v')
            ->andWhere('i.id IN (:ids)')
            ->setParameter('ids', $ideaIds)
            ->groupBy('i.id')
            ->getQuery()
            ->getScalarResult();

        $map = [];
        foreach ($zeilen as $zeile) {
            $map[(int) $zeile['id']] = (int) $zeile['anzahl'];
        }

        return $map;
    }

    public function countVotesForOne(BoardIdea $idea): int
    {
        return $this->countVotesFor([(int) $idea->getId()])[(int) $idea->getId()] ?? 0;
    }

    /**
     * Die geplanten Ideen für die öffentliche Roadmap (Feature 07, AK-12, AK-17).
     *
     * ⚠ **Eigene Methode statt `findPublishedPaginated()` mit Status-Filter.** Jene
     * liefert 20 Einträge je Seite, baut einen `Paginator` und behandelt
     * `Umgesetzt` gesondert — für zehn Einträge ohne Blätterung wäre die Hälfte
     * der Rückgabe Abfall.
     *
     * ⚠ **Gezählt wird über `COUNT(...) AS HIDDEN` mit `GROUP BY`, nicht über
     * einen `addSelect`-Join.** Ein fetch-join vervielfacht die SQL-Zeilen je
     * Entität, und `setMaxResults()` begrenzt Zeilen, nicht Objekte — eine Idee
     * mit zwölf Stimmen füllte sonst allein die ganze Spalte (BF-64).
     *
     * Sichtbarkeit wie im Board: nur freigegebene Ideen, keine zusammengeführten
     * Dubletten. Der Filter steht in den Kriterien und nicht in der Ausgabe, damit
     * eine wartende Idee nicht im ausgelieferten Quelltext landet (AK-14, AK-43).
     *
     * @return BoardIdea[]
     */
    public function findPublishedPlanned(int $limit): array
    {
        return $this->createQueryBuilder('i')
            ->addSelect('COUNT(v.id) AS HIDDEN stimmen')
            ->leftJoin('i.votes', 'v')
            ->andWhere('i.publishedAt IS NOT NULL')
            ->andWhere('i.duplicateOf IS NULL')
            ->andWhere('i.status = :status')
            ->setParameter('status', BoardIdeaStatus::PLANNED)
            ->groupBy('i.id')
            ->orderBy('stimmen', 'DESC')
            // Zweitschlüssel bei Gleichstand: die neuere Idee steht oben (EC-03).
            ->addOrderBy('i.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Wie viele geplante Ideen es insgesamt gibt (AK-17, EC-04).
     *
     * Eine eigene Zählabfrage statt eines zweiten Durchlaufs über den Bestand:
     * Damit lädt auch bei zweihundert geplanten Ideen kein Aufruf mehr als die
     * zehn angezeigten (AK-45).
     */
    public function countPublishedPlanned(): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.publishedAt IS NOT NULL')
            ->andWhere('i.duplicateOf IS NULL')
            ->andWhere('i.status = :status')
            ->setParameter('status', BoardIdeaStatus::PLANNED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Die Moderationsschlange: älteste zuerst (AK-24).
     *
     * @return BoardIdea[]
     */
    public function findAwaitingReview(): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.publishedAt IS NULL')
            ->andWhere('i.duplicateOf IS NULL')
            ->orderBy('i.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** Für die Kachel im Admin-Dashboard (AK-25). */
    public function countAwaitingReview(): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.publishedAt IS NULL')
            ->andWhere('i.duplicateOf IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Alle veröffentlichten Ideen für die Verwaltungsübersicht.
     *
     * @return BoardIdea[]
     */
    public function findPublishedForAdmin(): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.publishedAt IS NOT NULL')
            ->orderBy('i.publishedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Eingereichte Ideen eines Kontos — für den Datenexport (AK-67).
     *
     * @return BoardIdea[]
     */
    public function findBySubmitter(User $user): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.submittedBy = :user')
            ->setParameter('user', $user)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Nie freigegebene Einreichungen älter als die Frist löschen (AK-74).
     *
     * Die zugehörigen Stimmen verschwinden über den Fremdschlüssel — `ON DELETE
     * CASCADE` ist ein Datenbank-Constraint und greift auch bei einer
     * DQL-Löschung.
     *
     * @return int Zahl der gelöschten Zeilen
     */
    public function deleteStaleUnpublished(\DateTimeImmutable $before): int
    {
        return (int) $this->createQueryBuilder('i')
            ->delete()
            ->andWhere('i.publishedAt IS NULL')
            ->andWhere('i.createdAt < :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->execute();
    }

    /**
     * Wartende Ideen eines Kontos löschen, das gerade entfernt wird (EC-09).
     *
     * ⚠ **Nur die unveröffentlichten.** Veröffentlichte bleiben stehen, ihr
     * Verfasserbezug wird über `ON DELETE SET NULL` gekappt (AK-65).
     *
     * @return int Zahl der gelöschten Zeilen
     */
    public function deleteUnpublishedBy(User $user): int
    {
        return (int) $this->createQueryBuilder('i')
            ->delete()
            ->andWhere('i.publishedAt IS NULL')
            ->andWhere('i.submittedBy = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}
