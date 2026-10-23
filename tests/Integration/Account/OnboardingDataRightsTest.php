<?php

declare(strict_types=1);

namespace App\Tests\Integration\Account;

use App\Account\AccountDataExporter;
use App\Account\AccountDeleter;
use App\Entity\User;
use App\Enum\MarketingSyncState;
use App\Marketing\MarketingContactRegistry;
use App\Repository\MarketingContactRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Feature 12, AK-61 und AK-62 — was Export und Kontolöschung mit den neuen Angaben tun.
 */
final class OnboardingDataRightsTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    private function konto(string $email): User
    {
        $user = new User();
        $user->setName('Rechte')->setEmail($email)->setPassword('x')->setIsVerified(true)
            ->setMarketingConsentAt(new \DateTimeImmutable('2026-10-22 09:30:00'));
        $user->markOnboardingDue();
        $user->markOnboardingShown(new \DateTimeImmutable('2026-10-22 10:00:00'));
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function testAk62ExportNenntEinfuehrungUndNewsletter(): void
    {
        $user = $this->konto('f12-export@qa.lu');

        $export = static::getContainer()->get(AccountDataExporter::class)->export($user);

        self::assertTrue($export['account']['onboardingDue']);
        self::assertStringStartsWith('2026-10-22T10:00:00', (string) $export['account']['onboardingShownAt']);
        self::assertTrue($export['account']['marketingConsent']);
        self::assertNotNull($export['account']['marketingConsentAt']);
    }

    public function testAk61KontoloeschungNimmtAllesMit(): void
    {
        $user = $this->konto('f12-loeschen@qa.lu');
        static::getContainer()->get(MarketingContactRegistry::class)->recordUser($user, 'de');
        $this->em->flush();

        static::getContainer()->get(AccountDeleter::class)->delete($user);
        $this->em->clear();

        self::assertNull(static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'f12-loeschen@qa.lu']));
        $kontakt = static::getContainer()->get(MarketingContactRepository::class)->findOneByEmail('f12-loeschen@qa.lu');
        self::assertTrue(null === $kontakt || MarketingSyncState::REMOVAL_PENDING === $kontakt->getSyncState(), 'Der Brevo-Kontakt muss als Löschauftrag stehen (04/AK-14).');
    }
}
