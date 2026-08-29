<?php

namespace App\EventSubscriber;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Wandelt alle Exceptions unterhalb von /api/v1 in einheitliche JSON-Fehler:
 *   {"error": {"code": <int>, "message": "..."}}
 *
 * So bleiben API-Antworten konsistent, statt HTML-Fehlerseiten auszuliefern.
 */
final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly bool $debug,
        private readonly Security $security,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Vor dem Standard-Listener (Priorität 0) ausführen.
            KernelEvents::EXCEPTION => ['onKernelException', 10],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api/v1')) {
            return;
        }

        $throwable = $event->getThrowable();

        [$status, $message] = match (true) {
            $throwable instanceof HttpExceptionInterface => [
                $throwable->getStatusCode(),
                $this->messageFor($throwable),
            ],
            // Anonyme Anfrage auf geschützte Route → 401, sonst (Rolle fehlt) → 403.
            $throwable instanceof AccessDeniedException => $this->security->getUser() === null
                ? [401, 'Authentifizierung erforderlich.']
                : [403, 'Zugriff verweigert.'],
            $throwable instanceof AuthenticationException => [401, 'Authentifizierung erforderlich.'],
            default => [500, 'Interner Serverfehler.'],
        };

        $error = ['code' => $status, 'message' => $message];

        // Im Debug-Modus zusätzlich die Exception-Klasse + Originaltext bei 500.
        if ($this->debug && $status >= 500) {
            $error['exception'] = $throwable::class;
            $error['detail'] = $throwable->getMessage();
        }

        $response = new JsonResponse(['error' => $error], $status);

        // Header der HTTP-Exception übernehmen (z. B. Retry-After bei 429,
        // WWW-Authenticate bei 401), die sonst beim Neuaufbau verloren gingen.
        if ($throwable instanceof HttpExceptionInterface) {
            $response->headers->add($throwable->getHeaders());
        }

        $event->setResponse($response);
    }

    /**
     * Die Meldung einer HttpException wird grundsätzlich durchgereicht — sie kommt
     * meist aus unserem eigenen Code und sagt etwas Nützliches.
     *
     * Zwei Ausnahmen: 404 und 403 entstehen im Framework, und deren Text verrät
     * Interna. Der EntityValueResolver schreibt dort etwa
     * `"App\Entity\Restaurant" object not found by "…\EntityValueResolver".` —
     * das nennt ORM, Entity und Framework-Aufbau, und einem Client ist damit nicht
     * geholfen. Es ist **kein** Debug-Zusatz: Der Text stand auch in Produktion in
     * der Antwort.
     */
    private function messageFor(HttpExceptionInterface $exception): string
    {
        return match ($exception->getStatusCode()) {
            404 => 'Nicht gefunden.',
            403 => 'Zugriff verweigert.',
            default => $exception->getMessage(),
        };
    }
}
