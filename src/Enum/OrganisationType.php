<?php

namespace App\Enum;

/**
 * Art der Organisation – bestimmt Formularfelder, Validierungsgruppe,
 * Bestätigungsmail und Erfolgsmeldung.
 *
 * Die drei Typen sind kommerziell grundverschieden: COMMUNE ist ein bezahlter
 * Auftrag, COMPANY ein Sponsoring, ASSOCIATION ausdrücklich **kein**
 * Vertriebskanal – dort fließt in keine Richtung Geld. Der Beirat prüft die
 * Barrierefreiheits-Kriterien und ihre Gewichtung; genau diese Unabhängigkeit
 * wäre hinfällig, wenn sie erkauft werden könnte.
 */
enum OrganisationType: string
{
    case COMMUNE = 'commune';
    case COMPANY = 'company';
    case ASSOCIATION = 'association';

    public function transKey(): string
    {
        return 'organisation.type.' . $this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::COMMUNE => 'Gemeinde',
            self::COMPANY => 'Unternehmen',
            self::ASSOCIATION => 'Organisation',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::COMMUNE => '🏛️',
            self::COMPANY => '🏢',
            self::ASSOCIATION => '🤲',
        };
    }

    /**
     * URL-Segment der eigenen Unterseite (/organisationen/{slug}).
     *
     * Für ASSOCIATION bewusst „vereine" statt „organisationen" – sonst hieße
     * die Seite /organisationen/organisationen.
     */
    public function slug(): string
    {
        return match ($this) {
            self::COMMUNE => 'gemeinden',
            self::COMPANY => 'unternehmen',
            self::ASSOCIATION => 'vereine',
        };
    }

    public static function fromSlug(string $slug): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->slug() === $slug) {
                return $case;
            }
        }

        return null;
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::COMMUNE => 'bg-cyan-100 text-cyan-800',
            self::COMPANY => 'bg-purple-100 text-purple-700',
            self::ASSOCIATION => 'bg-teal-100 text-teal-800',
        };
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
