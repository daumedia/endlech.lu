<?php

declare(strict_types=1);

namespace App\Tests\Unit\RateLimit;

use App\RateLimit\ActionLimiter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

/**
 * BF-11: Ein Tippfehler darf kein Kontingent kosten.
 *
 * Und die Falle, in die der naheliegende Umbau lief: `consume(0)` ist keine
 * Prüfung. `SlidingWindowLimiter` vergleicht `verfügbar >= angefordert`, und
 * `0 >= 0` gilt auch bei leerem Kontingent — acht gültige Anmeldungen liefen so
 * durch, wo die sechste hätte scheitern müssen.
 */
final class ActionLimiterTest extends TestCase
{
    private function factory(int $limit = 5): RateLimiterFactory
    {
        return new RateLimiterFactory(
            ['id' => 'probe', 'policy' => 'sliding_window', 'limit' => $limit, 'interval' => '1 hour'],
            new InMemoryStorage(),
        );
    }

    public function testUngueltigeVersucheVerbrauchenNichts(): void
    {
        $factory = $this->factory();

        // Zehn Fehlversuche: geprüft, aber nie gebucht.
        for ($i = 0; $i < 10; ++$i) {
            self::assertTrue(ActionLimiter::for($factory, '127.0.0.1')->isAllowed());
        }

        // Das Kontingent steht unberührt: fünf echte Handlungen gehen noch.
        for ($i = 0; $i < 5; ++$i) {
            $limiter = ActionLimiter::for($factory, '127.0.0.1');
            self::assertTrue($limiter->isAllowed(), sprintf('Handlung %d wurde abgelehnt.', $i + 1));
            $limiter->consume();
        }
    }

    public function testDerDeckelGreiftNachDemLetztenErlaubtenVersuch(): void
    {
        $factory = $this->factory();

        for ($i = 0; $i < 5; ++$i) {
            ActionLimiter::for($factory, '127.0.0.1')->consume();
        }

        self::assertFalse(
            ActionLimiter::for($factory, '127.0.0.1')->isAllowed(),
            'Bei erschöpftem Kontingent muss isAllowed() false liefern — hier lag der Fehler in consume(0).',
        );
    }

    public function testGetrennteSchluesselHabenGetrennteKontingente(): void
    {
        $factory = $this->factory();

        for ($i = 0; $i < 5; ++$i) {
            ActionLimiter::for($factory, 'konto-a')->consume();
        }

        self::assertFalse(ActionLimiter::for($factory, 'konto-a')->isAllowed());
        self::assertTrue(ActionLimiter::for($factory, 'konto-b')->isAllowed());
    }

    public function testOhneSchluesselWirdAnonymGezaehlt(): void
    {
        $factory = $this->factory(1);

        ActionLimiter::for($factory, null)->consume();

        self::assertFalse(ActionLimiter::for($factory, null)->isAllowed());
        self::assertFalse(ActionLimiter::for($factory, 'anonymous')->isAllowed());
    }

    public function testRetryAfterIstNichtNegativ(): void
    {
        $factory = $this->factory(1);
        ActionLimiter::for($factory, '127.0.0.1')->consume();

        self::assertGreaterThanOrEqual(0, ActionLimiter::for($factory, '127.0.0.1')->retryAfter());
    }
}
