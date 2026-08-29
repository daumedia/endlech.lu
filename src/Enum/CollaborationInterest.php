<?php

namespace App\Enum;

/**
 * Formen der Zusammenarbeit mit Organisationen und Vereinen. Mehrfachauswahl,
 * gespeichert als JSON-Array.
 *
 * Bewusst ohne jede Geldkomponente: Hier fließt in keine Richtung etwas.
 */
enum CollaborationInterest: string
{
    case ADVISORY_BOARD = 'advisory_board';
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
            self::ADVISORY_BOARD => 'Sitz im Beirat',
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
