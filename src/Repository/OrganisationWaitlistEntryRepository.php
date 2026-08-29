<?php

namespace App\Repository;

use App\Entity\OrganisationWaitlistEntry;
use App\Enum\OrganisationType;
use App\Enum\WaitlistStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrganisationWaitlistEntry>
 */
class OrganisationWaitlistEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrganisationWaitlistEntry::class);
    }

    public function findOneByConfirmationToken(string $token): ?OrganisationWaitlistEntry
    {
        return $this->findOneBy(['confirmationToken' => $token]);
    }

    /**
     * Anmeldungen eines Organisationstyps, optional zusätzlich nach Status
     * gefiltert. Nimmt bewusst Strings entgegen (Query-Parameter aus dem Admin)
     * und verwirft unbekannte Werte, statt zu werfen.
     *
     * @return OrganisationWaitlistEntry[]
     */
    public function findByType(string $type, ?string $status = null): array
    {
        $organisationType = OrganisationType::tryFrom($type);

        if (!$organisationType) {
            return [];
        }

        $qb = $this->createQueryBuilder('o')
            ->where('o.type = :type')
            ->setParameter('type', $organisationType);

        if (null !== $status) {
            $waitlistStatus = WaitlistStatus::tryFrom($status);

            if (!$waitlistStatus) {
                return [];
            }

            $qb->andWhere('o.status = :status')
                ->setParameter('status', $waitlistStatus);
        }

        return $qb->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Admin-Liste: optional nach Typ und Status gefiltert.
     *
     * @return OrganisationWaitlistEntry[]
     */
    public function findFiltered(
        ?OrganisationType $type = null,
        ?WaitlistStatus $status = null,
        string $direction = 'DESC',
    ): array {
        $qb = $this->createQueryBuilder('o');

        if (null !== $type) {
            $qb->andWhere('o.type = :type')->setParameter('type', $type);
        }

        if (null !== $status) {
            $qb->andWhere('o.status = :status')->setParameter('status', $status);
        }

        return $qb->orderBy('o.createdAt', 'ASC' === strtoupper($direction) ? 'ASC' : 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Anzahl je Status, für die Filter-Pills im Admin.
     *
     * @return array<string, int>
     */
    public function countByStatus(): array
    {
        $rows = $this->createQueryBuilder('o')
            ->select('o.status AS status, COUNT(o.id) AS total')
            ->groupBy('o.status')
            ->getQuery()
            ->getArrayResult();

        $counts = [];

        foreach ($rows as $row) {
            $status = $row['status'];
            $counts[$status instanceof WaitlistStatus ? $status->value : (string) $status] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * Anzahl je Organisationstyp.
     *
     * @return array<string, int>
     */
    public function countByType(): array
    {
        $rows = $this->createQueryBuilder('o')
            ->select('o.type AS type, COUNT(o.id) AS total')
            ->groupBy('o.type')
            ->getQuery()
            ->getArrayResult();

        $counts = [];

        foreach ($rows as $row) {
            $type = $row['type'];
            $counts[$type instanceof OrganisationType ? $type->value : (string) $type] = (int) $row['total'];
        }

        return $counts;
    }
}
