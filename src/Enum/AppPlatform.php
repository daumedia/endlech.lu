<?php

namespace App\Enum;

/**
 * Die Plattform, für die sich jemand auf der App-Warteliste vormerkt.
 *
 * Genau zwei Fälle, und sie sind heute **nicht gleichwertig**: Für iOS läuft
 * eine offene TestFlight-Beta, für Android ist nichts gebaut. Genau diesen
 * Unterschied trägt `hasBeta()`.
 */
enum AppPlatform: string
{
    case IOS = 'ios';
    case ANDROID = 'android';

    public function transKey(): string
    {
        return 'app_platform.' . $this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::IOS => 'iOS',
            self::ANDROID => 'Android',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::IOS => '🍎',
            self::ANDROID => '🤖',
        };
    }

    /** Farbe für das Abzeichen; Form und Größe bleiben im Template. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::IOS => 'bg-cyan-100 text-cyan-800',
            self::ANDROID => 'bg-purple-100 text-purple-700',
        };
    }

    /**
     * Gibt es für diese Plattform eine Beta, in die jemand hineinkann?
     *
     * ⚠ **Diese Frage gehört hierher und nicht ins Template.** Sie wird an vier
     * Stellen gestellt: am Hinweis neben der Auswahl, in der Mail nach der
     * Bestätigung, in deren Betreffzeile und in der Verwaltungsliste. Vier
     * verstreute `platform == 'ios'`-Abfragen laufen beim ersten Android-Build
     * auseinander – und dann steht an einer der vier Stellen weiterhin „noch
     * nichts gebaut", während die App längst da ist.
     */
    public function hasBeta(): bool
    {
        return self::IOS === $this;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
