<?php

namespace App\Tests\Integration\Repository;

use App\Entity\PartnerWaitlistEntry;
use App\Enum\WaitlistStatus;
use App\Repository\PartnerWaitlistEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class PartnerWaitlistEntryRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private PartnerWaitlistEntryRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = self::getContainer()->get(PartnerWaitlistEntryRepository::class);
    }

    public function testFindPendingOlderThanRespectsBoundaryAndStatus(): void
    {
        $old = $this->persist('Alt', WaitlistStatus::PENDING, '-10 days');
        $recent = $this->persist('Neu', WaitlistStatus::PENDING, '-1 day');
        $oldConfirmed = $this->persist('Alt bestätigt', WaitlistStatus::CONFIRMED, '-10 days');

        $found = $this->repository->findPendingOlderThan(new \DateTimeImmutable('-7 days'));
        $ids = array_map(static fn (PartnerWaitlistEntry $e) => $e->getId(), $found);

        self::assertContains($old->getId(), $ids, 'Alte unbestätigte Anmeldung muss gefunden werden.');
        self::assertNotContains($recent->getId(), $ids, 'Junge Anmeldung darf nicht gefunden werden.');
        self::assertNotContains($oldConfirmed->getId(), $ids, 'Bestätigte Anmeldung darf nicht gefunden werden.');
    }

    public function testFindFilteredSortsAndFiltersByStatus(): void
    {
        $this->persist('Erste', WaitlistStatus::PENDING, '-3 days');
        $converted = $this->persist('Partner', WaitlistStatus::CONVERTED, '-2 days');

        $onlyConverted = $this->repository->findFiltered(WaitlistStatus::CONVERTED);
        self::assertCount(1, $onlyConverted);
        self::assertSame($converted->getId(), $onlyConverted[0]->getId());

        $asc = $this->repository->findFiltered(null, 'ASC');
        $desc = $this->repository->findFiltered(null, 'DESC');
        self::assertSame(array_reverse(array_map(static fn ($e) => $e->getId(), $asc)), array_map(static fn ($e) => $e->getId(), $desc));
    }

    public function testCountByStatusGroupsCorrectly(): void
    {
        $this->persist('A', WaitlistStatus::PENDING, '-1 day');
        $this->persist('B', WaitlistStatus::PENDING, '-1 day');
        $this->persist('C', WaitlistStatus::DECLINED, '-1 day');

        $counts = $this->repository->countByStatus();

        self::assertSame(2, $counts[WaitlistStatus::PENDING->value] ?? 0);
        self::assertSame(1, $counts[WaitlistStatus::DECLINED->value] ?? 0);
    }

    private function persist(string $name, WaitlistStatus $status, string $createdModifier): PartnerWaitlistEntry
    {
        $entry = new PartnerWaitlistEntry();
        $entry->setRestaurantName($name . ' ' . uniqid())
            ->setContactName('Test')
            ->setEmail(uniqid() . '@example.lu')
            ->setLocality('Luxemburg')
            ->setLocale('de')
            ->setStatus($status);

        // createdAt ist bewusst schreibgeschützt (Konstruktor) – für den Test
        // wird der Wert direkt über die Metadaten gesetzt.
        $reflection = new \ReflectionProperty(PartnerWaitlistEntry::class, 'createdAt');
        $reflection->setValue($entry, new \DateTimeImmutable($createdModifier));

        $this->em->persist($entry);
        $this->em->flush();

        return $entry;
    }
}
