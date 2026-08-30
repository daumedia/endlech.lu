<?php

namespace App\Press;

use App\Open\OpenStatsService;

/**
 * Die drei Livezahlen des Faktenblatts.
 *
 * ⚠ **Kein zweiter Rechenweg.** Die Zahl auf der Presseseite ist dieselbe wie auf
 * /open und in /open.json — zwei Wege ergäben früher oder später zwei
 * verschiedene Zahlen für dieselbe Aussage. Eine falsche Zahl auf der
 * Transparenzseite ist ein Fehler; eine falsche Zahl in einem Zeitungsartikel ist
 * nicht mehr einzufangen.
 *
 * ⚠ **Ruft ausschließlich `platform()`, nicht `all()`.** Letzteres berechnet
 * zusätzlich Wirkung und Finanzen; keine dieser Zahlen erscheint hier, und bei
 * leerem Zwischenspeicher zahlte der Besucher der Presseseite trotzdem dafür.
 * Dieselbe Entscheidung wie in `App\Comparison\ComparisonFigures`.
 *
 * Der Zwischenspeicher (`cache.open_stats`, eine Stunde) ist zugleich der Schutz
 * davor, dass jeder Aufruf den gesamten Bestand lädt. Ein Rate Limit wäre hier
 * das falsche Werkzeug: Es sperrte Redaktionen aus, die regelmäßig hinter einer
 * gemeinsamen Adresse sitzen, ohne die Last zu senken.
 *
 * Die Werte sind roh. Die Schreibweise entscheidet das Template über
 * `format_number` — sonst stünde in der englischen Fassung „1.234" für
 * eintausendzweihundertvierunddreißig.
 */
final readonly class PressFacts
{
    public function __construct(private OpenStatsService $stats)
    {
    }

    /**
     * @return array{restaurants: int, verified: int, communesCovered: int, totalCommunes: int}
     */
    public function all(): array
    {
        $platform = $this->stats->platform();

        return [
            'restaurants' => (int) $platform['restaurants'],
            'verified' => (int) $platform['verified'],
            'communesCovered' => (int) $platform['communesCovered'],
            'totalCommunes' => (int) $platform['totalCommunes'],
        ];
    }
}
