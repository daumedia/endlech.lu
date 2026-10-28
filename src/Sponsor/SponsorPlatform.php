<?php

declare(strict_types=1);

namespace App\Sponsor;

/**
 * Plattformen, auf deren Profil ein Sponsor verlinkt werden darf (Feature 15,
 * Überarbeitung 2026-10-28, AK-52, AK-53).
 *
 * ⚠ **Die erlaubten Hosts sind die Prüfung, nicht Zierde.** Ein „Instagram“-Link,
 * der auf einen anderen Server zeigt, wäre ein getarnter Werbelink unter einem
 * vertrauten Namen. `SponsorRules` lässt nur den Host selbst oder eine
 * Unterdomain davon zu (`www.instagram.com`, `lu.linkedin.com`).
 *
 * Die Reihenfolge der Fälle ist die Reihenfolge auf der Sponsorenseite. Weitere
 * Plattformen sind ein Nachtrag an der Spec (Decision Log #33).
 */
enum SponsorPlatform: string
{
    case INSTAGRAM = 'instagram';
    case FACEBOOK = 'facebook';
    case LINKEDIN = 'linkedin';

    /** Eigenname der Plattform — nicht übersetzt, wie Firmennamen. */
    public function label(): string
    {
        return match ($this) {
            self::INSTAGRAM => 'Instagram',
            self::FACEBOOK => 'Facebook',
            self::LINKEDIN => 'LinkedIn',
        };
    }

    /** @return list<string> */
    public function hosts(): array
    {
        return match ($this) {
            self::INSTAGRAM => ['instagram.com'],
            self::FACEBOOK => ['facebook.com'],
            self::LINKEDIN => ['linkedin.com'],
        };
    }

    /** Gehört die Adresse zu dieser Plattform? Host selbst oder eine Unterdomain davon. */
    public function owns(string $url): bool
    {
        $host = parse_url($url, \PHP_URL_HOST);
        if (!\is_string($host) || '' === $host) {
            return false;
        }

        $host = strtolower($host);
        foreach ($this->hosts() as $erlaubt) {
            if ($host === $erlaubt || str_ends_with($host, '.'.$erlaubt)) {
                return true;
            }
        }

        return false;
    }
}
