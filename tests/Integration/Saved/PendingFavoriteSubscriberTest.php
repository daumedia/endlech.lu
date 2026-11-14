<?php

declare(strict_types=1);

namespace App\Tests\Integration\Saved;

use App\Controller\RestaurantSavedController;
use App\Entity\Restaurant;
use App\Entity\User;
use App\EventSubscriber\PendingFavoriteSubscriber;
use App\Repository\RestaurantRepository;
use App\Repository\UserRepository;
use App\Saved\FavoriteService;
use App\Saved\PendingFavorite;
use App\Security\PasskeyAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * Feature 17, T16: der Zuhörer, der den Merkwunsch nach der Anmeldung einlöst — die Fälle,
 * die über das Anmeldeformular nicht erreichbar sind.
 */
final class PendingFavoriteSubscriberTest extends KernelTestCase
{
    private User $konto;
    private Restaurant $lokal;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $konto = $container->get(UserRepository::class)->findOneBy(['email' => 'user@endlech.lu']);
        $lokal = $container->get(RestaurantRepository::class)->findOneBy([], ['id' => 'ASC']);
        self::assertNotNull($konto);
        self::assertNotNull($lokal);
        $this->konto = $konto;
        $this->lokal = $lokal;
        $container->get('limiter.saved_change')->create(RestaurantSavedController::limiterKey($konto))->reset();
    }

    public function testAndereFirewallLaesstDenWunschLiegen(): void
    {
        $anfrage = $this->anfrageMitWunsch();

        $this->zuhoerer()->onLoginSuccess($this->ereignis($anfrage, $this->konto, 'api_login'));

        self::assertSame(0, $this->zahl());
        self::assertSame((int) $this->lokal->getId(), $this->pending()->peek($anfrage->getSession()));
        self::assertSame([], $this->sitzung($anfrage)->getFlashBag()->peekAll());
    }

    public function testFremdesBenutzerobjektLaesstDenWunschLiegen(): void
    {
        $anfrage = $this->anfrageMitWunsch();

        $this->zuhoerer()->onLoginSuccess($this->ereignis($anfrage, new InMemoryUser('fremd', null), 'main'));

        self::assertSame((int) $this->lokal->getId(), $this->pending()->peek($anfrage->getSession()));
        self::assertSame([], $this->sitzung($anfrage)->getFlashBag()->peekAll());
    }

    public function testOhneWunschGeschiehtNichts(): void
    {
        $anfrage = $this->anfrage();
        $rest = $this->rest();

        $this->zuhoerer()->onLoginSuccess($this->ereignis($anfrage, $this->konto, 'main'));

        self::assertSame(0, $this->zahl());
        self::assertSame([], $this->sitzung($anfrage)->getFlashBag()->peekAll());
        self::assertSame($rest, $this->rest());
    }

    public function testSchonGemerktMeldetOhneMessereignisUndOhneVerbrauch(): void
    {
        static::getContainer()->get(FavoriteService::class)->add($this->konto, $this->lokal);
        $anfrage = $this->anfrageMitWunsch();
        $rest = $this->rest();

        $this->zuhoerer()->onLoginSuccess($this->ereignis($anfrage, $this->konto, 'main'));

        self::assertSame(1, $this->zahl());
        self::assertSame(['Gemerkt: '.$this->lokal->getName()], $this->sitzung($anfrage)->getFlashBag()->peek('success'));
        self::assertSame([], $this->sitzung($anfrage)->getFlashBag()->peek('usage_event'), 'AK-66 zählt nur ein neu gesetztes Herz.');
        self::assertSame($rest, $this->rest(), 'AK-58: keine Änderung, kein Verbrauch.');
        self::assertNull($this->pending()->peek($anfrage->getSession()));
    }

    public function testNeuGemerktVerbrauchtGenauEinenSchritt(): void
    {
        $anfrage = $this->anfrageMitWunsch();
        $rest = $this->rest();

        $this->zuhoerer()->onLoginSuccess($this->ereignis($anfrage, $this->konto, 'main'));

        self::assertSame(1, $this->zahl());
        self::assertSame($rest - 1, $this->rest());
        self::assertSame(['herz_gesetzt'], $this->sitzung($anfrage)->getFlashBag()->peek('usage_event'));
    }

    public function testErschoepfterDeckelMeldetMitMinutenUndLoeschtDenWunsch(): void
    {
        static::getContainer()->get('limiter.saved_change')->create(RestaurantSavedController::limiterKey($this->konto))->consume(10000);
        $anfrage = $this->anfrageMitWunsch();

        $this->zuhoerer()->onLoginSuccess($this->ereignis($anfrage, $this->konto, 'main'));

        self::assertSame(0, $this->zahl());
        $fehler = $this->sitzung($anfrage)->getFlashBag()->peek('error');
        self::assertCount(1, $fehler);
        self::assertMatchesRegularExpression('/^Zu viele Änderungen\. Bitte versuche es in \d+ Minuten erneut\.$/u', $fehler[0]);
        self::assertSame([], $this->sitzung($anfrage)->getFlashBag()->peek('success'));
        self::assertNull($this->pending()->peek($anfrage->getSession()));
    }

    private function anfrage(): Request
    {
        $anfrage = Request::create('/de/login', 'POST');
        $anfrage->setSession(new Session(new MockArraySessionStorage()));
        $anfrage->setLocale('de');

        return $anfrage;
    }

    private function anfrageMitWunsch(): Request
    {
        $anfrage = $this->anfrage();
        $this->pending()->remember($anfrage->getSession(), $this->lokal);

        return $anfrage;
    }

    private function ereignis(Request $anfrage, UserInterface $benutzer, string $firewall): LoginSuccessEvent
    {
        $container = static::getContainer();
        $token = new PostAuthenticationToken($benutzer, $firewall, $benutzer->getRoles());

        return new LoginSuccessEvent(
            new PasskeyAuthenticator($container->get('router'), $container->get('translator')),
            new SelfValidatingPassport(new UserBadge($benutzer->getUserIdentifier(), static fn (): UserInterface => $benutzer)),
            $token,
            $anfrage,
            new RedirectResponse('/de/restaurants'),
            $firewall,
        );
    }

    private function zuhoerer(): PendingFavoriteSubscriber
    {
        return static::getContainer()->get(PendingFavoriteSubscriber::class);
    }

    private function pending(): PendingFavorite
    {
        return static::getContainer()->get(PendingFavorite::class);
    }

    private function sitzung(Request $anfrage): Session
    {
        $sitzung = $anfrage->getSession();
        self::assertInstanceOf(Session::class, $sitzung);

        return $sitzung;
    }

    private function rest(): int
    {
        return static::getContainer()->get('limiter.saved_change')
            ->create(RestaurantSavedController::limiterKey($this->konto))
            ->consume(0)
            ->getRemainingTokens();
    }

    private function zahl(): int
    {
        return (int) static::getContainer()->get(EntityManagerInterface::class)->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM favorite WHERE user_id = ? AND restaurant_id = ?',
            [$this->konto->getId(), $this->lokal->getId()],
        );
    }
}
