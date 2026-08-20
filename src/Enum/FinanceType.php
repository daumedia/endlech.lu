<?php

namespace App\Enum;

/**
 * Richtung eines Finanzeintrags auf der Open-Startup-Seite.
 *
 * Bewusst nur zwei Fälle: Die Seite zeigt aggregierte Kosten und Einnahmen,
 * keine Buchhaltung. Rückstellungen, Abgrenzungen oder Umbuchungen gehören
 * nicht hierher – dafür ist ein Steuerbüro zuständig, nicht eine
 * Transparenzseite.
 */
enum FinanceType: string
{
    case INCOME = 'income';
    case EXPENSE = 'expense';

    public function transKey(): string
    {
        return 'finance_type.' . $this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::INCOME => 'Einnahme',
            self::EXPENSE => 'Ausgabe',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::INCOME => '📥',
            self::EXPENSE => '📤',
        };
    }

    /**
     * Vorzeichen für die Aggregation. Beträge werden immer positiv erfasst –
     * ein negativer Betrag in der Datenbank wäre nur eine zweite, stille Art,
     * die Richtung auszudrücken, und würde Summen doppelt invertieren.
     */
    public function sign(): int
    {
        return self::INCOME === $this ? 1 : -1;
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::INCOME => 'bg-green-100 text-green-700',
            self::EXPENSE => 'bg-amber-100 text-amber-800',
        };
    }
}
