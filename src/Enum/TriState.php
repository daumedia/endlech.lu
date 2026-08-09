<?php

namespace App\Enum;

/**
 * Dreiwertige Antwort auf eine Ja/Nein-Frage.
 *
 * UNKNOWN ist bewusst ein eigener Zustand: Bei Barrierefreiheit ist
 * "gibt es nicht" eine belastbare Information, "weiß ich nicht" nicht.
 */
enum TriState: string
{
    case YES = 'yes';
    case NO = 'no';
    case UNKNOWN = 'unknown';

    public function transKey(): string
    {
        return 'tristate.' . $this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::YES => 'Ja',
            self::NO => 'Nein',
            self::UNKNOWN => 'Weiß nicht',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::YES => '✅',
            self::NO => '❌',
            self::UNKNOWN => '❓',
        };
    }

    public function isYes(): bool
    {
        return $this === self::YES;
    }
}
