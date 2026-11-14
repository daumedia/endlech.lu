<?php

declare(strict_types=1);

namespace App\Saved;

use App\Entity\Favorite;
use App\Entity\Restaurant;
use App\Entity\User;
use App\Repository\FavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Das Herz (Feature 17): merken und entfernen.
 *
 * ⚠ **Zwei Absichten, kein Umschalter** (AK-05). Ein Umschalter nähme auf einer veralteten
 * Seite — Telefon hat gemerkt, Laptop zeigt noch das leere Herz — das Lokal wieder heraus. So
 * ist „merken“ auf ein gemerktes Lokal schlicht nichts, ebenso „entfernen“ auf ein entferntes.
 *
 * Rückgabe jeweils **„wurde etwas geändert?“** — danach richtet sich der Deckel: verbraucht
 * wird nur, wenn tatsächlich gespeichert wurde (BF-11, AK-58).
 *
 * Prüfen und Schreiben unter der Schreibsperre des Kontos (`SavedWriteLock`): Zwei schnelle
 * Tipps landen nacheinander, der zweite findet die Zeile und tut nichts.
 */
final readonly class FavoriteService
{
    public function __construct(
        private EntityManagerInterface $em,
        private FavoriteRepository $favorites,
        private SavedWriteLock $lock,
    ) {
    }

    public function add(User $user, Restaurant $restaurant): bool
    {
        return $this->lock->forAccount($user, function () use ($user, $restaurant): bool {
            if (null !== $this->favorites->findOneByUserAndRestaurant($user, $restaurant)) {
                return false;
            }

            $this->em->persist(new Favorite($user, $restaurant));
            $this->em->flush();

            return true;
        });
    }

    public function remove(User $user, Restaurant $restaurant): bool
    {
        return $this->lock->forAccount($user, function () use ($user, $restaurant): bool {
            $vorhanden = $this->favorites->findOneByUserAndRestaurant($user, $restaurant);
            if (null === $vorhanden) {
                return false;
            }

            $this->em->remove($vorhanden);
            $this->em->flush();

            return true;
        });
    }

    public function isFavorite(User $user, Restaurant $restaurant): bool
    {
        return null !== $this->favorites->findOneByUserAndRestaurant($user, $restaurant);
    }
}
