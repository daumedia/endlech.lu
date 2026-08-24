<?php

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\ApiExceptionSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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

    /**
     * AK-28: In Produktion darf ein 500er weder Exception-Klasse noch Originaltext
     * preisgeben. Mit einem laufenden Server ist das schwer zu belegen — die
     * Unterscheidung hängt allein an $debug, also wird genau der geprüft.
     */
    public function testAk28ProduktionZeigtKeinExceptionDetailBei500(): void
    {
        $event = $this->dispatch('/api/v1/me', new \RuntimeException('SQLSTATE[HY000] Zugangsdaten zur Datenbank'), debug: false);

        $data = json_decode($event->getResponse()->getContent(), true);

        self::assertSame(500, $data['error']['code']);
        self::assertSame('Interner Serverfehler.', $data['error']['message']);
        self::assertArrayNotHasKey('exception', $data['error']);
        self::assertArrayNotHasKey('detail', $data['error']);
        self::assertStringNotContainsString('SQLSTATE', $event->getResponse()->getContent());
    }

    public function testAk28DebugModusZeigtDasDetail(): void
    {
        $event = $this->dispatch('/api/v1/me', new \RuntimeException('Klartext'), debug: true);

        $data = json_decode($event->getResponse()->getContent(), true);

        self::assertSame('RuntimeException', $data['error']['exception']);
        self::assertSame('Klartext', $data['error']['detail']);
    }

    /**
     * BF-28: Die Meldung des EntityValueResolver nennt Entity, Framework-Aufbau und
     * ORM. Sie darf nicht durchgereicht werden — und war kein Debug-Zusatz, sondern
     * stand auch in Produktion in der Antwort.
     *
     * Dieser Test stand vor der Reparatur in umgekehrter Richtung: Er hielt fest,
     * dass die Meldung durchkommt, und schlug fehl, sobald sie es nicht mehr tut.
     */
    public function testAk28VerraetKeineInternenKlassennamenBei404(): void
    {
        $event = $this->dispatch(
            '/api/v1/restaurants/999999',
            new NotFoundHttpException('"App\\Entity\\Restaurant" object not found by "Symfony\\Bridge\\Doctrine\\ArgumentResolver\\EntityValueResolver".'),
            debug: false,
        );

        $data = json_decode($event->getResponse()->getContent(), true);

        self::assertSame(404, $data['error']['code']);
        self::assertSame('Nicht gefunden.', $data['error']['message']);
        self::assertStringNotContainsString('App\\Entity', $event->getResponse()->getContent());
        self::assertStringNotContainsString('EntityValueResolver', $event->getResponse()->getContent());
    }

    /**
     * Die Meldung anderer HttpExceptions bleibt erhalten — sie kommt meist aus
     * eigenem Code und sagt etwas Nützliches.
     */
    public function testMeldungAndererHttpExceptionsBleibtErhalten(): void
    {
        $event = $this->dispatch('/api/v1/restaurants', new TooManyRequestsHttpException(30, 'Zu viele Anfragen.'), debug: false);

        $data = json_decode($event->getResponse()->getContent(), true);

        self::assertSame('Zu viele Anfragen.', $data['error']['message']);
    }

    private function dispatch(string $path, \Throwable $throwable, bool $debug = false): ExceptionEvent
    {
        $subscriber = new ApiExceptionSubscriber($debug, $this->createStub(Security::class));

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
