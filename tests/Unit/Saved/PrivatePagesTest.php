<?php

declare(strict_types=1);

namespace App\Tests\Unit\Saved;

use App\Saved\PrivatePages;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;

/**
 * Feature 17, T18: welche Seiten private Angaben eines Kontos zeigen (AK-63).
 *
 * Die Routen `app_saved_*` prüft dieser Lauf über den Namen — es muss sie dafür noch nicht geben.
 */
final class PrivatePagesTest extends TestCase
{
    #[DataProvider('privateRouten')]
    public function testAk63SeitenMitPrivatenAngabenSindPrivat(string $route): void
    {
        self::assertTrue((new PrivatePages(new TokenStorage()))->isPrivate($route));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function privateRouten(): iterable
    {
        yield 'Übersicht Gespeichert' => ['app_saved_index'];
        yield 'eine Liste' => ['app_saved_list_show'];
        yield 'Suche' => ['app_restaurant_index'];
        yield 'Detailseite' => ['app_restaurant_show'];
    }

    #[DataProvider('andereRouten')]
    public function testAk63AndereSeitenBleibenUnberuehrt(?string $route): void
    {
        self::assertFalse((new PrivatePages(new TokenStorage()))->isPrivate($route));
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function andereRouten(): iterable
    {
        yield 'Über uns' => ['app_about'];
        yield 'Startseite' => ['app_home'];
        yield 'ohne Route' => [null];
        // Der Präfix trägt den Unterstrich mit — ein Name, der nur so anfängt, gehört nicht dazu.
        yield 'Präfix ohne Unterstrich' => ['app_saved'];
        yield 'ähnlicher Restaurantname' => ['app_restaurant_index_legacy'];
    }

    public function testAk63PrivatIstEineSeiteNurMitAngemeldetemKonto(): void
    {
        $tokens = new TokenStorage();
        $seiten = new PrivatePages($tokens);
        $suche = new Request(attributes: ['_route' => 'app_restaurant_index']);

        self::assertFalse($seiten->isPrivateView($suche), 'Gast: Die Seite zeigt nichts Privates.');

        $tokens->setToken(new UsernamePasswordToken(new InMemoryUser('konto', null), 'main'));

        self::assertTrue($seiten->isPrivateView($suche));
        self::assertFalse($seiten->isPrivateView(new Request(attributes: ['_route' => 'app_about'])));
        self::assertFalse($seiten->isPrivateView(null));
    }
}
