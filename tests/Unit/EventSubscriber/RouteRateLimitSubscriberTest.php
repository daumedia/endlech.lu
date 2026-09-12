<?php

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\RouteRateLimitSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Feature 10, AK-23 · Der Deckel der Sitemap, mit echter Grenze.
 *
 * Im Testcontainer steht jeder Deckel auf 10000 (`when@test`), damit sich Aufrufe über die
 * Suite nicht summieren. Die Grenze von 60 lässt sich deshalb nur mit eigenen Fabriken prüfen —
 * dasselbe Muster wie `ActionLimiterTest`.
 */
final class RouteRateLimitSubscriberTest extends TestCase
{
    private function fabrik(string $id, int $limit = 60): RateLimiterFactory
    {
        return new RateLimiterFactory(
            ['id' => $id, 'policy' => 'sliding_window', 'limit' => $limit, 'interval' => '1 hour'],
            new InMemoryStorage(),
        );
    }

    private function abonnent(RateLimiterFactory $sitemap, RateLimiterFactory $datensatz): RouteRateLimitSubscriber
    {
        return new RouteRateLimitSubscriber(
            $this->fabrik('passkey', 10000),
            $this->fabrik('admin', 10000),
            $datensatz,
            $sitemap,
            $this->createStub(TokenStorageInterface::class),
        );
    }

    private function abruf(RouteRateLimitSubscriber $abonnent, string $pfad): void
    {
        $request = Request::create($pfad, 'GET', server: ['REMOTE_ADDR' => '198.51.100.7']);
        $abonnent->onKernelRequest(new RequestEvent(
            $this->createStub(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        ));
    }

    public function testSechzigAbrufeFreiDerEinundsechzigsteWirdAbgewiesen(): void
    {
        $abonnent = $this->abonnent($this->fabrik('sitemap'), $this->fabrik('datensatz'));

        for ($i = 1; $i <= 60; ++$i) {
            $this->abruf($abonnent, '/sitemap.xml');
        }

        try {
            $this->abruf($abonnent, '/sitemap.xml');
            self::fail('Der 61. Abruf innerhalb einer Stunde hätte abgewiesen werden müssen.');
        } catch (TooManyRequestsHttpException $e) {
            self::assertSame(429, $e->getStatusCode());
            self::assertGreaterThanOrEqual(1, (int) ($e->getHeaders()['Retry-After'] ?? 0), 'Die Wartezeit gehört in die Antwort.');
        }
    }

    /** Eigenes Kontingent: Der Datensatz verbraucht nichts von der Sitemap — und umgekehrt. */
    public function testDatensatzUndSitemapTeilenSichKeinKontingent(): void
    {
        $abonnent = $this->abonnent($this->fabrik('sitemap'), $this->fabrik('datensatz'));

        for ($i = 1; $i <= 60; ++$i) {
            $this->abruf($abonnent, '/open/dataset.json');
        }

        $this->abruf($abonnent, '/sitemap.xml');
        $this->expectException(TooManyRequestsHttpException::class);
        $this->abruf($abonnent, '/open/dataset.json');
    }
}
