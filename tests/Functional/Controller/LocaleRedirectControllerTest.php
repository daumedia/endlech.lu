<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\AbstractWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;

/**
 * `/` und die sprachfreien Kurzlinks leiten in die Sprache des Besuchers (2026-09-15).
 *
 * Reihenfolge: gewählte Sprache (Sitzung) → Browsersprache → Luxemburgisch. Vorher führten alle sieben
 * Adressen fest auf `/lb/…` — beim Release v2026.09.15 nachgemessen, auch für Französisch.
 */
final class LocaleRedirectControllerTest extends AbstractWebTestCase
{
    /**
     * ⚠ „ohne Kopfzeile" heißt hier LEER, nicht weggelassen: Symfonys Testclient setzt sonst von sich aus
     * `Accept-Language: en-us,en;q=0.5` (Vorgabe in `Request::create()`), und der Fall landete auf `/en/`.
     *
     * @return iterable<string, array{string, string, string}> Pfad, Accept-Language, erwartetes Ziel
     */
    public static function faelle(): iterable
    {
        yield 'Portugiesisch aus Portugal' => ['/', 'pt-PT,pt;q=0.9,en;q=0.8', '/pt/'];
        yield 'Deutsch aus Luxemburg' => ['/', 'de-LU,de;q=0.9,fr;q=0.8', '/de/'];
        yield 'Französisch' => ['/', 'fr-FR,fr;q=0.9', '/fr/'];
        yield 'Englisch' => ['/', 'en-GB,en;q=0.9', '/en/'];
        yield 'Luxemburgisch' => ['/', 'lb-LU,lb;q=0.9,de;q=0.8', '/lb/'];
        yield 'nicht angeboten → Vorgabe' => ['/', 'ja-JP,ja;q=0.9', '/lb/'];
        yield 'ohne Sprachangabe (Crawler) → Vorgabe' => ['/', '', '/lb/'];
        yield 'Kurzlink /open' => ['/open', 'pt-PT,pt;q=0.9', '/pt/open'];
        yield 'Kurzlink /vergleich' => ['/vergleich', 'fr-FR,fr;q=0.9', '/fr/vergleich'];
        yield 'Kurzlink /presse/ ohne Hilfsparameter' => ['/presse/', 'en-GB,en;q=0.9', '/en/presse'];
        yield 'Kurzlink /roadmap' => ['/roadmap', 'de-DE,de;q=0.9', '/de/roadmap'];
        yield 'Kurzlink /changelog' => ['/changelog', 'pt-BR,pt;q=0.9', '/pt/changelog'];
        yield 'Kurzlink /app' => ['/app', 'fr-LU,fr;q=0.9', '/fr/app'];
    }

    #[DataProvider('faelle')]
    public function testLeitetInDieSpracheDesBesuchers(string $pfad, string $sprache, string $ziel): void
    {
        $client = static::createClient();
        $client->request('GET', $pfad, server: ['HTTP_ACCEPT_LANGUAGE' => $sprache]);

        self::assertResponseStatusCodeSame(Response::HTTP_FOUND, 'Immer 302 — ein 301 bliebe in fremden Browsern stehen.');
        self::assertResponseRedirects($ziel);
        self::assertContains('Accept-Language', $client->getResponse()->getVary());
    }

    /** Wer eine Sprache gewählt hat, bekommt sie wieder — auch wenn der Browser etwas anderes sagt. */
    public function testGewaehlteSpracheGehtVorBrowsersprache(): void
    {
        $client = static::createClient();
        $client->request('GET', '/fr/about', server: ['HTTP_ACCEPT_LANGUAGE' => 'pt-PT,pt;q=0.9']);
        self::assertResponseIsSuccessful();

        $client->request('GET', '/', server: ['HTTP_ACCEPT_LANGUAGE' => 'pt-PT,pt;q=0.9']);

        self::assertResponseRedirects('/fr/');
    }

    /**
     * Ein Aufruf von `/` schreibt keine Sprache fest, die niemand gewählt hat. Mit `_locale: lb` in den
     * Routenvorgaben hätte schon der erste Aufruf Luxemburgisch in die Sitzung geschrieben.
     */
    public function testDieWeiterleitungSelbstIstKeineSprachwahl(): void
    {
        $client = static::createClient();
        $client->request('GET', '/', server: ['HTTP_ACCEPT_LANGUAGE' => 'de-DE,de;q=0.9']);
        self::assertResponseRedirects('/de/');

        $client->request('GET', '/', server: ['HTTP_ACCEPT_LANGUAGE' => 'de-DE,de;q=0.9']);
        self::assertResponseRedirects('/de/');
    }
}
