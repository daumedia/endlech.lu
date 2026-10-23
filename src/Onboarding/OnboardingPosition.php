<?php

declare(strict_types=1);

namespace App\Onboarding;

/**
 * Wo ein Bildschirm im Ablauf eines bestimmten Kontos steht (Feature 12).
 *
 * `total` ist die **tatsächliche** Zahl der Bildschirme dieses Kontos — vier, oder drei, wenn
 * der Newsletter-Schritt entfällt (AK-13, AK-14). „Schritt 4 von 4" bei einem Konto, das nur
 * drei sieht, wäre eine Anzeige, auf die man sich nicht verlassen kann.
 */
final readonly class OnboardingPosition
{
    public function __construct(
        public string $step,
        public int $number,
        public int $total,
        public ?string $previous,
        public ?string $next,
        public bool $isLast,
    ) {
    }
}
