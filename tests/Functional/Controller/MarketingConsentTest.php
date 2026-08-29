<?php

namespace App\Tests\Functional\Controller;

use App\Entity\MarketingContact;
use App\Entity\PartnerWaitlistEntry;
use App\Repository\MarketingContactRepository;
use App\Repository\PartnerWaitlistEntryRepository;
use App\Repository\UserRepository;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Die Werbe-Einwilligung in den Formularen (Feature 04).
 *
 * Der Schwerpunkt liegt auf den Eigenschaften, die man beim Aufräumen am
 * ehesten kaputt macht: dass das Häkchen **nicht** vorangehakt ist, dass es
 * **keine Bedingung** für die Anmeldung ist, und dass eine Einwilligung ohne
 * bestätigte Adresse **nicht** nach Brevo geht.
 */
final class MarketingConsentTest extends AbstractWebTestCase
{
    /**
     * AK-01, AK-02: Das Feld ist da – und leer.
     */
    public function testDasHaekchenIstDaUndNichtVorangehakt(): void
    {
        $client = static::createClient();

        foreach ([self::LOCALE . '/partner', self::LOCALE . '/organisationen', self::LOCALE . '/register'] as $pfad) {
            $crawler = $client->request('GET', $pfad);

            self::assertResponseIsSuccessful("Seite nicht erreichbar: {$pfad}");

            $checkbox = $crawler->filter('input[type="checkbox"][name$="[marketingConsent]"]');

            self::assertCount(1, $checkbox, "Einwilligungsfeld fehlt oder ist doppelt auf {$pfad}");
            self::assertNull($checkbox->attr('checked'), "Vorangehakt auf {$pfad} – eine vorangehakte Einwilligung ist keine");
            self::assertNull($checkbox->attr('required'), "Als Pflichtfeld markiert auf {$pfad} – das verstieße gegen das Koppelungsverbot");
        }
    }

    /**
     * ⚠ AK-03, der Kern: Die Einwilligung ist **keine Bedingung**.
     *
     * Ein `IsTrue`-Constraint, das jemand „zur Sicherheit" ergänzt, macht aus
     * jeder Einwilligung dieser Liste eine unwirksame (Art. 7 Abs. 4 DSGVO).
     * Dieser Test wird dann rot.
     */
    public function testAnmeldungLaeuftOhneHaekchenDurch(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/partner');

        $form = $this->formWithField($crawler, 'partner_waitlist[email]', [
            'partner_waitlist[restaurantName]' => 'Testhaus ohne Werbung',
            'partner_waitlist[contactName]' => 'Test Person',
            'partner_waitlist[email]' => 'ohne-werbung@example.lu',
            'partner_waitlist[locality]' => 'Esch-Uelzecht',
            'partner_waitlist[consent]' => true,
        ]);

        $client->submit($form);

        self::assertResponseRedirects();

        $eintrag = static::getContainer()->get(PartnerWaitlistEntryRepository::class)
            ->findOneBy(['email' => 'ohne-werbung@example.lu']);

        self::assertNotNull($eintrag, 'Die Anmeldung ist ohne Werbe-Häkchen nicht durchgelaufen');
        self::assertNull($eintrag->getMarketingConsentAt(), 'Ohne Häkchen darf kein Einwilligungszeitpunkt entstehen');
    }

    /**
     * AK-04: Gesetztes Häkchen hinterlässt einen **Zeitpunkt**, nicht ein Flag.
     */
    public function testGesetztesHaekchenHaeltDenZeitpunktFest(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/partner');

        $form = $this->formWithField($crawler, 'partner_waitlist[email]', [
            'partner_waitlist[restaurantName]' => 'Testhaus mit Werbung',
            'partner_waitlist[contactName]' => 'Test Person',
            'partner_waitlist[email]' => 'mit-werbung@example.lu',
            'partner_waitlist[locality]' => 'Esch-Uelzecht',
            'partner_waitlist[consent]' => true,
            'partner_waitlist[marketingConsent]' => true,
        ]);

        $client->submit($form);

        self::assertResponseRedirects();

        $eintrag = static::getContainer()->get(PartnerWaitlistEntryRepository::class)
            ->findOneBy(['email' => 'mit-werbung@example.lu']);

        self::assertNotNull($eintrag);
        self::assertNotNull($eintrag->getMarketingConsentAt(), 'Der Einwilligungszeitpunkt fehlt (Art. 7 Abs. 1 verlangt den Nachweis)');
    }

    /**
     * ⚠ AK-05 / EC-03: Zugestimmt, aber nie bestätigt – **kein** Brevo-Kontakt.
     *
     * Wer den Double-Opt-In nie abschloss, hat nie belegt, dass die Adresse
     * ihm gehört.
     */
    public function testUnbestaetigteAnmeldungErzeugtKeinenKontakt(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/partner');

        $form = $this->formWithField($crawler, 'partner_waitlist[email]', [
            'partner_waitlist[restaurantName]' => 'Nie bestätigt',
            'partner_waitlist[contactName]' => 'Test Person',
            'partner_waitlist[email]' => 'nie-bestaetigt@example.lu',
            'partner_waitlist[locality]' => 'Esch-Uelzecht',
            'partner_waitlist[consent]' => true,
            'partner_waitlist[marketingConsent]' => true,
        ]);

        $client->submit($form);

        $kontakt = static::getContainer()->get(MarketingContactRepository::class)
            ->findOneByEmail('nie-bestaetigt@example.lu');

        self::assertNull($kontakt, 'Eine unbestätigte Adresse ist im Auftragsbuch gelandet');
    }

