<?php

namespace App\Comparison;

use App\Open\OpenStatsService;

/**
 * Die eigenen Zahlen für die Merkmalstabelle.
 *
 * ⚠ Ruft **ausschließlich** `platform()`, nicht `all()`. Letzteres berechnet
 * zusätzlich Wirkung und Finanzen; auf einer Vergleichsseite erscheint keine
 * dieser Zahlen, und bei leerem Zwischenspeicher zahlte der Besucher trotzdem
 * dafür.
 *
 * ⚠ Es entsteht **kein zweiter Rechenweg**. Die Zahl auf einer Vergleichsseite
 * ist dieselbe wie auf /open — zwei Wege ergäben früher oder später zwei
 * verschiedene Zahlen für dieselbe Aussage, und die Transparenzseite ist die
 * veröffentlichte Fassung. Der Zwischenspeicher (`cache.open_stats`, eine
 * Stunde) ist zugleich der Schutz davor, dass jeder Aufruf den gesamten Bestand
 * lädt; ein Rate Limit wäre hier das falsche Werkzeug, es sperrte Besucher aus,
 * ohne die Last zu senken.
 *
 * Die Werte sind roh. Die Schreibweise entscheidet das Template über
 * `format_number` — sonst stünde in der englischen Fassung „1.234" für
 * eintausendzweihundertvierunddreißig.
 */
final readonly class ComparisonFigures
{
    public function __construct(private OpenStatsService $stats)
    {
    }

    /**
     * @return array<string, int|float>
     */
    public function all(): array
    {
        $platform = $this->stats->platform();

        return [
            'restaurants' => $platform['restaurants'],
            'verified' => $platform['verified'],
            'communesCovered' => $platform['communesCovered'],
            'totalCommunes' => $platform['totalCommunes'],
            'cantonsCovered' => $platform['cantonsCovered'],
            'totalCantons' => $platform['totalCantons'],
        ];
    }
}
