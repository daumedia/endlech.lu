<?php

declare(strict_types=1);

namespace App\Saved;

use App\Entity\Restaurant;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingException;
use Symfony\Component\Routing\RouterInterface;

/**
 * Zwei Aufgaben rund um das Herz (Feature 17):
 *
 * 1. **Der Merkwunsch eines Gastes** liegt in seiner Sitzung, bis er sich anmeldet
 *    (`PendingFavoriteSubscriber` löst ihn ein, AK-08 bis AK-10). Nur der letzte Wunsch zählt.
 *    Ein GET-Link „nach der Anmeldung merken“ wäre von fremden Seiten auslösbar; die Sitzung
 *    gehört dem Besucher (`design.md`, Technische Entscheidung 5).
 *
 * 2. **Das Feld `ziel`**: die Seite, auf der das Herz stand, damit die Antwort ohne
 *    JavaScript wieder bei derselben Karte landet (AK-06, AK-09).
 *
 * ⚠ **`ziel` kommt aus dem Formular, also aus der Hand des Absenders — kein offener
 * Redirect.** Übernommen wird es nur, wenn der Router es als eine von vier Routen in der
 * aktuellen Sprache erkennt; sonst gilt die Detailseite des Lokals. Vom Abfrageteil überlebt
 * bei der Suche alles (den wertet die Suche selbst aus), sonst nur `page` als ganze Zahl.
 */
final readonly class PendingFavorite
{
    private const string SESSION_KEY = 'gespeichert.merkwunsch';

    /** Routen, auf denen ein Herz steht. */
    private const array ZIELE = [
        'app_restaurant_index',
        'app_restaurant_show',
        'app_saved_favorites',
        'app_saved_list_show',
    ];

    public function __construct(private RouterInterface $router)
    {
    }

    public function remember(SessionInterface $session, Restaurant $restaurant): void
    {
        $session->set(self::SESSION_KEY, (int) $restaurant->getId());
    }

    public function peek(SessionInterface $session): ?int
    {
        $wert = $session->get(self::SESSION_KEY);

        return \is_int($wert) ? $wert : null;
    }

    public function take(SessionInterface $session): ?int
    {
        $wert = $this->peek($session);
        $session->remove(self::SESSION_KEY);

        return $wert;
    }

    /**
     * Relative Adresse (Pfad und ggf. Abfrage), ohne Anker.
     */
    public function safeTarget(string $ziel, Restaurant $restaurant, string $locale): string
    {
        return $this->pruefe($ziel, $locale)
            ?? $this->router->generate('app_restaurant_show', ['_locale' => $locale, 'id' => $restaurant->getId()]);
    }

    public static function anchor(Restaurant $restaurant): string
    {
        return '#favorit-'.$restaurant->getId();
    }

    private function pruefe(string $ziel, string $locale): ?string
    {
        // Nur ein absoluter Pfad dieser Herkunft: kein Schema, kein `//host`, kein `/\host`
        // (Browser lesen den Backslash als Schrägstrich), keine Steuerzeichen.
        if (1 !== preg_match('#^/(?![/\\\\])[^\x00-\x1F\x7F\\\\]*$#', $ziel)) {
            return null;
        }

        $pfad = (string) parse_url($ziel, \PHP_URL_PATH);
        $abfrage = (string) parse_url($ziel, \PHP_URL_QUERY);

        $kontext = $this->router->getContext();
        $methode = $kontext->getMethod();
        // Die Zielseiten sind GET-Routen; abgeschickt wird das Herz per POST.
        $kontext->setMethod('GET');
        try {
            $treffer = $this->router->match($pfad);
        } catch (RoutingException) {
            return null;
        } finally {
            $kontext->setMethod($methode);
        }

        if (!\in_array($treffer['_route'] ?? null, self::ZIELE, true) || ($treffer['_locale'] ?? null) !== $locale) {
            return null;
        }

        if ('app_restaurant_index' === $treffer['_route']) {
            return '' === $abfrage ? $pfad : $pfad.'?'.$abfrage;
        }

        parse_str($abfrage, $parameter);
        $seite = $parameter['page'] ?? null;
        if (\is_string($seite) && 1 === preg_match('/^[1-9]\d{0,5}$/', $seite)) {
            return $pfad.'?page='.$seite;
        }

        return $pfad;
    }
}
