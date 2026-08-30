<?php

namespace App\Tests\Integration\Marketing;

use App\Entity\MarketingContact;
use App\Entity\OrganisationWaitlistEntry;
use App\Entity\PartnerWaitlistEntry;
use App\Enum\MarketingOrigin;
use App\Enum\MarketingSyncState;
use App\Enum\OrganisationType;
use App\Marketing\MarketingContactRegistry;
use App\Repository\MarketingContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Das Auftragsbuch (Feature 04) — Zustandsübergänge und Sperrlogik.
 *
 * Geprüft wird hier die Mechanik, die man beim Lesen des Codes für richtig
 * hält und die trotzdem schiefgehen kann: dass eine Adresse aus zwei Quellen
 * **einen** Kontakt ergibt, dass eine Abmeldung den nächsten Lauf überlebt,
 * und dass ein Löschauftrag nicht verloren geht.
 */
final class MarketingContactRegistryTest extends KernelTestCase
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

    private function partner(string $email, bool $consent = true, bool $confirmed = true): PartnerWaitlistEntry
    {
        $entry = new PartnerWaitlistEntry();
        $entry->setRestaurantName('Haus ' . $email)
            ->setContactName('Kontakt')
            ->setEmail($email)
            ->setLocality('Esch-Uelzecht');

        if ($consent) {
            $entry->setMarketingConsentAt(new \DateTimeImmutable());
        }

        if ($confirmed) {
            $entry->confirm();
        }

        $this->em->persist($entry);
        $this->em->flush();

        return $entry;
    }

    /**
     * AK-05, EC-03: Ohne bestätigte Adresse entsteht kein Auftrag.
     */
    public function testAk05UnbestaetigteQuelleErzeugtKeinenAuftrag(): void
    {
        self::assertNull(
            $this->registry->recordWaitlistEntry($this->partner('unbestaetigt@qa.lu', true, false)),
            'Eine unbestätigte Adresse darf nicht ins Auftragsbuch',
        );

        self::assertNull(
            $this->registry->recordWaitlistEntry($this->partner('ohne-consent@qa.lu', false, true)),
            'Ohne Einwilligung darf kein Auftrag entstehen',
        );
    }

    /**
     * ⚠ EC-01: Dieselbe Adresse aus zwei Quellen ergibt **einen** Kontakt.
     *
     * Sonst käme jede Kampagne doppelt an.
     */
    public function testEc01ZweiQuellenEineAdresseErgebenEinenKontakt(): void
    {
        $this->registry->recordWaitlistEntry($this->partner('doppelt@qa.lu'));
        $this->em->flush();

        $organisation = new OrganisationWaitlistEntry();
        $organisation->setType(OrganisationType::COMMUNE)
            ->setOrganisationName('Gemeng Test')
            ->setContactName('Kontakt')
            // Absichtlich in anderer Schreibweise – der Unique-Index greift nur,
            // wenn beide Wege gleich normalisieren.
            ->setEmail('DOPPELT@qa.lu')
            ->setMarketingConsentAt(new \DateTimeImmutable());
        $organisation->confirm();

        $this->em->persist($organisation);
        $this->em->flush();

        $this->registry->recordWaitlistEntry($organisation);
        $this->em->flush();

        $anzahl = (int) $this->em->getConnection()
            ->fetchOne("SELECT COUNT(*) FROM marketing_contact WHERE email = 'doppelt@qa.lu'");

        self::assertSame(1, $anzahl, 'Aus zwei Quellen ist mehr als ein Kontakt entstanden');
    }

    /**
     * ⚠ AK-12: Wer sich abgemeldet hat, wird nicht erneut eingetragen.
     * ⚠ AK-45: Eine **jüngere** Einwilligung hebt die Sperre auf.
     *
     * Die beiden Kriterien stehen gegeneinander; maßgeblich ist der jüngere
     * Zeitpunkt (Entscheidung 8 des Entwurfs).
     */
    public function testAk12UndAk45SperreUndIhreAufhebung(): void
    {
        $this->registry->recordWaitlistEntry($this->partner('sperre@qa.lu'));
        $this->em->flush();

        $kontakt = $this->contacts->findOneByEmail('sperre@qa.lu');
        self::assertInstanceOf(MarketingContact::class, $kontakt);

        $kontakt->setRevokedAt(new \DateTimeImmutable());
        $this->em->flush();

        self::assertNull(
            $this->registry->record('sperre@qa.lu', MarketingOrigin::PARTNER, new \DateTimeImmutable('-1 day'), 'de'),
            'AK-12: Eine ältere Einwilligung darf die Sperre nicht aufheben',
        );

        self::assertNotNull(
            $this->registry->record('sperre@qa.lu', MarketingOrigin::PARTNER, new \DateTimeImmutable('+1 minute'), 'de'),
            'AK-45: Eine jüngere Einwilligung macht die Adresse wieder frei',
        );
    }

    /**
     * AK-12: Die Sperre wirkt auch in der Abfrage des Sync-Laufs.
     */
    public function testAk12GesperrteZeileWirdVomLaufNichtAufgegriffen(): void
    {
        $this->registry->recordWaitlistEntry($this->partner('gesperrt@qa.lu'));
        $this->em->flush();

        $kontakt = $this->contacts->findOneByEmail('gesperrt@qa.lu');
        $kontakt->setRevokedAt(new \DateTimeImmutable('+1 hour'));
        $this->em->flush();

        self::assertNotContains(
            'gesperrt@qa.lu',
            array_map(static fn (MarketingContact $c): string => $c->getEmail(), $this->contacts->findOpenForSync(100)),
            'Eine gesperrte Zeile darf der Lauf nicht aufgreifen',
        );
    }

    /**
     * ⚠ Ein Löschauftrag ist von der Sperre ausgenommen.
     *
     * Er muss gerade dann durchkommen, wenn die Adresse gesperrt ist – sonst
     * bliebe der Kontakt bei Brevo stehen, obwohl lokal alles gelöscht ist.
     */
    public function testLoeschauftragKommtTrotzSperreDurch(): void
    {
        $this->registry->recordWaitlistEntry($this->partner('sperre-loeschen@qa.lu'));
        $this->em->flush();

        $kontakt = $this->contacts->findOneByEmail('sperre-loeschen@qa.lu');
        $kontakt->setRevokedAt(new \DateTimeImmutable('+1 hour'));
        $kontakt->setSyncState(MarketingSyncState::REMOVAL_PENDING);
        $this->em->flush();

        self::assertContains(
            'sperre-loeschen@qa.lu',
            array_map(static fn (MarketingContact $c): string => $c->getEmail(), $this->contacts->findOpenForSync(100)),
            'Der Löschauftrag wurde von der Sperre mitgefangen',
        );
    }

    /**
     * AK-13, EC-04: Löschauftrag stellen – und eine unbekannte Adresse läuft
     * folgenlos durch.
     */
    public function testAk13UndEc04Loeschauftrag(): void
    {
        self::assertNull(
            $this->registry->scheduleRemoval('nie-dagewesen@qa.lu'),
            'EC-04: Eine unbekannte Adresse darf keinen Fehler erzeugen',
        );

        $entry = $this->partner('zu-loeschen@qa.lu');
        $this->registry->recordWaitlistEntry($entry);
        $this->em->flush();

        // Die auslösende Quelle geht mit – sie verschwindet gleich und zählt
        // deshalb nicht mehr als aktiv (BF-84).
        $this->registry->scheduleRemoval('zu-loeschen@qa.lu', $entry);
        $this->em->flush();

        self::assertSame(
            MarketingSyncState::REMOVAL_PENDING,
            $this->contacts->findOneByEmail('zu-loeschen@qa.lu')->getSyncState(),
        );
    }

    /**
     * ⚠ AK-13/AK-16: Ein Fehlversuch darf den Löschauftrag nicht verlieren.
     *
     * Fiele `removal_pending` auf `failed`, wäre der Auftrag weg – und der
     * Kontakt bliebe dauerhaft in Brevo, weil die Quelle in diesem Moment
     * schon gelöscht ist und ihn niemand neu stellen kann.
     */
    public function testLoeschauftragUeberlebtEinenFehlversuch(): void
    {
        $this->registry->recordWaitlistEntry($this->partner('fehlschlag@qa.lu'));
        $this->em->flush();

        $kontakt = $this->contacts->findOneByEmail('fehlschlag@qa.lu');
        $kontakt->setSyncState(MarketingSyncState::REMOVAL_PENDING);
        $kontakt->markFailed('HTTP 500');
        $this->em->flush();

        self::assertSame(MarketingSyncState::REMOVAL_PENDING, $kontakt->getSyncState());
        self::assertSame(1, $kontakt->getAttempts());
    }

    /**
     * AK-19: Rückzug nach fünf Versuchen – danach bleibt die Zeile sichtbar
     * stehen, statt jeden Lauf erneut Zeit zu kosten.
     */
    public function testAk19RueckzugNachFuenfVersuchen(): void
    {
        $this->registry->recordWaitlistEntry($this->partner('rueckzug@qa.lu'));
        $this->em->flush();

        $kontakt = $this->contacts->findOneByEmail('rueckzug@qa.lu');

        for ($i = 0; $i < 5; ++$i) {
            $kontakt->markFailed('HTTP 429');
        }

        $this->em->flush();

        self::assertNotContains(
            'rueckzug@qa.lu',
            array_map(static fn (MarketingContact $c): string => $c->getEmail(), $this->contacts->findOpenForSync(100)),
            'Nach dem Rückzug darf der Lauf die Zeile nicht mehr aufgreifen',
        );
        self::assertTrue($kontakt->isStuck(), 'Die Zeile muss in der Verwaltung als festgefahren erkennbar sein');
    }

    /**
     * EC-02: Der Adresswechsel führt dieselbe Zeile fort — die `ext_id` bleibt.
     */
    public function testEc02AdresswechselFuehrtDieselbeZeileFort(): void
    {
        $this->registry->recordWaitlistEntry($this->partner('alt@qa.lu'));
        $this->em->flush();

        $id = $this->contacts->findOneByEmail('alt@qa.lu')->getId();

        $this->registry->changeEmail('alt@qa.lu', 'neu@qa.lu');
        $this->em->flush();

        self::assertNull($this->contacts->findOneByEmail('alt@qa.lu'));

        $nachher = $this->contacts->findOneByEmail('neu@qa.lu');
        self::assertNotNull($nachher);
        self::assertSame($id, $nachher->getId(), 'Die ext_id hat sich geändert – Brevo legte einen zweiten Kontakt an');
    }
}
