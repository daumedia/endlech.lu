<?php

namespace App\Tests\Integration\Twig;

use App\Seo\SeoRegistry;
use App\Seo\SeoUrlBuilder;
use App\Twig\SeoExtension;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/** Feature 10 · Was der Seitenkopf aus der laufenden Anfrage macht. */
final class SeoExtensionTest extends KernelTestCase
{
    /**
     * @param array<string, string|int> $parameter
     * @param array<string, mixed>      $query
     */
    private function erweiterung(?string $route, array $parameter = [], array $query = []): SeoExtension
    {
        self::bootKernel();
        $stack = new RequestStack();
        if (null !== $route) {
            $request = new Request($query, [], ['_route' => $route, '_route_params' => $parameter]);
            $request->setLocale((string) ($parameter['_locale'] ?? 'lb'));
            $stack->push($request);
        }

        return new SeoExtension(
            $stack,
            new SeoRegistry(),
            new SeoUrlBuilder(static::getContainer()->get('router'), 'https://endlech.lu', ['lb', 'de', 'fr', 'en', 'pt']),
        );
    }

    /** AK-12 · Eine Seite aus dem Verzeichnis nennt ihre Adresse. */
    public function testVerzeichnisseiteBekommtIhrenCanonicalVerweis(): void
    {
        self::assertSame('https://endlech.lu/de/about', $this->erweiterung('app_about', ['_locale' => 'de'])->canonicalUrl());
        self::assertSame(
            'https://endlech.lu/fr/restaurants/5',
            $this->erweiterung('app_restaurant_show', ['_locale' => 'fr', 'id' => 5])->canonicalUrl(),
        );
    }

    /** Eine ausgeschlossene Seite bekommt keinen canonical-Verweis — wohl aber Sprachverweise wie bisher. */
    public function testAnmeldeseiteOhneCanonicalAberMitSprachverweisen(): void
    {
        $e = $this->erweiterung('app_login', ['_locale' => 'de']);

        self::assertNull($e->canonicalUrl());
        self::assertSame('https://endlech.lu/en/login', $e->alternateUrls()['en']);
    }

    /** AK-13, AK-14 · Seitenzahl bleibt in canonical UND Sprachverweisen, Sortierung fällt weg. */
    public function testFolgeseiteBehaeltIhreSeitenzahlUeberall(): void
    {
        $e = $this->erweiterung('app_restaurant_index', ['_locale' => 'fr'], ['page' => '2', 'sort' => 'name']);

        self::assertSame('https://endlech.lu/fr/restaurants?page=2', $e->canonicalUrl());
        self::assertSame('https://endlech.lu/de/restaurants?page=2', $e->alternateUrls()['de']);
        self::assertSame('https://endlech.lu/lb/restaurants?page=2', $e->alternateUrls()['x-default']);
    }

    /**
     * BF-147 · Eine Seite, die nicht blättert, verliert die Seitenzahl — in canonical UND
     * Sprachverweisen, auch auf der Detailseite und auf einer ausgeschlossenen Seite.
     */
    public function testSeiteOhneBlaetternVerliertDieSeitenzahl(): void
    {
        $ueber = $this->erweiterung('app_about', ['_locale' => 'de'], ['page' => '2']);
        self::assertSame('https://endlech.lu/de/about', $ueber->canonicalUrl());
        self::assertSame('https://endlech.lu/fr/about', $ueber->alternateUrls()['fr']);
        self::assertSame('https://endlech.lu/lb/about', $ueber->alternateUrls()['x-default']);

        $detail = $this->erweiterung('app_restaurant_show', ['_locale' => 'de', 'id' => 1], ['page' => '7']);
        self::assertSame('https://endlech.lu/de/restaurants/1', $detail->canonicalUrl());

        $anmeldung = $this->erweiterung('app_login', ['_locale' => 'de'], ['page' => '2']);
        self::assertSame('https://endlech.lu/en/login', $anmeldung->alternateUrls()['en']);
    }

    /** BF-147 · Die Board-Übersicht blättert ebenfalls — dort bleibt die Seitenzahl wie auf der Restaurantliste. */
    public function testBoardUebersichtBehaeltIhreSeitenzahl(): void
    {
        $e = $this->erweiterung('app_board_index', ['_locale' => 'de'], ['page' => '3', 'sort' => 'new']);

        self::assertSame('https://endlech.lu/de/community/ideen?page=3', $e->canonicalUrl());
        self::assertSame('https://endlech.lu/en/community/ideen?page=3', $e->alternateUrls()['en']);
    }

    public function testOhneAnfrageOderRouteNichts(): void
    {
        $e = $this->erweiterung(null);

        self::assertNull($e->canonicalUrl());
        self::assertSame([], $e->alternateUrls());
    }

    /** Die Weiterleitung von `/` ist keine Seite. */
    public function testWurzelweiterleitungOhneSprachverweise(): void
    {
        self::assertSame([], $this->erweiterung('app_root')->alternateUrls());
    }
}
