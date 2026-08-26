<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\AbstractWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Feature 02 – strukturelle Barrierefreiheit über alle öffentlichen Seiten.
 *
 * Trägt AK-54: Wer eine Seite hinzufügt, die gegen eine dieser Regeln verstößt
 * (kein Skip-Link, kein <main>-Sprungziel, nicht genau ein <h1>, falsches lang),
 * lässt diesen Prüflauf fehlschlagen. Die inhaltliche Prüfung (Kontrast,
 * Screenreader) übernimmt zusätzlich der axe-Lauf und die manuelle Matrix.
 */
final class AccessibilityStructureTest extends AbstractWebTestCase
{
    /** @return iterable<string, array{string}> */
    public static function publicRoutes(): iterable
    {
        yield 'home' => ['/'];
        yield 'restaurants' => ['/restaurants'];
        yield 'about' => ['/about'];
        yield 'partner' => ['/partner'];
        yield 'organisationen' => ['/organisationen'];
        yield 'open' => ['/open'];
        yield 'kriterien' => ['/criteria'];
        yield 'impressum' => ['/legal'];
        yield 'accessibility' => ['/accessibility'];
        yield 'login' => ['/login'];
        yield 'register' => ['/register'];
    }

    #[DataProvider('publicRoutes')]
    public function testExactlyOneH1(string $path): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.$path);

        self::assertResponseIsSuccessful();
        // AK-15: genau eine erste Überschriftenebene je Seite.
        self::assertCount(1, $crawler->filter('h1'), "Route {$path}: genau ein <h1> erwartet.");
    }

    #[DataProvider('publicRoutes')]
    public function testSkipLinkMainAndLang(string $path): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.$path);

        self::assertResponseIsSuccessful();
        // AK-01: erstes fokussierbares Element ist der Sprunglink zum Hauptinhalt.
        self::assertSame(
            '#hauptinhalt',
            $crawler->filter('body a')->first()->attr('href'),
            "Route {$path}: Skip-Link muss das erste <a> im <body> sein.",
        );
        // AK-05 / AK-31: das Sprungziel <main id="hauptinhalt"> existiert.
        self::assertCount(1, $crawler->filter('main#hauptinhalt'), "Route {$path}: <main id=hauptinhalt> fehlt.");
        // AK-28: Sprache am Wurzelelement.
        self::assertSame('de', $crawler->filter('html')->attr('lang'), "Route {$path}: falsches oder fehlendes lang.");
    }
}
