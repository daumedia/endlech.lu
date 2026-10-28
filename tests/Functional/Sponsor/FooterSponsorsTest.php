<?php

declare(strict_types=1);

namespace App\Tests\Functional\Sponsor;

use App\Repository\RestaurantRepository;
use App\Sponsor\Sponsor;
use App\Sponsor\SponsorPeriod;
use App\Tests\AbstractWebTestCase;
use App\Tests\Support\Sponsor\TestSponsors;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Feature 15 · Der Bereich „Sponsoren" im Footer (T13).
 */
final class FooterSponsorsTest extends AbstractWebTestCase
{
    private const string BEREICH = 'footer section[aria-labelledby="footer-sponsoren"]';

    /** @param list<Sponsor> $sponsoren */
    private function bereich(KernelBrowser $client, string $pfad, array $sponsoren): Crawler
    {
        TestSponsors::use($client, $sponsoren);
        $crawler = $client->request('GET', $pfad);
        self::assertResponseIsSuccessful();

        $bereich = $crawler->filter(self::BEREICH);
        self::assertCount(1, $bereich, "Kein Bereich „Sponsoren“ im Footer von $pfad.");

        return $bereich;
    }

    /** @return list<string> */
    private static function logos(Crawler $bereich): array
    {
        return $bereich->filter('img')->each(static fn (Crawler $img): string => (string) $img->attr('alt'));
    }

    public function testAk07OhneSponsorNurSponsorWerden(): void
    {
        $bereich = $this->bereich(static::createClient(), self::LOCALE.'/', []);

        self::assertCount(0, $bereich->filter('img'), 'Kein Platzhalterlogo.');
        $links = $bereich->filter('a');
        self::assertCount(1, $links, 'Ohne Sponsor nur „Sponsor werden →“.');
        self::assertSame(self::LOCALE.'/organisationen/unternehmen#anfrage', $links->attr('href'));
        self::assertStringContainsString('Sponsor werden', $links->text());
        self::assertStringNotContainsString('Hier könnte', $bereich->text());
    }

    /** @return iterable<string, array{string}> */
    public static function seiten(): iterable
    {
        yield 'Startseite' => ['/de/'];
        yield 'Restaurantliste' => ['/de/restaurants'];
        yield 'Restaurant-Detailseite' => ['DETAIL'];
        yield '/open' => ['/de/open'];
        yield 'Impressum' => ['/de/legal'];
        foreach (['lb', 'fr', 'en', 'pt'] as $sprache) {
            yield "Startseite $sprache" => ["/$sprache/"];
        }
    }

    #[DataProvider('seiten')]
    public function testAk01AktiveSponsorenAufJederSeite(string $pfad): void
    {
        $client = static::createClient();
        if ('DETAIL' === $pfad) {
            $id = $client->getContainer()->get(RestaurantRepository::class)->findAllIdsAscending()[0];
            $pfad = self::LOCALE.'/restaurants/'.$id;
        }

        $bereich = $this->bereich($client, $pfad, TestSponsors::all());

        // AK-02: ältester Beginn zuerst — Acme seit März 2026, Ëmil & Fils seit September 2026.
        self::assertSame(['Acme S.à r.l.', 'Ëmil & Fils S.à r.l.'], self::logos($bereich));
        self::assertNotEmpty(trim($bereich->filter('h2')->text()));
    }

    public function testAk03LogoFuehrtAufDieSponsorenseiteInDerselbenSprache(): void
    {
        $bereich = $this->bereich(static::createClient(), '/fr/', TestSponsors::all());
        $link = $bereich->filter('img[alt="Acme S.à r.l."]')->closest('a');

        self::assertSame('/fr/sponsoren/acme', $link?->attr('href'));
        self::assertNull($link->attr('target'), 'Kein neuer Tab.');
    }

    public function testAk04NameIstDerLinktextUndDieKachelIstGrossGenug(): void
    {
        $bereich = $this->bereich(static::createClient(), self::LOCALE.'/', TestSponsors::all());
        $link = $bereich->filter('img[alt="Acme S.à r.l."]')->closest('a');

        self::assertSame('', trim($link->text()), 'Das Bild ist der einzige Inhalt — sein alt ist der Name des Links.');
        self::assertStringContainsString('focus:outline-2', (string) $link->attr('class'));
        self::assertStringContainsString('h-14 w-36', (string) $link->attr('class'), '56 × 144 px, über 44 × 44.');
    }

    public function testAk05NurEhemaligeZeigenAlleSponsorenOhneLogos(): void
    {
        $bereich = $this->bereich(static::createClient(), self::LOCALE.'/', [TestSponsors::ehemals()]);

        self::assertCount(0, $bereich->filter('img'));
        $hrefs = $bereich->filter('a')->each(static fn (Crawler $a): string => (string) $a->attr('href'));
        self::assertSame([self::LOCALE.'/sponsoren', self::LOCALE.'/organisationen/unternehmen#anfrage'], $hrefs);
    }

    public function testAk05MitAktivenBeideLinks(): void
    {
        $bereich = $this->bereich(static::createClient(), self::LOCALE.'/', TestSponsors::all());
        $hrefs = $bereich->filter('ul')->last()->filter('a')->each(static fn (Crawler $a): string => (string) $a->attr('href'));

        self::assertSame([self::LOCALE.'/sponsoren', self::LOCALE.'/organisationen/unternehmen#anfrage'], $hrefs);
    }

    public function testAk06DreizehnAktiveZwoelfImFooter(): void
    {
        $sponsoren = [];
        foreach (range(1, 13) as $i) {
            $sponsoren[] = new Sponsor(\sprintf('s%02d', $i), \sprintf('Sponsor %02d', $i), 'acme.png', [new SponsorPeriod(\sprintf('2026-%02d-01', min($i, 12)))]);
        }

        $logos = self::logos($this->bereich(static::createClient(), self::LOCALE.'/', $sponsoren));

        self::assertCount(12, $logos);
        self::assertSame('Sponsor 01', $logos[0]);
        self::assertNotContains('Sponsor 13', $logos);
    }

    public function testAk10LogosLadenVerzoegertMitFesterGroesse(): void
    {
        $bereich = $this->bereich(static::createClient(), self::LOCALE.'/', TestSponsors::all());

        $bereich->filter('img')->each(static function (Crawler $img): void {
            self::assertSame('lazy', $img->attr('loading'));
            self::assertNotEmpty($img->attr('width'));
            self::assertNotEmpty($img->attr('height'));
            self::assertStringStartsWith('/assets/images/sponsors/', (string) $img->attr('src'), 'Vom eigenen Ursprung, mit Prüfsumme (AK-42, EC-06).');
        });
    }
}
