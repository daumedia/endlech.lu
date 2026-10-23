<?php

declare(strict_types=1);

namespace App\Tests\Integration\Account;

use App\Account\NewsletterSubscription;
use App\Entity\User;
use App\Enum\MarketingSyncState;
use App\Marketing\MarketingContactRegistry;
use App\Repository\MarketingContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Feature 12 — der eine Dienst hinter Profilschalter und Newsletter-Schritt.
 *
 * Zwei Wege, ein Verhalten (AK-35 = AK-33), und ein gemeinsames Kontingent: Der Dienst sagt
 * deshalb, ob sich etwas geändert hat — nur dann verbraucht der Aufrufer (AK-60).
 */
final class NewsletterSubscriptionTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private NewsletterSubscription $newsletter;
    private MarketingContactRepository $contacts;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        // Direkt gebaut: Bis die Controller ihn benutzen, entfernt der Container den Dienst.
        $this->newsletter = new NewsletterSubscription(
            static::getContainer()->get(MarketingContactRegistry::class),
            $this->em,
        );
        $this->contacts = static::getContainer()->get(MarketingContactRepository::class);
    }

    private function konto(string $email, bool $bestaetigt = true): User
    {
        $user = new User();
        $user->setName('Konto')->setEmail($email)->setPassword('x')->setIsVerified($bestaetigt);
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function testAk33AbonnierenSpeichertDieEinwilligungUndStelltDenAuftrag(): void
    {
        $user = $this->konto('f12-abo@qa.lu');

        self::assertTrue($this->newsletter->subscribe($user, 'lb'));

        self::assertNotNull($user->getMarketingConsentAt());
        $kontakt = $this->contacts->findOneByEmail('f12-abo@qa.lu');
        self::assertSame(MarketingSyncState::PENDING, $kontakt?->getSyncState());
        self::assertSame('lb', $kontakt?->getLocale());
    }

    public function testAk38UnbestaetigtWirdGespeichertAberNichtUebertragen(): void
    {
        $user = $this->konto('f12-unbestaetigt@qa.lu', bestaetigt: false);

        self::assertTrue($this->newsletter->subscribe($user, 'de'));

        self::assertNotNull($user->getMarketingConsentAt());
        self::assertNull($this->contacts->findOneByEmail('f12-unbestaetigt@qa.lu'), '04/AK-05: erst die bestätigte Adresse, dann der Kontakt.');
    }

    public function testAk60ZweitesAbonnierenAendertNichts(): void
    {
        $user = $this->konto('f12-doppelt@qa.lu');
        $this->newsletter->subscribe($user, 'de');
        $erste = $user->getMarketingConsentAt();

        self::assertFalse($this->newsletter->subscribe($user, 'de'));
        self::assertEquals($erste, $user->getMarketingConsentAt(), 'Der Einwilligungszeitpunkt ist ein Nachweis — er wandert nicht mit.');
    }

    public function testAk36AbbestellenNimmtDieEinwilligungZurueck(): void
    {
        $user = $this->konto('f12-ab@qa.lu');
        $this->newsletter->subscribe($user, 'de');

        self::assertTrue($this->newsletter->unsubscribe($user));

        self::assertNull($user->getMarketingConsentAt());
        self::assertTrue($this->contacts->findOneByEmail('f12-ab@qa.lu')?->isBlocked());
    }

    public function testAk60AbbestellenOhneAboAendertNichts(): void
    {
        $user = $this->konto('f12-nichts@qa.lu');

        self::assertFalse($this->newsletter->unsubscribe($user));
        self::assertNull($this->contacts->findOneByEmail('f12-nichts@qa.lu'));
    }

    public function testAk35AbUndWiederInDerselbenSekundeBleibtWirksam(): void
    {
        $user = $this->konto('f12-hin-her@qa.lu');
        $this->newsletter->subscribe($user, 'de');
        $this->newsletter->unsubscribe($user);
        $this->newsletter->subscribe($user, 'de');
        $this->em->clear();

        $kontakt = $this->contacts->findOneByEmail('f12-hin-her@qa.lu');
        self::assertFalse($kontakt?->isBlocked(), 'Nach dem Neuladen aus der Datenbank (Sekundengenauigkeit) muss das Abo gelten.');
    }
}
