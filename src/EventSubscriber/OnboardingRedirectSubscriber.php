<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use App\Onboarding\OnboardingFlow;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * Die Weiche nach der Anmeldung (Feature 12, design.md Entscheidung 1).
 *
 * Ersetzt **ausschließlich** eine Weiterleitung auf die Startseite — und nur für ein Konto, dem
 * die Einführung noch zusteht (`User::isOnboardingPending()`). Alles andere bleibt, wie Symfony
 * es entschieden hat:
 *
 * - **Eine gemerkte Zielseite gewinnt** (AK-03). Wer von der Erklärseite „Restaurant
 *   vorschlagen" kommt (B11), landet dort; die Einführung bleibt offen für die nächste
 *   Anmeldung. Symfony hat das Ziel zu diesem Zeitpunkt schon bestimmt — deshalb reicht der
 *   Blick auf die Weiterleitung, ein Sonderfall je Zielseite ist nicht nötig.
 * - **„Angemeldet bleiben" liefert keine Weiterleitung** (EC-06): Die automatische
 *   Wiederanmeldung läuft mitten in einer Anfrage, die angefragte Seite erscheint.
 *
 * ⚠ **Verglichen wird der Pfad**, nicht die Adresse: `form_login` liefert eine absolute, der
 * Passkey-Weg eine relative, und die Startseite heißt `/de/` oder `/de` je nach Erzeugung.
 *
 * Ein Zuhörer statt eigener Erfolgs-Handler je Authenticator: eine Stelle für Passwort und
 * Passkey. Die erste Anmeldung ist immer eine mit Passwort (ein Konto lässt sich per Passkey
 * nicht anlegen), die aufgeschobene aus AK-03 kann aber eine mit Passkey sein.
 */
final readonly class OnboardingRedirectSubscriber implements EventSubscriberInterface
{
    public function __construct(private UrlGeneratorInterface $urls)
    {
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

        $response = $event->getResponse();

        if (!$response instanceof RedirectResponse) {
            return;
        }

        $user = $event->getUser();

        if (!$user instanceof User || !$user->isOnboardingPending()) {
            return;
        }

        $locale = $event->getRequest()->getLocale();
        $startseite = $this->pfad($this->urls->generate('app_home', ['_locale' => $locale]));

        if ($this->pfad($response->getTargetUrl()) !== $startseite) {
            return;
        }

        $event->setResponse(new RedirectResponse($this->urls->generate('app_onboarding', [
            '_locale' => $locale,
            'schritt' => OnboardingFlow::WELCOME,
        ])));
    }

    private function pfad(string $adresse): string
    {
        return rtrim((string) parse_url($adresse, \PHP_URL_PATH), '/');
    }
}
