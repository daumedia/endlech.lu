<?php

namespace App\Press;

/**
 * Die drei Längen, in denen der freigegebene Beschreibungstext bereitsteht.
 *
 * Eine Redaktion braucht je nach Platz einen Halbsatz, einen Absatz oder eine
 * halbe Spalte. Wer nur eine Länge anbietet, zwingt sie zum Kürzen — und beim
 * Kürzen entsteht der Satz, den niemand freigegeben hat.
 *
 * ⚠ **Die Wortgrenzen stehen hier und nicht im Prüflauf.** Sie sind die Zusage
 * aus AK-08; ein Prüflauf, der seine eigenen Zahlen mitbringt, prüft gegen sich
 * selbst und ist dann grün, weil beide Seiten dieselbe falsche Annahme teilen.
 *
 * ⚠ **Geprüft wird je Sprache, nicht nur auf Deutsch.** Französisch braucht
 * regelmäßig 15–20 % mehr Wörter für denselben Inhalt; ein deutscher Text, der
 * mit 28 Wörtern gerade in der Grenze liegt, sprengt sie in der Übersetzung.
 * Deshalb sind die Spannen bewusst breit genug für vier Sprachfassungen
 * desselben Gedankens.
 */
enum BoilerplateLength: string
{
    case SHORT = 'short';
    case MEDIUM = 'medium';
    case LONG = 'long';

    /** Textschlüssel des Beschreibungstextes, Domain `press`. */
    public function transKey(): string
    {
        return 'boilerplate.'.$this->value;
    }

    /** Textschlüssel der Beschriftung („kurz", „mittel", „lang"). */
    public function labelKey(): string
    {
        return 'boilerplate.'.$this->value.'_label';
    }

    public function minWords(): int
    {
        return match ($this) {
            self::SHORT => 20,
            self::MEDIUM => 50,
            self::LONG => 95,
        };
    }

    public function maxWords(): int
    {
        return match ($this) {
            self::SHORT => 30,
            self::MEDIUM => 70,
            self::LONG => 125,
        };
    }

    /** Die auf der Seite genannte Richtgröße — die Mitte der Spanne, gerundet. */
    public function approxWords(): int
    {
        return match ($this) {
            self::SHORT => 25,
            self::MEDIUM => 60,
            self::LONG => 110,
        };
    }
}
