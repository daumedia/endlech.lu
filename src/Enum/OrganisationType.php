<?php

namespace App\Enum;

/**
 * Art der Organisation – bestimmt Formularfelder, Validierungsgruppe,
 * Bestätigungsmail und Erfolgsmeldung.
 *
 * Die beiden Typen sind kommerziell grundverschieden: COMMUNE ist ein bezahlter
 * Auftrag, COMPANY steht für Unternehmen UND Vereine – Sponsoring oder eine
 * Zusammenarbeit ohne Geld.
 *
 * ⚠ Bis 2026-10-14 gab es einen dritten Typ ASSOCIATION („Verein") mit einem
 * Beirat, der über Kriterien und Gewichtung mitentscheiden sollte. Der Beirat
 * ist gestrichen (zu viele Beteiligte, verlangsamt Entscheidungen), Vereine
 * laufen seither unter COMPANY. Migration `Version20261014120000` hat den
 * Bestand umgeschrieben; `/organisationen/vereine` leitet dauerhaft weiter.
 */
enum OrganisationType: string
{
    case COMMUNE = 'commune';
    case COMPANY = 'company';

    /**
     * Slugs entfallener Zielgruppenseiten → Typ, dessen Seite sie ersetzt.
     *
     * Die Route nimmt sie weiter an und leitet dauerhaft (301) weiter, damit
     * Lesezeichen, verlinkte Adressen und der Suchindex nicht auf einer 404
     * enden.
     */
    public const LEGACY_SLUGS = ['vereine' => 'unternehmen'];

    public function transKey(): string
    {
        return 'organisation.type.' . $this->value;
    }

    /**
     * Deutscher Anzeigename — wird dort verwendet, wo kein Übersetzungskatalog
     * greift (Screenreader-Ansage, interne Meldungen).
     *
     * ⚠ BF-39: Ein Label muss sagen, wer gemeint ist — „Organisation" ist der
     * Oberbegriff der ganzen Seite und sagt einem Screenreader-Nutzer beim Wechsel
     * des Formulartyps nichts. Deshalb „Unternehmen oder Verein", nicht
     * „Organisation".
     */
    public function label(): string
    {
        return match ($this) {
            self::COMMUNE => 'Gemeinde',
            self::COMPANY => 'Unternehmen oder Verein',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::COMMUNE => '🏛️',
            self::COMPANY => '🏢',
        };
    }

    /**
     * URL-Segment der eigenen Unterseite (/organisationen/{slug}).
     *
     * COMPANY behält `unternehmen`, obwohl die Seite seit 2026-10-14 auch Vereine
     * anspricht: Die Adresse steht in Links, Sitemap und Suchindex. Die frühere
     * Vereinsseite leitet dorthin weiter (siehe `LEGACY_SLUGS`).
     */
    public function slug(): string
    {
        return match ($this) {
            self::COMMUNE => 'gemeinden',
            self::COMPANY => 'unternehmen',
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
        };
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
