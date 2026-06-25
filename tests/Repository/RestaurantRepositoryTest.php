<?php

namespace App\Tests\Repository;

use App\Entity\Cuisine;
use App\Entity\OpeningHour;
use App\Entity\Restaurant;
use App\Entity\User;
use App\Enum\Language;
use App\Repository\RestaurantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Integrationstests gegen echtes MySQL (findPaginated nutzt JSON_CONTAINS).
 * DAMADoctrineTestBundle rollt jeden Test in einer Transaktion zurück.
 */
final class RestaurantRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private RestaurantRepository $repo;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repo = $container->get(RestaurantRepository::class);
    }

    private function newRestaurant(string $name, string $city = 'Luxembourg'): Restaurant
    {
        return (new Restaurant())->setName($name)->setCity($city);
    }

    private function persist(object ...$entities): void
    {
        foreach ($entities as $entity) {
            $this->em->persist($entity);
        }
        $this->em->flush();
    }

    /** @return int[] */
    private function ids(Paginator $paginator): array
    {
        return array_map(static fn (Restaurant $r) => $r->getId(), iterator_to_array($paginator));
    }

    public function testDefaultReturnsAllFixturesAsPaginator(): void
    {
        $paginator = $this->repo->findPaginated();

        self::assertInstanceOf(Paginator::class, $paginator);
        self::assertGreaterThanOrEqual(11, \count($paginator));
    }

    public function testVerifiedFilter(): void
    {
        $paginator = $this->repo->findPaginated('rating', 1, 100, ['verified' => 1]);

        $items = iterator_to_array($paginator);
        self::assertNotEmpty($items);
        foreach ($items as $restaurant) {
            self::assertTrue($restaurant->isVerified());
        }
        self::assertSame($this->repo->countVerified(), \count($paginator));
    }

    public function testSortByNameIsAscending(): void
    {
        $this->persist($this->newRestaurant('ZZZ Sortprobe'), $this->newRestaurant('AAA Sortprobe'));

        $names = array_map(
            static fn (Restaurant $r) => $r->getName(),
            iterator_to_array($this->repo->findPaginated('name', 1, 200)),
        );

        self::assertLessThan(
            array_search('ZZZ Sortprobe', $names, true),
            array_search('AAA Sortprobe', $names, true),
        );
    }

    public function testSortByNewestPutsFreshlyCreatedFirst(): void
    {
        $fresh = $this->newRestaurant('Brandneu '.uniqid());
        $this->persist($fresh);

        $items = iterator_to_array($this->repo->findPaginated('newest', 1, 1));

        self::assertSame($fresh->getId(), $items[0]->getId());
    }

    public function testPaginationLimitsAndPagesDoNotOverlap(): void
    {
        $page1 = $this->repo->findPaginated('name', 1, 5);
        $page2 = $this->repo->findPaginated('name', 2, 5);

        self::assertLessThanOrEqual(5, iterator_count($page1->getIterator()));
        self::assertSame([], array_intersect($this->ids($page1), $this->ids($page2)));
    }

    public function testCityFilterUsesLikeSearch(): void
    {
        $mine = $this->newRestaurant('Stadt Probe', 'Wormeldange-Haut');
        $this->persist($mine);

        $paginator = $this->repo->findPaginated('rating', 1, 100, ['city' => 'Wormeldange']);

        self::assertContains($mine->getId(), $this->ids($paginator));
        foreach (iterator_to_array($paginator) as $restaurant) {
            self::assertStringContainsStringIgnoringCase('Wormeldange', $restaurant->getCity());
        }
    }

    public function testCuisineFilterJoinsManyToMany(): void
    {
        $marker = uniqid();
        $cuisine = (new Cuisine())->setName('Testküche '.$marker)->setSlug('testkueche-'.$marker);

        $withCuisine = $this->newRestaurant('Mit Küche');
        $withCuisine->addCuisine($cuisine);
        $without = $this->newRestaurant('Ohne Küche');

        $this->persist($cuisine, $withCuisine, $without);

        $ids = $this->ids($this->repo->findPaginated('rating', 1, 100, ['cuisine' => [$cuisine->getId()]]));

        self::assertContains($withCuisine->getId(), $ids);
        self::assertNotContains($without->getId(), $ids);
    }

    public function testLanguageFilterUsesJsonContains(): void
    {
        $mine = $this->newRestaurant('Sprach Probe')->setSpokenLanguages([Language::PT]);
        $this->persist($mine);

        $paginator = $this->repo->findPaginated('rating', 1, 200, ['lang' => ['pt']]);

        self::assertContains($mine->getId(), $this->ids($paginator));
        foreach (iterator_to_array($paginator) as $restaurant) {
            $codes = array_map(static fn (Language $l) => $l->value, $restaurant->getSpokenLanguages());
            self::assertContains('pt', $codes);
        }
    }

    public function testBooleanAccessibilityFilter(): void
    {
        $yes = $this->newRestaurant('Rollstuhl Ja')->setIsWheelchairAccessible(true);
        $no = $this->newRestaurant('Rollstuhl Nein')->setIsWheelchairAccessible(false);
        $this->persist($yes, $no);

        $ids = $this->ids($this->repo->findPaginated('rating', 1, 200, ['wheelchair' => 1]));

        self::assertContains($yes->getId(), $ids);
        self::assertNotContains($no->getId(), $ids);
    }

    public function testOpenFilterIncludesOpenAndExcludesClosed(): void
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Luxembourg'));
        $today = (int) $now->format('N');

        // Slot 00:00–23:59:59 deckt fast jeden Zeitpunkt von "heute" ab (deterministisch,
        // unabhängig von der Uhrzeit des Testlaufs; die SQL-Nachtschicht-Logik selbst
        // wird in OpeningHoursServiceTest gesondert geprüft).
        $open = $this->newRestaurant('Immer Offen');
        $open->addOpeningHour(
            (new OpeningHour())->setDayOfWeek($today)
                ->setOpenTime(new \DateTime('00:00:00'))
                ->setCloseTime(new \DateTime('23:59:59'))
        );
        $closed = $this->newRestaurant('Ohne Zeiten'); // keine Slots => geschlossen

        $this->persist($open, $closed);

        $ids = $this->ids($this->repo->findPaginated('rating', 1, 200, ['open' => 1]));

        self::assertContains($open->getId(), $ids);
        self::assertNotContains($closed->getId(), $ids);
    }

    public function testCombinedFiltersAreAndedTogether(): void
    {
        $match = $this->newRestaurant('Alles erfüllt')
            ->setIsVerified(true)
            ->setIsWheelchairAccessible(true)
            ->setIsVegan(true);
        $partial = $this->newRestaurant('Nur verifiziert')->setIsVerified(true);
        $this->persist($match, $partial);

        $filters = ['verified' => 1, 'wheelchair' => 1, 'vegan' => 1];
        $items = iterator_to_array($this->repo->findPaginated('rating', 1, 200, $filters));
        $ids = array_map(static fn (Restaurant $r) => $r->getId(), $items);

        self::assertContains($match->getId(), $ids);
        self::assertNotContains($partial->getId(), $ids);
        foreach ($items as $restaurant) {
            self::assertTrue($restaurant->isVerified());
            self::assertTrue($restaurant->isWheelchairAccessible());
            self::assertTrue($restaurant->isVegan());
        }
    }

    public function testFindTopRatedIsLimitedAndDescending(): void
    {
        $top = $this->repo->findTopRated(6);

        self::assertLessThanOrEqual(6, \count($top));

        $previous = null;
        foreach ($top as $restaurant) {
            $rating = $restaurant->getRating();
            if ($previous !== null && $rating !== null) {
                self::assertLessThanOrEqual($previous, $rating);
            }
            if ($rating !== null) {
                $previous = $rating;
            }
        }
    }

    public function testFindBySubmitterReturnsOnlyOwnRestaurants(): void
    {
        $user = (new User())
            ->setName('Einreicher')
            ->setEmail('einreicher_'.uniqid().'@endlech.lu')
            ->setPassword('hashed');
        $r1 = $this->newRestaurant('Eingereicht 1')->setSubmittedBy($user);
        $r2 = $this->newRestaurant('Eingereicht 2')->setSubmittedBy($user);
        $foreign = $this->newRestaurant('Fremd');

        $this->persist($user, $r1, $r2, $foreign);

        $result = $this->repo->findBySubmitter($user);
        $ids = array_map(static fn (Restaurant $r) => $r->getId(), $result);

        self::assertCount(2, $result);
        self::assertContains($r1->getId(), $ids);
        self::assertContains($r2->getId(), $ids);
        self::assertNotContains($foreign->getId(), $ids);
    }

    public function testCountVerifiedMatchesFixtures(): void
    {
        self::assertSame(3, $this->repo->countVerified());
    }

    public function testCountCreatedSince(): void
    {
        self::assertSame(0, $this->repo->countCreatedSince(new \DateTimeImmutable('+1 day')));
        self::assertGreaterThanOrEqual(11, $this->repo->countCreatedSince(new \DateTimeImmutable('-10 years')));
    }

    public function testFindRecentIsLimited(): void
    {
        self::assertLessThanOrEqual(3, \count($this->repo->findRecent(3)));
    }
}
