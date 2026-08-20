<?php

namespace App\Enum;

/**
 * Sponsoring-Formate für Unternehmen. Mehrfachauswahl, gespeichert als
 * JSON-Array auf OrganisationWaitlistEntry.
 */
enum SponsorshipInterest: string
{
    case INCLUSION_BOXES = 'inclusion_boxes';
    case EMPLOYEE_ENGAGEMENT = 'employee_engagement';
    case COMMUNE_SPONSORSHIP = 'commune_sponsorship';
    case TRANSLATION = 'translation';
    case WORKSHOPS = 'workshops';
    case OTHER = 'other';

    public function transKey(): string
    {
        return 'organisation.sponsorship.' . $this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::INCLUSION_BOXES => 'Inclusion-Boxen finanzieren',
            self::EMPLOYEE_ENGAGEMENT => 'Mitarbeitende als Testpersonen',
            self::COMMUNE_SPONSORSHIP => 'Eine Gemeinde mitfinanzieren',
            self::TRANSLATION => 'Übersetzungen finanzieren',
            self::WORKSHOPS => 'Workshops finanzieren',
            self::OTHER => 'Etwas anderes',
        };
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
