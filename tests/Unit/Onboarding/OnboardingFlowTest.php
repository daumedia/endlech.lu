<?php

declare(strict_types=1);

namespace App\Tests\Unit\Onboarding;

use App\Entity\User;
use App\Onboarding\OnboardingFlow;
use PHPUnit\Framework\TestCase;

/**
 * Feature 12 — Reihenfolge und Zählung der Bildschirme (AK-12, AK-13, AK-14, AK-16).
 */
final class OnboardingFlowTest extends TestCase
{
    private function konto(bool $abonniert): User
    {
        $user = new User();

        if ($abonniert) {
            $user->setMarketingConsentAt(new \DateTimeImmutable());
        }

        return $user;
    }

    public function testAk12VierBildschirmeInDieserReihenfolge(): void
    {
        self::assertSame(
            ['willkommen', 'eintraege', 'profilbild', 'newsletter'],
            (new OnboardingFlow())->stepsFor($this->konto(abonniert: false)),
        );
    }

    public function testAk13MitEinwilligungEntfaelltDerNewsletter(): void
    {
        self::assertSame(
            ['willkommen', 'eintraege', 'profilbild'],
            (new OnboardingFlow())->stepsFor($this->konto(abonniert: true)),
        );
    }

    public function testAk14NummerUndZahlFolgenDerTatsaechlichenLaenge(): void
    {
        $flow = new OnboardingFlow();

        $vier = $flow->locate($this->konto(abonniert: false), 'eintraege');
        self::assertSame(2, $vier?->number);
        self::assertSame(4, $vier?->total);

        $drei = $flow->locate($this->konto(abonniert: true), 'profilbild');
        self::assertSame(3, $drei?->number);
        self::assertSame(3, $drei?->total);
        self::assertTrue($drei?->isLast, 'Ohne Newsletter ist das Profilbild der letzte Bildschirm.');
    }

    public function testAk16VorigerUndNaechsterSchritt(): void
    {
        $flow = new OnboardingFlow();
        $user = $this->konto(abonniert: false);

        $erster = $flow->locate($user, 'willkommen');
        self::assertNull($erster?->previous, 'Auf dem ersten Bildschirm gibt es kein „Zurück".');
        self::assertSame('eintraege', $erster?->next);

        $letzter = $flow->locate($user, 'newsletter');
        self::assertSame('profilbild', $letzter?->previous);
        self::assertNull($letzter?->next);
        self::assertTrue($letzter?->isLast);
    }

    public function testAk13NewsletterGehoertNichtZumAblaufEinesAbonnenten(): void
    {
        self::assertNull((new OnboardingFlow())->locate($this->konto(abonniert: true), 'newsletter'));
    }

    public function testUnbekannterSchrittGehoertZuKeinemAblauf(): void
    {
        self::assertNull((new OnboardingFlow())->locate($this->konto(abonniert: false), 'admin'));
    }
}
