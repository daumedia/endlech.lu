<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\RateLimit\ActionLimiter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Deckelt Wege, die keinen eigenen Controller im Projekt haben.
 *
 * Das Gegenstück zu `ApiRateLimitSubscriber`, der dasselbe für `/api/v1` tut.
 * Hier geht es um zwei Fälle, die sonst durch jedes Raster fallen:
 *
 * ⚠ **Passkey-Challenge (BF-18).** Die Endpunkte gehören dem Bundle und liegen
 * sprachfrei — damit fielen sie weder unter die Web- noch unter die API-Regeln.
 * Zehn Anfragen an `/passkey/login/options` wurden alle mit 200 beantwortet,
 * jede legte eine Challenge in der Sitzung ab. Nach der Konvention in `CLAUDE.md`
 * braucht jeder Weg, der ein Geheimnis prüft, einen Limiter.
 *
 * ⚠ **Schreibvorgänge im Verwaltungsbereich (BF-35).** Acht Umschaltungen in Folge
 * liefen ungebremst durch. Der Deckel ist bewusst weit — ein Admin ändert legitim
 * viel; er fängt die Schleife ab, nicht die Arbeit.
 *
 * ⚠ **Offene Datenendpunkte (BF-42).** Zwölf Abrufe, zwölfmal 200, und jeder lädt
 * den GESAMTEN Bestand. Das ist der Fall, den der Wortlaut der Konvention zuerst
 * nicht erfasste: Er löst keine Mail aus und prüft kein Geheimnis — er ist nur
 * teuer. Der Deckel ist entsprechend großzügig; der Datensatz soll abrufbar
 * bleiben, gedeckelt wird der Dauerabruf.
 */
final readonly class RouteRateLimitSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire(service: 'limiter.passkey_challenge')]
        private RateLimiterFactoryInterface $passkeyLimiter,
        #[Autowire(service: 'limiter.admin_write')]
        private RateLimiterFactoryInterface $adminWriteLimiter,
        #[Autowire(service: 'limiter.open_dataset')]
        private RateLimiterFactoryInterface $openDatasetLimiter,
        private TokenStorageInterface $tokenStorage,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // Nach dem Router (32) und nach der Firewall — der Verwaltungs-Deckel zählt
        // am Konto und braucht dafür den angemeldeten Nutzer.
        return [KernelEvents::REQUEST => ['onKernelRequest', 6]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $pfad = $request->getPathInfo();

        if (str_starts_with($pfad, '/passkey/')) {
            $this->deckeln($this->passkeyLimiter, $request->getClientIp());

            return;
        }

        if ('/open.json' === $pfad || str_starts_with($pfad, '/open/dataset.')) {
            $this->deckeln($this->openDatasetLimiter, $request->getClientIp());

            return;
        }

        if ($this->istVerwaltungsSchreibvorgang($request)) {
            // Am Konto: Der Bereich ist ohnehin angemeldeten Admins vorbehalten,
            // und eine IP sagt dort nichts aus (Büro-NAT, wechselndes Mobilnetz).
            $this->deckeln($this->adminWriteLimiter, $this->kontoKennung());
        }
    }

    private function istVerwaltungsSchreibvorgang(Request $request): bool
    {
        if (!\in_array($request->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return false;
        }

        $route = $request->attributes->get('_route');

        return \is_string($route) && str_starts_with($route, 'admin_');
    }

    private function kontoKennung(): string
    {
        $token = $this->tokenStorage->getToken();

        return $token?->getUserIdentifier() ?? 'anonymous';
    }

    private function deckeln(RateLimiterFactoryInterface $factory, ?string $schluessel): void
    {
        $limiter = ActionLimiter::for($factory, $schluessel);

        if ($limiter->isAllowed()) {
            $limiter->consume();

            return;
        }

        throw new TooManyRequestsHttpException(
            max(1, $limiter->retryAfter()),
            'Zu viele Anfragen. Bitte später erneut versuchen.',
        );
    }
}
