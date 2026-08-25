<?php

namespace App\Repository;

use App\Entity\RestaurantSuggestion;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RestaurantSuggestion>
 */
class RestaurantSuggestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RestaurantSuggestion::class);
    }

    /** @return RestaurantSuggestion[] */
    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status], ['createdAt' => 'DESC']);
    }

    /**
     * Alle Vorschläge eines Einreichers, neueste zuerst.
     *
     * ⚠ BF-32: Wer über die API einreicht, sah seinen Vorschlag nirgends —
     * `/me/submissions` las nur genehmigte Restaurants. Seit BF-24 entsteht über
     * die API aber gar kein Restaurant mehr, sondern ein Vorschlag; die
     * Einreichung verschwand damit vollständig aus der Sicht des Einreichers,
     * und ein Client konnte nicht einmal anzeigen, dass sie angekommen ist.
     *
     * @return RestaurantSuggestion[]
     */
    public function findBySuggester(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.suggestedBy = :user')
            ->setParameter('user', $user)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countPending(): int
    {
        return $this->count(['status' => RestaurantSuggestion::STATUS_PENDING]);
    }
}
