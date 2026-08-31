<?php

namespace App\Repository;

use App\Entity\BoardIdea;
use App\Entity\BoardVote;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BoardVote>
 */
class BoardVoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BoardVote::class);
    }

    public function findOneByIdeaAndUser(BoardIdea $idea, User $user): ?BoardVote
    {
        return $this->findOneBy(['idea' => $idea, 'user' => $user]);
    }

    /**
     * Die Kennungen der Ideen, denen dieses Konto zugestimmt hat.
     *
     * Eine Abfrage für die ganze Seite statt einer je Karte — sonst stellte das
     * Board bei 20 Einträgen 20 zusätzliche Fragen an die Datenbank.
     *
     * @param int[] $ideaIds
     *
     * @return int[]
     */
    public function findVotedIdeaIds(User $user, array $ideaIds): array
    {
        if ([] === $ideaIds) {
            return [];
        }

        $zeilen = $this->createQueryBuilder('v')
            ->select('IDENTITY(v.idea) AS ideaId')
            ->andWhere('v.user = :user')
            ->andWhere('v.idea IN (:ids)')
            ->setParameter('user', $user)
            ->setParameter('ids', $ideaIds)
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn (array $z): int => (int) $z['ideaId'], $zeilen);
    }

    /**
     * Für die Dublettenzusammenführung: alle Stimmen einer Idee (AK-34).
     *
     * @return BoardVote[]
     */
    public function findByIdea(BoardIdea $idea): array
    {
        return $this->findBy(['idea' => $idea]);
    }
}
