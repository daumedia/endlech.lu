<?php

declare(strict_types=1);

namespace App\Tests\Integration\Marketing;

use App\Entity\MarketingContact;
use App\Entity\PartnerWaitlistEntry;
use App\Entity\User;
use App\Enum\MarketingOrigin;
use App\Enum\MarketingSyncState;
use App\Marketing\MarketingContactRegistry;
use App\Repository\MarketingContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Feature 12 — was das Auftragsbuch beim Abonnieren und Abbestellen aus einem Konto tut.
 *
 * Zwei Änderungen gegenüber Feature 04: Die Sprache eines Konto-Kontakts kommt aus der Anfrage
 * statt fest `de` (AK-40), und ein Konto kann selbst abbestellen — mit einer Sperre statt einer
 * Löschung, damit die Willkommens-Mail nicht ein zweites Mal kommt (AK-44).
 */
final class Feature12RegistryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private MarketingContactRegistry $registry;
    private MarketingContactRepository $contacts;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->registry = static::getContainer()->get(MarketingContactRegistry::class);
        $this->contacts = static::getContainer()->get(MarketingContactRepository::class);
    }

    private function konto(string $email, bool $einwilligung = true, bool $bestaetigt = true): User
    {
        $user = new User();
        $user->setName('Konto '.$email)
            ->setEmail($email)
            ->setPassword('x')
            ->setIsVerified($bestaetigt);

        if ($einwilligung) {
            $user->setMarketingConsentAt(new \DateTimeImmutable('-1 hour'));
        }

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function testAk40SpracheDerAnfrageLandetAmKontakt(): void
    {
        $user = $this->konto('f12-sprache@qa.lu');

        $this->registry->recordUser($user, 'pt');
        $this->em->flush();

        self::assertSame('pt', $this->contacts->findOneByEmail('f12-sprache@qa.lu')?->getLocale());
    }

    public function testAk40OhneSpracheBleibtDieGespeicherte(): void
    {
        // Eine Neuableitung ohne Anfrage (BF-84, Quellenwechsel) darf die Sprache nicht
        // auf `de` zurückdrehen.
        $user = $this->konto('f12-bleibt@qa.lu');
        $this->registry->recordUser($user, 'fr');
        $this->em->flush();

        $this->registry->recordUser($user);
        $this->em->flush();

        self::assertSame('fr', $this->contacts->findOneByEmail('f12-bleibt@qa.lu')?->getLocale());
    }

    public function testOhneSpracheUndOhneZeileBleibtEsBeiDe(): void
    {
        $user = $this->konto('f12-vorgabe@qa.lu');

        $this->registry->recordUser($user);
        $this->em->flush();

        self::assertSame('de', $this->contacts->findOneByEmail('f12-vorgabe@qa.lu')?->getLocale());
    }

    public function testAk36AbbestellenOhneZeileLeertNurDieEinwilligung(): void
    {
        $user = $this->konto('f12-ohne-zeile@qa.lu', bestaetigt: false);

        $this->registry->revokeForAccount($user);
        $this->em->flush();

        self::assertNull($user->getMarketingConsentAt());
        self::assertNull($this->contacts->findOneByEmail('f12-ohne-zeile@qa.lu'), 'Eine Sperre auf Vorrat wäre ein Datensatz über jemanden, der nie übertragen wurde.');
    }

    /**
     * ⚠ BF-181: Bis zum dritten Fehlerauftrag hieß dieser Test „…aber nicht an Brevo geschickt" und verlangte, dass
     * eine nie übertragene Zeile KEINEN Sperrauftrag bekommt. Die Annahme dahinter („nie übertragen" lässt sich an
     * `synced_at`, `attempts` und dem Zustand ablesen) war falsch: Eine verlorene Antwort, ein Adresswechsel oder eine
     * neue Einwilligung setzen den Zähler zurück, während der Kontakt bei Brevo längst steht. Seither bekommt jede Zeile
     * den Auftrag. AK-46 bleibt gewahrt, weil der Auftrag nichts anlegt — belegt in
     * `Feature12SperreSyncTest::testBf181NieUebertragenerKontaktErzeugtNurDenSperraufruf`.
     */
    public function testAk46NieUebertragenerKontaktBekommtDenSperrauftrag(): void
    {
        $user = $this->konto('f12-nie@qa.lu');
        $this->registry->recordUser($user, 'de');
        $this->em->flush();

        $this->registry->revokeForAccount($user);
        $this->em->flush();

        $kontakt = $this->contacts->findOneByEmail('f12-nie@qa.lu');
        self::assertNotNull($kontakt);
        self::assertTrue($kontakt->isBlocked());
        self::assertSame(MarketingSyncState::BLACKLIST_PENDING, $kontakt->getSyncState(), 'Ob es ihn bei Brevo gibt, weiß die Zeile nicht sicher (BF-181).');
    }

    public function testAk36UebertragenerKontaktBekommtDenSperrauftrag(): void
    {
        $user = $this->konto('f12-uebertragen@qa.lu');
        $kontakt = $this->registry->recordUser($user, 'de');
        $this->em->flush();
        $kontakt?->markSynced();
        $this->em->flush();

        $this->registry->revokeForAccount($user);
        $this->em->flush();

        $kontakt = $this->contacts->findOneByEmail('f12-uebertragen@qa.lu');
        self::assertSame(MarketingSyncState::BLACKLIST_PENDING, $kontakt?->getSyncState());
        self::assertTrue($kontakt->isBlocked());
        self::assertNull($user->getMarketingConsentAt());
    }

    public function testAbbestellenNimmtDieEinwilligungAnAllenQuellenDerAdresse(): void
    {
        // design.md, Entscheidung 5: ein Brevo-Kontakt je Adresse — eine Sperre trifft ihn
        // ganz, also fällt die Einwilligung auch an der Warteliste.
        $user = $this->konto('f12-quellen@qa.lu');
        $eintrag = new PartnerWaitlistEntry();
        $eintrag->setRestaurantName('Haus')
            ->setContactName('Kontakt')
            ->setEmail('f12-quellen@qa.lu')
            ->setLocality('Esch-Uelzecht')
            ->setMarketingConsentAt(new \DateTimeImmutable('-2 hours'));
        $eintrag->confirm();
        $this->em->persist($eintrag);
        $this->em->flush();
        $this->registry->recordUser($user, 'de');
        $this->em->flush();

        $this->registry->revokeForAccount($user);
        $this->em->flush();

        self::assertNull($eintrag->getMarketingConsentAt());
    }

    public function testAk35ErneutesAbonnierenNachDemAbbestellenGibtDieZeileFrei(): void
    {
        $user = $this->konto('f12-wieder@qa.lu');
        $kontakt = $this->registry->recordUser($user, 'de');
        $this->em->flush();
        $kontakt?->markSynced();
        $this->em->flush();
        $this->registry->revokeForAccount($user);
        $this->em->flush();

        // Im selben Takt wieder eingewilligt — die Datenbank speichert Sekunden, also muss
        // die neue Einwilligung trotzdem sicher nach der Sperre liegen.
        $user->setMarketingConsentAt($this->registry->consentMomentFor('f12-wieder@qa.lu', new \DateTimeImmutable()));
        $this->registry->recordUser($user, 'de');
        $this->em->flush();
        $this->em->clear();

        $kontakt = $this->contacts->findOneByEmail('f12-wieder@qa.lu');
        self::assertFalse($kontakt?->isBlocked(), 'Nach dem Neuladen aus der Datenbank muss die Einwilligung jünger sein als die Sperre.');
        self::assertSame(MarketingSyncState::PENDING, $kontakt?->getSyncState());
        self::assertSame(MarketingOrigin::ACCOUNT, $kontakt?->getOrigin());
    }

    public function testEinwilligungsmomentOhneSperreIstJetzt(): void
    {
        $jetzt = new \DateTimeImmutable('2026-10-22 12:00:00');

        self::assertEquals($jetzt, $this->registry->consentMomentFor('f12-unbekannt@qa.lu', $jetzt));
    }

    public function testEinwilligungsmomentLiegtNachEinerGleichzeitigenSperre(): void
    {
        $kontakt = new MarketingContact();
        $kontakt->setEmail('f12-takt@qa.lu')
            ->setOrigin(MarketingOrigin::ACCOUNT)
            ->setConsentAt(new \DateTimeImmutable('2026-10-22 11:00:00'))
            ->setRevokedAt(new \DateTimeImmutable('2026-10-22 12:00:00'));
        $this->em->persist($kontakt);
        $this->em->flush();

        $moment = $this->registry->consentMomentFor('f12-takt@qa.lu', new \DateTimeImmutable('2026-10-22 12:00:00.400000'));

        self::assertSame('2026-10-22 12:00:01', $moment->format('Y-m-d H:i:s'));
    }
}
