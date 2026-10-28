<?php

declare(strict_types=1);

namespace App\Tests\Functional\Sponsor;

use App\Repository\RestaurantRepository;
use App\Seo\SeoRegistry;
use App\Sponsor\SponsorDirectory;
use App\Tests\AbstractWebTestCase;
use App\Tests\Support\Sponsor\TestSponsors;
use App\Usage\CollectPayloadNormalizer;
use App\Usage\UsageEventCatalogue;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Feature 15 · Prüfläufe über das ganze Feature (T19): Sponsoren ändern nichts an
 * Restaurants, nennen keinen Betrag, laden nichts von fremden Servern — und die alten
 * Formate sind von jeder öffentlichen Seite verschwunden.
 */
final class SponsorIntegrityTest extends AbstractWebTestCase
{
    private const array SPRACHEN = ['lb', 'de', 'fr', 'en', 'pt'];

    /** Was eine Seite liefert, mit oder ohne Sponsoren — ohne den Footer. */
    private function hauptinhalt(string $pfad, bool $mitSponsoren): string
    {
        $client = static::createClient();
        TestSponsors::use($client, $mitSponsoren ? TestSponsors::all() : []);
        $client->request('GET', $pfad);
        self::assertResponseIsSuccessful();
        $antwort = (string) $client->getResponse()->getContent();

        if (str_starts_with((string) $client->getResponse()->headers->get('Content-Type'), 'application/json')) {
            return $antwort;
        }

        return (new Crawler($antwort))->filter('main')->html();
    }

    /** @return iterable<string, array{string}> */
    public static function restaurantseiten(): iterable
    {
        yield 'Startseite' => ['/de/'];
        yield 'Liste nach Bewertung' => ['/de/restaurants?sort=rating'];
        yield 'Liste nach Name' => ['/de/restaurants?sort=name'];
        yield 'Liste neueste' => ['/de/restaurants?sort=newest'];
        yield 'Liste gefiltert' => ['/de/restaurants?wheelchair=1&toilet=1'];
        yield 'Liste Seite 2' => ['/de/restaurants?page=2'];
        yield 'Detailseite' => ['DETAIL'];
        yield 'Datensatz' => ['/open/dataset.json'];
    }

    /** AK-08 · Mit und ohne Sponsoren dieselben Einträge, dieselbe Reihenfolge, dieselben Werte. */
    #[DataProvider('restaurantseiten')]
    public function testAk08SponsorenAendernNichtsAnRestaurants(string $pfad): void
    {
        if ('DETAIL' === $pfad) {
            static::createClient();
            $pfad = '/de/restaurants/'.static::getContainer()->get(RestaurantRepository::class)->findAllIdsAscending()[0];
            static::ensureKernelShutdown();
        }

        $ohne = $this->hauptinhalt($pfad, false);
        static::ensureKernelShutdown();
        $mit = $this->hauptinhalt($pfad, true);

        self::assertSame($ohne, $mit);
    }

    /** AK-09 · Im Inhalt der Detailseite kein Logo, kein Name, kein Verweis. */
    public function testAk09DetailseiteOhneSponsorImInhalt(): void
    {
        $client = static::createClient();
        $id = $client->getContainer()->get(RestaurantRepository::class)->findAllIdsAscending()[0];
        TestSponsors::use($client, TestSponsors::all());
        $main = $client->request('GET', '/de/restaurants/'.$id)->filter('main');

        foreach (['Acme', 'Ëmil', '/sponsoren', '/assets/images/sponsors/'] as $spur) {
            self::assertStringNotContainsString($spur, $main->html());
        }
    }

    /** @return iterable<string, array{string}> */
    public static function sprachen(): iterable
    {
        foreach (self::SPRACHEN as $sprache) {
            yield $sprache => [$sprache];
        }
    }

