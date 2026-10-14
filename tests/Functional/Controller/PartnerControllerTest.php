<?php

namespace App\Tests\Functional\Controller;

use App\Entity\PartnerWaitlistEntry;
use App\Enum\WaitlistStatus;
use App\Repository\PartnerWaitlistEntryRepository;
use App\Tests\AbstractWebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

final class PartnerControllerTest extends AbstractWebTestCase
{
    private const VALID = [
        'partner_waitlist[restaurantName]' => 'Brasserie Test',
        'partner_waitlist[contactName]' => 'Anna Muster',
        'partner_waitlist[email]' => 'anna@brasserie-test.lu',
        'partner_waitlist[phone]' => '+352 123456',
        'partner_waitlist[locality]' => 'Strassen',
        'partner_waitlist[message]' => 'Wir haben eine Stufe am Eingang.',
        'partner_waitlist[consent]' => true,
    ];

    public function testLandingPageRendersWithSingleH1(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/partner');

        self::assertResponseIsSuccessful();
        self::assertSame(1, $crawler->filter('h1')->count(), 'Die Seite muss genau eine h1 haben.');
        self::assertGreaterThan(0, $crawler->filter('#warteliste')->count());
        // FAQ muss ohne JavaScript bedienbar sein.
        self::assertGreaterThan(0, $crawler->filter('details summary')->count());
    }

    public function testValidSubmissionCreatesPendingEntryAndSendsMail(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/partner');

        $client->submit($this->formWithField($crawler, 'partner_waitlist[email]', self::VALID));

        self::assertResponseRedirects(self::LOCALE . '/partner');

        $entry = $this->latestEntry($client);
        self::assertNotNull($entry);
        self::assertSame('Brasserie Test', $entry->getRestaurantName());
        self::assertSame(WaitlistStatus::PENDING, $entry->getStatus());
        self::assertSame('de', $entry->getLocale());
        self::assertSame(64, \strlen((string) $entry->getConfirmationToken()));
        self::assertFalse($entry->isConfirmed());

        self::assertEmailCount(1);
        self::assertEmailAddressContains(self::getMailerMessage(), 'To', 'anna@brasserie-test.lu');
    }

    public function testInvalidSubmissionReturns422AndFocusesFirstError(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/partner');

        $values = self::VALID;
        $values['partner_waitlist[restaurantName]'] = '';
        $values['partner_waitlist[email]'] = 'keine-email';

        $crawler = $client->submit($this->formWithField($crawler, 'partner_waitlist[email]', $values));

        self::assertResponseStatusCodeSame(422);
        self::assertNull($this->latestEntry($client), 'Ein ungültiger Submit darf nichts speichern.');

        // Der Fokus muss ohne JavaScript beim ersten fehlerhaften Feld landen.
        self::assertSame(
            1,
            $crawler->filter('#partner_waitlist_restaurantName[autofocus]')->count(),
            'Das erste fehlerhafte Feld braucht autofocus.',
        );

        // aria-invalid darf nur im Fehlerfall gesetzt sein (null würde aria-invalid="" rendern).
        self::assertSame('true', $crawler->filter('#partner_waitlist_restaurantName')->attr('aria-invalid'));
        self::assertNull($crawler->filter('#partner_waitlist_locality')->attr('aria-invalid'));
    }

    public function testMissingConsentIsRejected(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/partner');

        $values = self::VALID;
        unset($values['partner_waitlist[consent]']);

        $client->submit($this->formWithField($crawler, 'partner_waitlist[email]', $values));

        self::assertResponseStatusCodeSame(422);
        self::assertNull($this->latestEntry($client));
    }

    public function testHoneypotIsSilentlyDiscarded(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/partner');

        $values = self::VALID;
        $values['partner_waitlist[website]'] = 'https://spam.example';

        $client->submit($this->formWithField($crawler, 'partner_waitlist[email]', $values));

        // Der Bot bekommt dieselbe Antwort wie ein echter Absender …
        self::assertResponseRedirects(self::LOCALE . '/partner');
        // … es wird aber nichts gespeichert und nichts verschickt.
        self::assertNull($this->latestEntry($client));
        self::assertEmailCount(0);
    }

