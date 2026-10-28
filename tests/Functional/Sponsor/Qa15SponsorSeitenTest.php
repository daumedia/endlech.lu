<?php

declare(strict_types=1);

namespace App\Tests\Functional\Sponsor;

use App\Tests\AbstractWebTestCase;
use App\Tests\Support\Sponsor\TestSponsors;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * QA Feature 15 (2026-10-28) — Lücken der Prüfläufe des Baus und eine Reproduktion.
 *
 * - AK-18 verlangt das Zitat in der Originalsprache „in allen fünf Sprachfassungen“; der Bau prüft nur `/de/` und
 *   fällt bei fehlendem `lang` am `<html>` auf `'de'` zurück.
 * - AK-14 nennt auch die Übersicht und `/organisationen/unternehmen`; der Bau prüft Footer, Sponsorenseiten und
 *   Faktenblatt. Hier die Sponsorenkarten der Übersicht und die Logoreihe über dem Formular.
 * - BF-188 als Reproduktion am HTTP-Weg (übersprungen, solange offen; scharf mit `QA15_REPRO=1`).
 */
final class Qa15SponsorSeitenTest extends AbstractWebTestCase
{
    private const string VERRAETERISCH = '/€|\bEUR\b|\d+[,.]\d{2}|monatlich|jährlich|all Mount|all Joer|\bmensuel|\bannuel|\bmonthly\b|\byearly\b|\bmensal\b|\banual\b/iu';

    /** @return iterable<string, array{string}> */
    public static function sprachen(): iterable
    {
        foreach (['lb', 'de', 'fr', 'en', 'pt'] as $sprache) {
            yield $sprache => [$sprache];
        }
    }

    #[DataProvider('sprachen')]
    public function testAk18ZitatBleibtInJederSprachfassungFranzoesisch(string $sprache): void
    {
        $client = static::createClient();
        TestSponsors::use($client, TestSponsors::all());
        $crawler = $client->request('GET', "/$sprache/sponsoren/acme");
        self::assertResponseIsSuccessful();

        self::assertSame($sprache, $crawler->filter('html')->attr('lang'), 'Die Seite trägt ihre eigene Sprache.');
        $zitat = $crawler->filter('main blockquote');
        self::assertCount(1, $zitat);
        self::assertSame('fr', $zitat->attr('lang'));
        self::assertStringContainsString('Parce que l’accessibilité nous concerne tous.', $zitat->text(), 'Zitat unverändert, nicht übersetzt.');
    }

    #[DataProvider('sprachen')]
    public function testAk14UebersichtUndLogoreiheNennenKeineLaufzeitBeimSponsor(string $sprache): void
    {
        $client = static::createClient();
        TestSponsors::use($client, TestSponsors::all());
        $client->disableReboot();

        $uebersicht = $client->request('GET', "/$sprache/sponsoren");
        self::assertResponseIsSuccessful();
        $stellen = [
            'Übersicht, aktive' => $uebersicht->filter('#aktive-sponsoren ul')->text(),
            'Übersicht, ehemalige' => $uebersicht->filter('#ehemalige-sponsoren ul')->text(),
        ];

        $firma = $client->request('GET', "/$sprache/organisationen/unternehmen");
        self::assertResponseIsSuccessful();
        $reihe = $firma->filter('main a[href^="/'.$sprache.'/sponsoren/"]');
        self::assertCount(2, $reihe, 'Logoreihe über dem Formular: die zwei aktiven Testsponsoren.');
        $stellen['Logoreihe'] = implode(' ', $reihe->each(static fn ($a): string => $a->text().' '.$a->filter('img')->attr('alt')));

        foreach ($stellen as $wo => $text) {
            self::assertNotSame('', trim($text), "$wo ($sprache) ist leer — die Prüfung liefe ins Leere.");
            self::assertDoesNotMatchRegularExpression(self::VERRAETERISCH, $text, "$wo ($sprache) nennt einen Betrag oder eine Laufzeit.");
        }
    }

    /**
     * BF-188 · Steht ein Slug unter `removedSlugs()`, während sein Eintrag noch nicht gelöscht ist, leitet nur die
     * eigene Seite um; Footer, Übersicht und Sitemap nennen den Sponsor weiter.
     */
    public function testBf188EntfernterSponsorErscheintNirgendsAuchWennDerEintragNochSteht(): void
    {
        if ('1' !== getenv('QA15_REPRO')) {
            self::markTestSkipped('BF-188 offen — Reproduktion, siehe features/15-sponsoren/qa-report.md. Scharf mit QA15_REPRO=1.');
        }

        $client = static::createClient();
        TestSponsors::use($client, TestSponsors::all(), ['acme']);
        $client->disableReboot();

        $client->request('GET', '/de/sponsoren/acme');
        self::assertResponseStatusCodeSame(301);

        $footer = $client->request('GET', '/de/')->filter('footer section[aria-labelledby="footer-sponsoren"]');
        self::assertCount(0, $footer->filter('img[alt="Acme S.à r.l."]'), 'Footer nennt den entfernten Sponsor.');

        $uebersicht = $client->request('GET', '/de/sponsoren')->filter('main')->text();
        self::assertStringNotContainsString('Acme', $uebersicht, 'Übersicht nennt den entfernten Sponsor.');

        $client->request('GET', '/sitemap.xml');
        self::assertStringNotContainsString('/sponsoren/acme', (string) $client->getResponse()->getContent(), 'Sitemap nennt den entfernten Sponsor.');
    }
}
