<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\AbstractWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * B25 · PWA und mobile Navigation — die Zusagen, die sich an der gerenderten
 * Antwort prüfen lassen (QA vom 2026-09-12).
 *
 * ⚠ **Geprüft wird die Antwort, nicht die Vorlage.** Ein Lauf, der
 * `base.html.twig` als Zeichenkette durchsucht, träfe auch einen Kommentar —
 * derselbe Fehler, der BF-125 ausmachte.
 *
 * ⚠ **Was hier fehlt, fehlt mit Grund:** Das Verhalten des Service Workers
 * (AK-04 bis AK-10) lässt sich mit PHPUnit nicht prüfen — es läuft im Browser.
 * Dafür gibt es `qa/B25/sw-verhalten.mjs`: Das Skript lädt `public/sw.js`, ruft
 * die Handler mit nachgebauten Ereignissen auf und protokolliert, was der Worker
 * tatsächlich tut. Aufruf: `node qa/B25/sw-verhalten.mjs`.
 */
final class PwaTest extends AbstractWebTestCase
{
    /**
     * AK-01 · Der Dokumentkopf trägt Manifest, Themenfarbe und die iOS-Angaben.
     */
    public function testAk01KopfTraegtDiePwaAngaben(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/');

        self::assertResponseIsSuccessful();

        self::assertCount(1, $crawler->filter('link[rel="manifest"]'), 'AK-01: Ohne Manifest ist die Seite nicht installierbar.');
        self::assertSame('#0891b2', $crawler->filter('meta[name="theme-color"]')->attr('content'));

        foreach (['apple-mobile-web-app-capable', 'apple-mobile-web-app-status-bar-style', 'apple-mobile-web-app-title', 'mobile-web-app-capable'] as $name) {
            self::assertCount(1, $crawler->filter('meta[name="'.$name.'"]'), "AK-01: <meta name=\"{$name}\"> fehlt.");
        }

        self::assertStringContainsString(
            'viewport-fit=cover',
            (string) $crawler->filter('meta[name="viewport"]')->attr('content'),
            'EC-04: Ohne viewport-fit=cover greift env(safe-area-inset-bottom) nicht.',
        );

        // Neun Größen: acht mit `sizes`, dazu der 180er ohne Angabe.
        self::assertCount(9, $crawler->filter('link[rel="apple-touch-icon"]'), 'AK-01: Es sollen neun Größen sein.');
    }

    /**
     * AK-02 · Das Manifest trägt Scope, Anzeigeart und die drei Icon-Einträge.
     */
    public function testAk02ManifestTraegtScopeUndIcons(): void
    {
        // ⚠ Über das Dateisystem, nicht über den Testclient: Die PWA-Dateien sind
        // **statisch** und werden vom Webserver ausgeliefert — der Kernel kennt
        // keine Route dafür, und `$client->request('/manifest.webmanifest')` endet
        // mit „No route found". Ihre Erreichbarkeit über HTTP gehört deshalb in die
        // Messung am laufenden Server (QA-Bericht), ihr Inhalt hierher.
        $manifest = json_decode(
            (string) file_get_contents(\dirname(__DIR__, 2).'/public/manifest.webmanifest'),
            true,
            512,
            \JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($manifest);

        self::assertSame('/', $manifest['start_url']);
        self::assertSame('/', $manifest['scope'], 'AK-02: Der Scope / ist die Voraussetzung für den Worker-Scope.');
        self::assertSame('standalone', $manifest['display']);
        self::assertSame('portrait', $manifest['orientation']);

        $icons = array_map(
            static fn (array $i) => ($i['sizes'] ?? '').' '.($i['purpose'] ?? 'any'),
            $manifest['icons'],
        );
        self::assertContains('192x192 any', $icons);
        self::assertContains('512x512 any', $icons);
        self::assertContains('512x512 maskable', $icons, 'AK-02/EC-03: Ohne maskable beschneidet Android das Symbol.');
    }

    /**
     * AK-11 / AK-12 · Die Leiste trägt vier Felder, und das aktive Feld ist
     * ausgezeichnet — mit Farbe **und** `aria-current`.
     */
    public function testAk11Ak12LeisteTraegtVierFelderUndKennzeichnetDasAktive(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/');

        $leiste = $crawler->filter('nav.fixed.bottom-0');
        self::assertCount(1, $leiste, 'AK-11: Die feste Leiste fehlt.');

        self::assertCount(4, $leiste->filter('a'), 'AK-11: Vier Felder — Start, Restaurants, Über uns, Profil bzw. Anmelden.');

        self::assertStringContainsString(
            'pb-[env(safe-area-inset-bottom)]',
            (string) $leiste->attr('class'),
            'AK-11: Ohne die Safe-Area-Polsterung liegt die Leiste auf der Home-Leiste des iPhones.',
        );

        $aktiv = $leiste->filter('a[aria-current="page"]');
        self::assertCount(1, $aktiv, 'AK-12: Genau ein Feld gehört zur aktuellen Seite.');
        // BF-215: cyan-700 (5,4 : 1), nicht cyan-600 — 12 px Schrift auf Weiß maß mit cyan-600 3,61 : 1.
        self::assertStringContainsString(' text-cyan-700', (string) $aktiv->attr('class'), 'AK-12: Farbe und aria-current gehören zusammen.');
        self::assertStringNotContainsString(' text-cyan-600', (string) $aktiv->attr('class'), 'BF-215: cyan-600 liegt unter 4,5 : 1.');
    }

    /**
     * AK-13 · Im Verwaltungsbereich erscheint die Leiste nicht.
     */
    public function testAk13KeineLeisteImVerwaltungsbereich(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $crawler = $client->request('GET', self::LOCALE.'/admin');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('nav.fixed.bottom-0'), 'AK-13: Die Leiste gehört nicht in die Verwaltung.');

        // Gegenprobe: auf einer öffentlichen Seite ist sie auch angemeldet da.
        $crawler = $client->request('GET', self::LOCALE.'/profile');
        self::assertCount(1, $crawler->filter('nav.fixed.bottom-0'));
    }

