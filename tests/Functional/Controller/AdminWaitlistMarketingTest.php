<?php

namespace App\Tests\Functional\Controller;

use App\Entity\MarketingContact;
use App\Entity\PartnerWaitlistEntry;
use App\Enum\MarketingOrigin;
use App\Enum\MarketingSyncState;
use App\Enum\WaitlistStatus;
use App\Repository\MarketingContactRepository;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Die Marketing-Anzeige in der Wartelisten-Verwaltung (Feature 04).
 *
 * ⚠ Der Sinn dieser Anzeige: Eine nicht übertragene Adresse erzeugt **keinen
 * Fehler**. Sentry zeigt Ausfälle, aber keine Lücken. Fällt sie hier nicht
 * auf, fällt sie erst auf, wenn eine Kampagne jemanden nicht erreicht.
 */
final class AdminWaitlistMarketingTest extends AbstractWebTestCase
{
    private function eintragMitKontakt(
        KernelBrowser $client,
        string $email,
        ?MarketingSyncState $state,
        ?string $fehler = null,
    ): PartnerWaitlistEntry {
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $entry = new PartnerWaitlistEntry();
        $entry->setRestaurantName('Verwaltungshaus')
            ->setContactName('Kontakt')
            ->setEmail($email)
            ->setLocality('Esch-Uelzecht');

        if (null !== $state) {
            $entry->setMarketingConsentAt(new \DateTimeImmutable());
            $entry->confirm();
        }

        $em->persist($entry);

        if (null !== $state) {
            $kontakt = new MarketingContact();
            $kontakt->setEmail($email)->setOrigin(MarketingOrigin::PARTNER)->setSyncState($state);

            if (MarketingSyncState::SYNCED === $state) {
                $kontakt->markSynced();
            }

            if (null !== $fehler) {
                $kontakt->setLastError($fehler);
                $kontakt->setAttempts(5);
            }

            $em->persist($kontakt);
        }

        $em->flush();

        return $entry;
    }

    /**
     * AK-26, AK-18: Die Liste zeigt je Zeile, ob der Eintrag in Brevo steht.
     */
    public function testAk26ListeZeigtDenSyncZustand(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $this->eintragMitKontakt($client, 'ausstehend@qa.lu', MarketingSyncState::PENDING);
        $this->eintragMitKontakt($client, 'uebertragen@qa.lu', MarketingSyncState::SYNCED);
        $this->eintragMitKontakt($client, 'kaputt@qa.lu', MarketingSyncState::FAILED, 'HTTP 429');
        $this->eintragMitKontakt($client, 'ohne@qa.lu', null);

        $crawler = $client->request('GET', self::LOCALE . '/admin/warteliste');

        self::assertResponseIsSuccessful();

        $inhalt = $crawler->html();

        self::assertStringContainsString('Brevo', $inhalt, 'Die Spalte fehlt');
        self::assertStringContainsString('Ausstehend', $inhalt);
        self::assertStringContainsString('Übertragen', $inhalt);
        self::assertStringContainsString('Fehlgeschlagen', $inhalt);
        self::assertStringContainsString('Keine Einwilligung', $inhalt, 'Der leere Zustand ist für Screenreader nicht benannt');
    }

    /**
     * AK-27: Die Zählung steht in der Kopfzeile — die Gegenprobe zu Brevo.
     */
    public function testAk27ZaehlungStehtInDerKopfzeile(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $this->eintragMitKontakt($client, 'z1@qa.lu', MarketingSyncState::SYNCED);
        $this->eintragMitKontakt($client, 'z2@qa.lu', MarketingSyncState::PENDING);

        $client->request('GET', self::LOCALE . '/admin/warteliste');

        self::assertMatchesRegularExpression(
            '/\d+ eingewilligt · \d+ übertragen/u',
            $client->getResponse()->getContent(),
        );
    }

    /**
     * AK-15, AK-18: Die Detailansicht nennt den letzten Fehler — und zwar nur,
     * wenn es einen gibt.
     */
    public function testAk15DetailansichtZeigtDenLetztenFehler(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $mitFehler = $this->eintragMitKontakt($client, 'detail-fehler@qa.lu', MarketingSyncState::FAILED, 'HTTP 500');
        $ohne = $this->eintragMitKontakt($client, 'detail-ohne@qa.lu', null);

        $client->request('GET', self::LOCALE . '/admin/warteliste/partner/' . $mitFehler->getId());
        self::assertStringContainsString('HTTP 500', $client->getResponse()->getContent());

        $client->request('GET', self::LOCALE . '/admin/warteliste/partner/' . $ohne->getId());
        $inhalt = $client->getResponse()->getContent();
        self::assertStringContainsString('Keine Einwilligung', $inhalt);
        self::assertStringNotContainsString('Letzter Fehler', $inhalt, 'Eine dauerhaft leere Fehlerzeile lehrt, über die Stelle hinwegzusehen');
    }

    /**
     * ⚠ AK-09: Ein Statuswechsel in der Verwaltung stellt die Übertragung
     * zurück auf `pending`.
     *
     * Der Vertriebsstatus ist in Brevo ein Segmentkriterium — bliebe die
     * Änderung hier hängen, liefe eine Partnerprogramm-Kampagne an Häuser, mit
     * denen der Vorgang längst abgeschlossen ist.
     */
    public function testAk09StatuswechselStelltDieUebertragungZurueck(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $entry = $this->eintragMitKontakt($client, 'statuswechsel@qa.lu', MarketingSyncState::SYNCED);

        $crawler = $client->request('GET', self::LOCALE . '/admin/warteliste/partner/' . $entry->getId());
        $form = $this->formByAction($crawler, '/admin/warteliste/partner/' . $entry->getId() . '/status');
        $form['status'] = WaitlistStatus::CONVERTED->value;

        $client->submit($form);

        self::assertResponseRedirects();

        $kontakt = static::getContainer()->get(MarketingContactRepository::class)->findOneByEmail('statuswechsel@qa.lu');

        self::assertSame(
            MarketingSyncState::PENDING,
            $kontakt->getSyncState(),
            'Der Statuswechsel hat die Übertragung nicht angestoßen',
        );
        self::assertSame(WaitlistStatus::CONVERTED, $kontakt->getFunnelStatus());
    }

