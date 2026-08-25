<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\AbstractWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * BF-68: Ein Query-Parameter namens `_locale` durfte den Sprachumschalter kapern.
 *
 * Drei Wirkungen hatte das, und alle drei stehen hier als Test: ein Open Redirect
 * auf einen fremden Host, ein auslösbarer 500er auf jeder öffentlichen Seite, und
 * ein Umschalter, der dreimal dieselbe Sprache anbot.
 */
final class LanguageSwitcherTest extends AbstractWebTestCase
{
    /**
     * Kein Umschalter-Link darf je auf einen fremden Host zeigen.
     *
     * `///fremd.example/…` löst der Browser nach WHATWG zu `http://fremd.example/…`
     * auf — drei Schrägstriche genügen.
     */
    #[DataProvider('angriffswerte')]
    public function testKeinLinkVerlaesstDieEigeneHerkunft(string $locale): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/restaurants?_locale='.urlencode($locale));

        self::assertResponseIsSuccessful();

        $links = $crawler->filter('[data-language-switcher-target="menu"] a')->extract(['href']);
        self::assertNotEmpty($links, 'Der Umschalter rendert keine Links mehr.');

        foreach ($links as $href) {
            self::assertMatchesRegularExpression(
                '#^/(lb|de|fr|en)/#',
                $href,
                sprintf('Umschalter-Link "%s" ist kein eigener Pfad.', $href),
            );
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function angriffswerte(): iterable
    {
        yield 'protokollrelativer Host' => ['//qa-fremd.example/de'];
        yield 'unbekannter Sprachcode' => ['xx'];
        yield 'Sprachcode mit Zusatz' => ['de-DE'];
        yield 'Grossschreibung' => ['DE'];
        yield 'Pfadanteil' => ['de/../..'];
        yield 'Anführungszeichen' => ['de"><script>alert(1)</script>'];
        yield 'gültige Sprache' => ['en'];
    }

    /**
     * Der Parameter darf keine Seite mehr in einen Serverfehler kippen.
     */
    #[DataProvider('oeffentlicheSeiten')]
    public function testSeiteBleibtErreichbarTrotzLocaleImQuery(string $pfad): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE.$pfad.'?_locale=xx');

        self::assertResponseIsSuccessful(sprintf('%s kippt bei ?_locale=xx.', $pfad));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function oeffentlicheSeiten(): iterable
    {
        yield 'Startseite' => ['/'];
        yield 'Restaurantliste' => ['/restaurants'];
        yield 'Anmeldung' => ['/login'];
        yield 'Registrierung' => ['/register'];
        yield 'Transparenzseite' => ['/open'];
        yield 'Partnerseite' => ['/partner'];
        yield 'Organisationen' => ['/organisationen'];
        yield 'Über uns' => ['/about'];
        yield 'Impressum' => ['/legal'];
    }

    /**
     * Der Umschalter bietet die drei anderen Sprachen an — nicht dreimal dieselbe.
     */
    public function testUmschalterBietetDreiVerschiedeneSprachen(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/restaurants?_locale=en');

        $ziele = [];
        foreach ($crawler->filter('[data-language-switcher-target="menu"] a')->extract(['href']) as $href) {
            preg_match('#^/([a-z]{2})/#', $href, $treffer);
            $ziele[] = $treffer[1] ?? '';
        }

        self::assertSame(['lb', 'fr', 'en'], $ziele);
    }

    /**
     * Die übrigen Query-Parameter überleben den Sprachwechsel — nur `_locale` nicht.
     */
    public function testFilterUeberlebenDenSprachwechsel(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/restaurants?sort=name&wheelchair=1&_locale=xx&page=2');

        $links = $crawler->filter('[data-language-switcher-target="menu"] a')->extract(['href']);

        foreach ($links as $href) {
            self::assertStringContainsString('sort=name', $href);
            self::assertStringContainsString('wheelchair=1', $href);
            self::assertStringContainsString('page=2', $href);
            self::assertStringNotContainsString('_locale=', $href);
        }
    }
}
