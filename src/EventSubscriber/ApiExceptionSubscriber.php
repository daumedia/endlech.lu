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
                $throwable->getMessage(),
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

        $event->setResponse(new JsonResponse(['error' => $error], $status));
    }
}