    /**
     * AK-14 · Kein Betrag und keine Laufzeitart bei einem einzelnen Sponsor — im Footer, auf
     * den Sponsorenseiten und im Faktenblatt. Der allgemeine Paketsatz auf Übersicht und
     * Unternehmensseite nennt „monatlich oder jährlich“ für alle und ist deshalb ausgenommen.
     */
    #[DataProvider('sprachen')]
    public function testAk14KeinBetragUndKeineLaufzeitBeimSponsor(string $sprache): void
    {
        $verraeterisch = '/€|\bEUR\b|\d+[,.]\d{2}|monatlich|jährlich|all Mount|all Joer|\bmensuel|\bannuel|\bmonthly\b|\byearly\b|\bmensal\b|\banual\b/iu';
        $stellen = [];

        $client = static::createClient();
        TestSponsors::use($client, TestSponsors::all());
        $client->disableReboot();
        $stellen['Footer'] = $client->request('GET', "/$sprache/")->filter('footer section[aria-labelledby="footer-sponsoren"]')->text();
        foreach (['acme', 'ecole', 'ehemals'] as $slug) {
            $stellen["Seite $slug"] = $client->request('GET', "/$sprache/sponsoren/$slug")->filter('main')->text();
        }
        $stellen['Faktenblatt'] = $client->request('GET', "/$sprache/presse")->filter('#fakten dl')->text();

        foreach ($stellen as $wo => $text) {
            self::assertDoesNotMatchRegularExpression($verraeterisch, $text, "$wo ($sprache) nennt einen Betrag oder eine Laufzeit.");
        }
    }

    /**
     * AK-34 · Die alten Sponsoring-Formate und „mit Ihrem Team“ stehen auf keiner
     * öffentlichen Seite mehr als Angebot — geprüft über alle festen Seiten der Sitemap in
     * allen fünf Sprachen. Die Wortlaute stammen aus den Katalogen vor Feature 15.
     */
    public function testAk34AlteFormateAufKeinerOeffentlichenSeite(): void
    {
        $alt = [
            'lb' => ['Inclusion-Boxe finanzéieren', 'Mataarbechter als Testpersounen', 'Eng Gemeng matfinanzéieren', 'Iwwersetzungen a Workshoppen', 'mat Ärem Team'],
            'de' => ['Inclusion-Boxen finanzieren', 'Mitarbeitende als Testpersonen', 'Eine Gemeinde mitfinanzieren', 'Übersetzungen und Workshops', 'mit Ihrem Team', 'als Unterstützer genannt'],
            'fr' => ['Financer des Inclusion Boxes', 'Collaborateurs comme testeurs', 'Cofinancer une commune', 'Traductions et ateliers', 'avec votre équipe'],
            'en' => ['Fund Inclusion Boxes', 'Employees as test participants', 'Co-fund a municipality', 'Translations and workshops', 'with your team'],
            'pt' => ['Financiar Inclusion Boxes', 'Colaboradores como participantes nos testes', 'Cofinanciar uma comuna', 'Traduções e workshops', 'com a sua equipa'],
        ];

        $client = static::createClient();
        $client->disableReboot();
        $router = static::getContainer()->get('router');
        $funde = [];

        foreach ((new SeoRegistry())->fixedPages() as $seite) {
            foreach (self::SPRACHEN as $sprache) {
                $pfad = $router->generate($seite->route, [...$seite->parameters, '_locale' => $sprache]);
                $client->request('GET', $pfad);
                $html = (string) $client->getResponse()->getContent();
                foreach ($alt[$sprache] as $satz) {
                    if (str_contains($html, htmlspecialchars($satz, \ENT_QUOTES)) || str_contains($html, $satz)) {
                        $funde[] = "$pfad: „{$satz}“";
                    }
                }
            }
        }

        self::assertSame([], $funde);
    }

    /** @return iterable<string, array{string}> */
    public static function sponsorseiten(): iterable
    {
        yield 'Footer' => ['/de/'];
        yield 'Übersicht' => ['/de/sponsoren'];
        yield 'Sponsorenseite' => ['/de/sponsoren/acme'];
        yield 'Unternehmensseite' => ['/de/organisationen/unternehmen'];
    }

    /** AK-42 · Alles, was diese Seiten laden, kommt von endlech.lu. */
    #[DataProvider('sponsorseiten')]
    public function testAk42NichtsVonFremdenServern(string $pfad): void
    {
        $client = static::createClient();
        TestSponsors::use($client, TestSponsors::all());
        $crawler = $client->request('GET', $pfad);

        $quellen = [
            ...$crawler->filter('img[src], script[src], iframe[src], source[src]')->each(static fn (Crawler $n): string => (string) $n->attr('src')),
            ...$crawler->filter('link[rel="stylesheet"], link[rel="preload"], link[rel="modulepreload"], link[rel="icon"]')->each(static fn (Crawler $n): string => (string) $n->attr('href')),
        ];
        self::assertNotSame([], $quellen);

        $fremd = array_values(array_filter($quellen, static fn (string $q): bool => !str_starts_with($q, '/') || str_starts_with($q, '//')));
        self::assertSame([], $fremd, "Fremde Ressourcen auf $pfad.");
    }

