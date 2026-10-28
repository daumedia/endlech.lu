<?php

declare(strict_types=1);

namespace App\Tests\Unit\Sponsor;

use App\Sponsor\Sponsor;
use App\Sponsor\SponsorDirectory;
use App\Sponsor\SponsorPeriod;
use App\Sponsor\SponsorRules;
use App\Tests\Support\Sponsor\InMemorySponsorSource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/**
 * QA Feature 15 (2026-10-28) — Zeitgrenzen mit einer Serveruhr in UTC und Reproduktionen.
 *
 * Die Prüfläufe des Baus stellen die Uhr in `Europe/Luxembourg`. Produktion läuft in UTC; hier steht dieselbe Frage
 * mit UTC-Zeitpunkten, dazu die Nacht der Herbstumstellung (EC-02 nennt „die Nacht der Sommerzeitumstellung“, der Bau
 * prüft nur das Frühjahr).
 *
 * Die Reproduktionen (BF-188, BF-189) prüfen das richtige Verhalten, waren beim Schreiben rot und überspringen sich,
 * solange der Befund offen ist. Scharf: `QA15_REPRO=1 php bin/phpunit tests/Unit/Sponsor/Qa15SponsorTest.php`.
 * Wer den Befund behebt, entfernt den Aufruf `self::offen(…)`; setzt die Reparatur an anderer Stelle an als hier
 * angenommen, darf der Aufruf der Prüfung folgen — die Zusicherung bleibt.
 */
final class Qa15SponsorTest extends TestCase
{
    private static function offen(string $befund): void
    {
        if ('1' !== getenv('QA15_REPRO')) {
            self::markTestSkipped($befund.' offen — Reproduktion, siehe features/15-sponsoren/qa-report.md. Scharf mit QA15_REPRO=1.');
        }
    }

    /** @param list<Sponsor> $sponsors */
    private static function directory(string $utc, array $sponsors, array $removed = []): SponsorDirectory
    {
        $source = new InMemorySponsorSource();
        $source->replace($sponsors, $removed);

        return new SponsorDirectory($source, new MockClock($utc, 'UTC'));
    }

    /** @return iterable<string, array{string, SponsorPeriod, bool}> */
    public static function utcGrenzen(): iterable
    {
        $endeMaerz = new SponsorPeriod('2026-04-01', '2027-03-31');
        yield 'AK-24 Sommerzeit: 31.03. 23:59:59 Luxemburg = 21:59:59 UTC, noch aktiv' => ['2027-03-31 21:59:59', $endeMaerz, true];
        yield 'AK-24 Sommerzeit: 01.04. 00:00 Luxemburg = 22:00 UTC, ehemalig' => ['2027-03-31 22:00:00', $endeMaerz, false];

        $endeJanuar = new SponsorPeriod('2026-02-01', '2027-01-31');
        yield 'AK-24 Winterzeit: 31.01. 23:59:59 Luxemburg = 22:59:59 UTC, noch aktiv' => ['2027-01-31 22:59:59', $endeJanuar, true];
        yield 'AK-24 Winterzeit: 01.02. 00:00 Luxemburg = 23:00 UTC, ehemalig' => ['2027-01-31 23:00:00', $endeJanuar, false];

        // Nacht auf den 31.10.2027: 03:00 MESZ → 02:00 MEZ. Der Tageswechsel davor liegt noch in der Sommerzeit.
        $beginnHerbst = new SponsorPeriod('2027-10-31');
        yield 'EC-02 Herbst: 30.10. 23:59:59 Luxemburg = 21:59:59 UTC, noch nicht begonnen' => ['2027-10-30 21:59:59', $beginnHerbst, false];
        yield 'EC-02 Herbst: 31.10. 00:00 Luxemburg = 22:00 UTC, begonnen' => ['2027-10-30 22:00:00', $beginnHerbst, true];
        yield 'EC-02 Herbst: 31.10. 02:30 Luxemburg (doppelte Stunde) = 01:30 UTC, aktiv' => ['2027-10-31 01:30:00', $beginnHerbst, true];
        $endeHerbst = new SponsorPeriod('2027-01-01', '2027-10-31');
        yield 'EC-02 Herbst: 31.10. 23:59:59 Luxemburg (MEZ) = 22:59:59 UTC, noch aktiv' => ['2027-10-31 22:59:59', $endeHerbst, true];
        yield 'EC-02 Herbst: 01.11. 00:00 Luxemburg (MEZ) = 23:00 UTC, ehemalig' => ['2027-10-31 23:00:00', $endeHerbst, false];
    }

    #[DataProvider('utcGrenzen')]
    public function testAk24Ec02TageswechselMitServeruhrInUtc(string $utc, SponsorPeriod $zeitraum, bool $aktiv): void
    {
        $sponsor = new Sponsor('acme', 'Acme', 'acme.png', [$zeitraum]);
        $directory = self::directory($utc, [$sponsor]);

        self::assertSame($aktiv ? ['acme'] : [], array_map(static fn (Sponsor $s): string => $s->slug, $directory->footer()));
        self::assertSame($aktiv, $directory->isActive($sponsor));
    }

    /**
     * BF-188 · Ein Slug unter `removedSlugs()`, dessen Eintrag (noch) steht, erscheint weiter in Footer, Übersicht,
     * Sitemap, Faktenblatt-Zahl und Logoreihe — nur die eigene Seite leitet um. `design.md`: „Ein entfernter Sponsor ist
     * weder aktiv noch ehemalig“; EC-08: „die Entfernung gewinnt“.
     */
    public function testBf188EntfernterSlugGewinntUeberEinenStehengebliebenenEintrag(): void
    {
        self::offen('BF-188');
        $directory = self::directory('2027-06-15 08:00:00', [
            new Sponsor('acme', 'Acme', 'acme.png', [new SponsorPeriod('2026-03-01')]),
            new Sponsor('ehemals', 'Ehemals', 'e.png', [new SponsorPeriod('2025-01-01', '2026-12-31')]),
        ], ['acme', 'ehemals']);

        self::assertSame([], $directory->active());
        self::assertSame([], $directory->footer());
        self::assertSame([], $directory->former());
        self::assertSame([], $directory->visible());
        self::assertFalse($directory->hasAny());
    }

    /**
     * BF-189 · Die Logodatei eines entfernten Sponsors bleibt ausgeliefert, wenn sie im Verzeichnis liegen bleibt, und
     * kein Prüflauf merkt es. AK-43 verlangt: „seine Logodateien antworten mit 404“. Erwartet: Der Prüflauf über die
     * Produktionsquelle meldet eine Datei unter `assets/images/sponsors/`, zu der es keinen Eintrag gibt.
     */
    public function testBf189LiegengebliebeneLogodateiFaelltImPrueflaufAuf(): void
    {
        self::offen('BF-189');
        $verzeichnis = sys_get_temp_dir().'/qa15-logos-'.bin2hex(random_bytes(4));
        mkdir($verzeichnis);
        copy(__DIR__.'/../../Fixtures/Sponsor/logos/acme.png', $verzeichnis.'/acme.png');

        try {
            $verstoesse = SponsorRules::violations([], ['acme'], $verzeichnis, ['lb', 'de', 'fr', 'en', 'pt']);
        } finally {
            unlink($verzeichnis.'/acme.png');
            rmdir($verzeichnis);
        }

        self::assertNotSame([], $verstoesse, 'Eine Logodatei ohne Eintrag (hier die eines entfernten Sponsors) wird nicht gemeldet.');
    }
}
