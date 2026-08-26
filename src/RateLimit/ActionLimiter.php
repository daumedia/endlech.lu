<?php

declare(strict_types=1);

namespace App\RateLimit;

use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * Deckelt eine Handlung, nicht einen Aufruf.
 *
 * Das Projekt hatte zweimal dasselbe Problem an derselben Stelle:
 *
 * ⚠ **BF-11 — der Verbrauch stand vor der Gültigkeitsprüfung.** Fünf Tippfehler
 * sperrten eine Stunde lang aus, ohne dass ein Konto oder eine Mail entstanden
 * wäre. Der Deckel soll den Angreifer treffen, nicht den, der sich vertippt.
 *
 * ⚠ **`consume(0)` prüft NICHT.** Der naheliegende Umbau — abfragen mit `consume(0)`,
 * verbrauchen mit `consume(1)` — sieht richtig aus und ist es nicht:
 * `SlidingWindowLimiter` vergleicht `verfügbar >= angefordert`, und `0 >= 0` gilt
 * auch bei erschöpftem Kontingent. Nachgestellt: acht gültige Anmeldungen liefen
 * durch, wo der sechste hätte scheitern müssen. Maßgeblich ist
 * `getRemainingTokens()`.
 *
 * Verwendung:
 *
 *     $limiter = ActionLimiter::for($this->registrationLimiter, $request->getClientIp());
 *     if (!$limiter->isAllowed()) {
 *         return $this->render(..., new Response(null, Response::HTTP_TOO_MANY_REQUESTS));
 *     }
 *     // … Formular prüfen, Honeypot, alles was fehlschlagen darf …
 *     $limiter->consume();   // erst hier: die Handlung findet statt
 */
final readonly class ActionLimiter
{
    private function __construct(private LimiterInterface $limiter)
    {
    }

    /**
     * @param string|null $key Konto-Kennung oder IP; `null` fällt auf 'anonymous' zurück
     */
    public static function for(RateLimiterFactoryInterface $factory, ?string $key): self
    {
        return new self($factory->create($key ?? 'anonymous'));
    }

    /**
     * Fragt ab, ohne zu verbrauchen.
     */
    public function isAllowed(): bool
    {
        return $this->limiter->consume(0)->getRemainingTokens() >= 1;
    }

    /**
     * Sekunden bis zum nächsten freien Versuch — für `Retry-After`.
     */
    public function retryAfter(): int
    {
        $zeitpunkt = $this->limiter->consume(0)->getRetryAfter()->getTimestamp();

        return max(0, $zeitpunkt - time());
    }

    /**
     * Bucht die Handlung. Erst aufrufen, wenn sie tatsächlich stattfindet.
     */
    public function consume(): void
    {
        $this->limiter->consume(1);
    }
}
