<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\Cuisine;
use App\Entity\OpeningHour;
use App\Entity\Restaurant;
use App\Repository\RestaurantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Feature 17, T09: `findForCards()` lädt die Karten einer Seite, deren Nummern aus einer
 * eigenen Abfrage kommen (Favoriten zuletzt gemerkt zuerst, Listen) — in genau dieser
 * Reihenfolge und mit Öffnungszeiten und Küchen in derselben Abfrage (AK-04, AK-34).
 */
final class RestaurantRepositoryFindForCardsTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private RestaurantRepository $repo;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repo = $container->get(RestaurantRepository::class);
    }

    public function testAk04KartenKommenInDerReihenfolgeDerNummern(): void
    {
        [$eins, $zwei, $drei] = $this->lokale(3);

        $karten = $this->repo->findForCards([$drei->getId(), $eins->getId(), $zwei->getId()]);

        self::assertSame(
            [$drei->getId(), $eins->getId(), $zwei->getId()],
            array_map(static fn (Restaurant $r): ?int => $r->getId(), $karten),
        );
        self::assertTrue(array_is_list($karten));
    }

    public function testUnbekannteNummernFallenWeg(): void
    {
        [$eins, $zwei] = $this->lokale(2);
        $unbekannt = $this->hoechsteNummer() + 1000;

        $karten = $this->repo->findForCards([$zwei->getId(), $unbekannt, $eins->getId()]);

        self::assertSame(
            [$zwei->getId(), $eins->getId()],
            array_map(static fn (Restaurant $r): ?int => $r->getId(), $karten),
        );
    }

    public function testLeereNummernlisteLiefertLeereListe(): void
    {
        self::assertSame([], $this->repo->findForCards([]));
    }

    public function testAk34OeffnungszeitenUndKuechenSindMitGeladen(): void
    {
        [$lokal] = $this->lokale(1);
        foreach ([1, 2, 3] as $tag) {
            $lokal->addOpeningHour((new OpeningHour())
                ->setDayOfWeek($tag)
                ->setOpenTime(new \DateTime('11:00'))
                ->setCloseTime(new \DateTime('14:00')));
        }
        foreach (['eins', 'zwei'] as $nummer) {
            $kueche = (new Cuisine())->setName('Kartenkueche '.$nummer)->setSlug('kartenkueche-'.$nummer);
            $this->em->persist($kueche);
            $lokal->addCuisine($kueche);
        }
        $this->em->flush();
        $id = $lokal->getId();
        // Frisch aus der Datenbank, nicht aus der Identity Map.
        $this->em->clear();

        $karten = $this->repo->findForCards([$id]);

        // ⚠ Ein Lokal, nicht sechs Zeilen (3 Öffnungszeiten × 2 Küchen, BF-64).
        self::assertCount(1, $karten);
        $oeffnungszeiten = $karten[0]->getOpeningHours();
        $kuechen = $karten[0]->getCuisines();
        self::assertInstanceOf(PersistentCollection::class, $oeffnungszeiten);
        self::assertInstanceOf(PersistentCollection::class, $kuechen);
        self::assertTrue($oeffnungszeiten->isInitialized(), 'Öffnungszeiten müssen per Join geladen sein (kein N+1).');
        self::assertTrue($kuechen->isInitialized(), 'Küchen müssen per Join geladen sein (kein N+1).');
        self::assertCount(3, $oeffnungszeiten);
        self::assertCount(2, $kuechen);
    }

    /** @return list<Restaurant> */
    private function lokale(int $anzahl): array
    {
        $lokale = [];
        foreach (range(1, $anzahl) as $i) {
            $lokal = (new Restaurant())->setName('Karte '.$i)->setCity('Luxembourg');
            $this->em->persist($lokal);
            $lokale[] = $lokal;
        }
        $this->em->flush();

        return $lokale;
    }

    private function hoechsteNummer(): int
    {
        return (int) $this->em->getConnection()->fetchOne('SELECT MAX(id) FROM restaurant');
    }
}