    public function testUtmSourceIsStored(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/partner?utm_source=newsletter');

        $client->submit($this->formWithField($crawler, 'partner_waitlist[email]', self::VALID));

        self::assertSame('newsletter', $this->latestEntry($client)?->getSource());
    }

    public function testTurboRequestReturnsStream(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE . '/partner');

        $client->submit(
            $this->formWithField($crawler, 'partner_waitlist[email]', self::VALID),
            [],
            ['HTTP_ACCEPT' => 'text/vnd.turbo-stream.html, text/html'],
        );

        self::assertResponseIsSuccessful();
        $content = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('<turbo-stream action="replace" target="partner-waitlist-form">', $content);
    }

    public function testConfirmationActivatesEntryAndNotifiesTeam(): void
    {
        $client = static::createClient();
        $token = $this->submitAndGetToken($client);

        $client->request('GET', self::LOCALE . '/partner/confirmation/' . $token);

        self::assertResponseIsSuccessful();

        $entry = $this->latestEntry($client);
        self::assertSame(WaitlistStatus::CONFIRMED, $entry?->getStatus());
        self::assertTrue($entry->isConfirmed());

        // Der Test-Client bootet den Kernel je Request neu – gezählt wird also
        // nur der Confirm-Request selbst: die interne Meldung ans Team.
        // (Die Bestätigungsmail an den Interessenten prüft der Submit-Test.)
        self::assertEmailCount(1);
    }

    public function testSecondConfirmationIsGracefulAndSendsNoSecondMail(): void
    {
        $client = static::createClient();
        $token = $this->submitAndGetToken($client);

        $client->request('GET', self::LOCALE . '/partner/confirmation/' . $token);
        $crawler = $client->request('GET', self::LOCALE . '/partner/confirmation/' . $token);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('bereits', $crawler->filter('h1')->text());
        self::assertEmailCount(0, 'Der zweite Aufruf darf keine weitere Mail auslösen.');
    }

    public function testUnknownTokenReturns404NotServerError(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE . '/partner/confirmation/' . str_repeat('a', 64));

        self::assertResponseStatusCodeSame(404);
    }

    public function testMalformedTokenDoesNotMatchRoute(): void
    {
        $client = static::createClient();
        $client->request('GET', self::LOCALE . '/partner/confirmation/zu-kurz');

        self::assertResponseStatusCodeSame(404);
    }

    private function submitAndGetToken(KernelBrowser $client): string
    {
        $crawler = $client->request('GET', self::LOCALE . '/partner');
        $client->submit($this->formWithField($crawler, 'partner_waitlist[email]', self::VALID));

        $entry = $this->latestEntry($client);
        self::assertNotNull($entry);

        return (string) $entry->getConfirmationToken();
    }

    private function latestEntry(KernelBrowser $client): ?PartnerWaitlistEntry
    {
        $repository = $client->getContainer()->get(PartnerWaitlistEntryRepository::class);

        return $repository->findOneBy([], ['id' => 'DESC']);
    }

    /**
     * AK-15: Die interne Meldung ans Team ist fest auf Deutsch — unabhängig davon,
     * in welcher Sprache der Interessent bestätigt hat. Sonst käme die Meldung an
     * ein deutschsprachiges Team auf Französisch, weil ein Gemeindesekretär den
     * Link auf der französischen Fassung angeklickt hat.
     */
    public function testAk15InterneMeldungBleibtDeutschBeiFranzoesischerBestaetigung(): void
    {
        $client = static::createClient();
        $token = $this->submitAndGetToken($client);

        $client->request('GET', '/fr/partner/confirmation/' . $token);

        self::assertResponseIsSuccessful();
        self::assertEmailCount(1);

        $mail = self::getMailerMessage();
        self::assertStringContainsString('Partner-Anmeldung', $mail->getSubject());
    }

    /**
     * AK-15, zweite Hälfte: Die Adresse des Interessenten steht als Reply-To, damit
     * eine Antwort aus dem Postfach heraus beim richtigen Empfänger landet.
     */
    public function testAk15InterneMeldungTraegtDenInteressentenAlsReplyTo(): void
    {
        $client = static::createClient();
        $token = $this->submitAndGetToken($client);

        $client->request('GET', self::LOCALE . '/partner/confirmation/' . $token);

        $replyTo = self::getMailerMessage()->getReplyTo();
        self::assertNotEmpty($replyTo, 'Ohne Reply-To geht die Antwort an die Absenderadresse der Plattform.');
    }

    /**
     * AK-23 / BF-38: Die beiden Wartelisten führen **getrennte** Kontingente. Hinter
     * einer geteilten IP — einer Gemeindeverwaltung etwa — sperrt ein ausgeschöpfter
     * Partnerweg nicht auch den Organisationsweg.
     *
     * ⚠ **Dieser Lauf prüft das Verhalten, nicht den Quelltext (BF-125).** Die
     * Vorfassung durchsuchte `OrganisationController.php` als Zeichenkette nach
     * `limiter.partner_waitlist` — und traf den Ausdruck seit BF-38 nur noch im
     * **Kommentar** von Zeile 37, während die `#[Autowire]`-Zeile längst
     * `limiter.organisation_waitlist` nannte. Der Lauf bestätigte damit das Gegenteil
     * des tatsächlichen Verhaltens und wäre auch bei einem Rückbau grün geblieben: in
     * beide Richtungen blind. Ein Prüflauf, der Quelltext liest, prüft die Kommentare
     * mit.
     *
     * ⚠ Erschöpft wird in **einem** Zug, nicht über wiederholte Submits: In
     * `when@test` steht das Limit auf 10000 (Pflicht-Override), eine Schleife wäre
     * eine Wartezeit und kein Prüflauf. Muster aus `Bf136KontoloeschungLimiterTest`.
     */
    public function testAk23WartelistenFuehrenGetrennteKontingente(): void
    {
        $client = static::createClient();

        $factory = $client->getContainer()->get('limiter.partner_waitlist');
        self::assertInstanceOf(RateLimiterFactoryInterface::class, $factory);

        // Der Schlüssel ist die Adresse des Testclients — dieselbe, die beide
        // Controller über `getClientIp()` benutzen.
        $partnerKontingent = $factory->create('127.0.0.1');
        $partnerKontingent->reset();
        $partnerKontingent->consume(10_000);

        $this->sendePartnerFormular($client);
        $partnerStatus = $client->getResponse()->getStatusCode();

        $this->sendeOrganisationsFormular($client);
        $organisationStatus = $client->getResponse()->getStatusCode();

        // ⚠ Aufräumen VOR den Zusicherungen: Der Zähler liegt im Cache-Pool und
        // übersteht den DAMA-Rollback — ein leer gelassenes Kontingent färbt sonst
        // jeden folgenden Partner-Lauf rot, und zwar erst beim zweiten Aufruf der
        // Suite (BF-136, dieselbe Falle).
        $partnerKontingent->reset();

        self::assertSame(
            Response::HTTP_TOO_MANY_REQUESTS,
            $partnerStatus,
            'Vorbedingung: Der Partnerweg muss bei leerem Kontingent bremsen.',
        );
        self::assertSame(
            Response::HTTP_FOUND,
            $organisationStatus,
            'BF-38: Ein ausgeschöpfter Partnerweg darf den Organisationsweg nicht mitsperren.',
        );
    }

    private function sendePartnerFormular(KernelBrowser $client): void
    {
        $crawler = $client->request('GET', self::LOCALE . '/partner');

        $client->submit($this->formWithField($crawler, 'partner_waitlist[email]', [
            'partner_waitlist[restaurantName]' => 'Brasserie Kontingent',
            'partner_waitlist[contactName]' => 'Anna Muster',
            'partner_waitlist[email]' => 'kontingent_' . uniqid() . '@brasserie-test.lu',
            'partner_waitlist[locality]' => 'Strassen',
            'partner_waitlist[consent]' => true,
        ]));
    }

    private function sendeOrganisationsFormular(KernelBrowser $client): void
    {
        $crawler = $client->request('GET', self::LOCALE . '/organisationen');

        $client->submit($this->formWithField($crawler, 'organisation_waitlist[email]', [
            'organisation_waitlist[type]' => 'company',
            'organisation_waitlist[organisationName]' => 'Verein Kontingent',
            'organisation_waitlist[contactName]' => 'Alex Muster',
            'organisation_waitlist[email]' => 'kontingent_' . uniqid() . '@verein-test.lu',
            'organisation_waitlist[consent]' => true,
        ]));
    }
}
