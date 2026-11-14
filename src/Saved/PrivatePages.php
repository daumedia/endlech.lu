<?php

declare(strict_types=1);

namespace App\Saved;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Seiten, die einem angemeldeten Konto private Angaben zeigen (Feature 17, AK-63): gefüllte
 * Herzen und Häkchen auf Suche und Detailseite, Listen und Notizen unter „Gespeichert“.
 *
 * Nach dem Abmelden darf „Zurück“ sie nicht wieder hervorholen — weder aus Turbos
 * Schnappschüssen noch aus dem Verlaufsspeicher des Browsers. Zwei Stellen fragen deshalb
 * hier: `PrivatePageCacheSubscriber` (Kopfzeile `no-store`) und `PrivatePageExtension`
 * (`turbo-cache-control` im Seitenkopf).
 *
 * ⚠ **Eine Liste, eine Stelle.** Wer eine Seite anlegt, die Gespeichertes eines Kontos zeigt,
 * trägt sie hier ein — sonst sind Kopfzeile und Meta-Element dort einfach nicht da, und kein
 * Prüflauf ohne Browser bemerkt es.
 */
final readonly class PrivatePages
{
    /** Alle Seiten des Bereichs „Gespeichert“. */
    private const string PRAEFIX = 'app_saved_';

    /** Seiten außerhalb des Bereichs, die Gespeichertes mitzeigen. */
    private const array ROUTEN = ['app_restaurant_index', 'app_restaurant_show'];

    public function __construct(private TokenStorageInterface $tokens)
    {
    }

    public function isPrivate(?string $route): bool
    {
        if (null === $route) {
            return false;
        }

        return str_starts_with($route, self::PRAEFIX) || \in_array($route, self::ROUTEN, true);
    }

    /**
     * Angemeldet UND private Route — die Bedingung für Kopfzeile und Meta-Element.
     *
     * Ein Gast sieht auf diesen Seiten nichts Privates; für ihn bleibt alles wie bisher.
     *
     * ⚠ Erst die Route, dann das Konto: Das Lesen des Tokens zählt als Sitzungsnutzung, und die
     * übrigen Seiten sollen davon nichts merken.
     */
    public function isPrivateView(?Request $request): bool
    {
        $route = $request?->attributes->get('_route');
        if (!\is_string($route) || !$this->isPrivate($route)) {
            return false;
        }

        return null !== $this->tokens->getToken()?->getUser();
    }
}
