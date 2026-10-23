<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * Feature 12 — wann die Einführung für ein Konto fällig ist.
 *
 * Die Regel steht an der Entity und nicht im Zuhörer auf die Anmeldung, weil drei Stellen sie
 * brauchen (Weiterleitung nach der Anmeldung, Bestätigungslink, Verwaltung) und sie an keiner
 * auseinanderlaufen darf.
 */
final class UserOnboardingTest extends TestCase
{
    private function bestaetigt(): User
    {
        $user = new User();
        $user->setIsVerified(true);

        return $user;
    }

    public function testAk06BestandskontoIstNieFaellig(): void
    {
        // Ein bestätigtes Konto ohne Merker ist ein Bestandskonto: Es wurde vor der
        // Auslieferung bestätigt und bekommt die Einführung nicht.
        self::assertFalse($this->bestaetigt()->isOnboardingPending());
    }

    public function testAk01VorgesehenUndBestaetigtIstFaellig(): void
    {
        $user = $this->bestaetigt();
        $user->markOnboardingDue();

        self::assertTrue($user->isOnboardingPending());
    }

    public function testAk04UnbestaetigtIstNichtFaellig(): void
    {
        $user = new User();
        $user->markOnboardingDue();

        self::assertFalse($user->isOnboardingPending());
    }

    public function testAk02NachDerAnzeigeNichtMehrFaellig(): void
    {
        $user = $this->bestaetigt();
        $user->markOnboardingDue();
        $user->markOnboardingShown(new \DateTimeImmutable('2026-10-22 10:00'));

        self::assertFalse($user->isOnboardingPending());
        self::assertTrue($user->isOnboardingDue(), 'Der Merker bleibt — die Verwaltung zählt vorgesehene Konten.');
    }

    public function testAk53ZweitesVermerkenUeberschreibtDasErsteDatumNicht(): void
    {
        $user = $this->bestaetigt();
        $user->markOnboardingDue();
        $user->markOnboardingShown(new \DateTimeImmutable('2026-10-22 10:00'));
        $user->markOnboardingShown(new \DateTimeImmutable('2026-11-01 12:00'));

        self::assertSame('2026-10-22 10:00', $user->getOnboardingShownAt()?->format('Y-m-d H:i'));
    }

    public function testAk53NeuesKontoTraegtKeineAngabe(): void
    {
        $user = new User();

        self::assertFalse($user->isOnboardingDue());
        self::assertNull($user->getOnboardingShownAt());
    }
}
