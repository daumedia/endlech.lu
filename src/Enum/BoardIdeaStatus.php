<?php

namespace App\Enum;

/**
 * Bearbeitungsstand einer öffentlichen Idee auf dem Community-Board.
 *
 * ⚠ **„Wartet auf Freigabe" ist KEIN Fall dieses Enums.** Dieser Zustand steckt
 * in `BoardIdea::$publishedAt` (`null` = wartet). Die Trennung ist Absicht: Die
 * fünf Fälle hier beschreiben eine *öffentliche* Idee, „wartet" ist eine andere
 * Achse. Vermischt könnte ein Statuswechsel eine bereits veröffentlichte Idee
 * versehentlich vom Netz nehmen — und AK-71 („kein Beitrag war je ohne Freigabe
 * öffentlich") wäre nicht mehr an einer einzigen Bedingung prüfbar.
 *
 * DECLINED ist ausdrücklich ein *öffentlicher* Zustand: Eine abgelehnte Idee
 * bleibt mit ihrer Begründung stehen (AK-28). Produktprinzip 2 — „Lücken werden
 * gezeigt, nicht versteckt" — gilt auch für die eigenen Absagen.
 */
enum BoardIdeaStatus: string
{
    case NEW = 'new';
    case REVIEWING = 'reviewing';
    case PLANNED = 'planned';
    case DONE = 'done';
    case DECLINED = 'declined';

    /**
     * ⚠ **Flacher Schlüssel (`board.status_new`), nicht verschachtelt.** Der
     * Katalogprüflauf scannt Template-Literale und `src/Form/` — einen hier in
     * PHP zusammengesetzten Schlüssel sieht er nicht. Eine Abweichung fällt
     * deshalb erst im Browser auf, als roher Schlüsselname auf der Seite.
     */
    public function transKey(): string
    {
        return 'board.status_' . $this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Neu',
            self::REVIEWING => 'In Prüfung',
            self::PLANNED => 'Geplant',
            self::DONE => 'Umgesetzt',
            self::DECLINED => 'Abgelehnt',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::NEW => '💡',
            self::REVIEWING => '🔎',
            self::PLANNED => '📌',
            self::DONE => '✅',
            self::DECLINED => '🚫',
        };
    }

    /**
     * Tailwind-Klassen für das Status-Abzeichen.
     *
     * ⚠ Liefert **ausschließlich Farbe** (Design-System). Form und Größe bleiben
     * im Template, und das Wort steht immer daneben — Farbe trägt nie allein
     * (AK-45).
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::NEW => 'bg-cyan-100 text-cyan-800',
            self::REVIEWING => 'bg-amber-100 text-amber-800',
            self::PLANNED => 'bg-purple-100 text-purple-700',
            self::DONE => 'bg-green-100 text-green-700',
            self::DECLINED => 'bg-gray-200 text-gray-700',
        };
    }
}
