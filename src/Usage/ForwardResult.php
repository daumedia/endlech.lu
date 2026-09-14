<?php

declare(strict_types=1);

namespace App\Usage;

/**
 * Ergebnis einer Weiterleitung an Umami (Feature 11).
 *
 * Der Browser erfährt nie, **warum** nichts weitergeleitet wurde (Zähl-Eingang nicht eingerichtet,
 * Unterbrecher, Fehlschlag): Er bekommt in allen Fällen `202 {}`. Der Tracker verwirft die Antwort
 * ohnehin — und eine unterscheidbare Antwort verriete von außen, ob der zweite VPS gerade erreichbar ist.
 */
final readonly class ForwardResult
{
    private function __construct(
        public int $status,
        public string $body,
    ) {
    }

    /** Umamis Antwort, unverändert (enthält nur das Sitzungs-Token für den nächsten Aufruf). */
    public static function forwarded(string $body): self
    {
        return new self(200, $body);
    }

    public static function notForwarded(): self
    {
        return new self(202, '{}');
    }
}
