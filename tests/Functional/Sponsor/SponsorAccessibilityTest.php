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
 * Feature 15 · Barrierefreiheit und Randfälle der Sponsorenseiten (T20). Die Übersicht steht
 * zusätzlich in den Listen von `AccessibilityStructureTest` und `HeadingOrderTest`; hier
 * stehen die Seiten, die Testsponsoren brauchen.
 */
final class SponsorAccessibilityTest extends AbstractWebTestCase
{
    /** @return iterable<string, array{string}> */
    public static function seitenMitSponsoren(): iterable
    {
        yield 'aktiver Sponsor' => ['/de/sponsoren/acme'];
        yield 'ehemaliger Sponsor' => ['/de/sponsoren/ehemals'];
        yield 'Übersicht gefüllt' => ['/de/sponsoren'];
        yield 'Unternehmensseite mit Logoreihe' => ['/de/organisationen/unternehmen'];
        yield 'Startseite mit Logos im Footer' => ['/de/'];
    }

    private function seite(string $pfad): Crawler
    {
        $client = static::createClient();
        TestSponsors::use($client, TestSponsors::all());
        $crawler = $client->request('GET', $pfad);
        self::assertResponseIsSuccessful();

        return $crawler;
    }

    /** Feature 02 AK-15 · genau ein h1, keine übersprungene Ebene — auch mit Sponsoren. */
    #[DataProvider('seitenMitSponsoren')]
    public function testAk04UeberschriftenOhneLuecke(string $pfad): void
    {
        $crawler = $this->seite($pfad);

        self::assertCount(1, $crawler->filter('h1'));
        $vorige = 0;
        foreach ($crawler->filter('h1, h2, h3, h4, h5, h6') as $knoten) {
            $ebene = (int) substr($knoten->nodeName, 1);
            self::assertLessThanOrEqual($vorige + 1, $ebene, "$pfad springt auf h$ebene: „".trim($knoten->textContent).'“');
            $vorige = $ebene;
        }
    }

    /** Feature 02 · Jedes Bild hat ein alt-Attribut; im Footer ist es der Name (AK-04). */
    #[DataProvider('seitenMitSponsoren')]
    public function testAk04JedesLogoHatAlt(string $pfad): void
    {
        $this->seite($pfad)->filter('img[src*="/images/sponsors/"]')->each(static function (Crawler $img): void {
            self::assertNotNull($img->attr('alt'), 'alt fehlt.');
        });
    }

    /**
     * Feature 02 · Kein Link im Link und kein Link ohne Namen. Gefunden im Selbsttest:
     * `link|default(true)` machte aus einem ausdrücklichen `false` wieder `true` — die
     * Kachel auf der Übersicht lag als Link in der Karte, die schon ein Link ist, und auf
     * der Sponsorenseite stand ein Logo-Link mit leerem alt.
     */
    #[DataProvider('seitenMitSponsoren')]
    public function testAk04KeinVerschachtelterUndKeinNamenloserLink(string $pfad): void
    {
        $crawler = $this->seite($pfad);

        self::assertCount(0, $crawler->filter('a a'), "$pfad: Link im Link.");
        $crawler->filter('main a, footer a')->each(static function (Crawler $a) use ($pfad): void {
            $name = trim($a->text().' '.implode(' ', $a->filter('img')->each(static fn (Crawler $i): string => (string) $i->attr('alt'))));
            self::assertNotSame('', $name, "$pfad: Link ohne Namen auf ".$a->attr('href'));
        });
    }

    /** EC-06 · Die Logodatei trägt ihre Prüfsumme im Namen — ein neues Logo hat eine neue Adresse. */
    public function testEc06LogoAdresseTraegtPruefsumme(): void
    {
        $src = (string) $this->seite('/de/')->filter('footer img[alt="Acme S.à r.l."]')->attr('src');

        self::assertMatchesRegularExpression('#^/assets/images/sponsors/acme-[0-9A-Za-z_-]{6,}\.png$#', $src);
    }

    /** EC-04 · Ein sehr breites Logo steht in derselben Fläche wie die übrigen, unverzerrt. */
    public function testEc04BreitesLogoInDerselbenFlaeche(): void
    {
        $kacheln = $this->seite('/de/')->filter('footer section[aria-labelledby="footer-sponsoren"] img')
            ->each(static fn (Crawler $img): string => (string) $img->closest('a')?->attr('class').'|'.$img->attr('class').'|'.$img->attr('width').'x'.$img->attr('height'));

        self::assertCount(2, $kacheln);
        self::assertSame($kacheln[0], $kacheln[1], 'Acme (288 × 112) und Ëmil (640 × 80, 8 : 1) teilen Fläche, Bildklassen und Maße.');
        self::assertStringContainsString('object-contain', $kacheln[0]);
    }

    /** AK-06 · Zwölf Logos brechen um, statt die Seite bei 320 px waagerecht zu verbreitern. */
    public function testZwoelfLogosBrechenUm(): void
    {
        $sponsoren = array_map(
            static fn (int $i): Sponsor => new Sponsor('s'.$i, 'Sponsor '.$i, 'breit.webp', [new SponsorPeriod('2026-01-01')]),
            range(1, 12),
        );
        $client = static::createClient();
        TestSponsors::use($client, $sponsoren);
        $liste = $client->request('GET', '/de/')->filter('footer section[aria-labelledby="footer-sponsoren"] ul')->first();

        self::assertStringContainsString('flex-wrap', (string) $liste->attr('class'));
        self::assertCount(12, $liste->filter('li'));
    }
}
