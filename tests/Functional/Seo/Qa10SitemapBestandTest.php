<?php

namespace App\Tests\Functional\Seo;

use App\Entity\Restaurant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * QA Feature 10 · Bestandsänderungen über HTTP, wie ein Crawler sie sieht.
 *
 * Im Testbetrieb ist `cache.sitemap` ein Array-Adapter, der mit jedem Kernel-Neustart leer ist:
 * Jeder Abruf erzeugt die Sitemap neu. Die Frist „binnen 60 Minuten" selbst ist am laufenden
 * Server im Produktionsmodus belegt (`qa-report.md`, AK-08/AK-09); hier geht es darum, dass der
 * Bestand überhaupt richtig ankommt.
 *
 * DAMA hält die Verbindung über die Anfragen hinweg in einer Transaktion — was der Test ändert,
 * sieht der nächste Abruf, und am Ende wird alles zurückgerollt.
 */
final class Qa10SitemapBestandTest extends WebTestCase
{
    private function eintraege(KernelBrowser $client): array
    {
        $client->request('GET', '/sitemap.xml');
        self::assertResponseIsSuccessful();
        preg_match_all('#<loc>([^<]+)</loc>#', (string) $client->getResponse()->getContent(), $m);

        return $m[1];
    }

    private function em(KernelBrowser $client): EntityManagerInterface
    {
        return $client->getContainer()->get(EntityManagerInterface::class);
    }

    /** AK-10 · Ohne ein einziges Restaurant: HTTP 200 und genau die 84 Einträge der festen Seiten. */
    public function testAk10OhneRestaurantsGenauVierundachtzigEintraege(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $this->em($client)->getConnection()->executeStatement('DELETE FROM restaurant');

        $eintraege = $this->eintraege($client);

        self::assertCount(84, $eintraege);
        self::assertSame([], array_values(preg_grep('#/restaurants/\d+$#', $eintraege)));
    }

    /** AK-08 · Ein neu angelegtes Restaurant steht mit seiner Detailseite in allen vier Sprachen darin. */
    public function testAk08NeuesRestaurantInAllenVierSprachen(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $neu = (new Restaurant())->setName('QA10 Probe')->setCity('Esch-sur-Alzette');
        $this->em($client)->persist($neu);
        $this->em($client)->flush();

        $eintraege = $this->eintraege($client);

        foreach (['lb', 'de', 'fr', 'en'] as $s) {
            self::assertContains("https://endlech.lu/$s/restaurants/{$neu->getId()}", $eintraege);
        }
        self::assertCount((21 + 12) * 4, $eintraege);
    }

    /** AK-09 · Ein gelöschtes Restaurant fällt mit allen vier Sprachfassungen heraus. */
    public function testAk09GeloeschtesRestaurantFaelltHeraus(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $vorher = $this->eintraege($client);
        preg_match('#/restaurants/(\d+)$#', (string) current(preg_grep('#/restaurants/\d+$#', $vorher)), $m);
        $id = (int) $m[1];

        $restaurant = $this->em($client)->find(Restaurant::class, $id);
        self::assertNotNull($restaurant);
        $this->em($client)->remove($restaurant);
        $this->em($client)->flush();

        $nachher = $this->eintraege($client);
        self::assertSame([], array_values(preg_grep("#/restaurants/$id$#", $nachher)));
        self::assertCount(\count($vorher) - 4, $nachher);
    }

    /**
     * Befund BF-147 (QA Feature 10).
     *
     * Die Seitenzahl-Regel aus AK-13 gilt laut `design.md` (Entscheidung 11) nur für die
     * Restaurantliste. Umgesetzt war sie für jede Seite: `/de/about?page=2` erklärte sich selbst
     * zur maßgeblichen Adresse, obwohl die Seite nicht blättert und inhaltlich `/de/about` ist.
     * Jeder, der eine solche Adresse verlinkt, erzeugte damit eine sich selbst kanonisierende
     * Dublette jeder Seite aus der Sitemap.
     */
    public function testBf147SeitenzahlNurAufSeitenDieBlaettern(): void
    {
        $client = static::createClient();
        $canonical = static function (Crawler $c): ?string {
            $k = $c->filter('link[rel="canonical"]');

            return $k->count() ? $k->attr('href') : null;
        };

        foreach ([
            '/de/about?page=2' => 'https://endlech.lu/de/about',
            '/de/presse?page=12' => 'https://endlech.lu/de/presse',
            '/lb/vergleich/wheelmap?page=3' => 'https://endlech.lu/lb/vergleich/wheelmap',
        ] as $aufruf => $soll) {
            self::assertSame($soll, $canonical($client->request('GET', $aufruf)), $aufruf);
        }

        // Die Restaurantliste blättert — dort bleibt die Seitenzahl (AK-13).
        self::assertSame('https://endlech.lu/de/restaurants?page=2', $canonical($client->request('GET', '/de/restaurants?page=2')));
    }

    /**
     * Nachprüfung BF-147 (QA 2026-09-13) · über HTTP, nicht über die Erweiterung: Die Detailseite
     * verliert die Seitenzahl auch in den Sprachverweisen, die Board-Übersicht behält sie (OF-07),
     * und auf einer ausgeschlossenen Seite trägt kein Sprachverweis eine Abfrage.
     */
    public function testBf147DetailseiteBoardUndAusschlussUeberHttp(): void
    {
        $client = static::createClient();
        $verweise = static function (Crawler $c): array {
            $v = [];
            $c->filter('link[rel="alternate"][hreflang]')->each(static function (Crawler $k) use (&$v): void {
                $v[(string) $k->attr('hreflang')] = (string) $k->attr('href');
            });
            $k = $c->filter('link[rel="canonical"]');

            return ['canonical' => $k->count() ? $k->attr('href') : null, 'sprachen' => $v];
        };

        $id = (int) $client->getContainer()->get(EntityManagerInterface::class)
            ->getConnection()->fetchOne('SELECT MIN(id) FROM restaurant');
        $detail = $verweise($client->request('GET', "/de/restaurants/$id?page=7"));
        self::assertResponseIsSuccessful();
        self::assertSame("https://endlech.lu/de/restaurants/$id", $detail['canonical']);
        self::assertSame("https://endlech.lu/fr/restaurants/$id", $detail['sprachen']['fr'] ?? null);

        $board = $verweise($client->request('GET', '/de/community/ideen?page=2&sort=newest'));
        self::assertResponseIsSuccessful();
        self::assertSame('https://endlech.lu/de/community/ideen?page=2', $board['canonical']);
        self::assertSame('https://endlech.lu/lb/community/ideen?page=2', $board['sprachen']['x-default'] ?? null);

        $anmeldung = $verweise($client->request('GET', '/de/login?page=2'));
        self::assertResponseIsSuccessful();
        self::assertNull($anmeldung['canonical']);
        self::assertCount(5, $anmeldung['sprachen']);
        foreach ($anmeldung['sprachen'] as $href) {
            self::assertStringNotContainsString('?', $href);
        }
    }
}
