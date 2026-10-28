<?php

declare(strict_types=1);

namespace App\Tests\Unit\Sponsor;

use App\Sponsor\Sponsor;
use App\Sponsor\SponsorPeriod;
use App\Sponsor\SponsorPlatform;
use App\Sponsor\SponsorRules;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * QA Feature 15, Nachprüfung der Überarbeitung (2026-10-28) — Prüfregeln für Kurzbeschreibung und Profile.
 *
 * Ergänzt `SponsorRulesTest` um die Angriffsfälle aus dem QA-Durchlauf (Tarnadressen für AK-53), die Grenze von
 * 500 Zeichen (AK-51) und zwei Reproduktionen:
 * - BF-196: Ein ausgeschriebener Eurobetrag („500 Euro“, „500 euros“, „50EUR“) passiert den Prüflauf (AK-51).
 * - BF-197: Ein `null` in Beschreibung oder Profil bricht den Prüflauf mit `TypeError` ab, statt Sponsor, Sprache
 *   bzw. Plattform zu nennen (AK-51, AK-53).
 *
 * Die Reproduktionen prüfen das richtige Verhalten, waren beim Schreiben rot und überspringen sich, solange der Befund
 * offen ist. Scharf: `QA15_REPRO=1 php bin/phpunit tests/Unit/Sponsor/Qa15NachpruefungTest.php`. Wer den Befund
 * behebt, entfernt den Aufruf `self::offen(…)` — und ändert sonst nichts an der Prüfung.
 */
final class Qa15NachpruefungTest extends TestCase
{
    private const string LOGOS = __DIR__.'/../../Fixtures/Sponsor/logos';
    private const array SPRACHEN = ['lb', 'de', 'fr', 'en', 'pt'];

    private static function offen(string $befund): void
    {
        if ('1' !== getenv('QA15_REPRO')) {
            self::markTestSkipped($befund.' offen — Reproduktion, siehe features/15-sponsoren/qa-report.md (Nachprüfung). Scharf mit QA15_REPRO=1.');
        }
    }

    /** @return list<string> */
    private static function pruefe(Sponsor $sponsor): array
    {
        return SponsorRules::violations([$sponsor], [], self::LOGOS, self::SPRACHEN);
    }

    /** @param array<string, string>|null $beschreibung */
    private static function sponsor(?array $beschreibung = null, array $profile = []): Sponsor
    {
        return new Sponsor('acme', 'Acme S.à r.l.', 'acme.png', [new SponsorPeriod('2026-01-01')], description: $beschreibung, profiles: $profile);
    }

    /** @return array<string, string> */
    private static function beschreibung(array $abweichend = []): array
    {
        return [...array_fill_keys(self::SPRACHEN, 'Wir erfüllen Herzenswünsche.'), ...$abweichend];
    }

    /** @return iterable<string, array{string, string}> */
    public static function tarnadressen(): iterable
    {
        yield 'Benutzerteil vor fremdem Host' => ['instagram', 'https://www.instagram.com@evil.example/acme'];
        yield 'Fragezeichen vor @' => ['instagram', 'https://evil.example?@www.instagram.com/'];
        yield 'Raute vor @' => ['instagram', 'https://evil.example#@www.instagram.com/'];
        yield 'Rückstrich vor @ (Browser: Host evil.example)' => ['instagram', 'https://evil.example\\@www.instagram.com/acme'];
        yield 'Rückstrich im Host' => ['facebook', 'https://evil.example\\.facebook.com/'];
        yield 'Tabulator im Host' => ['facebook', "https://evil.example\t.facebook.com/"];
        yield 'Leerzeichen im Host' => ['linkedin', 'https://evil.example .linkedin.com/'];
        yield 'Prozentkodierter Punkt im Host' => ['linkedin', 'https://evil%2eexample.linkedin.com/'];
        yield 'Plattform als Pfad' => ['instagram', 'https://evil.example/www.instagram.com/acme'];
        yield 'Plattform als Präfix des Hosts' => ['instagram', 'https://instagram.com.evil.example/acme'];
        yield 'Plattformname im Host ohne Punkt' => ['linkedin', 'https://evillinkedin.com/acme'];
        yield 'Kyrillisches а (IDN)' => ['instagram', 'https://www.instagrаm.com/acme'];
        yield 'Punycode-Doppelgänger' => ['instagram', 'https://www.xn--instagrm-6fg.com/acme'];
        yield 'Kurzadresse fb.me' => ['facebook', 'https://fb.me/acme'];
        yield 'Kurzadresse lnkd.in' => ['linkedin', 'https://lnkd.in/acme'];
        yield 'javascript:' => ['instagram', 'javascript:alert(1)//www.instagram.com'];
        yield 'protokollrelativ' => ['instagram', '//www.instagram.com/acme'];
    }

