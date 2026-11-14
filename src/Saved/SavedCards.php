<?php

declare(strict_types=1);

namespace App\Saved;

use App\Entity\Restaurant;
use App\Entity\SavedList;
use App\Entity\User;
use App\Repository\FavoriteRepository;
use App\Repository\RestaurantRepository;
use App\Repository\SavedListEntryRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Das Kartenraster der Seiten „Favoriten“ und Liste (Feature 17) — für die Seite selbst, für
 * den Stream nach „Aus Liste entfernen“ (AK-26) und für das Herz auf „Favoriten“ (AK-14).
 * Eine Quelle, damit Seite, Stream und Weiterleitung dieselbe Seite und dasselbe Fokusziel
 * rechnen; vorher stand die Logik privat in `SavedController` und das Herz kam nicht heran.
 *
 * `$liste === null` heißt „Favoriten“, sonst die Einträge dieser Liste. **Den Besitz der Liste
 * prüft der Aufrufer** (`SavedListRepository::findOneOwned`) — hier kommt nur eine eigene an.
 *
 * Die Karten kommen in zwei Schritten (Nummern der Seite, dann `findForCards()`), weil
 * `setMaxResults()` mit Joins Zeilen statt Lokale zählte (BF-64). Herz-Zustand und Notiz aller
 * Karten der Seite kosten je eine Abfrage (`SavedState`, AK-34).
 */
