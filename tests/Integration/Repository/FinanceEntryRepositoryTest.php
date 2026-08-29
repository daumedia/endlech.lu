<?php

namespace App\Tests\Integration\Repository;

use App\Entity\FinanceEntry;
use App\Enum\FinanceCategory;
use App\Enum\FinanceType;
use App\Repository\FinanceEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class FinanceEntryRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private FinanceEntryRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = self::getContainer()->get(FinanceEntryRepository::class);
    }

    public function testSumByCategoryGroupsAmounts(): void
    {
        $before = $this->repository->sumByCategory(FinanceType::EXPENSE)[FinanceCategory::DOMAIN->value]['total'] ?? 0.0;

        $this->persist(FinanceCategory::DOMAIN, '10.00');
        $this->persist(FinanceCategory::DOMAIN, '15.50');

        $row = $this->repository->sumByCategory(FinanceType::EXPENSE)[FinanceCategory::DOMAIN->value];

        self::assertEqualsWithDelta($before + 25.5, $row['total'], 0.001);
        self::assertSame(FinanceCategory::DOMAIN, $row['category']);
    }

    public function testSumByTypeSeparatesIncomeFromExpenses(): void
    {
        $expensesBefore = $this->repository->sumByType(FinanceType::EXPENSE);
        $incomeBefore = $this->repository->sumByType(FinanceType::INCOME);

        $this->persist(FinanceCategory::HOSTING, '30.00');
        $this->persist(FinanceCategory::DONATION, '70.00');

        self::assertEqualsWithDelta($expensesBefore + 30.0, $this->repository->sumByType(FinanceType::EXPENSE), 0.001);
        self::assertEqualsWithDelta($incomeBefore + 70.0, $this->repository->sumByType(FinanceType::INCOME), 0.001);
    }

    public function testUntilCutsOffLaterEntries(): void
    {
        $this->persist(FinanceCategory::HOSTING, '99.00', new \DateTimeImmutable('+1 year'));

        $sum = $this->repository->sumByType(FinanceType::EXPENSE, new \DateTimeImmutable('today'));

        self::assertLessThan(
            $this->repository->sumByType(FinanceType::EXPENSE),
            $sum,
            'Ein Beleg nach dem Stichtag darf nicht in die Summe eingehen.',
        );
    }

    public function testSumQuantityOnlyCountsTheGivenCategory(): void
    {
        $before = $this->repository->sumQuantity(FinanceCategory::INCLUSION_BOX_MATERIALS);

        $this->persist(FinanceCategory::INCLUSION_BOX_MATERIALS, '40.00', quantity: 3);

        self::assertSame($before + 3, $this->repository->sumQuantity(FinanceCategory::INCLUSION_BOX_MATERIALS));
    }

    public function testFindEarliestDateReturnsTheOldestEntryOfThatType(): void
    {
        $this->persist(FinanceCategory::DONATION, '5.00', new \DateTimeImmutable('2020-06-15'));

        self::assertSame('2020-06-15', $this->repository->findEarliestDate(FinanceType::INCOME)?->format('Y-m-d'));
    }

    public function testFindForAdminFiltersByType(): void
    {
        $entries = $this->repository->findForAdmin(FinanceType::INCOME);

        self::assertNotEmpty($entries);
        foreach ($entries as $entry) {
            self::assertSame(FinanceType::INCOME, $entry->getType());
        }
    }

    /**
     * Die Richtung hängt ausschließlich an der Kategorie. Eine Ausgabe unter
     * einer Einnahmekategorie wäre in der veröffentlichten Summe nicht mehr
     * als Fehler erkennbar.
     */
    public function testCategoryDeterminesTheType(): void
    {
        $entry = $this->persist(FinanceCategory::HOSTING, '1.00');
        self::assertSame(FinanceType::EXPENSE, $entry->getType());

        $entry->setCategory(FinanceCategory::DONATION);
        self::assertSame(FinanceType::INCOME, $entry->getType());
    }

    public function testSwitchingToACategoryWithoutQuantityClearsIt(): void
    {
        $entry = $this->persist(FinanceCategory::INCLUSION_BOX_MATERIALS, '50.00', quantity: 4);
        self::assertSame(4, $entry->getQuantity());

        $entry->setCategory(FinanceCategory::HOSTING);

        self::assertNull($entry->getQuantity(), 'Sonst bliebe die Stückzahl als Leiche in der Impact-Zahl stehen.');
    }

    private function persist(FinanceCategory $category, string $amount, ?\DateTimeImmutable $date = null, ?int $quantity = null): FinanceEntry
    {
        $entry = (new FinanceEntry())
            ->setCategory($category)
            ->setAmount($amount)
            ->setDate($date ?? new \DateTimeImmutable('-1 month'))
            ->setQuantity($quantity);

        $this->em->persist($entry);
        $this->em->flush();

        return $entry;
    }
}
