<?php

namespace App\Tests\Integration\Marketing;

use App\Entity\PartnerWaitlistEntry;
use App\Entity\User;
use App\Enum\MarketingOrigin;
use App\Enum\MarketingSyncState;
use App\Marketing\MarketingContactRegistry;
use App\Repository\MarketingContactRepository;
use App\Waitlist\WaitlistConfirmationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Regressionen zu den Befunden aus `qa-report.md` (Feature 04, 2026-08-29).
 *
 * Sie waren die ausführbaren Reproduktionen der QA und sind seit der Reparatur
 * am selben Tag grün. **Sie bleiben stehen**: Jeder dieser Fehler war beim
 * Lesen des Codes nicht zu sehen — er entstand aus dem Zusammenspiel zweier
 * für sich richtiger Stellen. Ein solcher Fehler kommt wieder, wenn nichts ihn
 * festhält.
 *
 * Zugehörig: BF-84 (Löschsemantik bei geteilter Adresse), BF-84b
 * (Webhook-Echo), BF-85 (Doppelanlage ohne `flush()`).
 * BF-83 steht in `AdminWaitlistMarketingTest`, BF-86 in `MarketingSyncServiceTest`.
 */
final class MarketingBefundeTest extends KernelTestCase
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

    /**
     * BF-83 wird über den echten Verwaltungsweg geprüft und steht deshalb in
     * `tests/Functional/Controller/AdminWaitlistMarketingTest.php`
     * (`testBf83…`). Hier stünde er auf der falschen Ebene: Die Registry
     * verhält sich vertragsgemäß — sie prüft `isConfirmed()`. Die
     * Zweideutigkeit entstand im Controller, der `confirmedAt` vorher
     * nachsetzte.
     */

    /**
     * ⚠ BF-89: `confirmedAt` und `selfConfirmedAt` bedeuten Verschiedenes.
     *
     * Das ist die Zusicherung, auf der AK-05 seit dieser Reparatur ruht. Wer
     * sie aufhebt — etwa indem der Verwaltungs-Backfill auch `selfConfirmedAt`
     * setzt —, öffnet den Weg wieder, über den eine nie bestätigte Adresse
     * nach Brevo gelangte.
     */
    public function testBf89BackfillSetztKeineSelbstbestaetigung(): void
    {
        $entry = new PartnerWaitlistEntry();
        $entry->setRestaurantName('Backfill')
            ->setContactName('Kontakt')
            ->setEmail('bf89-feld@qa.lu')
            ->setLocality('Esch-Uelzecht')
            ->setMarketingConsentAt(new \DateTimeImmutable());

        // Das tut der Verwaltungs-Statuswechsel.
        $entry->setConfirmedAt(new \DateTimeImmutable());

        self::assertTrue($entry->isConfirmed(), 'Vorbedingung: gilt als bestätigt');
        self::assertFalse($entry->hasSelfConfirmed(), 'Der Backfill darf keine Selbstbestätigung erzeugen');

        $this->em->persist($entry);
        $this->em->flush();

        self::assertNull(
            $this->registry->recordWaitlistEntry($entry),
            'BF-89: Ein bloß verwaltungsseitig bestätigter Eintrag ging nach Brevo',
        );

        // Der echte Bestätigungslink setzt beides.
        $entry->confirm();

        self::assertTrue($entry->hasSelfConfirmed());
        self::assertNotNull(
            $this->registry->recordWaitlistEntry($entry),
            'Nach dem echten Double-Opt-In muss der Eintrag durchgehen',
        );
    }

    /**
     * ⚠ BF-89, die Kehrseite: Nach einem Backfill muss der **echte**
     * Bestätigungslink noch einlösbar sein.
     *
     * Vorher lief er in „bereits bestätigt" und trug nichts ein — wer
     * tatsächlich bestätigte, kam nie nach Brevo.
     */
    public function testBf89EchterLinkBleibtNachEinemBackfillEinloesbar(): void
    {
        $entry = new PartnerWaitlistEntry();
        $entry->setRestaurantName('Kehrseite')
            ->setContactName('Kontakt')
            ->setEmail('bf89-kehrseite@qa.lu')
            ->setLocality('Esch-Uelzecht')
            ->setMarketingConsentAt(new \DateTimeImmutable());
        $entry->generateConfirmationToken();
        $entry->setConfirmedAt(new \DateTimeImmutable());

        $this->em->persist($entry);
        $this->em->flush();

        $ergebnis = static::getContainer()->get(WaitlistConfirmationService::class)->confirm($entry);

        self::assertSame(
            WaitlistConfirmationService::RESULT_CONFIRMED,
            $ergebnis,
            'Der echte Link lief in „bereits bestätigt"',
        );
        self::assertNotNull($this->contacts->findOneByEmail('bf89-kehrseite@qa.lu'));

        // Ein zweiter Klick bleibt „bereits bestätigt".
        self::assertSame(
            WaitlistConfirmationService::RESULT_ALREADY,
            static::getContainer()->get(WaitlistConfirmationService::class)->confirm($entry),
        );
    }

    /**
     * ⚠ BF-91: Der eingelöste Bestätigungslink darf einen bereits
     * fortgeschrittenen **Vertriebsstatus nicht zurücksetzen**.
     *
     * Eingeführt durch die BF-89-Reparatur: Seit `confirm()` nicht mehr an
     * `isConfirmed()` scheitert, läuft es bei einem verwaltungsseitig
     * weitergesetzten Eintrag tatsächlich durch — und
     * `PartnerWaitlistEntry::confirm()` setzt neben den Zeitstempeln auch
     * `status = CONFIRMED`. Ein gewonnener Kunde (`converted`) wird damit
     * durch einen Klick des Interessenten wieder zu einer bloßen Bestätigung.
     *
     * Der Vertriebsstand ist ein vom Betreiber gepflegtes Datum. Er geht hier
     * unbemerkt verloren.
     */
    public function testBf91BestaetigungSetztDenVertriebsstatusNichtZurueck(): void
    {
        $entry = new PartnerWaitlistEntry();
        $entry->setRestaurantName('Gewonnener Kunde')
            ->setContactName('Kontakt')
            ->setEmail('bf91@qa.lu')
            ->setLocality('Esch-Uelzecht');
        $entry->generateConfirmationToken();

        // Der Verwaltungsweg: Backfill plus fortgeschrittener Vertriebsstatus.
        $entry->setConfirmedAt(new \DateTimeImmutable());
        $entry->setStatus(\App\Enum\WaitlistStatus::CONVERTED);

        $this->em->persist($entry);
        $this->em->flush();

        static::getContainer()->get(WaitlistConfirmationService::class)->confirm($entry);
        $this->em->flush();

        self::assertSame(
            \App\Enum\WaitlistStatus::CONVERTED,
            $entry->getStatus(),
            'BF-91: Der Bestätigungsklick hat den Vertriebsstatus auf „confirmed" zurückgesetzt',
        );
        self::assertTrue(
            $entry->hasSelfConfirmed(),
            'Die Selbstbestätigung soll trotzdem festgehalten werden',
        );
    }

    /**
     * ⚠ BF-84 (EC-01): Bei zwei aktiven Quellen mit derselben Adresse löscht
     * der Widerruf **einer** Quelle den geteilten Kontakt vollständig.
     *
     * Die andere Quelle hat nie widerrufen und meldet weiterhin eine gültige
     * Einwilligung — ihre Wirkung ist trotzdem weg, ohne jede Fehleranzeige.
     */
    public function testBf84WiderrufEinerQuelleDarfDieAndereNichtMitnehmen(): void
    {
        $email = 'bf84@qa.lu';

        $entry = new PartnerWaitlistEntry();
        $entry->setRestaurantName('Sein Restaurant')
            ->setContactName('Kontakt')
            ->setEmail($email)
            ->setLocality('Esch-Uelzecht')
            ->setMarketingConsentAt(new \DateTimeImmutable());
        $entry->confirm();
        $this->em->persist($entry);

        $user = new User();
        $user->setName('Sein Konto')
            ->setEmail($email)
            ->setPassword('irrelevant')
            ->setIsVerified(true)
            ->setMarketingConsentAt(new \DateTimeImmutable());
        $this->em->persist($user);
        $this->em->flush();

        $this->registry->recordWaitlistEntry($entry);
        $this->em->flush();
        $this->registry->recordUser($user);
        $this->em->flush();

        // Nur die Warteliste wird widerrufen – das Konto bleibt bestehen.
        static::getContainer()->get(WaitlistConfirmationService::class)->revoke($entry);

        self::assertTrue($user->hasMarketingConsent(), 'Vorbedingung: das Konto hat nicht widerrufen');

        $kontakt = $this->contacts->findOneByEmail($email);

        self::assertNotNull($kontakt, 'BF-84: Die Zeile ist verschwunden');
        self::assertNotSame(
            MarketingSyncState::REMOVAL_PENDING,
            $kontakt->getSyncState(),
            'BF-84: Der Kontakt wird gelöscht, obwohl das Konto seine Einwilligung nie zurückgenommen hat',
        );

        // Die Zusicherung, nicht nur die Abwesenheit des Fehlers: Die Zeile
        // gehört jetzt der verbliebenen Quelle und geht als Aktualisierung
        // hinaus — Herkunft `account`, kein Vertriebsstatus mehr.
        self::assertSame(MarketingOrigin::ACCOUNT, $kontakt->getOrigin());
        self::assertNull($kontakt->getFunnelStatus(), 'Der Vertriebsstatus der aufgegebenen Warteliste steht noch da');
        self::assertSame(MarketingSyncState::PENDING, $kontakt->getSyncState());
    }

    /**
     * ⚠ BF-84b: Die eigene Löschung kommt als `contactDeleted` zurück und
     * tilgt die Einwilligung an **allen** Quellen.
     *
     * Brevo löst dieses Ereignis auch bei einer Löschung über die API aus —
     * also bei unserem eigenen `delete()`-Aufruf. Damit verschwindet ein
     * Nachweis nach Art. 7 Abs. 1 DSGVO, den niemand widerrufen hat.
     */
    public function testBf84bWebhookEchoDarfDieEinwilligungNichtTilgen(): void
    {
        $user = new User();
        $user->setName('Konto')
            ->setEmail('bf84b@qa.lu')
            ->setPassword('irrelevant')
            ->setIsVerified(true)
            ->setMarketingConsentAt(new \DateTimeImmutable());

        $this->em->persist($user);
        $this->em->flush();

        $this->registry->recordUser($user);
        $this->em->flush();

        // Fall 1: `contactDeleted` ist keine Willenserklärung des Empfängers —
        // es entwertet die Einwilligung an der Quelle nicht.
        $this->registry->blockByEmail('bf84b@qa.lu', false);
        $this->em->flush();

        self::assertTrue(
            $user->hasMarketingConsent(),
            'BF-84b: Eine gelöschte Karteikarte bei Brevo hat die Einwilligung am Konto getilgt',
        );

        // Fall 2: Nach unserer eigenen Löschung ist die Zeile weg. Ein Echo,
        // das jetzt eintrifft, darf ins Leere laufen — auch als „Abmeldung".
        $kontakt = $this->contacts->findOneByEmail('bf84b@qa.lu');
        $this->em->remove($kontakt);
        $this->em->flush();

        $this->registry->blockByEmail('bf84b@qa.lu');
        $this->em->flush();

        self::assertTrue(
            $user->hasMarketingConsent(),
            'BF-84b: Das Echo der eigenen Löschung hat die Einwilligung am Konto getilgt',
        );
    }

    /**
     * ⚠ BF-85: Zwei `record()`-Aufrufe ohne `flush()` dazwischen erzeugen zwei
     * Entities für dieselbe Adresse — der `flush()` scheitert am Unique-Index.
     *
     * `MarketingContactRepository::findOneByEmail()` sieht die vorgemerkte,
     * noch nicht geschriebene Zeile nicht. Heute trifft das keinen
     * Anwendungspfad (jeder Aufrufer flusht sofort), aber `MarketingImportCommand`
     * musste deshalb bereits von Hand entdoppeln — die nächste Stelle, die das
     * vergisst, bekommt eine `UniqueConstraintViolationException`.
     */
    public function testBf85ZweiAufrufeOhneFlushDuerfenNichtKollidieren(): void
    {
        $this->registry->record(
            'bf85@qa.lu',
            \App\Enum\MarketingOrigin::PARTNER,
            new \DateTimeImmutable(),
            'de',
        );

        $this->registry->record(
            'bf85@qa.lu',
            \App\Enum\MarketingOrigin::ACCOUNT,
            new \DateTimeImmutable(),
            'de',
        );

        $this->em->flush();

        self::assertSame(
            1,
            (int) $this->em->getConnection()->fetchOne("SELECT COUNT(*) FROM marketing_contact WHERE email = 'bf85@qa.lu'"),
        );
    }
}
