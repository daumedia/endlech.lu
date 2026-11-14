<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Controller\RestaurantSavedController;
use App\Entity\User;
use App\RateLimit\ActionLimiter;
use App\Repository\RestaurantRepository;
use App\Saved\FavoriteService;
use App\Saved\PendingFavorite;
use App\Usage\UsageEventCatalogue;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Löst den Merkwunsch eines Gastes nach der Anmeldung ein (Feature 17, AK-08 bis AK-10).
 *
 * Das Herz eines Gastes legt den Wunsch in die Sitzung und die Seite des Herzens als Zielseite
 * der Firewall `main` ab (`RestaurantSavedController::add`). Hier wird gemerkt, „Gemerkt: <Name>“
 * und der Messauslöser gehen als Meldung auf die Zielseite mit, der Wunsch wird gelöscht.
 *
 * ⚠ **Ein Zuhörer statt eines Erfolgs-Handlers je Authenticator** — wie
 * `OnboardingRedirectSubscriber`: eine Stelle für Passwort und Passkey (AK-09). Ein
 * fehlgeschlagener Versuch feuert dieses Ereignis nicht, der Wunsch bleibt liegen (AK-10).
 *
 * ⚠ **Die Antwort bleibt unangetastet.** Die Zielseite hat Symfony schon bestimmt; der Zuhörer
 * der Einführung greift nur bei der Startseite und lässt sie stehen (EC-04).
 *
 * ⚠ **Deckel wie jede Änderung** (`saved_change`, am Konto): verbraucht wird nur, wenn neu
 * gespeichert wurde (BF-11, AK-58). Ist er erschöpft, wird nicht gemerkt — der Wunsch ist
 * trotzdem weg, sonst holte ihn jede spätere Anmeldung derselben Sitzung unbemerkt nach.
 *
 * Gibt es das Lokal nicht mehr, wird der Wunsch still verworfen (EC-03): keine Meldung, die
 * Zielseite zeigt dann selbst „Nicht gefunden“.
 */
final readonly class PendingFavoriteSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PendingFavorite $pending,
        private RestaurantRepository $restaurants,
        private FavoriteService $favorites,
        private TranslatorInterface $translator,
        #[Autowire(service: 'limiter.saved_change')]
        private RateLimiterFactoryInterface $changeLimiter,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [LoginSuccessEvent::class => 'onLoginSuccess'];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        if ('main' !== $event->getFirewallName()) {
            return;
        }

        $user = $event->getUser();
        $request = $event->getRequest();

        if (!$user instanceof User || !$request->hasSession()) {
            return;
        }

        $session = $request->getSession();
        $id = $this->pending->take($session);

        if (null === $id) {
            return;
        }

        $restaurant = $this->restaurants->find($id);

        if (null === $restaurant) {
            return;
        }

        $flashes = $session instanceof FlashBagAwareSessionInterface ? $session->getFlashBag() : null;
        // Die Sprache der Anmeldeseite — ausdrücklich, damit die Meldung nicht von der Reihenfolge
        // der Zuhörer abhängt, die den Übersetzer auf die Anfrage stellen.
        $locale = $request->getLocale();

        $limiter = ActionLimiter::for($this->changeLimiter, RestaurantSavedController::limiterKey($user));
        if (!$limiter->isAllowed()) {
            $flashes?->add('error', $this->translator->trans('saved.flash.rate_limited', [
                '%minutes%' => max(1, (int) ceil($limiter->retryAfter() / 60)),
            ], null, $locale));

            return;
        }

        $neu = $this->favorites->add($user, $restaurant);
        if ($neu) {
            $limiter->consume();
        }

        $flashes?->add('success', $this->translator->trans('saved.flash.added', ['%name%' => $restaurant->getName()], null, $locale));
        if ($neu) {
            // BF-171: erst nach gelungener Handlung zählen — als unsichtbarer Auslöser auf der Zielseite.
            $flashes?->add('usage_event', UsageEventCatalogue::HEART_SET);
        }

        // AK-09 mit JavaScript: Turbo folgt der Weiterleitung nach der Anmeldung per fetch und
        // verliert dabei den Anker `#favorit-…` (im Browser gemessen 2026-11-14 — die Seite stand
        // oben, das Herz 2000 px tiefer). Diese Markierung lässt `scroll_to_controller` zur Karte
        // rollen und den Fokus aufs Herz setzen. Ohne JavaScript greift der echte Anker.
        // Die Kennung baut der Server aus der Lokalnummer — nie aus einer Eingabe.
        $flashes?->add('scroll_to', 'favorit-'.$restaurant->getId());
    }
}
