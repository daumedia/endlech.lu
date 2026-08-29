<?php

namespace App\Enum;

/**
 * Zeithorizont einer Gemeinde-Anfrage. Gemeindebudgets werden jährlich
 * beschlossen – NEXT_BUDGET_YEAR ist deshalb ein häufiger und völlig normaler
 * Fall, kein Ausweichen.
 */
enum OrganisationTimeframe: string
{
    case ASAP = 'asap';
    case THIS_YEAR = 'this_year';
    case NEXT_BUDGET_YEAR = 'next_budget_year';
    case UNCLEAR = 'unclear';

    public function transKey(): string
    {
        return 'organisation.timeframe.' . $this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::ASAP => 'So bald wie möglich',
            self::THIS_YEAR => 'Noch dieses Jahr',
            self::NEXT_BUDGET_YEAR => 'Im nächsten Haushaltsjahr',
            self::UNCLEAR => 'Noch offen',
        };
    }
}