    /** AK-46 · Kein Messauslöser am Website-Link und an den Logos — gezählt wird nur der Seitenaufruf. */
    public function testAk46KeinMessausloeserAmWebsiteLink(): void
    {
        $client = static::createClient();
        TestSponsors::use($client, TestSponsors::all());
        $crawler = $client->request('GET', '/de/sponsoren/acme');

        $links = $crawler->filter('a[rel~="sponsored"], footer section[aria-labelledby="footer-sponsoren"] a');
        self::assertGreaterThan(1, $links->count());
        $links->each(static function (Crawler $a): void {
            foreach ($a->getNode(0)->attributes as $attribut) {
                self::assertStringStartsNotWith('data-', $attribut->name, 'Messauslöser oder Stimulus-Anbindung an einem Sponsorenlink.');
            }
        });
    }

    /**
     * Überarbeitung · AK-55 · Instagram, Facebook und LinkedIn kommen auf der Seite nur als Ziel eines
     * bezahlten Links vor — kein eingebetteter Beitrag, kein Symbol, kein Skript von dort, und kein
     * Messauslöser am Link (AK-46 prüft die Attribute).
     */
    public function testAk55PlattformenNurAlsLinkziel(): void
    {
        $client = static::createClient();
        TestSponsors::use($client, TestSponsors::all());
        $crawler = $client->request('GET', '/de/sponsoren/acme');

        $alle = $crawler->filter('[src], [href], [srcset], [data-src]')->each(static fn (Crawler $n): string => $n->nodeName().' '.implode(' ', array_filter([
            $n->attr('src'), $n->attr('href'), $n->attr('srcset'), $n->attr('data-src'),
        ])));
        $plattform = array_values(array_filter($alle, static fn (string $z): bool => 1 === preg_match('#(instagram|facebook|linkedin)\.com#i', $z)));

        self::assertCount(2, $plattform, 'Acme hat zwei Profile.');
        foreach ($plattform as $zeile) {
            self::assertStringStartsWith('a ', $zeile, "Plattform nicht als Link eingebunden: $zeile");
        }
        self::assertCount(2, $crawler->filter('a[rel~="sponsored"][href*="instagram.com"], a[rel~="sponsored"][href*="linkedin.com"]'));
    }

    /** AK-47 · Der Pfad einer Sponsorenseite erreicht die Nutzungsmessung — und gehört nur diesem Sponsor. */
    public function testAk47SponsorenpfadPassiertDieMessung(): void
    {
        $normalizer = new CollectPayloadNormalizer(new UsageEventCatalogue(), 'kennung', 'https://endlech.lu');

        $ergebnis = $normalizer->normalize(['type' => 'event', 'payload' => [
            'website' => 'kennung',
            'hostname' => 'endlech.lu',
            'url' => 'https://endlech.lu/de/sponsoren/acme',
            'title' => 'Acme',
            'screen' => '390x844',
            'language' => 'de-LU',
        ]]);

        self::assertSame('/de/sponsoren/acme', $ergebnis['payload']['url'] ?? null);
    }

    /** AK-45 · Sponsoren kennen die Warteliste nicht — in keiner Richtung. */
    public function testAk45KeinBezugZurWarteliste(): void
    {
        $abhaengigkeiten = array_map(
            static fn (\ReflectionParameter $p): string => (string) $p->getType(),
            (new \ReflectionMethod(SponsorDirectory::class, '__construct'))->getParameters(),
        );
        self::assertSame(['App\Sponsor\SponsorSource', 'Psr\Clock\ClockInterface'], $abhaengigkeiten);

        foreach (glob(\dirname(__DIR__, 3).'/src/Sponsor/*.php') ?: [] as $datei) {
            self::assertStringNotContainsString('Waitlist', (string) file_get_contents($datei), basename($datei));
        }
    }
}
