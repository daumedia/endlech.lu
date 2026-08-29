<?php

namespace App\Comparison;

/**
 * Bewertung einer Merkmalszeile: erfüllt, nicht erfüllt, eingeschränkt erfüllt.
 *
 * ⚠ **Nicht `App\Enum\TriState` wiederverwenden**, obwohl es ähnlich aussieht.
 * Dessen dritter Fall heißt `UNKNOWN` und bedeutet auf dieser Plattform „nicht
 * erhoben" – die Unterscheidung zwischen „nein" und „weiß nicht" ist der Grund,
 * warum es TriState überhaupt gibt (Vorschlags-Wizard, B11). `PARTIAL` bedeutet
 * dagegen „erhoben und teilweise erfüllt". Beides in einen Topf zu werfen hieße,
 * genau die Unterscheidung aufzugeben, für die anderswo eine Migration
 * geschrieben wurde.
 *
 * ⚠ Das Symbol trägt die Aussage nie allein (AK-23, Feature 02/AK-17): Im
 * Template steht es `aria-hidden`, daneben `sr-only` der übersetzte Text. In
 * Graustufen bleibt die Aussage über die Form erkennbar – Haken, Strich, Tilde –
 * nicht über die Farbe.
 */
enum Verdict: string
{
    case YES = 'yes';
    case NO = 'no';
    case PARTIAL = 'partial';

    public function transKey(): string
    {
        return 'verdict.' . $this->value;
    }

    public function symbol(): string
    {
        return match ($this) {
            self::YES => '✓',
            self::NO => '–',
            self::PARTIAL => '~',
        };
    }

    /**
     * Textfarbe des Symbols.
     *
     * Bernstein für PARTIAL ist hier zulässig, weil das Symbol dekorativ ist und
     * die Aussage im sr-only-Text steht; die Kontrastregel greift trotzdem –
     * `amber-700` auf Weiß liegt über 4,5:1, `amber-500` läge darunter.
     */
    public function textClass(): string
    {
        return match ($this) {
            self::YES => 'text-green-700',
            self::NO => 'text-gray-500',
            self::PARTIAL => 'text-amber-700',
        };
    }
}
