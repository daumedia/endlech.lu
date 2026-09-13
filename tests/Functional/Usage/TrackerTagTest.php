<?php

namespace App\Tests\Functional\Usage;

use App\Tests\AbstractWebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Feature 11 · Das Zählskript im Seitenkopf, über HTTP.
 *
 * Im Test steht in `.env.test` eine Platzhalter-Kennung, damit das Skript gerendert wird; ein
 * Zähl-Eingang ist nicht gesetzt, es geht also nie etwas an eine echte Instanz.
 */
final class TrackerTagTest extends AbstractWebTestCase
{
    private function skript(Crawler $seite): Crawler
    {
        return $seite->filter('script[src^="/zaehler.js"]');
    }

    /** AK-01, AK-03, AK-09, AK-22, AK-24 · Das Skript mit allen Schutzattributen. */
    public function testRestaurantlisteTraegtDasSkriptMitAllenAttributen(): void
    {
        $client = static::createClient();
        $skript = $this->skript($client->request('GET', self::LOCALE.'/restaurants'));

        self::assertResponseIsSuccessful();
        self::assertCount(1, $skript);
        self::assertSame('/zaehler.js?v=3.3.1', $skript->attr('src'));
        self::assertSame('00000000-0000-4000-8000-000000000011', $skript->attr('data-website-id'));
        self::assertSame('endlech.lu', $skript->attr('data-domains'));
        self::assertSame('true', $skript->attr('data-exclude-search'));
        self::assertSame('true', $skript->attr('data-exclude-hash'));
        self::assertSame('true', $skript->attr('data-do-not-track'));
        self::assertSame('endlechNutzungVorVersand', $skript->attr('data-before-send'));
        self::assertNotNull($skript->attr('defer'));
    }

    /**
     * AK-24 · Kein Skript und kein Stylesheet von einem anderen Host. Verweise (canonical,
     * Sprachverweise, Links auf Restaurant-Websites) laden nichts und zählen nicht.
     */
    public function testKeineFremdeRessourceImSeitenkopf(): void
    {
        $client = static::createClient();
        $seite = $client->request('GET', self::LOCALE.'/restaurants');

        $quellen = [
            ...$seite->filter('script[src]')->each(static fn (Crawler $k): string => (string) $k->attr('src')),
            ...$seite->filter('link[rel="stylesheet"]')->each(static fn (Crawler $k): string => (string) $k->attr('href')),
        ];

        self::assertNotSame([], $quellen);
        foreach ($quellen as $quelle) {
            self::assertStringStartsWith('/', $quelle, $quelle);
            self::assertStringStartsNotWith('//', $quelle, $quelle);
        }
    }

    /** AK-08 · Anmeldung und Registrierung werden gemessen. */
    public function testAnmeldungUndRegistrierungTragenDasSkript(): void
    {
        $client = static::createClient();

        self::assertCount(1, $this->skript($client->request('GET', self::LOCALE.'/login')));
        self::assertCount(1, $this->skript($client->request('GET', self::LOCALE.'/register')));
    }

    /** AK-06 · Verwaltung und Profil nie. */
    public function testVerwaltungUndProfilTragenKeinSkript(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $this->loginAs($client, 'admin@endlech.lu');

        $verwaltung = $client->request('GET', self::LOCALE.'/admin');
        self::assertResponseIsSuccessful();
        self::assertCount(0, $this->skript($verwaltung));

        $profil = $client->request('GET', self::LOCALE.'/profile');
        self::assertResponseIsSuccessful();
        self::assertCount(0, $this->skript($profil));
    }

    /** AK-07 · Eine Token-Seite, die tatsächlich eine Seite rendert, trägt kein Skript. */
    public function testTokenSeiteTraegtKeinSkript(): void
    {
        $client = static::createClient();
        $seite = $client->request('GET', self::LOCALE.'/app/abmelden/'.str_repeat('ab', 32));

        self::assertResponseIsSuccessful();
        self::assertCount(0, $this->skript($seite));
    }

    /** Das Skript liegt als Datei vor und wird vom Webserver ausgeliefert, nicht von PHP. */
    public function testZaehlskriptIstEineDateiUndKeineRoute(): void
    {
        self::assertFileExists(__DIR__.'/../../../public/zaehler.js');

        $client = static::createClient();
        $client->request('GET', '/zaehler.js');
        self::assertResponseStatusCodeSame(404, 'Der Symfony-Kernel kennt /zaehler.js nicht — die Datei liefert der Webserver aus.');
    }
}
