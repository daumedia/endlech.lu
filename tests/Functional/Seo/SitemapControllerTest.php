<?php

namespace App\Tests\Functional\Seo;

use App\Controller\Seo\SitemapController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Yaml\Yaml;

/** Feature 10 · `/sitemap.xml` über HTTP, wie ein Crawler sie abruft. */
final class SitemapControllerTest extends WebTestCase
{
    private const string SCHEMA = __DIR__.'/../../Fixtures/Sitemap/sitemap-mit-sprachverweisen.xsd';

    /** @return list<string> alle <loc>-Adressen */
    private function adressen(string $xml): array
    {
        $d = new \DOMDocument();
        self::assertTrue($d->loadXML($xml));
        $locs = [];
        foreach ($d->getElementsByTagNameNS('http://www.sitemaps.org/schemas/sitemap/0.9', 'loc') as $loc) {
            $locs[] = $loc->textContent;
        }

        return $locs;
    }

    /** AK-01 · 200, als XML, öffentlich zwischenspeicherbar, gültig gegen das Schema. */
    public function testDieSitemapAntwortetAlsGueltigesXml(): void
    {
        $client = static::createClient();
        $client->request('GET', '/sitemap.xml');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/xml; charset=utf-8');
        $cache = (string) $client->getResponse()->headers->get('Cache-Control');
        self::assertStringContainsString('public', $cache);
        self::assertStringContainsString('max-age='.SitemapController::MAX_AGE, $cache);

        $d = new \DOMDocument();
        $d->loadXML((string) $client->getResponse()->getContent());
        libxml_use_internal_errors(true);
        $gueltig = $d->schemaValidate(self::SCHEMA);
        libxml_clear_errors();
        self::assertTrue($gueltig);
    }

    /**
     * AK-04 · JEDE Adresse der Sitemap antwortet mit 200 — ohne Weiterleitung, ohne 404.
     *
     * Aufgerufen wird der Pfad; der Host lautet auf `https://endlech.lu` und ist im Test nicht
     * erreichbar. Mit den Fixtures sind das 160 Abrufe (32 Seiten × 5 Sprachen).
     */
    public function testJedeAdresseDerSitemapAntwortetMit200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/sitemap.xml');
        $adressen = $this->adressen((string) $client->getResponse()->getContent());

        self::assertCount(160, $adressen);
        $fehler = [];
        foreach ($adressen as $adresse) {
            self::assertStringStartsWith('https://endlech.lu/', $adresse);
            $pfad = substr($adresse, \strlen('https://endlech.lu'));
            $client->request('GET', $pfad);
            $status = $client->getResponse()->getStatusCode();
            if (200 !== $status) {
                $fehler[] = "$pfad → $status";
            }
        }

        self::assertSame([], $fehler);
    }

    /** AK-03, EC-06 · Abruf über `www` und `http`: Die Adressen lauten trotzdem auf die Hauptadresse. */
    public function testAbrufUeberWwwUndHttpAendertDieAdressenNicht(): void
    {
        $client = static::createClient();
        $client->request('GET', 'http://www.endlech.lu/sitemap.xml');

        self::assertResponseIsSuccessful();
        foreach ($this->adressen((string) $client->getResponse()->getContent()) as $adresse) {
            self::assertStringStartsWith('https://endlech.lu/', $adresse);
        }
        self::assertStringNotContainsString('www.endlech.lu', (string) $client->getResponse()->getContent());
    }

    /** Nur im Wurzelverzeichnis — eine Sprachfassung der Sitemap gibt es nicht. */
    public function testKeineSitemapUnterEinemSprachpraefix(): void
    {
        $client = static::createClient();
        $client->request('GET', '/de/sitemap.xml');

        self::assertResponseStatusCodeSame(404);
    }

    /**
     * AK-08, AK-09 · Speicherdauer auf dem Server plus `max-age` in der Antwort ergibt höchstens
     * 3600 s. Beide Speicher addieren sich; mit zweimal 3600 könnte eine Fassung zwei Stunden alt
     * sein. Gelesen wird der PRODUKTIONSWERT aus `cache.yaml`, nicht der des Testblocks.
     */
    public function testSpeicherdauerUndMaxAgeHaltenSechzigMinutenEin(): void
    {
        $konfig = Yaml::parseFile(__DIR__.'/../../../config/packages/cache.yaml');
        $lebensdauer = $konfig['framework']['cache']['pools']['cache.sitemap']['default_lifetime'] ?? null;

        self::assertIsInt($lebensdauer);
        self::assertLessThanOrEqual(3600, $lebensdauer + SitemapController::MAX_AGE);
    }
}
