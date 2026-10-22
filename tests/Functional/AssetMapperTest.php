<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\AbstractWebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Auslieferung über AssetMapper (seit 2026-10-22, vorher Webpack Encore).
 *
 * Belegt an einer echten Seite, was beim Umbau schiefgehen kann, ohne dass ein
 * anderer Prüflauf es sieht: Die Seite funktioniert im Testclient auch dann,
 * wenn der Browser ihr JavaScript nie ausführen würde.
 *
 * ⚠ Setzt `typescript:build` voraus (lokal per `--watch`, in der CI vor PHPUnit):
 * Ohne die kompilierten Module unter `var/typescript/` scheitert schon das
 * Rendern von `importmap()`.
 */
final class AssetMapperTest extends AbstractWebTestCase
{
    /**
     * Template und `SecurityHeadersSubscriber` erzeugen ihre Hälfte in
     * verschiedenen Schichten. Weicht die Nonce im Skript von der in der
     * Richtlinie ab, meldet der Browser jeden Seitenaufruf als Verstoß — und
     * scharf geschaltet liefe kein Modul.
     */
    public function testJedesInlineSkriptTraegtDieNonceDerRichtlinie(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/legal');
        self::assertResponseIsSuccessful();

        $richtlinie = (string) $client->getResponse()->headers->get('Content-Security-Policy-Report-Only');
        self::assertMatchesRegularExpression("/(^|; )script-src 'self' 'nonce-[A-Za-z0-9+\\/]+={0,2}';/", $richtlinie);
        preg_match("/'nonce-([^']+)'/", $richtlinie, $treffer);

        $inline = $crawler->filter('script:not([src])');
        // Importmap und `import 'app'`.
        self::assertGreaterThanOrEqual(2, $inline->count());
        foreach ($inline as $skript) {
            self::assertSame($treffer[1], $skript->getAttribute('nonce'), 'Inline-Skript ohne passende Nonce: '.substr($skript->textContent, 0, 80));
        }
    }

    /**
     * ⚠ Jede Anfrage bekommt eine eigene Nonce. Eine feste wäre aus jedem
     * Seitenquelltext ablesbar und schützte vor nichts.
     */
    public function testNonceWechseltJeAnfrage(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE.'/legal');
        $erste = $client->getResponse()->headers->get('Content-Security-Policy-Report-Only');
        $client->request('GET', self::LOCALE.'/legal');

        self::assertNotSame($erste, $client->getResponse()->headers->get('Content-Security-Policy-Report-Only'));
    }

    /**
     * ⚠ Regression vom 2026-10-22, gefunden im Browser, nicht im Prüflauf: Turbo
     * vergleicht die Elemente mit `data-turbo-track` nach ihrem Quelltext und lädt
     * die Seite neu, sobald er abweicht. Leeren tut Turbo dabei nur das
     * `nonce`-Attribut. Der eingebaute Polyfill-Lader von `importmap()` trug die
     * Nonce aber in seinem Skripttext — jeder Klick wurde zum vollen Neuladen
     * (`turbo:reload` mit `tracked_element_mismatch`), Turbo Drive war tot.
     */
    public function testVerfolgteKopfelementeSindUeberAnfragenHinwegGleich(): void
    {
        $client = static::createClient();
        $erste = $this->verfolgteElemente($client->request('GET', self::LOCALE.'/legal'));
        $zweite = $this->verfolgteElemente($client->request('GET', self::LOCALE.'/about'));

        self::assertNotEmpty($erste);
        self::assertSame($erste, $zweite);
    }

    /**
     * Fremd-CSS zuerst, app.css zuletzt (wie unter Encore) — und alles von der
     * eigenen Herkunft. Der eingebaute Polyfill-Lader griffe ohne Eintrag in
     * importmap.php auf `ga.jspm.io` zurück; der eigene holt `es-module-shims`
     * lokal, mit Prüfsumme im Namen.
     */
    public function testStylesheetsUndModuleKommenVonDerEigenenHerkunft(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/legal');

        $stylesheets = $crawler->filter('link[rel="stylesheet"]')->each(static fn (Crawler $l): string => (string) $l->attr('href'));
        self::assertCount(3, $stylesheets);
        self::assertMatchesRegularExpression('#^/assets/vendor/tom-select/#', $stylesheets[0]);
        self::assertMatchesRegularExpression('#^/assets/vendor/glightbox/#', $stylesheets[1]);
        self::assertMatchesRegularExpression('#^/assets/styles/app-[\w-]+\.css$#', $stylesheets[2]);

        foreach ($crawler->filter('link[rel="modulepreload"]') as $verweis) {
            self::assertStringStartsWith('/assets/', $verweis->getAttribute('href'));
        }

        $html = (string) $client->getResponse()->getContent();
        self::assertStringNotContainsString('jspm.io', $html);

        $lader = $crawler->filter('script[data-shim]');
        self::assertCount(1, $lader);
        self::assertMatchesRegularExpression('#^/assets/es_module_shims_loader-[\w-]+\.js$#', (string) $lader->attr('src'));
        self::assertMatchesRegularExpression('#^/assets/vendor/es-module-shims/#', (string) $lader->attr('data-shim'));
    }

    /**
     * ⚠ Für jede CSS-Datei, die ein Modul importiert, schreibt AssetMapper ein
     * `data:`-Modul ins Importmap. Eine scharfe CSP ohne `script-src data:`
     * ließe daran den ganzen Modulbaum scheitern. Stylesheets gehören deshalb
     * als `<link>` in base.html.twig, nicht als `import` in ein Modul.
     */
    public function testKeinModulImportiertCss(): void
    {
        $wurzel = \dirname(__DIR__, 2).'/assets';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($wurzel, \FilesystemIterator::SKIP_DOTS));
        $treffer = [];

        foreach ($iterator as $datei) {
            $pfad = $datei->getPathname();
            if (str_contains($pfad, '/assets/vendor/') || !preg_match('/\.(ts|js)$/', $pfad)) {
                continue;
            }
            if (preg_match('/^\s*import\s+[\'"][^\'"]+\.css[\'"]/m', (string) file_get_contents($pfad))) {
                $treffer[] = substr($pfad, \strlen($wurzel) + 1);
            }
        }

        self::assertSame([], $treffer);
    }

    /**
     * Fehlt `app` im Importmap, rendert die Seite im Testclient trotzdem — der
     * Browser liefe nur ohne JavaScript, ohne dass ein anderer Prüflauf es merkt.
     */
    public function testImportmapFuehrtAppUndDenStimulusLader(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/legal');

        $daten = json_decode($crawler->filter('script[type="importmap"]')->text(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertMatchesRegularExpression('#^/assets/app-[\w-]+\.js$#', $daten['imports']['app']);
        self::assertArrayHasKey('@symfony/stimulus-bundle', $daten['imports']);
        self::assertArrayHasKey('@hotwired/turbo', $daten['imports']);
        self::assertSame("import 'app';", $crawler->filter('script[type="module"]')->text());
    }

    /**
     * Quelltext aller Kopfelemente, die Turbo verfolgt — mit geleertem `nonce`,
     * genau wie Turbo selbst vergleicht.
     *
     * @return list<string>
     */
    private function verfolgteElemente(Crawler $seite): array
    {
        return $seite->filter('head [data-turbo-track]')->each(static function (Crawler $element): string {
            $knoten = $element->getNode(0);
            \assert($knoten instanceof \DOMElement);
            if ($knoten->hasAttribute('nonce')) {
                $knoten->setAttribute('nonce', '');
            }

            return (string) $knoten->ownerDocument?->saveHTML($knoten);
        });
    }
}