    /**
     * AK-05, AK-06: Erst die Bestätigung erzeugt den Auftrag.
     */
    public function testBestaetigungErzeugtDenAuftrag(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $eintrag = new PartnerWaitlistEntry();
        $eintrag->setRestaurantName('Bestätigtes Haus')
            ->setContactName('Test Person')
            ->setEmail('bestaetigt@example.lu')
            ->setLocality('Esch-Uelzecht')
            ->setMarketingConsentAt(new \DateTimeImmutable());
        $token = $eintrag->generateConfirmationToken();

        $em->persist($eintrag);
        $em->flush();

        $client->request('GET', self::LOCALE . '/partner/confirmation/' . $token);

        self::assertResponseIsSuccessful();

        $kontakt = static::getContainer()->get(MarketingContactRepository::class)
            ->findOneByEmail('bestaetigt@example.lu');

        self::assertInstanceOf(MarketingContact::class, $kontakt, 'Nach der Bestätigung fehlt der Auftrag im Auftragsbuch');
        self::assertSame('pending', $kontakt->getSyncState()->value);
        self::assertSame('partner', $kontakt->getOrigin()->value);
    }

    /**
     * ⚠ EC-06: Die Wahl überlebt ein ungültiges Formular.
     *
     * Fällt das Häkchen nach einem 422 stillschweigend zurück, hakt der Nutzer
     * es beim zweiten Anlauf nicht erneut an – und die Einwilligung geht
     * verloren, ohne dass jemand es merkt.
     */
    public function testHaekchenUeberlebtEinUngueltigesFormular(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/partner');

        // Ohne Pflicht-Häkchen `consent` ist der Submit ungültig → 422.
        $form = $this->formWithField($crawler, 'partner_waitlist[email]', [
            'partner_waitlist[restaurantName]' => 'Unvollständig',
            'partner_waitlist[contactName]' => 'Test Person',
            'partner_waitlist[email]' => 'unvollstaendig@example.lu',
            'partner_waitlist[locality]' => 'Esch-Uelzecht',
            'partner_waitlist[marketingConsent]' => true,
        ]);

        $crawler = $client->submit($form);

        self::assertResponseStatusCodeSame(422);

        $checkbox = $crawler->filter('input[type="checkbox"][name="partner_waitlist[marketingConsent]"]');

        self::assertCount(1, $checkbox);
        self::assertNotNull(
            $checkbox->attr('checked'),
            'Nach dem 422 ist die Werbe-Einwilligung zurückgefallen – der Nutzer hakt sie kein zweites Mal an',
        );
    }

    /**
     * ⚠ BF-91, zweiter Effekt: Eine **späte** Bestätigung löst keine
     * „Neue Anmeldung"-Meldung für einen Vorgang aus, den das Team längst
     * bearbeitet hat.
     */
    public function testBf91SpaeteBestaetigungBenachrichtigtDasTeamNicht(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $entry = new PartnerWaitlistEntry();
        $entry->setRestaurantName('Längst bearbeitet')
            ->setContactName('Kontakt')
            ->setEmail('bf91-spaet@qa.lu')
            ->setLocality('Esch-Uelzecht');
        $token = $entry->generateConfirmationToken();
        // Der Verwaltungsweg: Backfill plus fortgeschrittener Stand.
        $entry->setConfirmedAt(new \DateTimeImmutable());
        $entry->setStatus(\App\Enum\WaitlistStatus::CONVERTED);

        $em->persist($entry);
        $em->flush();

        $client->request('GET', self::LOCALE . '/partner/confirmation/' . $token);

        self::assertResponseIsSuccessful();
        self::assertEmailCount(0, null, 'Das Team wurde für einen abgeschlossenen Vorgang erneut benachrichtigt');
    }

    /**
     * Die Gegenprobe: Der **normale** Ablauf ist unberührt — Status wird
     * gesetzt, das Team bekommt seine Meldung.
     */
    public function testNormaleBestaetigungBenachrichtigtDasTeamWeiterhin(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $entry = new PartnerWaitlistEntry();
        $entry->setRestaurantName('Ganz normal')
            ->setContactName('Kontakt')
            ->setEmail('bf91-normal@qa.lu')
            ->setLocality('Esch-Uelzecht');
        $token = $entry->generateConfirmationToken();

        $em->persist($entry);
        $em->flush();

        $client->request('GET', self::LOCALE . '/partner/confirmation/' . $token);

        self::assertResponseIsSuccessful();
        self::assertSame(\App\Enum\WaitlistStatus::CONFIRMED, $entry->getStatus());
        self::assertEmailCount(1, null, 'Der normale Ablauf benachrichtigt das Team nicht mehr');
    }

    /**
     * AK-44: Der Datenexport gibt die Einwilligung mit aus.
     */
    public function testDatenexportNenntDieEinwilligung(): void
    {
        $client = static::createClient();
        $user = $this->loginAs($client, 'user@endlech.lu');

        $zeitpunkt = new \DateTimeImmutable('2026-08-01 10:00:00');
        $user->setMarketingConsentAt($zeitpunkt);
        static::getContainer()->get(EntityManagerInterface::class)->flush();

        $export = static::getContainer()->get(\App\Account\AccountDataExporter::class)->export(
            static::getContainer()->get(UserRepository::class)->find($user->getId()),
        );

        self::assertTrue($export['account']['marketingConsent']);
        self::assertSame($zeitpunkt->format(\DateTimeInterface::ATOM), $export['account']['marketingConsentAt']);
    }
}
