<?php

declare(strict_types=1);

namespace App\Tests\Unit\Saved;

use App\Entity\User;
use App\Saved\SavedWriteLock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;

/**
 * Feature 17, T02: Solange ein Konto schreibt, wartet jede zweite Schreibarbeit desselben
 * Kontos — die eines anderen Kontos nicht. Damit entstehen bei Doppelklick oder zwei Tabs
 * weder doppelte Einträge (AK-05, AK-31) noch eine 26. Liste (EC-05).
 */
final class SavedWriteLockTest extends TestCase
{
    public function testZweiteSperreDesselbenKontosIstBelegtDieEinesAnderenNicht(): void
    {
        $locks = new LockFactory(new InMemoryStore());
        $sperre = new SavedWriteLock($locks);

        [$selbes, $anderes] = $sperre->forAccount($this->konto(7), static function () use ($locks): array {
            $selbes = $locks->createLock('gespeichert-7');
            $anderes = $locks->createLock('gespeichert-8');

            return [$selbes->acquire(false), $anderes->acquire(false)];
        });

        self::assertFalse($selbes, 'Während Konto 7 schreibt, darf keine zweite Schreibarbeit von Konto 7 beginnen.');
        self::assertTrue($anderes, 'Ein anderes Konto darf gleichzeitig schreiben.');
    }

    public function testSperreWirdNachDerArbeitFreigegebenAuchBeiAusnahme(): void
    {
        $locks = new LockFactory(new InMemoryStore());
        $sperre = new SavedWriteLock($locks);

        try {
            $sperre->forAccount($this->konto(7), static function (): never {
                throw new \RuntimeException('Arbeit scheitert');
            });
        } catch (\RuntimeException) {
        }

        self::assertTrue($locks->createLock('gespeichert-7')->acquire(false));
    }

    public function testGibtDasErgebnisDerArbeitZurueck(): void
    {
        $sperre = new SavedWriteLock(new LockFactory(new InMemoryStore()));

        self::assertSame(42, $sperre->forAccount($this->konto(7), static fn (): int => 42));
    }

    private function konto(int $id): User
    {
        $user = $this->createStub(User::class);
        $user->method('getId')->willReturn($id);

        return $user;
    }
}
