<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Comparison\Competitor;
use App\Tests\AbstractWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Routing\RouterInterface;

/**
 * Vergleichsseiten über HTTP (Feature 03).
 */
final class ComparisonControllerTest extends AbstractWebTestCase
{
    /** @return iterable<string, array{string}> */
    public static function slugs(): iterable
    {
        foreach (Competitor::cases() as $case) {
            yield $case->value => [$case->slug()];
        }
    }

    /** AK-02 */
    public function testDieUebersichtNenntAlleVergleiche(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/vergleich');

        self::assertResponseIsSuccessful();

        foreach (Competitor::cases() as $competitor) {
            self::assertStringContainsString(
                $competitor->brand(),
                $crawler->filter('main')->text(),
                $competitor->brand().' fehlt auf der Übersicht.',
            );
            self::assertGreaterThan(
                0,
                $crawler->filter('main a[href$="/vergleich/'.$competitor->slug().'"]')->count(),
                'Kein Link auf '.$competitor->slug().'.',
            );
        }
    }

    /** AK-03 */
    #[DataProvider('slugs')]
    public function testJedeVergleichsseiteAntwortet(string $slug): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE.'/vergleich/'.$slug);

        self::assertResponseIsSuccessful();
    }

    /** AK-04: fremder Slug → 404, nicht die Übersicht und kein Serverfehler. */
    public function testEinUnbekannterSlugErgibt404(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE.'/vergleich/foobar');

        self::assertResponseStatusCodeSame(404);
    }

    /**
     * EC-01: abweichende Schreibweise wird nicht stillschweigend korrigiert.
     *
     * `jaccede` steht bewusst in der Liste: Der Vergleich war vorgesehen und wurde
     * gestrichen, weil die Plattform seit dem 2. Juli 2026 nur noch ein statischer
     * Abzug ist. Käme die Adresse eines Tages wieder durch, wäre das ein Fehler,
     * den sonst niemand bemerkt.
     *
     * Ein angehängter Schrägstrich gehört NICHT hierher — `/vergleich/wheelmap/`
     * ist dieselbe Adresse, und Symfony leitet sie regulär um. Das als Fehler zu
     * werten hieße, Standardverhalten zu brechen.
     */
    public function testAbweichendeSchreibweiseErgibt404(): void
    {
        $client = static::createClient();

        foreach (['Google-Maps', 'google_maps', 'jaccede', 'wheelmapp', 'trip-advisor', '../admin'] as $slug) {
            $client->request('GET', self::LOCALE.'/vergleich/'.$slug);
            self::assertResponseStatusCodeSame(404, 'Slug "'.$slug.'" hätte 404 liefern müssen.');
        }
    }

    /**
     * AK-05: Der Sprachwechsel bleibt auf derselben Vergleichsseite.
     *
     * Das ist keine Selbstverständlichkeit, sondern die Folge davon, dass der Slug
     * in allen vier Sprachen derselbe ist. Ein übersetzter Slug ließe den
     * Sprachumschalter und die hreflang-Schleife auf jeder Seite der Website
     * werfen.
     */
    public function testDerSprachwechselBleibtAufDerSeite(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/de/vergleich/wheelmap');

        self::assertResponseIsSuccessful();
        self::assertGreaterThan(
            0,
            $crawler->filter('a[href="/fr/vergleich/wheelmap"]')->count(),
            'Kein Weg zur französischen Fassung derselben Seite.',
        );
    }

    /** AK-19: Jede der vier Adressen trägt einen eigenen Fenstertitel. */
    public function testAlleSeitenTragenVerschiedeneTitel(): void
    {
        $client = static::createClient();
        $titel = [];

        foreach (array_merge(['/vergleich'], array_map(
            static fn (Competitor $c): string => '/vergleich/'.$c->slug(),
            Competitor::cases(),
        )) as $pfad) {
            $crawler = $client->request('GET', self::LOCALE.$pfad);
            self::assertResponseIsSuccessful();
            $titel[$pfad] = trim($crawler->filter('title')->text());
        }

        self::assertSame(
            \count($titel),
            \count(array_unique($titel)),
            "Zwei Seiten tragen denselben Titel:\n".print_r($titel, true),
        );
    }

    /** AK-20, AK-21: Kurzbeschreibung und kanonische Adresse im Kopfbereich. */
    public function testKopfbereichTraegtBeschreibungUndKanonischeAdresse(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/fr/vergleich/tripadvisor');

        self::assertResponseIsSuccessful();

        $beschreibung = $crawler->filter('meta[name="description"]');
        self::assertCount(1, $beschreibung, 'Keine Kurzbeschreibung im Kopfbereich.');
        self::assertNotSame('', trim($beschreibung->attr('content') ?? ''));

        $canonical = $crawler->filter('link[rel="canonical"]');
        self::assertCount(1, $canonical, 'Keine kanonische Adresse.');
        self::assertStringEndsWith('/fr/vergleich/tripadvisor', (string) $canonical->attr('href'));
    }

    /**
     * AK-31: Der sprachfreie Kurzlink führt auf die Übersicht.
     *
     * Dieselbe Begründung wie bei /open: Die kurze Adresse steht in Mails und
     * Vorträgen und darf nicht an einer Sprachwahl scheitern.
     */
    public function testDerSprachfreieKurzlinkLeitetAufDieUebersicht(): void
    {
        $client = static::createClient();
        $client->request('GET', '/vergleich');

        self::assertResponseRedirects();
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertMatchesRegularExpression('#/(lb|de|fr|en)/vergleich$#', $client->getRequest()->getUri());
    }

    /**
     * Das Slug-Requirement der Route und das Enum müssen übereinstimmen.
     *
     * PHP-Attribute lassen keinen Methodenaufruf zu, das Muster steht deshalb von
     * Hand in der Route. Genau dieser Abgleich hat beim Streichen von Jaccede
     * gegriffen — ohne ihn wäre `/vergleich/jaccede` weiterhin durch den Regex
     * gekommen und erst im Controller auf 404 gelaufen.
     */
    public function testDasSlugMusterDerRouteEntsprichtDemEnum(): void
    {
        $router = static::getContainer()->get(RouterInterface::class);
        $route = $router->getRouteCollection()->get('app_comparison_show');

        self::assertNotNull($route);
        self::assertSame(
            Competitor::slugPattern(),
            $route->getRequirement('slug'),
            'Das Muster der Route und die Aufzählung sind auseinandergelaufen.',
        );
    }

    /** AK-01: Die Fußzeile führt den Bereich auf jeder öffentlichen Seite. */
    public function testDieFusszeileFuehrtDieVergleiche(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/');

        self::assertResponseIsSuccessful();

        foreach (Competitor::cases() as $competitor) {
            self::assertGreaterThan(
                0,
                $crawler->filter('footer a[href$="/vergleich/'.$competitor->slug().'"]')->count(),
                'Fußzeile ohne Link auf '.$competitor->slug().'.',
            );
        }

        self::assertGreaterThan(
            0,
            $crawler->filter('footer a[href$="/vergleich"]')->count(),
            'Fußzeile ohne Link auf die Übersicht.',
        );
    }

    /**
     * AK-15, AK-30: keine fremde Ressource, kein Logo eines Wettbewerbers.
     *
     * Das ist zugleich Marken- und Datenschutzentscheidung: Ein von einem fremden
     * Server nachgeladenes Logo gäbe die IP-Adresse jedes Besuchers weiter.
     */
    #[DataProvider('slugs')]
    public function testKeineFremdeRessource(string $slug): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE.'/vergleich/'.$slug);
        $html = (string) $client->getResponse()->getContent();

        preg_match_all('/(?:src|srcset)\s*=\s*"([^"]+)"/i', $html, $treffer);

        foreach ($treffer[1] as $quelle) {
            self::assertDoesNotMatchRegularExpression(
                '#^(https?:)?//#i',
                $quelle,
                'Ressource von einem fremden Server: '.$quelle,
            );
        }

        preg_match_all('/<link[^>]+href\s*=\s*"([^"]+)"/i', $html, $links);

        foreach ($links[1] as $href) {
            if (preg_match('#^https?://#i', $href) && !str_contains($href, 'localhost')) {
                self::assertMatchesRegularExpression(
                    '#^https?://localhost#i',
                    $href,
                    'Stylesheet oder Icon von einem fremden Server: '.$href,
                );
            }
        }
    }
}
