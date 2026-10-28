<?php

namespace App\Enum;

/**
 * Laufzeit des Sponsoring-Pakets, wie ein Unternehmen sie im Formular angibt
 * (Feature 15, AK-37).
 *
 * Seit dem 2026-10-28 gibt es **ein** Paket — monatlich oder jährlich, Preis auf
 * Anfrage. Es ersetzt die vier Formate aus `SponsorshipInterest`.
 *
 * ⚠ **Gespeichert im bestehenden JSON-Feld `sponsorship_interests`**, nicht in
 * einer eigenen Spalte (Feature 15, Entscheidung #7). Neue Einträge tragen dort
 * höchstens einen dieser Werte; alte Zeilen behalten ihre Formate und werden als
 * „frühere Angabe“ gezeigt (AK-39).
 *
 * ⚠ **Die Laufzeit erscheint nirgends öffentlich** — weder beim Sponsor noch in
 * einer Zahl. Sie verriete, was ein Sponsor zahlt (AK-14).
 */
enum SponsorshipTerm: string
{
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';

    public function transKey(): string
    {
        return 'organisation.sponsorship_term.' . $this->value;
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
