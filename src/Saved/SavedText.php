<?php

declare(strict_types=1);

namespace App\Saved;

/**
 * Die eine Stelle, an der Listennamen und Notizen normalisiert werden (Feature 17).
 *
 * Formulare (Längenprüfung) und Dienste (Speichern, Dublette) rufen dieselben Funktionen —
 * sonst prüfte das Formular eine andere Länge, als der Dienst speichert.
 */
final class SavedText
{
    /**
     * Listenname: Randleerraum weg, Unicode NFC (ein „é“ aus zwei Codepunkten zählte sonst
     * doppelt und wäre eine andere Dublette als dasselbe „é“ aus einem).
     */
    public static function name(string $wert): string
    {
        return self::nfc(trim($wert));
    }

    /**
     * Vergleichsschlüssel für Dubletten: der normalisierte Name in Kleinbuchstaben (AK-19).
     * „ kirchberg “ und „Kirchberg“ ergeben denselben Schlüssel, „Café“ und „Cafe“ nicht.
     *
     * ⚠ **Einfache Kleinschreibung, nicht `mb_strtolower()`** (BF-211). Die volle Abbildung macht
     * aus „İ“ (U+0130) zwei Codepunkte („i“ + U+0307); geprüft wird aber nur die Länge des
     * Namens, und `name_key` ist so breit wie `name` — 31 „İ“ ergaben HTTP 500. Die einfache
     * Abbildung ersetzt jeden Codepunkt durch genau einen, der Schlüssel ist so lang wie der Name.
     * Folge: „İstanbul“ und „Istanbul“ gelten als derselbe Name.
     *
     * ⚠ **Danach die einfache Faltung** (BF-220). Die einfache Kleinschreibung kennt keinen
     * Kontext: „ΟΔΟΣ“ ergab `οδοσ`, „οδος“ blieb `οδος` — zwei Listen. Die Faltung bildet das
     * Schluss-Sigma auf „σ“ ab und ist ebenfalls 1 : 1 je Codepunkt. Allein reicht sie nicht:
     * „İ“ hat keine einfache Faltung und bliebe „İ“ — deshalb erst klein, dann falten.
     */
    public static function nameKey(string $wert): string
    {
        $klein = mb_convert_case(self::name($wert), \MB_CASE_LOWER_SIMPLE, 'UTF-8');

        return mb_convert_case($klein, \MB_CASE_FOLD_SIMPLE, 'UTF-8');
    }

    /**
     * Notiz: Zeilenumbrüche einheitlich als `\n` **vor** dem Zählen — ein Formular schickt
     * `\r\n`, der Nutzer zählt einen Umbruch (AK-35). Dann Randleerraum weg, NFC.
     */
    public static function note(string $wert): string
    {
        return self::nfc(trim(str_replace(["\r\n", "\r"], "\n", $wert)));
    }

    public static function length(string $wert): int
    {
        return mb_strlen($wert);
    }

    private static function nfc(string $wert): string
    {
        $normalisiert = \Normalizer::normalize($wert, \Normalizer::FORM_C);

        return false === $normalisiert ? $wert : $normalisiert;
    }
}
