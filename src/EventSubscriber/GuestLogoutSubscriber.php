<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Ein Gast, der die Abmeldeadresse aufruft, wird zur Startseite geschickt.
 *
 * ⚠ BF-17: Seit `logout.enable_csrf` verlangt die Abmeldung einen POST mit
 * gültigem Token. Für einen Gast gibt es kein Token — die Firewall antwortet mit
 * **403**. Das ist die Kehrseite eines Schutzes, den es aus gutem Grund gibt
 * (ohne ihn genügte ein `<img src="/de/logout">` auf einer fremden Seite), aber
 * für den Aufrufer ist es eine Fehlermeldung ohne Fehler: Er wollte abgemeldet
 * sein, und das ist er.
 *
 * Als Exception-Listener und nicht als Route, weil die Firewall den Controller
 * gar nicht erst erreicht.
 */
final readonly class GuestLogoutSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private AuthenticationUtils $authenticationUtils,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // Vor dem Standard-ExceptionListener der Security-Komponente (priority 1),
        // damit dieser die 403-Seite nicht bereits gebaut hat.
        return [KernelEvents::EXCEPTION => ['onKernelException', 2]];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if ('app_logout' !== $event->getRequest()->attributes->get('_route')) {
            return;
        }

        $exception = $event->getThrowable();
        if (!$exception instanceof AccessDeniedException && !str_contains($exception->getMessage(), 'CSRF')) {
            return;
        }

        $event->setResponse(new RedirectResponse(
            $this->urlGenerator->generate('app_home', ['_locale' => $event->getRequest()->getLocale()]),
        ));
    }
}
