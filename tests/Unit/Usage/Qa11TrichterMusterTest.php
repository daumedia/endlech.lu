<?php

namespace App\Tests\Unit\Usage;

use PHPUnit\Framework\TestCase;

/**
 * QA Feature 11 · Befund BF-150 — Reproduktion, seit der Behebung am 2026-09-13 scharf.
 *
 * Umami 3.3.1 wertet `*` in einem Trichterschritt nur am Anfang oder Ende als Platzhalter
 * (`src/queries/sql/reports/getFunnel.ts`: `if (cv.value.startsWith('*') || cv.value.endsWith('*'))`
 * und `replace(/^\*|\*$/g, '%')`). Ein `*` in der Mitte bleibt ein wörtliches Sternchen. Gemessen
 * gegen eine Umami-3.3.1-Instanz mit echten Zählaufrufen (`qa/11/trichter.ausgabe.txt`):
 * `/*\/restaurants` → 0 Besucher, `*\/restaurants` → 1 Besucher auf denselben Daten.
 *
 * `growth/config.json` und `features/11-nutzungsmessung/design.md` verwenden die mittige Form — der
 * Growth-Loop zählte jeden Trichter mit 0.
 *
 * Die Gegenrichtung (trifft jeder Schritt die Seiten aller Sprachen?) prüft `GrowthTrichterTest`.
 */
final class Qa11TrichterMusterTest extends TestCase
{
    public function testBf150KeinPlatzhalterMittenImPfad(): void
    {
        $config = json_decode((string) file_get_contents(__DIR__.'/../../../growth/config.json'), true, 16, \JSON_THROW_ON_ERROR);
        $ketten = ['conversion_events' => $config['umami']['conversion_events'], ...$config['weitere_trichter']];

        foreach ($ketten as $name => $schritte) {
            foreach ($schritte as $schritt) {
                if (!str_starts_with($schritt, '/') && !str_starts_with($schritt, '*')) {
                    continue; // Ereignisname
                }
                $innen = substr($schritt, 1, -1);
                self::assertStringNotContainsString('*', $innen, sprintf(
                    '%s: „%s" — Umami 3.3.1 ersetzt * nur am Anfang oder Ende; in der Mitte bleibt es ein wörtliches Sternchen.',
                    $name,
                    $schritt,
                ));
            }
        }
    }
}
