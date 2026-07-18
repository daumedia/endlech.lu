<?php

namespace App\Tests\Integration\Repository;

use App\Entity\RestaurantSuggestion;
use App\Repository\RestaurantSuggestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RestaurantSuggestionRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private RestaurantSuggestionRepository $repo;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repo = $container->get(RestaurantSuggestionRepository::class);
    }

    private function persistSuggestion(string $name, string $status): RestaurantSuggestion
    {
        $suggestion = (new RestaurantSuggestion())
            ->setName($name)
            ->setCity('Luxembourg')
            ->setCuisine('Test')
            ->setStatus($status);
        $this->em->persist($suggestion);
        $this->em->flush();

        return $suggestion;
    }

    public function testCountPendingCountsOnlyPending(): void
    {
        $before = $this->repo->countPending();

        $this->persistSuggestion('Pending A', RestaurantSuggestion::STATUS_PENDING);
        $this->persistSuggestion('Pending B', RestaurantSuggestion::STATUS_PENDING);
        $this->persistSuggestion('Approved', RestaurantSuggestion::STATUS_APPROVED);

        self::assertSame($before + 2, $this->repo->countPending());
    }

    public function testFindByStatusFiltersAndSortsByCreatedAtDesc(): void
    {
        $pending = $this->persistSuggestion('Pending X', RestaurantSuggestion::STATUS_PENDING);
        $approved = $this->persistSuggestion('Approved Y', RestaurantSuggestion::STATUS_APPROVED);

        $pendingResults = $this->repo->findByStatus(RestaurantSuggestion::STATUS_PENDING);
        $pendingIds = array_map(static fn (RestaurantSuggestion $s) => $s->getId(), $pendingResults);

        self::assertContains($pending->getId(), $pendingIds);
        self::assertNotContains($approved->getId(), $pendingIds);
        foreach ($pendingResults as $suggestion) {
            self::assertSame(RestaurantSuggestion::STATUS_PENDING, $suggestion->getStatus());
        }

        $approvedResults = $this->repo->findByStatus(RestaurantSuggestion::STATUS_APPROVED);
        $approvedIds = array_map(static fn (RestaurantSuggestion $s) => $s->getId(), $approvedResults);
        self::assertContains($approved->getId(), $approvedIds);
    }
}
