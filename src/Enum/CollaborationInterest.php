<?php

namespace App\Enum;

/**
 * Formen der Zusammenarbeit ohne Geld – für Unternehmen und Vereine, neben den
 * Sponsoring-Formaten (`SponsorshipInterest`). Mehrfachauswahl, gespeichert als
 * JSON-Array.
 *
 * Bewusst ohne jede Geldkomponente: Hier fließt in keine Richtung etwas.
 *
 * ⚠ `advisory_board` („Sitz im Beirat") ist am 2026-10-14 entfallen, der Beirat
 * wurde gestrichen. Migration `Version20261014120000` hat den Wert aus dem
 * Bestand entfernt.
 */
enum CollaborationInterest: string
{
    case DATA_ACCESS = 'data_access';
    case JOINT_COMMUNICATION = 'joint_communication';
    case REFERRALS = 'referrals';
    case OTHER = 'other';

    public function transKey(): string
    {
        return 'organisation.collaboration.' . $this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::DATA_ACCESS => 'Zugang zu aggregierten Daten',
            self::JOINT_COMMUNICATION => 'Gemeinsame Kommunikation',
            self::REFERRALS => 'Weiterempfehlung an unsere Zielgruppe',
            self::OTHER => 'Etwas anderes',
        };
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
