<?php

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * Drosselt Anfragen an /api/v1 pro Client-IP. Der Login-Endpunkt wird strenger
 * limitiert (Brute-Force-Schutz). Bei Überschreitung → JSON-429 via
 * {@see ApiExceptionSubscriber}.
 */
final class ApiRateLimitSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire(service: 'limiter.api_anonymous')]
        private readonly RateLimiterFactoryInterface $apiAnonymousLimiter,
        #[Autowire(service: 'limiter.api_login')]
        private readonly RateLimiterFactoryInterface $apiLoginLimiter,
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

        $factory = str_starts_with($path, '/api/v1/auth/login')
            ? $this->apiLoginLimiter
            : $this->apiAnonymousLimiter;

        $limiter = $factory->create($request->getClientIp() ?? 'anonymous');
        if (!$limiter->consume(1)->isAccepted()) {
            throw new TooManyRequestsHttpException(message: 'Zu viele Anfragen. Bitte später erneut versuchen.');
        }
    }
}
