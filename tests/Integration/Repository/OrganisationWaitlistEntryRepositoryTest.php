<?php

namespace App\Tests\Integration\Repository;

use App\Entity\OrganisationWaitlistEntry;
use App\Enum\OrganisationType;
use App\Enum\WaitlistStatus;
use App\Repository\OrganisationWaitlistEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class OrganisationWaitlistEntryRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private OrganisationWaitlistEntryRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = self::getContainer()->get(OrganisationWaitlistEntryRepository::class);
    }

    public function testFindByTypeFiltersByTypeAndStatus(): void
    {
        $commune = $this->persist(OrganisationType::COMMUNE, WaitlistStatus::PENDING);
        $company = $this->persist(OrganisationType::COMPANY, WaitlistStatus::PENDING);
        $qualified = $this->persist(OrganisationType::COMMUNE, WaitlistStatus::QUALIFIED);

        $communes = $this->ids($this->repository->findByType('commune'));
        self::assertContains($commune->getId(), $communes);
        self::assertContains($qualified->getId(), $communes);
        self::assertNotContains($company->getId(), $communes);

        $pendingCommunes = $this->ids($this->repository->findByType('commune', 'pending'));
        self::assertContains($commune->getId(), $pendingCommunes);
        self::assertNotContains($qualified->getId(), $pendingCommunes);
    }

    public function testFindByTypeReturnsEmptyForUnknownValues(): void
    {
        $this->persist(OrganisationType::COMMUNE, WaitlistStatus::PENDING);

        // Unbekannte Werte kommen aus Query-Parametern – sie dürfen nicht werfen.
        self::assertSame([], $this->repository->findByType('gibtesnicht'));
        self::assertSame([], $this->repository->findByType('commune', 'gibtesnicht'));
    }

    public function testCountByTypeGroupsCorrectly(): void
    {
        $this->persist(OrganisationType::COMPANY, WaitlistStatus::PENDING);
        $this->persist(OrganisationType::COMPANY, WaitlistStatus::CONFIRMED);
        $this->persist(OrganisationType::ASSOCIATION, WaitlistStatus::PENDING);

        $counts = $this->repository->countByType();

        self::assertSame(2, $counts[OrganisationType::COMPANY->value] ?? 0);
        self::assertSame(1, $counts[OrganisationType::ASSOCIATION->value] ?? 0);
    }

    /** @param OrganisationWaitlistEntry[] $entries @return int[] */
    private function ids(array $entries): array
    {
        return array_map(static fn (OrganisationWaitlistEntry $e) => $e->getId(), $entries);
    }

    private function persist(OrganisationType $type, WaitlistStatus $status): OrganisationWaitlistEntry
    {
        $entry = new OrganisationWaitlistEntry();
        $entry->setType($type)
            ->setOrganisationName($type->value . ' ' . uniqid())
            ->setContactName('Test')
            ->setEmail(uniqid() . '@example.lu')
            ->setLocale('de')
            ->setStatus($status);

        $this->em->persist($entry);
        $this->em->flush();

        return $entry;
    }
}
