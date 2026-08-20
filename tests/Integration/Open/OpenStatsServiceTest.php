<?php

namespace App\Tests\Integration\Open;

use App\Entity\FinanceEntry;
use App\Entity\Restaurant;
use App\Enum\FinanceCategory;
use App\Open\OpenStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class OpenStatsServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private OpenStatsService $stats;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->stats = self::getContainer()->get(OpenStatsService::class);
    }

    public function testPlatformCountsMatchTheDatabase(): void
    {
        $before = $this->stats->computeAll()['platform'];

        $this->persistRestaurant('Testhaus', 'Bettembourg', verified: true);
        $after = $this->stats->computeAll()['platform'];

        self::assertSame($before['restaurants'] + 1, $after['restaurants']);
        self::assertSame($before['verified'] + 1, $after['verified']);
    }

    /**
     * Ein Ort, den die Gemeindetabelle nicht kennt, darf weder raten noch die
     * Gesamtzahl verfälschen: Er zählt mit, taucht aber getrennt als
     * unzugeordnet auf.
     */
    public function testUnknownCityCountsInTotalButNotInCoverage(): void
    {
        $before = $this->stats->computeAll()['platform'];

        $this->persistRestaurant('Nirgendwo', 'Atlantis');
        $after = $this->stats->computeAll()['platform'];

        self::assertSame($before['restaurants'] + 1, $after['restaurants']);
        self::assertSame($before['unassigned'] + 1, $after['unassigned']);
        self::assertSame($before['communesCovered'], $after['communesCovered']);
    }

    public function testScoreDistributionCoversTheWholeScale(): void
    {
        $distribution = $this->stats->computeAll()['platform']['scoreDistribution'];

        self::assertCount(11, $distribution, 'Die Skala läuft von 0 bis 10 einschließlich.');
        self::assertSame(
            $this->stats->computeAll()['platform']['restaurants'],
            array_sum($distribution),
            'Jedes Restaurant muss in genau einem Punktefach landen.',
        );
    }

    public function testCantonRowsListAllTwelveCantons(): void
    {
        $rows = $this->stats->computeAll()['platform']['byCanton'];

        self::assertCount(12, $rows, 'Auch Kantone ohne Eintrag gehören in die Liste.');
        self::assertSame(100, array_sum(array_column($rows, 'communeTotal')));
    }

    /**
     * Die Quartalssperre ist eine Datenregel, keine Anzeigeregel: Die
     * Einnahmen dürfen gar nicht erst im Ergebnis stehen, sonst stünden sie
     * trotz ausgeblendeter Anzeige in /open.json.
     */
    public function testRecentIncomeIsWithheldStructurally(): void
    {
        $this->persistFinance(FinanceCategory::DONATION, '250.00', new \DateTimeImmutable('today'));

        $finance = $this->stats->computeAll()['finance'];

        self::assertFalse($finance['incomeVisible']);
        self::assertSame([], $finance['income']);
        self::assertNull($finance['totalIncome']);
        self::assertNull($finance['balance']);
        self::assertNotNull($finance['incomeVisibleFrom'], 'Das Freigabedatum gehört dazu – sonst wirkt die Lücke wie ein Versehen.');
    }

    public function testIncomeBecomesVisibleAfterACompletedQuarter(): void
    {
        $this->persistFinance(FinanceCategory::SPONSORSHIP, '1000.00', new \DateTimeImmutable('-2 years'));

        $finance = $this->stats->computeAll()['finance'];

        self::assertTrue($finance['incomeVisible']);
        self::assertGreaterThanOrEqual(1000.0, $finance['totalIncome']);
        self::assertNotSame([], $finance['income']);
    }

    public function testExpensesAreAggregatedByCategoryOnly(): void
    {
        $finance = $this->stats->computeAll()['finance'];

        foreach ($finance['expenses'] as $row) {
            self::assertArrayHasKey('category', $row);
            self::assertArrayNotHasKey('note', $row, 'Notizen sind intern und dürfen die Anwendung nicht verlassen.');
            self::assertArrayNotHasKey('restaurant', $row);
        }
    }

    public function testInclusionBoxesComeFromTheMaterialReceipts(): void
    {
        $before = $this->stats->computeAll()['impact']['inclusionBoxesDelivered'];

        $this->persistFinance(FinanceCategory::INCLUSION_BOX_MATERIALS, '80.00', new \DateTimeImmutable('-1 month'), 5);

        self::assertSame($before + 5, $this->stats->computeAll()['impact']['inclusionBoxesDelivered']);
    }

    /**
     * Nur ausgemessene Häuser gehören in den Nenner – sonst läse sich jede
     * fehlende Messung wie ein zu enger Durchgang.
     */
    public function testMeasuredDimensionsAreCountedSeparately(): void
    {
        $before = $this->stats->computeAll()['impact'];

        $this->persistRestaurant('Mit Maß', 'Mersch', doorWidth: 100);
        $this->persistRestaurant('Ohne Maß', 'Mersch');

        $after = $this->stats->computeAll()['impact'];

        self::assertSame($before['documentedDoorWidths'] + 1, $after['documentedDoorWidths']);
        self::assertSame($before['wideDoors'] + 1, $after['wideDoors']);
    }

    public function testCacheIsUsedAndCanBeInvalidated(): void
    {
        $first = $this->stats->platform()['restaurants'];

        $this->persistRestaurant('Frisch', 'Remich');

        self::assertSame($first, $this->stats->platform()['restaurants'], 'Der zweite Aufruf muss aus dem Cache kommen.');

        $this->stats->invalidate();

        self::assertSame($first + 1, $this->stats->platform()['restaurants'], 'Nach invalidate() muss neu gerechnet werden.');
    }

    private function persistRestaurant(string $name, string $city, bool $verified = false, ?int $doorWidth = null): Restaurant
    {
        $restaurant = (new Restaurant())
            ->setName($name)
            ->setCity($city)
            ->setIsVerified($verified)
            ->setDoorWidthCm($doorWidth);

        $this->em->persist($restaurant);
        $this->em->flush();

        return $restaurant;
    }

    private function persistFinance(FinanceCategory $category, string $amount, \DateTimeImmutable $date, ?int $quantity = null): FinanceEntry
    {
        $entry = (new FinanceEntry())
            ->setCategory($category)
            ->setAmount($amount)
            ->setDate($date)
            ->setQuantity($quantity);

        $this->em->persist($entry);
        $this->em->flush();

        return $entry;
    }
}