    /**
     * AK-15 · `<main>` trägt das Polster, damit der Inhalt nicht hinter der
     * Leiste liegt — und gibt es auf dem Desktop wieder frei.
     */
    public function testAk15MainTraegtDasPolsterFuerDieLeiste(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/');

        $klassen = (string) $crawler->filter('main')->attr('class');

        self::assertStringContainsString('pb-16', $klassen, 'AK-15: Ohne Polster liegt der Seitenfuß hinter der Leiste.');
        self::assertStringContainsString('md:pb-0', $klassen, 'AK-15: Auf dem Desktop gibt es keine Leiste und darf kein Polster bleiben.');
    }

    /**
     * EC-01 · Die PWA-Dateien liegen sprachfrei auf Wurzelebene. Mit
     * Sprachpräfix dürfen sie **nicht** erreichbar sein — der Scope `/` verlangt
     * die Wurzel, und ein zweiter Weg zur selben Datei wäre ein zweiter Scope.
     *
     * @return iterable<string, array{string}>
     */
    public static function pwaDateien(): iterable
    {
        yield 'Service Worker' => ['/sw.js'];
        yield 'Manifest' => ['/manifest.webmanifest'];
        yield 'Offline-Seite' => ['/offline.html'];
    }

    #[DataProvider('pwaDateien')]
    public function testEc01DateienLiegenSprachfreiAufWurzelebene(string $pfad): void
    {
        $public = \dirname(__DIR__, 2).'/public';

        self::assertFileExists($public.$pfad, sprintf('EC-01: %s muss auf Wurzelebene unter public/ liegen.', $pfad));
        self::assertGreaterThan(0, (int) filesize($public.$pfad), sprintf('%s ist leer.', $pfad));

        // ⚠ Und es darf **keine Route** geben, die dieselbe Datei unter dem
        // Sprachpräfix anbietet: Der Worker-Scope ist `/`, ein zweiter Weg wäre ein
        // zweiter Scope. Gefragt wird der Router, nicht der Webserver — dieselbe
        // Ursachenprüfung wie in `RouteDirectoryCollisionTest` (BF-100).
        $client = static::createClient();
        $matcher = $client->getContainer()->get('router');

        try {
            $matcher->match(self::LOCALE.$pfad);
            self::fail(sprintf('EC-01: Für %s existiert eine Route — damit gäbe es die Datei zweimal.', self::LOCALE.$pfad));
        } catch (\Symfony\Component\Routing\Exception\ResourceNotFoundException) {
            self::assertTrue(true);
        }
    }

    /**
     * BF-143 · Das Polster für die Leiste entsteht nur, wo die Leiste auch liegt.
     *
     * Vorher trug `<main>` `pb-16` auf jeder Route — auch im Verwaltungsbereich, wo
     * die Leiste bewusst fehlt (AK-13). Gemessen waren das 64 px leerer Streifen
     * unter dem Inhalt.
     */
    public function testBf143PolsterNurWoDieLeisteLiegt(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $crawler = $client->request('GET', self::LOCALE.'/admin');
        $klassen = (string) $crawler->filter('main')->attr('class');

        self::assertStringNotContainsString('pb-16', $klassen, 'BF-143: Ohne Leiste kein Polster.');
        self::assertCount(0, $crawler->filter('nav.fixed.bottom-0'), 'Vorbedingung: Hier gibt es keine Leiste.');

        // Gegenprobe: auf einer öffentlichen Seite bleibt beides.
        $crawler = $client->request('GET', self::LOCALE.'/profile');
        self::assertStringContainsString('pb-16', (string) $crawler->filter('main')->attr('class'));
        self::assertCount(1, $crawler->filter('nav.fixed.bottom-0'));
    }

    /**
     * BF-145 · Die Offline-Seite ist als luxemburgisch ausgezeichnet.
     *
     * Ihr Text ist luxemburgisch („Keng Internetverbindung"), und
     * `translation.yaml` setzt `default_locale: lb`. Mit `lang="de"` wandte ein
     * Screenreader deutsche Ausspracheregeln darauf an (WCAG 3.1.1).
     */
    public function testBf145OfflineSeiteIstAlsLuxemburgischAusgezeichnet(): void
    {
        $inhalt = (string) file_get_contents(\dirname(__DIR__, 2).'/public/offline.html');

        self::assertMatchesRegularExpression(
            '/<html[^>]*\slang="lb"/',
            $inhalt,
            'BF-145: Die Sprachauszeichnung muss zum Inhalt passen.',
        );
        self::assertStringContainsString(
            'Keng Internetverbindung',
            $inhalt,
            'Vorbedingung: Wer den Text übersetzt, zieht das lang-Attribut mit.',
        );
    }
}
