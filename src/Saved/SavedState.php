<?php

declare(strict_types=1);

namespace App\Saved;

use App\Entity\Restaurant;
use App\Entity\User;
use App\Repository\FavoriteRepository;
use App\Repository\RestaurantNoteRepository;

/**
 * Welche Lokale einer Kartenseite hat das Konto gemerkt, und welche Notizen gehören dazu?
 * Je **eine** Abfrage für die ganze Seite, egal wie viele Karten (AK-04, AK-34; Muster
 * `BoardVoteRepository::findVotedIdeaIds`) — eine Frage je Karte wäre N+1.
 *
 * ⚠ **Ein Doctrine-`Paginator` führt bei jedem Durchlauf seine Abfragen erneut aus.** Wer ihn
 * hier hineingibt und danach im Template noch einmal durchläuft, lädt die Seite zweimal. Der
 * Controller materialisiert deshalb einmal (`iterator_to_array($paginator, false)`) und gibt
 * dasselbe Array hierher und ins Template; `count($paginator)` bleibt davon unberührt.
 */
final readonly class SavedState
{
    public function __construct(
        private FavoriteRepository $favorites,
        private RestaurantNoteRepository $notes,
    ) {
    }

    /**
     * @param iterable<Restaurant> $restaurants
     */
    public function forRestaurants(?User $user, iterable $restaurants): SavedPage
    {
        // AK-52: Ein Gast hat keinen Merkzustand — und es gibt nichts abzufragen.
        if (null === $user) {
            return SavedPage::empty();
        }

        // Nach Nummer verschlüsselt: Dasselbe Lokal zweimal auf der Seite fragt nur einmal.
        $ids = [];
        foreach ($restaurants as $restaurant) {
            $id = $restaurant->getId();
            if (null !== $id) {
                $ids[$id] = $id;
            }
        }

        if ([] === $ids) {
            return SavedPage::empty();
        }

        $ids = array_values($ids);

        return SavedPage::of(
            $this->favorites->findFavoriteRestaurantIds($user, $ids),
            $this->notes->findTextsFor($user, $ids),
        );
    }
}