final readonly class SavedCards
{
    /** Karten je Seite (Spec, Decision Log 23). */
    public const int PER_PAGE = 12;

    /**
     * Sitzungstoken der handgeschriebenen Formulare einer Liste („Löschen“, „Aus Liste
     * entfernen“). Je Liste, damit ein Token aus einer Seite nicht für eine andere Liste gilt.
     */
    private const string LIST_TOKEN_PREFIX = 'gespeichert-liste-';

    public function __construct(
        private FavoriteRepository $favorites,
        private SavedListEntryRepository $entries,
        private RestaurantRepository $restaurants,
        private SavedState $state,
        private UrlGeneratorInterface $urls,
    ) {
    }

    public static function listToken(SavedList $liste): string
    {
        return self::LIST_TOKEN_PREFIX.$liste->getId();
    }

    public function total(User $konto, ?SavedList $liste): int
    {
        return null === $liste ? $this->favorites->countByUser($konto) : $this->entries->countByList($liste);
    }

    /**
     * Die Variablen für `saved/_cards.html.twig`, oder `null` für eine Seite hinter dem Ende —
     * der Aufrufer antwortet dann mit 404 wie die Suche (BF-60, EC-16).
     *
     * @return array<string, mixed>|null
     */
    public function page(User $konto, ?SavedList $liste, int $seite): ?array
    {
        $gesamt = $this->total($konto, $liste);
        $letzte = self::lastPage($gesamt);

        if ($seite > $letzte && $seite > 1) {
            return null;
        }

        return $this->variables($konto, $liste, $this->ids($konto, $liste, $seite), $gesamt, $seite, $letzte);
    }

    /**
     * Eine Seitenzahl aus einem Formular, begrenzt auf das Raster: mindestens 1, höchstens die
     * letzte Seite (BF-213). **Vor jedem Rechnen damit** — `(seite - 1) * PER_PAGE` lief bei
     * `PHP_INT_MAX` zu `float` über (TypeError, HTTP 500). Eine Seite hinter dem Ende wird damit so
     * behandelt wie vorher eine knapp dahinter: Entfernen wirkt, die Antwort zeigt die letzte Seite.
     */
    public function clampPage(User $konto, ?SavedList $liste, int $seite): int
    {
        return min(max(1, $seite), self::lastPage($this->total($konto, $liste)));
    }

    /**
     * Wo stand die Karte vor dem Entfernen? Danach rückt die nächste an genau diese Stelle.
     * `null`, wenn sie nicht (mehr) auf der Seite stand — zweiter Tab, Doppelklick.
     */
    public function position(User $konto, ?SavedList $liste, int $seite, Restaurant $lokal): ?int
    {
        $stelle = array_search($lokal->getId(), $this->ids($konto, $liste, $seite), true);

        return false === $stelle ? null : $stelle;
    }

    /**
     * Das Raster nach dem Entfernen, mit Fokusziel (AK-14, AK-26): die nächste Karte, bei der
     * letzten die vorige, ist keine mehr übrig, der Leertext.
     *
     * ⚠ **Die Seite wird geklemmt**, wenn sie durch das Entfernen leer würde (letzte Karte auf
     * Seite 2): Sonst stünde danach eine leere Seite da, die beim nächsten Laden 404 ist.
     *
     * @param int|null $stelle aus `position()`, vor dem Entfernen gerechnet
     *
     * @return array<string, mixed> mit `seite` (geklemmt) und `fokusKarte` (Lokalnummer oder null)
     */
    public function afterRemoval(User $konto, ?SavedList $liste, int $seite, ?int $stelle): array
    {
        $gesamt = $this->total($konto, $liste);
        $letzte = self::lastPage($gesamt);
        $geklemmt = $seite > $letzte;
        $seite = min($seite, $letzte);
        $nummern = $this->ids($konto, $liste, $seite);
        $fokus = self::focusAfterRemoval($nummern, $stelle ?? 0, $geklemmt);

        return [
            ...$this->variables($konto, $liste, $nummern, $gesamt, $seite, $letzte),
            'fokusKarte' => $fokus,
            'fokusLeer' => null === $fokus,
        ];
    }

    /** Relative Adresse einer Seite des Rasters, ohne Anker. */
    public function address(?SavedList $liste, int $seite): string
    {
        [$route, $parameter] = self::route($liste);

        return $this->urls->generate($route, $parameter + ($seite > 1 ? ['page' => $seite] : []));
    }

    /**
     * Der Anker der Weiterleitung ohne JavaScript — dasselbe Ziel, das mit Turbo den Fokus bekommt.
     *
     * @param array<string, mixed> $karten aus `afterRemoval()`
     */
    public static function anchor(array $karten): string
    {
        $fokus = $karten['fokusKarte'] ?? null;

        return \is_int($fokus) ? '#karte-'.$fokus : '#gespeichert-leer';
    }

    /**
     * Stand das Herz auf „Favoriten“? Dann die Seitenzahl, sonst `null`.
     *
     * ⚠ **Nur für ein bereits geprüftes `ziel`** (`PendingFavorite::safeTarget`). Das liefert
     * den Pfad allein oder mit `?page=N`; verglichen wird mit der erzeugten Adresse derselben
     * Sprache. Alles andere — auch eine abweichende Schreibweise — gilt als „nicht Favoriten“, und
     * das Herz antwortet wie überall (nur das Herz). Das ist der sichere Ausgang.
     */
    public function favoritesPage(string $ziel): ?int
    {
        $pfad = $this->urls->generate('app_saved_favorites');
        if (!str_starts_with($ziel, $pfad)) {
            return null;
        }

        $rest = substr($ziel, \strlen($pfad));
        if ('' === $rest) {
            return 1;
        }

        return 1 === preg_match('/^\?page=([1-9]\d{0,5})$/', $rest, $treffer) ? (int) $treffer[1] : null;
    }

    private static function lastPage(int $gesamt): int
    {
        return max(1, (int) ceil($gesamt / self::PER_PAGE));
    }

    /**
     * @param int[] $nummern Lokale der Seite nach dem Entfernen, in Anzeigereihenfolge
     */
    private static function focusAfterRemoval(array $nummern, int $stelle, bool $geklemmt): ?int
    {
        if ([] === $nummern) {
            return null;
        }

        // Nach dem Klemmen ist die vorige Karte die letzte der nun gezeigten Seite.
        if ($geklemmt) {
            return $nummern[array_key_last($nummern)];
        }

        return $nummern[min($stelle, \count($nummern) - 1)];
    }

    /**
     * @return array{string, array<string, int|null>}
     */
    private static function route(?SavedList $liste): array
    {
        return null === $liste
            ? ['app_saved_favorites', []]
            : ['app_saved_list_show', ['id' => $liste->getId()]];
    }

    /**
     * @return int[]
     */
    private function ids(User $konto, ?SavedList $liste, int $seite): array
    {
        $versatz = ($seite - 1) * self::PER_PAGE;

        return null === $liste
            ? $this->favorites->findRestaurantIdsPage($konto, $versatz, self::PER_PAGE)
            : $this->entries->findRestaurantIdsPage($liste, $versatz, self::PER_PAGE);
    }

    /**
     * @param int[] $nummern
     *
     * @return array<string, mixed>
     */
    private function variables(User $konto, ?SavedList $liste, array $nummern, int $gesamt, int $seite, int $letzte): array
    {
        [$route, $parameter] = self::route($liste);
        $lokale = $this->restaurants->findForCards($nummern);

        return [
            'lokale' => $lokale,
            // Herz-Zustand und Notiz für alle Karten der Seite in je einer Abfrage (AK-34).
            'gespeichert' => $this->state->forRestaurants($konto, $lokale),
            'liste' => $liste,
            'entfernenToken' => null === $liste ? null : self::listToken($liste),
            'gesamt' => $gesamt,
            'zahlText' => null === $liste ? 'saved.favorites.count' : 'saved.lists.count',
            'seite' => $seite,
            'letzteSeite' => $letzte,
            'blaetternRoute' => $route,
            'blaetternParameter' => $parameter,
            // Die Seite, auf die das Herz ohne JavaScript zurückführt — auch aus dem Stream, wo die
            // aktuelle Adresse die des Formulars wäre (EC-15, AK-14).
            'ziel' => $this->address($liste, $seite),
            'fokusKarte' => null,
            'fokusLeer' => false,
            'leertext' => null === $liste ? 'saved.favorites.empty' : 'saved.list.empty',
        ];
    }
}
