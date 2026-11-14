<?php

declare(strict_types=1);

namespace App\Tests\Unit\Saved;

use App\Saved\SavedText;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Feature 17 · Vergleichsschlüssel der Listennamen nach der Behebung von BF-211 — aus der zweiten QA vom 2026-11-14.
 *
 * Läuft immer: die Fälle, die vor und nach BF-211 gleich gelten müssen (AK-19, EC-07).
 * BF-220 läuft seit der Behebung am 2026-11-14 ebenfalls immer (Rückfallschutz).
 */
final class Qa17NachpruefungTextTest extends TestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function gleicheNamen(): iterable
    {
        yield 'AK-19 Leerzeichen und Kleinschreibung' => ['Kirchberg', ' kirchberg '];
        yield 'Umlaute' => ['ÄÖÜ-Treff', 'äöü-treff'];
        yield 'BF-211 türkisches I wie lateinisches I' => ['İstanbul', 'Istanbul'];
    }

    #[DataProvider('gleicheNamen')]
    public function testAk19GleicheNamenTrotzGrossUndKleinschreibung(string $a, string $b): void
    {
        self::assertSame(SavedText::nameKey($a), SavedText::nameKey($b));
    }

    /** EC-07 / BF-211: Der Schlüssel ist für jeden geprüften Namen höchstens so lang wie der Name. */
    public function testEc07SchluesselNieLaengerAlsDerName(): void
    {
        foreach (['İ', 'ΟΔΟΣ', 'ß', 'ẞ', 'ǅ', '😀', '日本'] as $zeichen) {
            $name = str_repeat($zeichen, intdiv(60, mb_strlen($zeichen)));
            self::assertLessThanOrEqual(mb_strlen(SavedText::name($name)), mb_strlen(SavedText::nameKey($name)), $zeichen);
        }
    }

    /**
     * BF-220: Seit BF-211 bildet `nameKey()` den Schlüssel mit der einfachen Kleinschreibung. Sie kennt das
     * griechische Schluss-Sigma nicht: „ΟΔΟΣ“ ergibt `οδοσ`, „οδος“ ergibt `οδος` — zwei Listen, die sich nur in
     * Groß- und Kleinschreibung unterscheiden, lassen sich anlegen (Entwicklungsserver: beide 303). Vorher
     * (`mb_strtolower`) galten sie als Dublette. Decision Log 11: „ohne Unterschied von Groß- und Kleinschreibung“.
     */
    public function testBf220GriechischesSchlussSigmaGiltAlsDieselbeListe(): void
    {
        // Scharf seit der Behebung am 2026-11-14 (zweiter Fehlerauftrag 17) — Rückfallschutz.
        self::assertSame(SavedText::nameKey('οδος'), SavedText::nameKey('ΟΔΟΣ'));
    }
}
