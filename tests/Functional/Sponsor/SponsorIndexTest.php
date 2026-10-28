<?php

declare(strict_types=1);

namespace App\Tests\Functional\Sponsor;

use App\Sponsor\Sponsor;
use App\Sponsor\SponsorPeriod;
use App\Tests\AbstractWebTestCase;
use App\Tests\Support\Sponsor\TestSponsors;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Feature 15 · Die Übersicht `/sponsoren` (T14).
 */
final class SponsorIndexTest extends AbstractWebTestCase
{
    /** @param list<Sponsor> $sponsoren */
    private function seite(array $sponsoren, string $sprache = 'de'): Crawler
    {
        $client = static::createClient();
        TestSponsors::use($client, $sponsoren);
        $crawler = $client->request('GET', "/$sprache/sponsoren");
        self::assertResponseIsSuccessful();

        return $crawler->filter('main');
    }

    /** @return iterable<string, array{string}> */
    public static function sprachen(): iterable
    {
        foreach (['lb', 'de', 'fr', 'en', 'pt'] as $sprache) {
            yield $sprache => [$sprache];
        }
    }

    #[DataProvider('sprachen')]
    public function testAk11PaketRegelnUndVerwendungInAllenSprachen(string $sprache): void
    {
        $client = static::createClient();
        TestSponsors::use($client, []);
        $crawler = $client->request('GET', "/$sprache/sponsoren");
        $main = $crawler->filter('main');
        $t = static::getContainer()->get('translator');

        self::assertCount(1, $main->filter('h1'));
        foreach (['sponsor.index.gets_1', 'sponsor.index.gets_2', 'sponsor.not_included.point_1', 'sponsor.not_included.point_2',
            'sponsor.not_included.point_3', 'sponsor.not_included.point_4', 'organisation.company.exclusion_1', 'sponsor.usage.text'] as $key) {
            self::assertStringContainsString($t->trans($key, [], null, $sprache), $main->text(), "$key fehlt auf /$sprache/sponsoren.");
        }
        self::assertCount(1, $main->filter('a[href="/'.$sprache.'/open#finance-heading"]'), 'Link auf den Block Finanzen.');
        self::assertCount(1, $main->filter('a[href="/'.$sprache.'/organisationen/unternehmen#anfrage"]'), '„Sponsor werden →“.');
    }

    public function testAk12AlleAktivenMitSeitAngabeUndLink(): void
    {
        $sponsoren = [];
        foreach (range(1, 13) as $i) {
            $sponsoren[] = new Sponsor(\sprintf('s%02d', $i), \sprintf('Sponsor %02d', $i), 'acme.png', [new SponsorPeriod(\sprintf('2026-%02d-01', min($i, 12)))]);
        }
        $main = $this->seite($sponsoren);
        $aktiv = $main->filter('#aktive-sponsoren li');

        self::assertCount(13, $aktiv, 'Auf der Übersicht alle, nicht nur zwölf.');
        self::assertStringContainsString('Sponsor 01', $aktiv->first()->text());
        self::assertStringContainsString('seit Januar 2026', $aktiv->first()->text());
        self::assertSame('/de/sponsoren/s01', $aktiv->first()->filter('a')->attr('href'));
    }

    public function testAk13LeerSagtDerzeitKeineSponsoren(): void
    {
        $main = $this->seite([]);

        self::assertStringContainsString('Endlech.lu hat derzeit keine Sponsoren.', $main->text());
        self::assertCount(0, $main->filter('#aktive-sponsoren li'));
        self::assertCount(0, $main->filter('#ehemalige-sponsoren'));
    }

    public function testAk15EhemaligeImEigenenAbschnitt(): void
    {
        $main = $this->seite(TestSponsors::all());

        $ehemalige = $main->filter('#ehemalige-sponsoren li');
        self::assertCount(1, $ehemalige);
        self::assertStringContainsString('Ehemals AG', $ehemalige->text());
        self::assertStringContainsString('Januar 2025 – Dezember 2026', $ehemalige->text());
        self::assertSame('/de/sponsoren/ehemals', $ehemalige->filter('a')->attr('href'));

        self::assertStringNotContainsString('Ehemals AG', $main->filter('#aktive-sponsoren')->text());
        self::assertStringNotContainsString('Morgen GmbH', $main->text(), 'Noch nicht begonnen.');
    }

    public function testNurEhemaligeLassenDerzeitKeineStehen(): void
    {
        $main = $this->seite([TestSponsors::ehemals()]);

        self::assertStringContainsString('Endlech.lu hat derzeit keine Sponsoren.', $main->text());
        self::assertCount(1, $main->filter('#ehemalige-sponsoren li'));
    }
}
