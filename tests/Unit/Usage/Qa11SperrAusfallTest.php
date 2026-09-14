<?php

namespace App\Tests\Unit\Usage;

use App\Usage\UmamiForwarder;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Lock\Exception\LockStorageException;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;

/**
 * QA Feature 11 · Befund BF-152 — Reproduktion, seit der Behebung am 2026-09-13 scharf.
 *
 * Seit BF-149 belegt `UmamiForwarder` vor jeder Weiterleitung eine Sperre. `Lock::acquire()` fängt nur den
 * erwarteten Konflikt ab („Platz belegt"); jede andere Ausnahme des Speichers — bei `FlockStore` etwa, wenn
 * die Sperrdatei nicht geöffnet werden kann — wird als `LockAcquiringException` weitergeworfen
 * (`vendor/symfony/lock/Lock.php`, letzter `catch` in `acquire()`). `forward()` und `CollectController` fangen
 * sie nicht: Der Zählaufruf endet als 500, obwohl `ForwardResult` zusichert, dass der Browser in jedem
 * Fall `202 {}` bekommt, und Sentry meldet jeden einzelnen Aufruf.
 *
 * Behoben in `UmamiForwarder::forward()` und `platzFreigeben()`; weitere Fälle in `UmamiForwarderTest`.
 */
final class Qa11SperrAusfallTest extends TestCase
{
    public function testBf152DefekteSperreFuehrtNichtZumServerfehler(): void
    {
        $aufrufe = 0;
        $client = new MockHttpClient(static function () use (&$aufrufe): MockResponse {
            ++$aufrufe;

            return new MockResponse('{}');
        });
        $defekt = new class extends InMemoryStore {
            public function save(Key $key): void
            {
                throw new LockStorageException('fopen(/tmp/sf.umami-weiterleitung.lock): Failed to open stream: Permission denied');
            }
        };
        $weiterleitung = new UmamiForwarder($client, new ArrayAdapter(), new NullLogger(), new LockFactory($defekt), 'https://203.0.113.77:8443');

        $ergebnis = $weiterleitung->forward(['type' => 'event', 'payload' => ['url' => '/de/restaurants']], null, null, null);

        self::assertSame(202, $ergebnis->status);
        self::assertSame(0, $aufrufe);
    }
}
