<?php

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * Drosselt Anfragen an /api/v1 pro Client-IP. Login und Registrierung werden
 * strenger limitiert — der eine gegen das Durchprobieren von Passwörtern, die
 * andere gegen den Mailversand an fremde Postfächer. Bei Überschreitung → JSON-429
 * via {@see ApiExceptionSubscriber}.
 */
final class ApiRateLimitSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire(service: 'limiter.api_anonymous')]
        private readonly RateLimiterFactoryInterface $apiAnonymousLimiter,
        #[Autowire(service: 'limiter.api_login')]
        private readonly RateLimiterFactoryInterface $apiLoginLimiter,
        #[Autowire(service: 'limiter.api_register')]
        private readonly RateLimiterFactoryInterface $apiRegisterLimiter,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 20],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();
        if (!str_starts_with($path, '/api/v1')) {
            return;
        }

        // Die Registrierung braucht einen eigenen, engen Deckel: Sie verschickt
        // eine Mail an eine frei wählbare Adresse, und zwar auch dann, wenn die
        // Adresse bereits vergeben ist (das ist der Anti-Enumeration geschuldet
        // und richtig so). Unter dem anonymen Limit waren das 100 Mails je
        // Minute an ein fremdes Postfach — nachgestellt mit elf in Sekunden.
        $factory = match (true) {
            str_starts_with($path, '/api/v1/auth/login') => $this->apiLoginLimiter,
            str_starts_with($path, '/api/v1/auth/register') => $this->apiRegisterLimiter,
            default => $this->apiAnonymousLimiter,
        };

        $limit = $factory->create($request->getClientIp() ?? 'anonymous')->consume(1);
        if (!$limit->isAccepted()) {
            // Retry-After (Sekunden bis zum nächsten erlaubten Versuch) für intelligentes Backoff.
            $retryAfter = max(1, $limit->getRetryAfter()->getTimestamp() - time());

            throw new TooManyRequestsHttpException($retryAfter, 'Zu viele Anfragen. Bitte später erneut versuchen.');
        }
    }
}
