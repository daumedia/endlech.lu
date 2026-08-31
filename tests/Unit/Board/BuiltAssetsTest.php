<?php

declare(strict_types=1);

namespace App\Tests\Unit\Board;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Regressionsschutz für BF-105 (QA 06², 2026-08-30).
 *
 * **Der Fehler:** Feature 06 legte neun Templates mit neuen Utility-Klassen an,
 * fasste `assets/` aber nicht an — und schloss daraus, dass kein Bau nötig sei.
 * `line-clamp-3` fehlte daraufhin im ausgelieferten CSS. Die Testsuite blieb
 * grün, weil sie kein CSS auswertet; aufgefallen wäre es erst an
 * `verify-assets` im Deploy oder im Browser.
 *
 * ⚠ **Tailwind v4 scannt in diesem Projekt `templates/`**
 * (`assets/styles/app.css`: `@source "../../templates"`). Ein Twig-Template mit
 * einer neuen Klasse **ist** eine Asset-Änderung. Die Projektregel in
 * `CLAUDE.md` nennt bislang nur `assets/`.
 *
 * Dieser Prüflauf schließt die Lücke für die Klassen, die dieses Feature
 * eingeführt hat. Er ersetzt `verify-assets` nicht — er meldet den Fall nur
 * früher, nämlich im normalen Lauf statt erst im Deploy.
 */
final class BuiltAssetsTest extends TestCase
{
    private static function gebautesCss(): string
    {
        $wurzel = \dirname(__DIR__, 3);
        $dateien = glob($wurzel . '/public/build/*.css') ?: [];

        self::assertNotEmpty($dateien, 'Kein gebautes CSS unter public/build — `npm run build` fehlt.');

        return implode("\n", array_map(static fn (string $f): string => (string) file_get_contents($f), $dateien));
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function klassen(): iterable
    {
        // Regeln, die ausschließlich oder überwiegend Feature 06 einführt.
        yield 'line-clamp-3 (Textauszug auf der Karte)' => ['.line-clamp-3'];
        yield 'min-h-[44px] (Zielgröße des Titels, BF-104)' => ['.min-h-\[44px\]'];
        yield 'min-h-[48px] (Zielgröße der Bedienelemente)' => ['.min-h-\[48px\]'];
        yield 'whitespace-pre-line (Zeilenumbrüche ohne nl2br)' => ['.whitespace-pre-line'];
    }

    #[DataProvider('klassen')]
    public function testKlasseIstImGebautenCssVorhanden(string $selektor): void
    {
        self::assertStringContainsString(
            $selektor,
            self::gebautesCss(),
            sprintf(
                "Die Regel `%s` fehlt im gebauten CSS.\n"
                . "Ursache ist fast immer: ein Template wurde geändert, aber `npm run build` lief nicht.\n"
                . "⚠ Tailwind scannt `templates/` — ein Twig-Template IST eine Asset-Änderung (BF-105).",
                $selektor,
            ),
        );
    }
}
