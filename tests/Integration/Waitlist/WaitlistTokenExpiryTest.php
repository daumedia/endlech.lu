<?php

declare(strict_types=1);

namespace App\Tests\Integration\Waitlist;

use App\Entity\PartnerWaitlistEntry;
use App\Waitlist\WaitlistConfirmationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * BF-36: Ein Bestätigungslink verfällt nach sieben Tagen.
 *
 * Vorher galt er unbegrenzt — ein Link aus einer Mail von vor einem Jahr
 * bestätigte weiterhin eine Einwilligung, an die sich niemand mehr erinnert.
 * `User::generateVerificationToken()` macht es im selben Projekt richtig
 * (24 Stunden).
 */
final class WaitlistTokenExpiryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private WaitlistConfirmationService $service;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->service = $container->get(WaitlistConfirmationService::class);
    }

    private function eintrag(string $alter): PartnerWaitlistEntry
    {
        $entry = new PartnerWaitlistEntry();
        $entry->setRestaurantName('Ablauf Probe');
        $entry->setContactName('QA');
        $entry->setEmail('ablauf-'.uniqid().'@example.test');
        $entry->setLocality('Wiltz');
        $entry->setConsentAt(new \DateTimeImmutable());
        $entry->setLocale('de');
        $entry->generateConfirmationToken();

        // `createdAt` setzt der Konstruktor; für den Test zurückdatieren.
        $spiegel = new \ReflectionProperty(PartnerWaitlistEntry::class, 'createdAt');
        $spiegel->setValue($entry, new \DateTimeImmutable($alter));

        $this->em->persist($entry);
        $this->em->flush();

        return $entry;
    }

    #[DataProvider('fristen')]
    public function testAlterEntscheidetUeberDieGueltigkeit(string $alter, string $erwartet): void
    {
        self::assertSame($erwartet, $this->service->confirm($this->eintrag($alter)));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function fristen(): iterable
    {
        yield 'heute' => ['now', WaitlistConfirmationService::RESULT_CONFIRMED];
        yield 'sechs Tage alt' => ['-6 days', WaitlistConfirmationService::RESULT_CONFIRMED];
        yield 'acht Tage alt' => ['-8 days', WaitlistConfirmationService::RESULT_EXPIRED];
        yield 'ein Jahr alt' => ['-1 year', WaitlistConfirmationService::RESULT_EXPIRED];
    }

    /**
     * Ein bereits bestätigter Eintrag bleibt bestätigt, auch wenn er alt ist —
     * sonst läse ein zweiter Klick sich wie ein Fehlschlag.
     */
    public function testBereitsBestaetigtSchlaegtAblaufNichtUm(): void
    {
        $entry = $this->eintrag('-1 year');
        $entry->confirm();
        $this->em->flush();

        self::assertSame(WaitlistConfirmationService::RESULT_ALREADY, $this->service->confirm($entry));
    }

    public function testUnbekannterEintragBleibtUngueltig(): void
    {
        self::assertSame(WaitlistConfirmationService::RESULT_INVALID, $this->service->confirm(null));
    }
}