    /** AK-53 · Tarnadressen werden abgewiesen, die Meldung nennt Sponsor und Plattform. */
    #[DataProvider('tarnadressen')]
    public function testAk53TarnadresseWirdMitSponsorUndPlattformAbgewiesen(string $plattform, string $adresse): void
    {
        $verstoesse = implode("\n", self::pruefe(self::sponsor(profile: [$plattform => $adresse])));

        self::assertStringContainsString('`acme`', $verstoesse, 'Kein Verstoß gemeldet.');
        self::assertStringContainsString(SponsorPlatform::from($plattform)->label(), $verstoesse);
    }

    /** AK-53 · Die üblichen Schreibweisen echter Profile bleiben gültig (keine Gegenprobe gegen die eigene Regel). */
    public function testAk53UeblicheProfiladressenBleibenGueltig(): void
    {
        self::assertSame([], self::pruefe(self::sponsor(profile: [
            'instagram' => 'https://WWW.INSTAGRAM.COM/acme/',
            'facebook' => 'https://m.facebook.com/acme',
            'linkedin' => 'https://lu.linkedin.com/company/acme',
        ])));
    }

    /** AK-51 · Genau 500 Zeichen (Mehrbyte) sind erlaubt, 501 nicht — in jeder Sprache. */
    public function testAk51GrenzeVon500ZeichenJeSprache(): void
    {
        self::assertSame([], self::pruefe(self::sponsor(array_fill_keys(self::SPRACHEN, str_repeat('ë', 500)))));

        foreach (self::SPRACHEN as $sprache) {
            $verstoesse = implode("\n", self::pruefe(self::sponsor(self::beschreibung([$sprache => str_repeat('ë', 501)]))));
            self::assertStringContainsString('`acme`', $verstoesse);
            self::assertStringContainsString("`$sprache`", $verstoesse);
            self::assertStringContainsString('501', $verstoesse);
        }
    }

    /** AK-51 · Nur Zeilenumbrüche gelten als leer. */
    public function testAk51NurZeilenumbruecheSindLeer(): void
    {
        $verstoesse = implode("\n", self::pruefe(self::sponsor(self::beschreibung(['fr' => "\n\n"]))));

        self::assertStringContainsString('`fr`', $verstoesse);
        self::assertStringContainsString('leer', $verstoesse);
    }

    /** @return iterable<string, array{string, string}> */
    public static function ausgeschriebeneBetraege(): iterable
    {
        yield 'lb · 500 Euro' => ['lb', 'Mir bezuelen 500 Euro am Joer.'];
        yield 'de · 500 Euro' => ['de', 'Wir zahlen 500 Euro im Jahr.'];
        yield 'fr · 500 euros' => ['fr', 'Nous versons 500 euros par an.'];
        yield 'en · 500 euros' => ['en', 'We pay 500 euros a year.'];
        yield 'pt · 500 euros' => ['pt', 'Pagamos 500 euros por ano.'];
        yield 'de · 50EUR' => ['de', 'Ab 50EUR im Monat.'];
        yield 'de · 50 EURO' => ['de', 'Ab 50 EURO im Monat.'];
        yield 'en · 50 eur' => ['en', 'From 50 eur a month.'];
    }

    /**
     * BF-196 · AK-51: „nennt einen Eurobetrag → ein Prüflauf schlägt fehl und nennt Sponsor, Sprache und Angabe“.
     * Die Regel erkennt nur `€` und `EUR` mit Wortgrenze.
     */
    #[DataProvider('ausgeschriebeneBetraege')]
    public function testBf196AusgeschriebenerEurobetragFaelltAuf(string $sprache, string $text): void
    {
        self::offen('BF-196');

        $verstoesse = implode("\n", self::pruefe(self::sponsor(self::beschreibung([$sprache => $text]))));

        self::assertStringContainsString('`acme`', $verstoesse, "„{$text}“ ($sprache) passiert den Prüflauf.");
        self::assertStringContainsString("`$sprache`", $verstoesse);
    }

    /**
     * BF-197 · AK-51/AK-53: Ein `null` als Beschreibung einer Sprache oder als Profiladresse („kommt noch“) soll wie
     * „fehlt“ bzw. „keine https://-Adresse“ gemeldet werden — mit Sponsor und Sprache/Plattform —, nicht als
     * `TypeError` aus `trim()`/`str_starts_with()`, der alle übrigen Verstöße verschluckt.
     */
    public function testBf197NullWirdMitSponsorUndAngabeGemeldet(): void
    {
        self::offen('BF-197');

        $beschreibung = self::beschreibung();
        $beschreibung['pt'] = null;
        $verstoesse = implode("\n", SponsorRules::violations([
            self::sponsor($beschreibung),
            new Sponsor('zwei', 'Zwei SA', 'acme.png', [new SponsorPeriod('2026-01-01')], profiles: ['instagram' => null]),
        ], [], self::LOGOS, self::SPRACHEN));

        self::assertStringContainsString('`acme`', $verstoesse);
        self::assertStringContainsString('`pt`', $verstoesse);
        self::assertStringContainsString('`zwei`', $verstoesse);
        self::assertStringContainsString('Instagram', $verstoesse);
    }
}