    /**
     * ⚠ BF-83 (AK-05): Ein Statuswechsel an einem **nie bestätigten** Eintrag
     * darf keinen Marketing-Kontakt auslösen.
     *
     * `applyStatus()` setzt `confirmedAt` nach, wenn ein Eintrag von Hand
     * weitergesetzt wird — ein gewolltes Bestandsmuster für telefonisch
     * geführte Kontakte. Vor der Reparatur meldete `isConfirmed()` danach
     * `true`, und die Registry trug die Adresse ein: Sie ging nach Brevo,
     * ohne dass ihr Inhaber je den Bestätigungslink geklickt hatte.
     *
     * Ein Telefonat rechtfertigt den Vertriebsstatus, nicht die Werbung.
     */
    public function testBf83StatuswechselAnUnbestaetigtemEintragErzeugtKeinenKontakt(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $em = static::getContainer()->get(EntityManagerInterface::class);

        $entry = new PartnerWaitlistEntry();
        $entry->setRestaurantName('Nie bestätigt')
            ->setContactName('Kontakt')
            ->setEmail('bf83@qa.lu')
            ->setLocality('Esch-Uelzecht')
            ->setMarketingConsentAt(new \DateTimeImmutable());
        $entry->generateConfirmationToken();

        $em->persist($entry);
        $em->flush();

        self::assertFalse($entry->isConfirmed(), 'Vorbedingung: der Eintrag ist unbestätigt');

        $crawler = $client->request('GET', self::LOCALE . '/admin/warteliste/partner/' . $entry->getId());
        $form = $this->formByAction($crawler, '/admin/warteliste/partner/' . $entry->getId() . '/status');
        $form['status'] = WaitlistStatus::CONTACTED->value;

        $client->submit($form);

        self::assertResponseRedirects();

        // Nach dem Request neu laden – die Instanz aus dem Test kennt die
        // Änderung des Requests nicht.
        $nachher = static::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(PartnerWaitlistEntry::class)
            ->findOneBy(['email' => 'bf83@qa.lu']);

        self::assertNotNull($nachher->getConfirmedAt(), 'Der Bestandsvorgang selbst soll weiter funktionieren');
        self::assertSame(WaitlistStatus::CONTACTED, $nachher->getStatus());

        self::assertNull(
            static::getContainer()->get(MarketingContactRepository::class)->findOneByEmail('bf83@qa.lu'),
            'BF-83: Eine nie per Double-Opt-In bestätigte Adresse steht im Auftragsbuch',
        );
    }

    /**
     * ⚠ BF-89 (AK-05): Auch der **zweite** Statuswechsel darf keinen
     * Marketing-Kontakt auslösen.
     *
     * Die Reparatur von BF-83 hält den Bestätigungsstand fest, *bevor* der
     * Backfill läuft — das deckt den ersten Wechsel. Beim zweiten steht
     * `confirmedAt` bereits, gesetzt vom ersten Wechsel: Der Eintrag gilt jetzt
     * als „bereits bestätigt", und die Adresse geht hinaus. Ein Vertriebsablauf
     * mit zwei Schritten (Telefonat → Vorprüfung) ist der Normalfall, nicht die
     * Ausnahme.
     *
     * Die Ursache liegt tiefer als die Reihenfolge: `confirmedAt` trägt zwei
     * Bedeutungen — echter Double-Opt-In und Verwaltungs-Backfill — und
     * unterscheidet sie nicht.
     */
    public function testBf89AuchDerZweiteStatuswechselErzeugtKeinenKontakt(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'admin@endlech.lu');

        $em = static::getContainer()->get(EntityManagerInterface::class);

        $entry = new PartnerWaitlistEntry();
        $entry->setRestaurantName('Zweiter Wechsel')
            ->setContactName('Kontakt')
            ->setEmail('bf89@qa.lu')
            ->setLocality('Esch-Uelzecht')
            ->setMarketingConsentAt(new \DateTimeImmutable());
        $entry->generateConfirmationToken();

        $em->persist($entry);
        $em->flush();

        $id = $entry->getId();

        foreach ([WaitlistStatus::CONTACTED, WaitlistStatus::QUALIFIED] as $status) {
            $crawler = $client->request('GET', self::LOCALE . '/admin/warteliste/partner/' . $id);
            $form = $this->formByAction($crawler, '/admin/warteliste/partner/' . $id . '/status');
            $form['status'] = $status->value;

            $client->submit($form);
            self::assertResponseRedirects();
        }

        self::assertNull(
            static::getContainer()->get(MarketingContactRepository::class)->findOneByEmail('bf89@qa.lu'),
            'BF-89: Der zweite Statuswechsel hat die nie bestätigte Adresse ins Auftragsbuch getragen',
        );
    }

    /**
     * AK-35, AK-38: Ein Nutzer ohne Verwaltungsrolle sieht davon nichts.
     */
    public function testAk35NutzerOhneRolleSiehtDieSyncAnsichtNicht(): void
    {
        $client = static::createClient();
        $this->loginAs($client, 'user@endlech.lu');

        $client->request('GET', self::LOCALE . '/admin/warteliste');

        self::assertResponseStatusCodeSame(403);
    }
}
