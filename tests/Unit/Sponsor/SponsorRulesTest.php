<?php

declare(strict_types=1);

namespace App\Tests\Unit\Sponsor;

use App\Sponsor\Sponsor;
use App\Sponsor\SponsorPeriod;
use App\Sponsor\SponsorRules;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Die Regeln für Sponsoreneinträge greifen (Feature 15, T06) — Gegenproben zu
 * `SponsorRegistryTest`, der die echte Liste prüft.
 */
final class SponsorRulesTest extends TestCase
{
    private const string LOGOS = __DIR__.'/../../Fixtures/Sponsor/logos';

    /** Testdaten: die fünf Sprachen. Die echte Liste liest `SponsorRegistryTest` aus `translation.yaml`. */
    private const array SPRACHEN = ['lb', 'de', 'fr', 'en', 'pt'];

    /** @return array<string, string> */
    private static function beschreibung(string $text = 'Wir erfüllen Herzenswünsche.'): array
    {
        return array_fill_keys(self::SPRACHEN, $text);
    }

    private static string $temp;

    public static function setUpBeforeClass(): void
    {
        self::$temp = sys_get_temp_dir().'/sponsor-rules-'.bin2hex(random_bytes(4));
        mkdir(self::$temp);
        // Ein SVG mit Skript, das sich als PNG ausgibt (AK-28).
        file_put_contents(self::$temp.'/getarnt.png', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        file_put_contents(self::$temp.'/echt.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        // Ein PNG über 25 KB (AK-10): gültige Signatur, aufgefüllt.
        file_put_contents(self::$temp.'/schwer.png', "\x89PNG\r\n\x1a\n".str_repeat("\0", 26 * 1024));
        foreach (range(1, 12) as $i) {
            file_put_contents(self::$temp.\sprintf('/mittel%02d.png', $i), "\x89PNG\r\n\x1a\n".str_repeat("\0", 13 * 1024));
        }
        copy(self::LOGOS.'/acme.png', self::$temp.'/acme.png');
    }

    public static function tearDownAfterClass(): void
    {
        array_map('unlink', glob(self::$temp.'/*') ?: []);
        rmdir(self::$temp);
    }

    private static function gueltig(string $slug = 'acme', ?string $logo = 'acme.png', mixed ...$felder): Sponsor
    {
        return new Sponsor(...[
            'slug' => $slug,
            'name' => 'Acme S.à r.l.',
            'logo' => $logo,
            'periods' => [new SponsorPeriod('2027-01-01')],
            'website' => 'https://acme.lu',
            'quote' => 'Weil Barrierefreiheit uns alle angeht.',
            'quoteLocale' => 'de',
            ...$felder,
        ]);
    }

    /** @param list<Sponsor> $sponsors */
    private static function pruefe(array $sponsors, array $removed = []): array
    {
        return SponsorRules::violations($sponsors, $removed, self::$temp, self::SPRACHEN);
    }

    public function testGueltigerEintragHatKeinenVerstoss(): void
    {
        self::assertSame([], self::pruefe([self::gueltig()]));
        self::assertSame([], self::pruefe([self::gueltig(website: null, quote: null, quoteLocale: null)]));
        self::assertSame([], self::pruefe([self::gueltig(description: self::beschreibung(), profiles: [
            'instagram' => 'https://www.instagram.com/acme/',
            'facebook' => 'https://facebook.com/acme',
            'linkedin' => 'https://lu.linkedin.com/company/acme',
        ])]), 'Überarbeitung: Beschreibung in fünf Sprachen und drei gültige Profile.');
    }

    /** @return iterable<string, array{Sponsor, string}> */
    public static function verstoesse(): iterable
    {
        yield 'AK-27 leerer Name' => [self::gueltig(name: '  '), 'Name'];
        yield 'AK-27 Name über 80 Zeichen' => [self::gueltig(name: str_repeat('ä', 81)), 'Name'];
        yield 'AK-27 Slug mit Großbuchstaben' => [self::gueltig(slug: 'Acme'), 'Adresse'];
        yield 'EC-05 Slug mit Umlaut' => [self::gueltig(slug: 'ëmil-fils'), 'Adresse'];
        yield 'EC-05 Slug mit doppeltem Bindestrich' => [self::gueltig(slug: 'acme--sarl'), 'Adresse'];
        yield 'AK-27 kein Zeitraum' => [self::gueltig(periods: []), 'Zeitraum'];
        yield 'AK-27 Beginn kein Datum' => [self::gueltig(periods: [new SponsorPeriod('2027-02-30')]), 'Beginn'];
        yield 'AK-27 Ende vor Beginn' => [self::gueltig(periods: [new SponsorPeriod('2027-05-01', '2027-04-30')]), 'Ende'];
        yield 'AK-27 überlappende Zeiträume' => [self::gueltig(periods: [new SponsorPeriod('2027-01-01', '2027-06-30'), new SponsorPeriod('2027-06-30')]), 'überlapp'];
        yield 'AK-27 offener Zeitraum nicht zuletzt' => [self::gueltig(periods: [new SponsorPeriod('2026-01-01'), new SponsorPeriod('2027-01-01', '2027-02-01')]), 'überlapp'];
        yield 'AK-27 Zeiträume nicht geordnet' => [self::gueltig(periods: [new SponsorPeriod('2027-01-01', '2027-02-01'), new SponsorPeriod('2025-01-01', '2025-02-01')]), 'Reihenfolge'];
        yield 'AK-19 Zitat über 300 Zeichen' => [self::gueltig(quote: str_repeat('é', 301)), 'Zitat'];
        yield 'AK-27 Zitat ohne Sprache' => [self::gueltig(quoteLocale: null), 'Sprache'];
        yield 'AK-27 Sprache ohne Zitat' => [self::gueltig(quote: null), 'Sprache'];
        yield 'AK-27 Sprachcode ungültig' => [self::gueltig(quoteLocale: 'Deutsch'), 'Sprache'];
        yield 'AK-27 Website ohne https' => [self::gueltig(website: 'http://acme.lu'), 'Website'];
        yield 'AK-27 Website keine Adresse' => [self::gueltig(website: 'https://'), 'Website'];
        yield 'AK-27 Logodatei fehlt' => [self::gueltig(logo: 'fehlt.png'), 'Logo'];
        yield 'AK-28 SVG als PNG getarnt' => [self::gueltig(logo: 'getarnt.png'), 'Logo'];
        yield 'AK-28 echte SVG' => [self::gueltig(logo: 'echt.svg'), 'Logo'];
        yield 'AK-10 Logo über 25 KB' => [self::gueltig(logo: 'schwer.png'), '25 KB'];
        // Überarbeitung 2026-10-28
        yield 'AK-51 Beschreibung ohne Portugiesisch' => [self::gueltig(description: array_diff_key(self::beschreibung(), ['pt' => 1])), 'pt'];
        yield 'AK-51 Beschreibung in fremder Sprache' => [self::gueltig(description: [...self::beschreibung(), 'it' => 'Ciao']), 'it'];
        yield 'AK-51 Beschreibung leer' => [self::gueltig(description: [...self::beschreibung(), 'fr' => '   ']), 'fr'];
        yield 'AK-51 Beschreibung über 500 Zeichen' => [self::gueltig(description: [...self::beschreibung(), 'de' => str_repeat('ü', 501)]), 'de'];
        yield 'AK-51 Beschreibung mit Eurobetrag' => [self::gueltig(description: [...self::beschreibung(), 'en' => 'Sponsoring from 50 € a month.']), 'en'];
        yield 'AK-51 Beschreibung mit EUR' => [self::gueltig(description: [...self::beschreibung(), 'lb' => 'Ab 50 EUR.']), 'lb'];
        yield 'AK-53 Profil ohne https' => [self::gueltig(profiles: ['instagram' => 'http://instagram.com/acme']), 'Instagram'];
        yield 'AK-53 Profil auf fremdem Server' => [self::gueltig(profiles: ['instagram' => 'https://instagram.com.example.org/acme']), 'Instagram'];
        yield 'AK-53 Profil der falschen Plattform' => [self::gueltig(profiles: ['linkedin' => 'https://www.facebook.com/acme']), 'LinkedIn'];
        yield 'AK-53 unbekannte Plattform' => [self::gueltig(profiles: ['tiktok' => 'https://www.tiktok.com/@acme']), 'tiktok'];
    }

    #[DataProvider('verstoesse')]
    public function testVerstossWirdMitSponsorUndAngabeGemeldet(Sponsor $sponsor, string $angabe): void
    {
        $verstoesse = self::pruefe([$sponsor]);

        self::assertNotSame([], $verstoesse, 'Kein Verstoß gemeldet.');
        self::assertStringContainsString('`'.$sponsor->slug.'`', implode("\n", $verstoesse));
        self::assertStringContainsString($angabe, implode("\n", $verstoesse));
    }

    public function testAk27ZweiSponsorenMitDerselbenAdresse(): void
    {
        $verstoesse = self::pruefe([self::gueltig(), self::gueltig(name: 'Acme Zwei')]);

        self::assertStringContainsString('doppelt', implode("\n", $verstoesse));
    }

    public function testAk43EntfernterSlugDarfNichtZugleichEingetragenSein(): void
    {
        $verstoesse = self::pruefe([self::gueltig()], ['acme']);

        self::assertStringContainsString('entfernt', implode("\n", $verstoesse));
    }

    public function testAk10ZwoelfLogosZusammenHoechstens150Kb(): void
    {
        $sponsoren = [];
        foreach (range(1, 12) as $i) {
            $sponsoren[] = self::gueltig(slug: 's'.$i, logo: \sprintf('mittel%02d.png', $i));
        }

        // 12 × 13 KB = 156 KB — jede Datei für sich erlaubt, zusammen zu schwer.
        self::assertStringContainsString('150 KB', implode("\n", self::pruefe($sponsoren)));
        self::assertSame([], self::pruefe(\array_slice($sponsoren, 0, 11)));
    }
}
