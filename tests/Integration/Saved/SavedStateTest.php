<?php

declare(strict_types=1);

namespace App\Tests\Integration\Saved;

use App\Entity\Favorite;
use App\Entity\Restaurant;
use App\Entity\RestaurantNote;
use App\Entity\User;
use App\Repository\FavoriteRepository;
use App\Repository\RestaurantNoteRepository;
use App\Repository\RestaurantRepository;
use App\Saved\SavedState;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Feature 17, T09: Merkzustand und Notizen einer Kartenseite gegen die echte Datenbank —
 * je Konto getrennt (AK-04, AK-34, AK-52).
 */
final class SavedStateTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private SavedState $state;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        // Aus den echten Repositories gebaut: Solange kein Controller `SavedState` benutzt
        // (T19/T20), entfernt der Container den privaten Dienst beim Kompilieren.
        $this->state = new SavedState(
            $container->get(FavoriteRepository::class),
            $container->get(RestaurantNoteRepository::class),
        );
    }

    public function testAk04GemerktesLokalIstGefuelltUndNotizStehtDabei(): void
    {
        $a = $this->konto('state-a@example.test');
        [$eins, $zwei, $drei] = $this->lokale('State', 3);
        $this->merke($a, $eins, $drei);
        $this->notiere($a, $drei, "Seiteneingang\nlinks");
        $this->notiere($a, $zwei, 'Nur Notiz, kein Herz');

        $seite = $this->state->forRestaurants($a, [$eins, $zwei, $drei]);

        self::assertTrue($seite->isFavorite($eins->getId()));
        self::assertFalse($seite->isFavorite($zwei->getId()));
        self::assertTrue($seite->isFavorite($drei->getId()));
        self::assertNull($seite->noteFor($eins->getId()));
        self::assertSame('Nur Notiz, kein Herz', $seite->noteFor($zwei->getId()));
        self::assertSame("Seiteneingang\nlinks", $seite->noteFor($drei->getId()));
    }

    public function testAk34NotizStehtAuchAufDerFavoritenseiteAusDemPaginator(): void
    {
        $a = $this->konto('state-paginator@example.test');
        [$eins, $zwei] = $this->lokale('Paginator', 2);
        $this->merke($a, $eins, $zwei);
        $this->notiere($a, $zwei, 'Rampe hinten');

        $karten = static::getContainer()->get(RestaurantRepository::class)
            ->findPaginated('name', 1, 12, ['favorites_of' => $a]);

        $seite = $this->state->forRestaurants($a, $karten);

        self::assertTrue($seite->isFavorite($eins->getId()));
        self::assertTrue($seite->isFavorite($zwei->getId()));
        self::assertSame('Rampe hinten', $seite->noteFor($zwei->getId()));
    }

    public function testAk52KontoBSiehtWederHerzNochNotizVonKontoA(): void
    {
        $a = $this->konto('state-a2@example.test');
        $b = $this->konto('state-b2@example.test');
        [$eins, $zwei] = $this->lokale('Getrennt', 2);
        $this->merke($a, $eins, $zwei);
        $this->notiere($a, $eins, 'Privat von A');

        $seite = $this->state->forRestaurants($b, [$eins, $zwei]);

        self::assertFalse($seite->isFavorite($eins->getId()));
        self::assertFalse($seite->isFavorite($zwei->getId()));
        self::assertNull($seite->noteFor($eins->getId()));
    }

    public function testAk52GastSiehtWederHerzNochNotiz(): void
    {
        $a = $this->konto('state-a3@example.test');
        [$eins] = $this->lokale('Gast', 1);
        $this->merke($a, $eins);
        $this->notiere($a, $eins, 'Privat von A');

        $seite = $this->state->forRestaurants(null, [$eins]);

        self::assertFalse($seite->isFavorite($eins->getId()));
        self::assertNull($seite->noteFor($eins->getId()));
    }

    private function konto(string $email): User
    {
        $user = (new User())->setName('x')->setEmail($email)->setPassword('x')->setIsVerified(true);
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /** @return list<Restaurant> */
    private function lokale(string $praefix, int $anzahl): array
    {
        $lokale = [];
        foreach (range(1, $anzahl) as $i) {
            $lokal = (new Restaurant())->setName(\sprintf('%s %d', $praefix, $i))->setCity('Luxembourg');
            $this->em->persist($lokal);
            $lokale[] = $lokal;
        }
        $this->em->flush();

        return $lokale;
    }

    private function merke(User $konto, Restaurant ...$lokale): void
    {
        foreach ($lokale as $lokal) {
            $this->em->persist(new Favorite($konto, $lokal));
        }
        $this->em->flush();
    }

    private function notiere(User $konto, Restaurant $lokal, string $text): void
    {
        $this->em->persist(new RestaurantNote($konto, $lokal, $text));
        $this->em->flush();
    }
}
