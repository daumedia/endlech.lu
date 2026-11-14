<?php

declare(strict_types=1);

namespace App\Tests\Unit\Saved;

use App\Entity\Restaurant;
use App\Entity\User;
use App\Repository\FavoriteRepository;
use App\Repository\RestaurantNoteRepository;
use App\Saved\SavedPage;
use App\Saved\SavedState;
use PHPUnit\Framework\TestCase;

/**
 * Feature 17, T09: Der Merkzustand einer Kartenseite kostet je Art **eine** Abfrage, egal wie
 * viele Karten — kein N+1 (AK-04, AK-34). Ein Gast kostet keine (AK-52).
 */
final class SavedStateTest extends TestCase
{
    public function testAk34ZwoelfKartenKostenJeEineAbfrageFuerFavoritenUndNotizen(): void
    {
        $konto = new User();
        $lokale = array_map($this->lokal(...), range(1, 12));

        $favoriten = $this->createMock(FavoriteRepository::class);
        $favoriten->expects(self::once())
            ->method('findFavoriteRestaurantIds')
            ->with($konto, range(1, 12))
            ->willReturn([3, 7]);

        $notizen = $this->createMock(RestaurantNoteRepository::class);
        $notizen->expects(self::once())
            ->method('findTextsFor')
            ->with($konto, range(1, 12))
            ->willReturn([7 => 'Seiteneingang links', 9 => 'Rampe hinten']);

        $seite = (new SavedState($favoriten, $notizen))->forRestaurants($konto, $lokale);

        self::assertTrue($seite->isFavorite(3));
        self::assertTrue($seite->isFavorite(7));
        self::assertFalse($seite->isFavorite(4));
        self::assertSame('Seiteneingang links', $seite->noteFor(7));
        self::assertSame('Rampe hinten', $seite->noteFor(9));
        self::assertNull($seite->noteFor(3));
    }

    public function testAk04DasselbeLokalZweimalAufDerSeiteFragtSeineNummerNurEinmalAb(): void
    {
        $konto = new User();
        $lokal = $this->lokal(5);

        $favoriten = $this->createMock(FavoriteRepository::class);
        $favoriten->expects(self::once())
            ->method('findFavoriteRestaurantIds')
            ->with($konto, [5, 6])
            ->willReturn([5]);

        $notizen = $this->createMock(RestaurantNoteRepository::class);
        $notizen->expects(self::once())->method('findTextsFor')->with($konto, [5, 6])->willReturn([]);

        $seite = (new SavedState($favoriten, $notizen))->forRestaurants($konto, [$lokal, $this->lokal(6), $lokal]);

        self::assertTrue($seite->isFavorite(5));
        self::assertFalse($seite->isFavorite(6));
    }

    public function testAk52GastLoestKeineAbfrageAusUndSiehtNichts(): void
    {
        $favoriten = $this->createMock(FavoriteRepository::class);
        $favoriten->expects(self::never())->method('findFavoriteRestaurantIds');

        $notizen = $this->createMock(RestaurantNoteRepository::class);
        $notizen->expects(self::never())->method('findTextsFor');

        $seite = (new SavedState($favoriten, $notizen))
            ->forRestaurants(null, array_map($this->lokal(...), range(1, 12)));

        foreach (range(1, 12) as $id) {
            self::assertFalse($seite->isFavorite($id));
            self::assertNull($seite->noteFor($id));
        }
    }

    public function testOhneKartenKeineAbfrage(): void
    {
        $favoriten = $this->createMock(FavoriteRepository::class);
        $favoriten->expects(self::never())->method('findFavoriteRestaurantIds');

        $notizen = $this->createMock(RestaurantNoteRepository::class);
        $notizen->expects(self::never())->method('findTextsFor');

        $seite = (new SavedState($favoriten, $notizen))->forRestaurants(new User(), []);

        self::assertFalse($seite->isFavorite(1));
    }

    public function testLeereSeiteKenntNichts(): void
    {
        self::assertFalse(SavedPage::empty()->isFavorite(1));
        self::assertNull(SavedPage::empty()->noteFor(1));
    }

    private function lokal(int $id): Restaurant
    {
        $lokal = (new Restaurant())->setName('Lokal '.$id)->setCity('Luxembourg');
        (new \ReflectionProperty(Restaurant::class, 'id'))->setValue($lokal, $id);

        return $lokal;
    }
}
