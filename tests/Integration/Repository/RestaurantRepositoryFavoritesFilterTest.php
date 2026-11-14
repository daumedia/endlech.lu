<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\Cuisine;
use App\Entity\Favorite;
use App\Entity\OpeningHour;
use App\Entity\Restaurant;
use App\Entity\User;
use App\Repository\RestaurantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Feature 17, T09: Filter „Nur meine Favoriten“ in `findPaginated` — eine zusätzliche
 * Bedingung, kombinierbar mit jedem anderen Filter, Sortierung und Blättern (AK-38), und nur
 * mit den Favoriten des übergebenen Kontos (AK-52).
 *
 * Geprüft wird über eigene Konten und Lokale; die Fixture-Lokale tragen keine Favoriten dieser
 * Konten und dürfen deshalb nie im Ergebnis stehen.
 */
final class RestaurantRepositoryFavoritesFilterTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private RestaurantRepository $repo;
    private User $a;

    /** @var list<Restaurant> die fünf Favoriten von A; [0] und [1] rollstuhlgerecht */
    private array $favoriten;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repo = $container->get(RestaurantRepository::class);

        $this->a = $this->konto('filter-a@example.test');

        $this->favoriten = [
            $this->lokal('Fav Rollstuhl B', rollstuhl: true)->setRating(4.0),
            $this->lokal('Fav Rollstuhl A', rollstuhl: true)->setRating(3.0),
            $this->lokal('Fav Treppe 1'),
            $this->lokal('Fav Treppe 2'),
            $this->lokal('Fav Treppe 3'),
        ];

        // ⚠ Mehrere Öffnungszeiten und Küchen am ersten Treffer: Ohne fetchJoinCollection
        // begrenzte `setMaxResults()` Zeilen statt Lokale (BF-64) — Seite 1 der Größe 1 wäre
        // dann zwar richtig, aber die Zählung und Seite 2 nicht.
        $this->mitKartenDaten($this->favoriten[0]);

        // Rollstuhlgerecht, aber nicht gemerkt: darf trotz `wheelchair` nie erscheinen.
        $this->lokal('Nicht gemerkt Rollstuhl', rollstuhl: true);

        $this->em->flush();
        foreach ($this->favoriten as $lokal) {
            $this->em->persist(new Favorite($this->a, $lokal));
        }
        $this->em->flush();
    }

    public function testAk38NurFavoritenLiefertGenauDieFuenf(): void
    {
        $karten = $this->repo->findPaginated('name', 1, 12, ['favorites_of' => $this->a]);

        self::assertCount(5, $karten);
        self::assertEqualsCanonicalizing($this->idsVon($this->favoriten), $this->ids($karten));
    }

    public function testAk38FilterMitRollstuhlLiefertZwei(): void
    {
        $karten = $this->repo->findPaginated('rating', 1, 12, [
            'favorites_of' => $this->a,
            'wheelchair' => true,
        ]);

        self::assertCount(2, $karten);
        // Sortierung wie bei jedem Filter: Bewertung absteigend.
        self::assertSame($this->idsVon([$this->favoriten[0], $this->favoriten[1]]), $this->ids($karten));
    }

    public function testAk38SortierungNachNameGiltAuchMitFavoriten(): void
    {
        $karten = $this->repo->findPaginated('name', 1, 12, [
            'favorites_of' => $this->a,
            'wheelchair' => true,
        ]);

        self::assertSame($this->idsVon([$this->favoriten[1], $this->favoriten[0]]), $this->ids($karten));
    }

    public function testAk38BlaetternMitSeitengroesseEinsErgibtZweiSeiten(): void
    {
        $filter = ['favorites_of' => $this->a, 'wheelchair' => true];

        $seite1 = $this->repo->findPaginated('rating', 1, 1, $filter);
        $seite2 = $this->repo->findPaginated('rating', 2, 1, $filter);
        $seite3 = $this->repo->findPaginated('rating', 3, 1, $filter);

        self::assertCount(2, $seite1, 'Die Zählung umfasst alle Treffer, nicht nur die Seite.');
        self::assertSame([$this->favoriten[0]->getId()], $this->ids($seite1));
        self::assertSame([$this->favoriten[1]->getId()], $this->ids($seite2));
        self::assertSame([], $this->ids($seite3));
    }

    public function testAk38KombinierbarMitOrtUndKueche(): void
    {
        $kueche = $this->favoriten[0]->getCuisines()->first();
        self::assertInstanceOf(Cuisine::class, $kueche);

        $karten = $this->repo->findPaginated('rating', 1, 12, [
            'favorites_of' => $this->a,
            'city' => 'Luxembourg',
            'cuisine' => [$kueche->getId()],
        ]);

        self::assertSame([$this->favoriten[0]->getId()], $this->ids($karten));
    }

    public function testAk52KontoBOhneFavoritenSiehtKeinenTreffer(): void
    {
        $b = $this->konto('filter-b@example.test');

        $karten = $this->repo->findPaginated('rating', 1, 12, ['favorites_of' => $b]);

        self::assertCount(0, $karten);
        self::assertSame([], $this->ids($karten));
    }

    public function testAk52KontoBSiehtNurSeinenEigenenFavoriten(): void
    {
        $b = $this->konto('filter-b2@example.test');
        $this->em->persist(new Favorite($b, $this->favoriten[2]));
        $this->em->flush();

        $karten = $this->repo->findPaginated('rating', 1, 12, ['favorites_of' => $b]);

        self::assertSame([$this->favoriten[2]->getId()], $this->ids($karten));
    }

    public function testOhneSchluesselOderMitNullBleibtDieSucheUnveraendert(): void
    {
        $ohne = \count($this->repo->findPaginated('rating', 1, 6, []));

        self::assertSame($ohne, \count($this->repo->findPaginated('rating', 1, 6, ['favorites_of' => null])));
        // Mindestens die fünf Favoriten und das ungemerkte Lokal sind darin.
        self::assertGreaterThanOrEqual(6, $ohne);
    }

    /** @return list<int|null> */
    private function ids(Paginator $karten): array
    {
        return array_map(static fn (Restaurant $r): ?int => $r->getId(), iterator_to_array($karten, false));
    }

    /**
     * @param list<Restaurant> $lokale
     *
     * @return list<int|null>
     */
    private function idsVon(array $lokale): array
    {
        return array_map(static fn (Restaurant $r): ?int => $r->getId(), $lokale);
    }

    private function konto(string $email): User
    {
        $user = (new User())->setName('x')->setEmail($email)->setPassword('x')->setIsVerified(true);
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function lokal(string $name, bool $rollstuhl = false): Restaurant
    {
        $lokal = (new Restaurant())
            ->setName($name)
            ->setCity('Luxembourg')
            ->setIsWheelchairAccessible($rollstuhl);
        $this->em->persist($lokal);

        return $lokal;
    }

    private function mitKartenDaten(Restaurant $lokal): void
    {
        foreach ([1, 2, 3] as $tag) {
            $lokal->addOpeningHour((new OpeningHour())
                ->setDayOfWeek($tag)
                ->setOpenTime(new \DateTime('11:00'))
                ->setCloseTime(new \DateTime('14:00')));
        }
        foreach (['eins', 'zwei'] as $nummer) {
            $kueche = (new Cuisine())->setName('Favoritenkueche '.$nummer)->setSlug('favoritenkueche-'.$nummer);
            $this->em->persist($kueche);
            $lokal->addCuisine($kueche);
        }
    }
}
