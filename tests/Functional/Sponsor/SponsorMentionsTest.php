<?php

declare(strict_types=1);

namespace App\Tests\Functional\Sponsor;

use App\Tests\AbstractWebTestCase;
use App\Tests\Support\Sponsor\TestSponsors;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Feature 15 · Verweise auf die Sponsoren in `/open` und im Faktenblatt (T18).
 */
final class SponsorMentionsTest extends AbstractWebTestCase
{
    /** @return iterable<string, array{string}> */
    public static function sprachen(): iterable
    {
        foreach (['lb', 'de', 'fr', 'en', 'pt'] as $sprache) {
            yield $sprache => [$sprache];
        }
    }

    /**
     * AK-30 · Der Link steht im Block Finanzen — auch während der Quartalssperre: Die
     * Fixtures legen zwei Einnahmen ins laufende Quartal, die Sperre greift also lokal.
     */
    #[DataProvider('sprachen')]
    public function testAk30OpenVerlinktDieSponsorenAuchWaehrendDerSperre(string $sprache): void
    {
        $client = static::createClient();
        TestSponsors::use($client, TestSponsors::all());
        $crawler = $client->request('GET', "/$sprache/open");
        self::assertResponseIsSuccessful();

        $finanzen = $crawler->filter('section[aria-labelledby="finance-heading"]');
        $link = $finanzen->filter('a[href="/'.$sprache.'/sponsoren"]');
        self::assertCount(1, $link);
        self::assertSame(
            static::getContainer()->get('translator')->trans('open.finance.sponsors_link', [], null, $sprache),
            trim($link->text()),
        );
        self::assertStringNotContainsString('Acme', $finanzen->text(), 'Kein Betrag und kein Name bei den Finanzen.');
    }

    /** @return Crawler die Zeile „Sponsoren" im Faktenblatt */
    private function zeile(array $sponsoren, string $sprache = 'de'): Crawler
    {
        $client = static::createClient();
        TestSponsors::use($client, $sponsoren);
        $crawler = $client->request('GET', "/$sprache/presse");
        self::assertResponseIsSuccessful();

        $label = static::getContainer()->get('translator')->trans('facts.sponsors', [], 'press', $sprache);
        $zeile = $crawler->filter('#fakten dl > div')->reduce(static fn (Crawler $d): bool => trim($d->filter('dt')->text()) === $label);
        self::assertCount(1, $zeile, 'Zeile „Sponsoren“ fehlt im Faktenblatt.');

        return $zeile;
    }

    public function testAk32ZahlDerAktivenMitLink(): void
    {
        $zeile = $this->zeile(TestSponsors::all());

        self::assertStringContainsString('2 aktive Sponsoren', $zeile->text());
        self::assertCount(1, $zeile->filter('a[href="/de/sponsoren"]'));
    }

    public function testAk32OhneSponsorDerzeitKeine(): void
    {
        $zeile = $this->zeile([]);

        self::assertStringContainsString('Derzeit keine', $zeile->text());
        self::assertCount(1, $zeile->filter('a[href="/de/sponsoren"]'));
    }

    #[DataProvider('sprachen')]
    public function testAk32InAllenSprachen(string $sprache): void
    {
        $this->zeile([TestSponsors::acme()], $sprache);
    }
}
