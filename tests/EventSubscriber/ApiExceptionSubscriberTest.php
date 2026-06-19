<?php

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\ApiExceptionSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ApiExceptionSubscriberTest extends TestCase
{
    public function testPreservesRetryAfterHeaderOn429(): void
    {
        $event = $this->dispatch('/api/v1/restaurants', new TooManyRequestsHttpException(42, 'Zu viele Anfragen.'));

        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertSame(429, $response->getStatusCode());
        self::assertSame('42', $response->headers->get('Retry-After'));

        $data = json_decode($response->getContent(), true);
        self::assertSame(429, $data['error']['code']);
    }

    public function testIgnoresNonApiRequests(): void
    {
        $event = $this->dispatch('/de/restaurants', new TooManyRequestsHttpException(42));

        self::assertNull($event->getResponse());
    }

    private function dispatch(string $path, \Throwable $throwable): ExceptionEvent
    {
        $subscriber = new ApiExceptionSubscriber(false, $this->createStub(Security::class));

        $event = new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create($path),
            HttpKernelInterface::MAIN_REQUEST,
            $throwable,
        );

        $subscriber->onKernelException($event);

        return $event;
    }
}
