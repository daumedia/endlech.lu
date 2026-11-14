<?php

declare(strict_types=1);

namespace App\Tests\Functional\Sponsor;

use App\Tests\AbstractWebTestCase;
use App\Tests\Support\Sponsor\TestSponsors;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Feature 15 · Die Angebotsseite für Unternehmen (T16).
 */
final class CompanyPageTest extends AbstractWebTestCase
{
    /** @return iterable<string, array{string}> */
    public static function sprachen(): iterable
    {
        foreach (['lb', 'de', 'fr', 'en', 'pt'] as $sprache) {
            yield $sprache => [$sprache];
        }
    }

    private function seite(array $sponsoren, string $pfad = '/de/organisationen/unternehmen'): Crawler
    {
        $client = static::createClient();
        TestSponsors::use($client, $sponsoren);
        $crawler = $client->request('GET', $pfad);
        self::assertResponseIsSuccessful();

        return $crawler->filter('main');
    }

    #[DataProvider('sprachen')]
    public function testAk33GenauEinPaket(string $sprache): void
    {
        $main = $this->seite([], "/$sprache/organisationen/unternehmen");
        $t = static::getContainer()->get('translator');

        foreach (['package_title', 'package_text', 'package_gets_1', 'package_gets_2', 'package_usage'] as $key) {
            self::assertStringContainsString($t->trans('organisation.company.'.$key, [], null, $sprache), $main->text(), "$key fehlt ($sprache).");
        }
        self::assertCount(1, $main->filter('a[href="/'.$sprache.'/sponsoren"]'), 'Link auf /sponsoren.');
    }

    #[DataProvider('sprachen')]
    public function testAk35Ak36OhneGeldUndAusschlussregelUnveraendert(string $sprache): void
    {
        $main = $this->seite([], "/$sprache/organisationen/unternehmen");
        $t = static::getContainer()->get('translator');

        foreach (['no_money_title', 'no_money_text', 'exclusion_title', 'exclusion_1', 'exclusion_reason'] as $key) {
            self::assertStringContainsString($t->trans('organisation.company.'.$key, [], null, $sprache), $main->text(), "$key fehlt ($sprache).");
        }
    }

    public function testAk31ReiheMitDenAktivenUeberDemFormular(): void
    {
        $anfrage = $this->seite(TestSponsors::all())->filter('#anfrage');

        $logos = $anfrage->filter('img')->each(static fn (Crawler $img): string => (string) $img->attr('alt'));
        self::assertSame(['Acme S.à r.l.', 'Ëmil & Fils S.à r.l.'], $logos, 'Dieselbe Auswahl und Reihenfolge wie im Footer.');
        self::assertStringContainsString('Sie sponsern Endlech.lu bereits', $anfrage->text());
        self::assertSame('/de/sponsoren/acme', $anfrage->filter('img[alt="Acme S.à r.l."]')->closest('a')?->attr('href'));
    }

    /**
     * Fehlerauftrag 17 (Folge von BF-206): Seit der globale Fokus-Fallback in `@layer base` steht,
     * wirkt die Fokusfarbe der Kachel. Der Rahmen liegt auf dem Grund neben der weißen Kachel —
     * cyan-400 (für den dunklen Footer) maß auf `bg-gray-50` 1,73 : 1, cyan-700 5,05 : 1.
     */
    public function testFokusfarbeDerKachelPasstZumGrund(): void
    {
        $client = static::createClient();
        TestSponsors::use($client, TestSponsors::all());
        $crawler = $client->request('GET', '/de/organisationen/unternehmen');

        $reihe = (string) $crawler->filter('#anfrage img[alt="Acme S.à r.l."]')->closest('a')?->attr('class');
        $fusszeile = (string) $crawler->filter('footer img[alt="Acme S.à r.l."]')->closest('a')?->attr('class');

        self::assertStringContainsString('focus:outline-cyan-700', $reihe, 'Auf hellem Grund');
        self::assertStringNotContainsString('focus:outline-cyan-400', $reihe);
        self::assertStringContainsString('focus:outline-cyan-400', $fusszeile, 'Im dunklen Footer');
    }

    public function testAk31OhneAktivenSponsorKeineReihe(): void
    {
        $anfrage = $this->seite([TestSponsors::ehemals()])->filter('#anfrage');

        self::assertCount(0, $anfrage->filter('img'));
        self::assertStringNotContainsString('Sie sponsern Endlech.lu bereits', $anfrage->text());
    }

    public function testAk31NurAufDerUnternehmensseite(): void
    {
        $anfrage = $this->seite(TestSponsors::all(), '/de/organisationen/gemeinden')->filter('#anfrage');

        self::assertCount(0, $anfrage->filter('img'));
    }

    public function testAk37ZweiRadiosOhneVorauswahl(): void
    {
        $radios = $this->seite([])->filter('input[type="radio"][name="organisation_waitlist[sponsorshipTerm]"]');

        self::assertSame(['monthly', 'yearly'], $radios->each(static fn (Crawler $r): string => (string) $r->attr('value')));
        self::assertSame([null, null], $radios->each(static fn (Crawler $r): ?string => $r->attr('checked')));
    }
}
