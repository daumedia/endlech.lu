<?php

declare(strict_types=1);

namespace App\Tests\Unit\Saved;

use App\Saved\SavedText;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Feature 17 · Vergleichsschlüssel nach der Behebung von BF-220 — aus der dritten QA vom 2026-11-14.
 *
 * Die Reparatur faltet nach der einfachen Kleinschreibung zusätzlich einfach (`MB_CASE_FOLD_SIMPLE`). Am
 * Entwicklungsserver ausgezählt: Bei 194 Codepunkten ändert sich dadurch der Schlüssel; als neu gleich gelten
 * nur Formvarianten eines Buchstabens (ς/σ, ſ/s, µ/μ, ϐ/β, ϑ/θ, ϕ/φ, ϖ/π, ϰ/κ, ϱ/ρ, ϵ/ε, ẛ/ṡ, ﬅ/ﬆ, sieben
 * altkyrillische Varianten, die kombinierende Ypogegrammeni), dazu Cherokee (dort waren Groß und Klein schon
 * vorher gleich, nur die Schreibweise des Schlüssels wechselt). Das ist Unicodes Definition von
 * „ohne Unterschied der Groß- und Kleinschreibung“ (Decision Log 11) — und genau die Paare, die als Häkchen
 * nicht unterscheidbar wären.
 *
 * Diese Fälle halten fest, was als gleich gilt und was ausdrücklich verschieden bleibt (Akzent, türkisches
 * punktloses ı, Ligatur gegen Buchstabenfolge, ß gegen ss), und dass die Eigenschaft aus BF-211 auch für
 * Zeichen hält, die NFC in mehrere Codepunkte zerlegt.
 */
final class Qa17Nachpruefung3TextTest extends TestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function neuGleich(): iterable
    {
        yield 'langes s' => ['Paſta', 'Pasta'];
        yield 'Mikrozeichen gegen griechisches My' => ['5 µg', '5 μg'];
        yield 'griechisches Beta in Symbolform' => ['ϐ-Bar', 'β-bar'];
        yield 'Schluss-Sigma in Großschrift' => ['ΟΔΟΣ', 'Οδος'];
        yield 'Cherokee Groß und Klein' => ["\u{13A0}\u{13A1}", "\u{AB70}\u{AB71}"];
    }

    #[DataProvider('neuGleich')]
    public function testBf220FaltungMachtFormvariantenGleich(string $a, string $b): void
    {
        self::assertSame(SavedText::nameKey($a), SavedText::nameKey($b));
    }

    /** @return iterable<string, array{string, string}> */
    public static function bleibtVerschieden(): iterable
    {
        yield 'Akzent (AK-19, Entwurf)' => ['Café', 'Cafe'];
        yield 'türkisches punktloses ı' => ['ıstanbul', 'istanbul'];
        yield 'Ligatur gegen Buchstabenfolge' => ['ﬆ', 'st'];
        yield 'ß gegen ss (nur volle Faltung setzte gleich)' => ['Straße', 'Strasse'];
        yield 'Akzent im Griechischen' => ['Ὀδός', 'Οδος'];
    }

    #[DataProvider('bleibtVerschieden')]
    public function testBf220FaltungBleibtBeiDerEinfachenAbbildung(string $a, string $b): void
    {
        self::assertNotSame(SavedText::nameKey($a), SavedText::nameKey($b));
    }

    /**
     * EC-07 / BF-211: Zeichen, die NFC in zwei oder drei Codepunkte zerlegt (U+0344, U+0F73, U+FB2C,
     * U+1D160). Gezählt und gespeichert wird der normalisierte Name; der Schlüssel ist genauso lang.
     */
    public function testEc07SchluesselSoLangWieDerNormalisierteNameAuchBeiZerlegung(): void
    {
        foreach (["\u{0344}", "\u{0F73}", "\u{FB2C}", "\u{1D160}", "\u{2ADC}"] as $zeichen) {
            $name = SavedText::name(str_repeat($zeichen, 20));
            self::assertGreaterThan(20, mb_strlen($name), 'NFC zerlegt U+'.strtoupper(dechex(mb_ord($zeichen))));
            self::assertSame(mb_strlen($name), mb_strlen(SavedText::nameKey($name)), 'U+'.strtoupper(dechex(mb_ord($zeichen))));
        }
    }
}
