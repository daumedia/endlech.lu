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
 * QA Feature 11 · Befund BF-152 — **Reproduktion, übersprungen bis zur Behebung.**
 *
 * Seit BF-149 belegt `UmamiForwarder` vor jeder Weiterleitung eine Sperre. `Lock::acquire()` fängt nur den
 * erwarteten Konflikt ab („Platz belegt"); jede andere Ausnahme des Speichers — bei `FlockStore` etwa, wenn
 * die Sperrdatei nicht geöffnet werden kann — wird als `LockAcquiringException` weitergeworfen
 * (`vendor/symfony/lock/Lock.php`, letzter `catch` in `acquire()`). `forward()` und `CollectController` fangen
 * sie nicht: Der Zählaufruf endet als 500, obwohl `ForwardResult` zusichert, dass der Browser in jedem
 * Fall `202 {}` bekommt, und Sentry meldet jeden einzelnen Aufruf.
 *
 * `sdd-build`: die Zeile mit `markTestSkipped` entfernen, sobald behoben.
 */
final class Qa11SperrAusfallTest extends TestCase
{
    public function testBf152DefekteSperreFuehrtNichtZumServerfehler(): void
    {
        self::markTestSkipped('BF-152 offen — Reproduktion für sdd-build, siehe features/11-nutzungsmessung/qa-report.md');

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
        $weiterleitung = new UmamiForwarder($client, new ArrayAdapter(), new NullLogger(), new LockFactory($defekt), 'https://203.0.113.77:8443', 'sha256//QUJD');

        $ergebnis = $weiterleitung->forward(['type' => 'event', 'payload' => ['url' => '/de/restaurants']], null, null, null);

        self::assertSame(202, $ergebnis->status);
        self::assertSame(0, $aufrufe);
    }
}
