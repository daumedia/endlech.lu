<?php

declare(strict_types=1);

namespace App\Tests\Unit\Saved;

use App\Entity\Restaurant;
use App\Saved\PendingFavorite;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Loader\ClosureLoader;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Router;

/**
 * Feature 17, T08: Das Feld `ziel` kommt aus dem Formular und damit aus der Hand des
 * Absenders. Es darf **nie** zu einer fremden oder unerwarteten Adresse führen (kein offener
 * Redirect, Muster `BoardLegacyRedirectController`).
 */
final class PendingFavoriteTest extends TestCase
{
    private const string DETAIL = '/de/restaurants/12';

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function ziele(): iterable
    {
        yield 'Suche mit Filter und Seite bleibt' => ['/de/restaurants?wheelchair=1&page=2', '/de/restaurants?wheelchair=1&page=2'];
        yield 'Detailseite bleibt' => ['/de/restaurants/7', '/de/restaurants/7'];
        yield 'Favoriten mit Seite bleibt' => ['/de/gespeichert/favoriten?page=2', '/de/gespeichert/favoriten?page=2'];
        yield 'Liste: nur page überlebt' => ['/de/gespeichert/listen/5?page=3&x=<b>', '/de/gespeichert/listen/5?page=3'];
        yield 'Liste: ungültige Seite fällt weg' => ['/de/gespeichert/listen/5?page=abc', '/de/gespeichert/listen/5'];
        yield 'fremde Domain' => ['https://example.com/de/restaurants', self::DETAIL];
        yield 'protokollrelativ' => ['//example.com/de/restaurants', self::DETAIL];
        yield 'Backslash' => ['/\\example.com', self::DETAIL];
        yield 'javascript' => ['javascript:alert(1)', self::DETAIL];
        yield 'nicht erlaubte Route' => ['/de/admin', self::DETAIL];
        yield 'unbekannter Pfad' => ['/de/gibtsnicht', self::DETAIL];
        yield 'andere Sprache' => ['/fr/restaurants', self::DETAIL];
        yield 'leer' => ['', self::DETAIL];
        yield 'Steuerzeichen' => ["/de/restaurants\r\nLocation: https://example.com", self::DETAIL];
    }

    #[DataProvider('ziele')]
    public function testAk06Ak09ZielWirdNurAusErlaubtenRoutenUebernommen(string $ziel, string $erwartet): void
    {
        self::assertSame($erwartet, $this->dienst()->safeTarget($ziel, $this->lokal(12), 'de'));
    }

    public function testAk08MerkwunschLiegtInDerSitzungBisZumAbholen(): void
    {
        $sitzung = new Session(new MockArraySessionStorage());
        $dienst = $this->dienst();

        $dienst->remember($sitzung, $this->lokal(12));

        self::assertSame(12, $dienst->peek($sitzung));
        self::assertSame(12, $dienst->take($sitzung));
        self::assertNull($dienst->take($sitzung), 'Ein abgeholter Wunsch wird nicht zweimal eingelöst.');
    }

    public function testAk08NeuerWunschErsetztDenAlten(): void
    {
        $sitzung = new Session(new MockArraySessionStorage());
        $dienst = $this->dienst();

        $dienst->remember($sitzung, $this->lokal(12));
        $dienst->remember($sitzung, $this->lokal(13));

        self::assertSame(13, $dienst->take($sitzung));
    }

    public function testDieRequestMethodeDesRoutersBleibtUnveraendert(): void
    {
        $router = $this->router();
        $router->getContext()->setMethod('POST');

        (new PendingFavorite($router))->safeTarget('/de/gespeichert/favoriten', $this->lokal(12), 'de');

        self::assertSame('POST', $router->getContext()->getMethod());
    }

    private function dienst(): PendingFavorite
    {
        $router = $this->router();
        // Wie im Betrieb: Das Herz wird per POST abgeschickt, die Zielseiten sind GET-Routen.
        $router->getContext()->setMethod('POST');

        return new PendingFavorite($router);
    }

    private function router(): Router
    {
        $routen = new RouteCollection();
        $sprache = ['_locale' => 'lb|de|fr|en|pt'];
        $routen->add('app_restaurant_index', new Route('/{_locale}/restaurants', [], $sprache));
        $routen->add('app_restaurant_show', new Route('/{_locale}/restaurants/{id}', [], $sprache + ['id' => '\d+']));
        $routen->add('app_saved_favorites', new Route('/{_locale}/gespeichert/favoriten', [], $sprache, [], '', [], ['GET']));
        $routen->add('app_saved_list_show', new Route('/{_locale}/gespeichert/listen/{id}', [], $sprache + ['id' => '\d+'], [], '', [], ['GET']));
        $routen->add('admin_dashboard', new Route('/{_locale}/admin', [], $sprache));

        /** @var LoaderInterface $loader */
        $loader = new ClosureLoader();

        return new Router($loader, static fn (): RouteCollection => $routen, [], new RequestContext());
    }

    private function lokal(int $id): Restaurant
    {
        $restaurant = $this->createStub(Restaurant::class);
        $restaurant->method('getId')->willReturn($id);

        return $restaurant;
    }
}
