<?php

namespace App\Tests\Functional\Seo;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/** Feature 10 · Der Seitenkopf sagt dasselbe wie die Sitemap. */
final class SeoHeadTest extends WebTestCase
{
    /** @return array<string, array<string, string>> Adresse => [hreflang => href] aus der Sitemap */
    private function sitemap(KernelBrowser $client): array
    {
        $client->request('GET', '/sitemap.xml');
        $d = new \DOMDocument();
        self::assertTrue($d->loadXML((string) $client->getResponse()->getContent()));

        $eintraege = [];
        foreach ($d->getElementsByTagNameNS('http://www.sitemaps.org/schemas/sitemap/0.9', 'url') as $url) {
            $loc = (string) $url->getElementsByTagNameNS('http://www.sitemaps.org/schemas/sitemap/0.9', 'loc')->item(0)?->textContent;
            foreach ($url->getElementsByTagNameNS('http://www.w3.org/1999/xhtml', 'link') as $link) {
                $eintraege[$loc][$link->getAttribute('hreflang')] = $link->getAttribute('href');
            }
        }

        return $eintraege;
    }

    private function canonical(Crawler $crawler): ?string
    {
        $knoten = $crawler->filter('link[rel="canonical"]');
        self::assertLessThanOrEqual(1, $knoten->count(), 'Höchstens ein canonical-Verweis je Seite.');

        return $knoten->count() ? $knoten->attr('href') : null;
    }

    /** @return array<string, string> */
    private function sprachverweise(Crawler $crawler): array
    {
        $verweise = [];
        $crawler->filter('link[rel="alternate"][hreflang]')->each(
            static function (Crawler $k) use (&$verweise): void {
                $verweise[(string) $k->attr('hreflang')] = (string) $k->attr('href');
            },
        );

        return $verweise;
    }

    /**
     * AK-12, AK-14 · JEDE Seite der Sitemap nennt als canonical genau ihre Sitemap-Adresse, und
     * ihre Sprachverweise decken sich mit ihrem Sitemap-Eintrag. 128 Abrufe mit den Fixtures.
     */
    public function testJedeSitemapSeiteNenntIhreAdresseUndIhreSprachen(): void
    {
        $client = static::createClient();
        $abweichungen = [];

        foreach ($this->sitemap($client) as $adresse => $sitemapVerweise) {
            $crawler = $client->request('GET', substr($adresse, \strlen('https://endlech.lu')));

            if ($this->canonical($crawler) !== $adresse) {
                $abweichungen[] = "canonical $adresse → ".var_export($this->canonical($crawler), true);
            }
            if ($this->sprachverweise($crawler) !== $sitemapVerweise) {
                $abweichungen[] = "hreflang $adresse";
            }
        }

        self::assertSame([], $abweichungen, implode("\n", $abweichungen));
    }

    /**
     * AK-13 · die vier Beispielzeilen der Spec, gegen die ausgelieferte Seite.
     *
     * @return iterable<string, array{string, string}>
     */
    public static function listenaufrufe(): iterable
    {
        yield 'Sortierung' => ['/de/restaurants?sort=name', 'https://endlech.lu/de/restaurants'];
        yield 'Filter' => ['/de/restaurants?wheelchair=1&city=Esch', 'https://endlech.lu/de/restaurants'];
        yield 'Seite 2 mit Sortierung' => ['/de/restaurants?page=2&sort=name', 'https://endlech.lu/de/restaurants?page=2'];
        yield 'Seite 1' => ['/de/restaurants?page=1', 'https://endlech.lu/de/restaurants'];
        yield 'Seite 0' => ['/de/restaurants?page=0', 'https://endlech.lu/de/restaurants'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('listenaufrufe')]
    public function testRestaurantlisteNenntDieRichtigeMassgeblicheAdresse(string $aufruf, string $erwartet): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', $aufruf);

        self::assertResponseIsSuccessful();
        self::assertSame($erwartet, $this->canonical($crawler));
    }

    /**
     * AK-13, Beispielzeile `?page=abc` — **so nicht erfüllbar, siehe OF-06.** Die Restaurantliste
     * weist eine nicht-numerische Seitenzahl schon heute mit 400 ab (B05); eine Seite, die einen
     * canonical-Verweis tragen könnte, entsteht gar nicht. Festgehalten wird das tatsächliche
     * Verhalten: Fehlerantwort, kein Verweis. Die Abbildungsregel selbst prüft `SeoUrlBuilderTest`.
     */
    public function testUngueltigeSeitenzahlLiefertKeineSeiteUndKeinenVerweis(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/de/restaurants?page=abc');

        self::assertResponseStatusCodeSame(400);
        self::assertNull($this->canonical($crawler));
    }

    /** AK-14 · Die Folgeseite trägt ihre Seitenzahl auch in den Sprachverweisen. */
    public function testFolgeseiteTraegtIhreSeitenzahlInDenSprachverweisen(): void
    {
        $client = static::createClient();
        $verweise = $this->sprachverweise($client->request('GET', '/de/restaurants?page=2&wheelchair=1'));

        self::assertSame('https://endlech.lu/fr/restaurants?page=2', $verweise['fr'] ?? null);
        self::assertSame('https://endlech.lu/lb/restaurants?page=2', $verweise['x-default'] ?? null);
    }

    /** EC-05 · Eine Seitenzahl jenseits der letzten Seite nennt sich selbst. */
    public function testSeiteJenseitsDerLetztenNenntSichSelbst(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/de/restaurants?page=99');

        if (200 === $client->getResponse()->getStatusCode()) {
            self::assertSame('https://endlech.lu/de/restaurants?page=99', $this->canonical($crawler));
        } else {
            self::assertNull($this->canonical($crawler), 'Eine Fehlerseite nennt keine maßgebliche Adresse.');
        }
    }

    /** Eine ausgeschlossene Seite nennt keine maßgebliche Adresse. */
    public function testAnmeldeseiteOhneCanonical(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/de/login');

        self::assertResponseIsSuccessful();
        self::assertNull($this->canonical($crawler));
        self::assertNotEmpty($this->sprachverweise($crawler), 'Sprachverweise bleiben wie bisher.');
    }
}
