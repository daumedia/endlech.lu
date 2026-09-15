<?php

namespace App\Tests\Integration\Seo;

use App\Repository\RestaurantRepository;
use App\Seo\SeoRegistry;
use App\Seo\SeoUrlBuilder;
use App\Seo\SitemapGenerator;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Feature 10 · Die Sitemap selbst.
 *
 * ⚠ Der Erzeuger wird von Hand gebaut: Solange ihn noch kein Controller benutzt, entfernt
 * Symfony den privaten Dienst aus dem Testcontainer. Router, Twig und Repository sind die
 * echten.
 */
final class SitemapGeneratorTest extends KernelTestCase
{
    private const string SCHEMA = __DIR__.'/../../Fixtures/Sitemap/sitemap-mit-sprachverweisen.xsd';
    private const string NS_SITEMAP = 'http://www.sitemaps.org/schemas/sitemap/0.9';
    private const string NS_XHTML = 'http://www.w3.org/1999/xhtml';

    private ArrayAdapter $cache;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->cache = new ArrayAdapter();
    }

    private function erzeuger(?RestaurantRepository $repository = null): SitemapGenerator
    {
        $c = static::getContainer();

        return new SitemapGenerator(
            new SeoRegistry(),
            new SeoUrlBuilder($c->get('router'), 'https://endlech.lu', ['lb', 'de', 'fr', 'en', 'pt']),
            $repository ?? $c->get(RestaurantRepository::class),
            $c->get('twig'),
            $this->cache,
        );
    }

    private function dokument(string $xml): \DOMDocument
    {
        $d = new \DOMDocument();
        self::assertTrue($d->loadXML($xml), 'Die Sitemap ist kein wohlgeformtes XML.');

        return $d;
    }

    private function restaurantAnzahl(): int
    {
        return \count(static::getContainer()->get(RestaurantRepository::class)->findAllIdsAscending());
    }

    /** AK-02 · (21 + n) × 5 Einträge — mit den Fixtures 160. */
    public function testEinundzwanzigPlusRestaurantsJeSprache(): void
    {
        $n = $this->restaurantAnzahl();
        $urls = $this->dokument($this->erzeuger()->xml())->getElementsByTagNameNS(self::NS_SITEMAP, 'url');

        self::assertSame(11, $n, 'Fixture-Bestand');
        self::assertSame((21 + $n) * 5, $urls->length);
        self::assertSame(160, $urls->length);
    }

    /** AK-01 · gültig gegen sitemap.xsd samt Schema für die Sprachverweise. */
    public function testDieSitemapBestehtDieSchemaPruefung(): void
    {
        libxml_use_internal_errors(true);
        $gueltig = $this->dokument($this->erzeuger()->xml())->schemaValidate(self::SCHEMA);
        $fehler = array_map(static fn (\LibXMLError $e): string => trim($e->message), libxml_get_errors());
        libxml_clear_errors();

        self::assertTrue($gueltig, implode("\n", $fehler));
    }

    /** Die XML-Deklaration steht am ersten Zeichen — ein Leerzeichen davor macht die Datei ungültig. */
    public function testDieDateiBeginntMitDerXmlDeklaration(): void
    {
        self::assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $this->erzeuger()->xml());
    }

    /** AK-05 · fünf Sprachen plus Vorgabe lb; der Eintrag führt sich selbst mit auf. */
    public function testJederEintragNenntAlleSprachenUndDieVorgabe(): void
    {
        $d = $this->dokument($this->erzeuger()->xml());

        foreach ($d->getElementsByTagNameNS(self::NS_SITEMAP, 'url') as $url) {
            $loc = $url->getElementsByTagNameNS(self::NS_SITEMAP, 'loc')->item(0)?->textContent;
            $verweise = [];
            foreach ($url->getElementsByTagNameNS(self::NS_XHTML, 'link') as $link) {
                $verweise[$link->getAttribute('hreflang')] = $link->getAttribute('href');
            }

            self::assertSame(['lb', 'de', 'fr', 'en', 'pt', 'x-default'], array_keys($verweise), (string) $loc);
            self::assertSame($verweise['lb'], $verweise['x-default']);
            self::assertMatchesRegularExpression('#^https://endlech\.lu/(lb|de|fr|en|pt)/#', (string) $loc);
            preg_match('#^https://endlech\.lu/(lb|de|fr|en|pt)/#', (string) $loc, $m);
            self::assertSame($loc, $verweise[$m[1]], 'Der Eintrag muss sich selbst unter seiner Sprache aufführen.');
        }
    }

    /** AK-03 · keine Abfrageparameter in der Sitemap. */
    public function testKeineAdresseTraegtEinFragezeichen(): void
    {
        self::assertStringNotContainsString('?', substr($this->erzeuger()->xml(), \strlen('<?xml version="1.0" encoding="UTF-8"?>')));
    }

    /** AK-07 · kein Änderungsdatum, keine Priorität, keine Häufigkeit. */
    public function testKeinDatumKeinePrioritaetKeineHaeufigkeit(): void
    {
        $d = $this->dokument($this->erzeuger()->xml());

        foreach (['lastmod', 'priority', 'changefreq'] as $element) {
            self::assertSame(0, $d->getElementsByTagNameNS(self::NS_SITEMAP, $element)->length, $element);
        }
    }

    /** AK-06, AK-22 · keine Adresse aus ausgeschlossenen Bereichen, kein @, kein Token. */
    public function testKeineAusgeschlosseneAdresseKeinTokenKeinAt(): void
    {
        $xml = $this->erzeuger()->xml();
        preg_match_all('#https://endlech\.lu[^"<]*#', $xml, $treffer);

        self::assertNotEmpty($treffer[0]);
        foreach (array_unique($treffer[0]) as $adresse) {
            self::assertStringNotContainsString('@', $adresse);
            self::assertDoesNotMatchRegularExpression('/[0-9a-f]{24,}/i', $adresse, 'Token-artige Zeichenfolge');
            self::assertDoesNotMatchRegularExpression(
                '#/(admin|profile|api|login|register|passwort-vergessen|passwort-zuruecksetzen|verify|confirmation|abmelden|thanks|suggest|eingereicht)(/|$)#',
                $adresse,
            );
            self::assertDoesNotMatchRegularExpression('#/community/ideen/(?!$)#', $adresse, 'Einzelne Ideen gehören nicht hinein.');
        }
    }

    /** AK-10 · Ohne ein einziges Restaurant: die 105 Einträge der festen Seiten (21 × 5 Sprachen). */
    public function testOhneRestaurantsGenauDieFestenSeiten(): void
    {
        static::getContainer()->get(EntityManagerInterface::class)->getConnection()->executeStatement('DELETE FROM restaurant');

        $urls = $this->dokument($this->erzeuger()->xml())->getElementsByTagNameNS(self::NS_SITEMAP, 'url');
        self::assertSame(21 * 5, $urls->length);
    }

    /**
     * AK-11 · Scheitert die Abfrage, wirft der Erzeuger — und hinterlässt KEINE Fassung.
     *
     * ⚠ Das ist der Prüflauf, der den naheliegenden `try`/`catch` verhindert. Mit ihm lieferte
     * der Erzeuger eine Sitemap ohne Restaurantseiten, und dieser Lauf würde rot.
     */
    public function testEineScheiterndeAbfrageWirftUndSpeichertNichts(): void
    {
        $kaputt = $this->createStub(RestaurantRepository::class);
        $kaputt->method('findAllIdsAscending')->willThrowException(
            $this->createStub(ConnectionException::class),
        );

        try {
            $this->erzeuger($kaputt)->xml();
            self::fail('Die Sitemap hätte bei scheiternder Abfrage nicht entstehen dürfen.');
        } catch (ConnectionException) {
            // erwartet
        }

        self::assertFalse($this->cache->hasItem(SitemapGenerator::CACHE_KEY), 'Es darf keine halbe Fassung gespeichert sein.');
    }

    /**
     * EC-01 · Liegt eine gespeicherte Fassung vor, übersteht die Sitemap einen Datenbankausfall.
     *
     * Eine bis zu 50 Minuten alte, VOLLSTÄNDIGE Fassung ist richtiger als ein Fehler. Falsch wäre
     * nur ein halber Stand — und den verhindert AK-11 oben.
     */
    public function testGespeicherteFassungUeberstehtEinenDatenbankausfall(): void
    {
        $vollstaendig = $this->erzeuger()->xml();

        $kaputt = $this->createStub(RestaurantRepository::class);
        $kaputt->method('findAllIdsAscending')->willThrowException(
            $this->createStub(ConnectionException::class),
        );

        $waehrendDesAusfalls = $this->erzeuger($kaputt)->xml();

        self::assertSame($vollstaendig, $waehrendDesAusfalls);
        self::assertSame(160, $this->dokument($waehrendDesAusfalls)->getElementsByTagNameNS(self::NS_SITEMAP, 'url')->length);
    }

    /** AK-08, AK-09 · Einmal erzeugt, wird die Fassung wiederverwendet, bis sie abläuft. */
    public function testDieFassungWirdGespeichertUndWiederverwendet(): void
    {
        $erzeuger = $this->erzeuger();
        $erste = $erzeuger->xml();

        self::assertTrue($this->cache->hasItem(SitemapGenerator::CACHE_KEY));
        self::assertSame($erste, $erzeuger->xml());
    }
}
