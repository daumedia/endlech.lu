<?php

namespace App\Tests\Integration\Twig;

use App\Twig\UsageExtension;
use App\Usage\UsageTrackingPolicy;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

/** Feature 11 · Auf welchen Seiten das Zählskript liegt — AK-06 bis AK-09. */
final class UsageExtensionTest extends KernelTestCase
{
    private const string KENNUNG = '00000000-0000-4000-8000-000000000011';

    /** @param array<string, string|int> $parameter */
    private function erweiterung(?string $route, string $pfad, array $parameter = [], string $kennung = self::KENNUNG): UsageExtension
    {
        $stack = new RequestStack();
        if (null !== $route) {
            $request = Request::create($pfad);
            $request->attributes->set('_route', $route);
            $request->attributes->set('_route_params', ['_locale' => 'de', ...$parameter]);
            $stack->push($request);
        }

        return new UsageExtension($stack, new UsageTrackingPolicy($kennung), '3.3.1', 'https://endlech.lu');
    }

    /**
     * AK-07 · Jede Route mit Parameter `token` — aus dem Router gelesen, nicht abgeschrieben. Eine
     * neue Bestätigungs- oder Abmelde-Route ist damit ohne Pflege mit abgedeckt.
     */
    public function testKeineRouteMitTokenBekommtDasSkript(): void
    {
        self::bootKernel();
        /** @var RouterInterface $router */
        $router = static::getContainer()->get('router');
        $token = str_repeat('ab', 32);

        $gefunden = 0;
        foreach ($router->getRouteCollection()->all() as $name => $route) {
            if (!str_contains($route->getPath(), '{token}')) {
                continue;
            }
            ++$gefunden;
            $pfad = str_replace(['{_locale}', '{token}'], ['de', $token], $route->getPath());

            self::assertFalse($this->erweiterung($name, $pfad, ['token' => $token])->isEnabled(), $name);
        }

        self::assertGreaterThanOrEqual(9, $gefunden, 'Vorbedingung: Die neun Token-Routen aus der Spec sind im Router.');
    }

    /** @return iterable<string, array{string, string, bool}> */
    public static function seiten(): iterable
    {
        yield 'Restaurantliste (AK-01)' => ['app_restaurant_index', '/de/restaurants', true];
        yield 'Detailseite' => ['app_restaurant_show', '/de/restaurants/3', true];
        yield 'Anmeldung (AK-08)' => ['app_login', '/de/login', true];
        yield 'Registrierung (AK-08)' => ['app_register', '/de/register', true];
        yield 'Vorschlagsformular (AK-08)' => ['community_vorschlagen', '/de/community/suggest', true];
        yield 'Ideen-Formular (AK-08)' => ['app_board_new', '/de/community/ideen/neu', true];
        yield 'Verwaltung (AK-06)' => ['admin_dashboard', '/de/admin', false];
        yield 'Verwaltung, Unterseite (AK-06)' => ['admin_restaurant_index', '/de/admin/restaurants', false];
        yield 'Profil (AK-06)' => ['app_profile', '/de/profile', false];
        yield 'Profil bearbeiten (AK-06)' => ['app_profile_edit', '/de/profile/edit', false];
        yield 'Passkey unter /profile (AK-06)' => ['app_passkey_rename', '/de/profile/passkeys/1/umbenennen', false];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('seiten')]
    public function testSeite(string $route, string $pfad, bool $erwartet): void
    {
        self::assertSame($erwartet, $this->erweiterung($route, $pfad)->isEnabled());
    }

    /** AK-09 · Ohne Website-Kennung (lokal, CI, Produktion vor dem Einrichten) nirgends ein Skript. */
    public function testOhneKennungNieEinSkript(): void
    {
        self::assertFalse($this->erweiterung('app_restaurant_index', '/de/restaurants', [], '')->isEnabled());
    }

    /** EC-04 · Ohne Route (etwa eine Fehlerseite ohne Seitenkopf) kein Skript. */
    public function testOhneRouteKeinSkript(): void
    {
        self::assertFalse($this->erweiterung(null, '/')->isEnabled());
    }

    /** Die Kennung im Seitenkopf ist die konfigurierte — im Test die Platzhalter-Kennung aus `.env.test`. */
    public function testContainerNutztDieKonfigurierteKennung(): void
    {
        self::bootKernel();

        self::assertSame(self::KENNUNG, static::getContainer()->get(UsageTrackingPolicy::class)->websiteId());
    }
}
