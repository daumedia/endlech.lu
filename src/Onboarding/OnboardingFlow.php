<?php

declare(strict_types=1);

namespace App\Onboarding;

use App\Entity\User;

/**
 * Die Bildschirme der Einführung und ihre Reihenfolge (Feature 12, AK-12).
 *
 * Steht als eigene Klasse und nicht im Controller, weil Kopf („Schritt N von M"), Fußleiste
 * („Zurück", „Weiter") und die Weiterleitung beim Newsletter dieselbe Antwort brauchen —
 * verteilt liefen sie beim ersten zusätzlichen Schritt auseinander.
 *
 * Der Newsletter steht zuletzt, damit er nicht wie ein Eintrittspreis wirkt (Spec, Decision
 * Log 5), und entfällt ganz, wenn das Konto schon eingewilligt hat (AK-13).
 */
final readonly class OnboardingFlow
{
    public const string WELCOME = 'willkommen';
    public const string ENTRIES = 'eintraege';
    public const string AVATAR = 'profilbild';
    public const string NEWSLETTER = 'newsletter';

    /** Alle Schritte, die es überhaupt gibt — Grundlage der Routenbedingung. */
    public const array ALL = [self::WELCOME, self::ENTRIES, self::AVATAR, self::NEWSLETTER];

    /**
     * @return list<string>
     */
    public function stepsFor(User $user): array
    {
        return array_values(array_filter(
            self::ALL,
            static fn (string $step): bool => self::NEWSLETTER !== $step || !$user->hasMarketingConsent(),
        ));
    }

    /**
     * `null`, wenn der Schritt im Ablauf dieses Kontos nicht vorkommt.
     */
    public function locate(User $user, string $step): ?OnboardingPosition
    {
        $steps = $this->stepsFor($user);
        $index = array_search($step, $steps, true);

        if (false === $index) {
            return null;
        }

        $total = \count($steps);

        return new OnboardingPosition(
            step: $step,
            number: $index + 1,
            total: $total,
            previous: $steps[$index - 1] ?? null,
            next: $steps[$index + 1] ?? null,
            isLast: $index === $total - 1,
        );
    }
}
