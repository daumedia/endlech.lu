<?php

declare(strict_types=1);

namespace App\Saved;

/**
 * Merkzustand und Notizen eines Kontos für die Karten **einer** Seite (Feature 17).
 *
 * Reines Nachschlagen ohne Datenbank: `SavedState` hat die beiden Abfragen vorher gestellt.
 * Für Gäste und für Lokale, die nicht auf der Seite standen, gilt „nicht gemerkt, keine
 * Notiz“ — es gibt keinen Weg, hier den Zustand eines anderen Kontos hineinzubekommen (AK-52).
 */
final readonly class SavedPage
{
    /**
     * @param array<int, true>   $favorites Lokalnummer => true
     * @param array<int, string> $notes     Lokalnummer => Notiztext
     */
    private function __construct(
        private array $favorites,
        private array $notes,
    ) {
    }

    public static function empty(): self
    {
        return new self([], []);
    }

    /**
     * @param int[]              $favoriteRestaurantIds
     * @param array<int, string> $notes                 Lokalnummer => Notiztext
     */
    public static function of(array $favoriteRestaurantIds, array $notes): self
    {
        return new self(array_fill_keys($favoriteRestaurantIds, true), $notes);
    }

    public function isFavorite(int $restaurantId): bool
    {
        return isset($this->favorites[$restaurantId]);
    }

    public function noteFor(int $restaurantId): ?string
    {
        return $this->notes[$restaurantId] ?? null;
    }
}
