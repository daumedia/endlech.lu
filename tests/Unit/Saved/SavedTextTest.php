<?php

declare(strict_types=1);

namespace App\Tests\Unit\Saved;

use App\Entity\SavedList;
use App\Saved\SavedText;
use PHPUnit\Framework\TestCase;

final class SavedTextTest extends TestCase
{
    public function testAk19GrossUndKleinUndRandleerraumErgebenDenselbenSchluessel(): void
    {
        self::assertSame(SavedText::nameKey('Kirchberg'), SavedText::nameKey(' kirchberg '));
    }

    public function testAk19AkzentBleibtUnterscheidbar(): void
    {
        self::assertNotSame(SavedText::nameKey('Café'), SavedText::nameKey('Cafe'));
    }

    public function testEc07ZerlegtesUndZusammengesetztesEGeltenGleich(): void
    {
        $zerlegt = "Cafe\u{0301}";   // e + Akut als zwei Codepunkte
        $zusammen = "Caf\u{00E9}";   // é als ein Codepunkt

        self::assertSame(SavedText::nameKey($zusammen), SavedText::nameKey($zerlegt));
        self::assertSame(4, SavedText::length(SavedText::name($zerlegt)));
    }

    /**
     * BF-211 (EC-07): Der Schlüssel ist nie länger als der Name. Geprüft wird nur die Länge des
     * Namens, gespeichert werden beide in Spalten derselben Breite — ein „İ“ (U+0130) wurde mit
     * `mb_strtolower()` zu zwei Codepunkten („i“ + U+0307), 31 davon sprengten `name_key`.
     * Über jeden Codepunkt, weil die nächste Ausnahme sonst wieder erst in der Datenbank auffällt.
     */
    public function testBf211SchluesselHatFuerJedenCodepunktDieLaengeDesNamens(): void
    {
        $laenger = [];
        for ($codepunkt = 0; $codepunkt <= 0x10FFFF; ++$codepunkt) {
            if ($codepunkt >= 0xD800 && $codepunkt <= 0xDFFF) {
                continue; // Ersatzzeichen sind kein gültiges UTF-8
            }
            $zeichen = mb_chr($codepunkt, 'UTF-8');
            if (SavedText::length(SavedText::nameKey($zeichen)) > SavedText::length(SavedText::name($zeichen))) {
                $laenger[] = \sprintf('U+%04X', $codepunkt);
            }
        }

        self::assertSame([], $laenger);
    }

    public function testBf211SechzigTuerkischeIErgebenEinenSchluesselVonSechzigZeichen(): void
    {
        $name = str_repeat("\u{0130}", SavedList::MAX_NAME_LENGTH);

        self::assertSame(SavedList::MAX_NAME_LENGTH, SavedText::length(SavedText::nameKey($name)));
        self::assertSame(SavedText::nameKey('Istanbul'), SavedText::nameKey("\u{0130}stanbul"));
    }

    /**
     * BF-220: Die einfache Kleinschreibung aus BF-211 kennt keinen Kontext — „Σ“ wird immer „σ“, das
     * Schluss-Sigma „ς“ bleibt „ς“. „ΟΔΟΣ“ und „οδος“ waren damit zwei Namen. Alle drei Formen müssen
     * denselben Schlüssel ergeben, ohne dass er länger wird als der Name.
     */
    public function testBf220SigmaInJederFormErgibtDenselbenSchluessel(): void
    {
        $schluessel = SavedText::nameKey('οδος');

        foreach (['ΟΔΟΣ', 'Οδος', 'οδοσ', 'ΟΔΟς'] as $schreibweise) {
            self::assertSame($schluessel, SavedText::nameKey($schreibweise), $schreibweise);
        }
        self::assertSame(4, SavedText::length($schluessel));
    }

    public function testAk35ZeilenumbruchZaehltAlsEinZeichen(): void
    {
        $text = SavedText::note("eins\r\nzwei\rdrei");

        self::assertSame("eins\nzwei\ndrei", $text);
        self::assertSame(14, SavedText::length($text));
    }

    public function testEc08NurLeerraumWirdZuLeer(): void
    {
        self::assertSame('', SavedText::note(" \r\n\t \n"));
    }
}
