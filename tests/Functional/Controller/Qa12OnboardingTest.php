<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\AppWaitlistEntry;
use App\Entity\MarketingContact;
use App\Entity\User;
use App\Enum\AppPlatform;
use App\Enum\MarketingSyncState;
use App\Marketing\BrevoContactClient;
use App\Marketing\MarketingPayloadMapper;
use App\Marketing\MarketingSyncService;
use App\Repository\MarketingContactRepository;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * QA Feature 12 — Prüfläufe der unabhängigen Abnahme (2026-10-22).
 *
 * Zwei Arten von Fällen:
 *
 * - **Belege** für Kriterien, die bis an die Grenze des Systems geprüft werden: Was geht nach einer
 *   Einwilligung in der Einführung tatsächlich an Brevo hinaus? Dazu läuft der echte
 *   `MarketingSyncService` gegen einen nachgebauten Brevo-Speicher (`MockHttpClient`), der sich merkt,
 *   welcher Kontakt gesperrt ist — der Rumpf ist der, den die Anwendung schickt, nicht der, den der
 *   Quelltext verspricht.
 * - **Reproduktionen** der Befunde BF-154 bis BF-160 (`testBf…`). Sie prüfen das **richtige** Verhalten
 *   und waren beim Schreiben rot (Ausgabe in `qa/12/reproduktionen.ausgabe.txt`). Damit die Suite grün
 *   bleibt, überspringen sie sich, solange der Befund offen ist — wer ihn behebt, entfernt die Zeile
 *   `self::markTestSkipped(…)` und sieht den Lauf grün.
 */
final class Qa12OnboardingTest extends AbstractWebTestCase
{
    /** Der nachgebaute Brevo-Speicher: ext_id => ['email' => …, 'blacklisted' => bool]. */
    private array $brevo = [];

    /** @var list<array{string, string, array<string, mixed>}> */
    private array $aufrufe = [];

    private function offen(string $befund): void
    {
        if ('1' !== getenv('QA12_REPRO')) {
            self::markTestSkipped($befund.' offen — Reproduktion, siehe features/12-onboarding-neue-nutzer/qa-report.md. Scharf mit QA12_REPRO=1.');
        }
    }

    private function client(): KernelBrowser
    {
        $client = static::createClient();
        // Eine Kernel-Instanz für Anfragen UND Abgleich: derselbe EntityManager sieht dieselben Zeilen.
        $client->disableReboot();
        $client->getContainer()->get('limiter.newsletter_toggle')->create('qa12@example.org')->reset();

        return $client;
    }

    private function em(KernelBrowser $client): EntityManagerInterface
    {
        return $client->getContainer()->get(EntityManagerInterface::class);
    }

    private function konto(KernelBrowser $client, string $email, bool $bestaetigt = true): User
    {
        $user = new User();
        $user->setName('Qa Zwölf');
        $user->setEmail($email);
        $user->setPassword('unbenutzt');
        $user->setRoles([]);
        $user->setIsVerified($bestaetigt);

        if ($bestaetigt) {
            $user->markOnboardingDue();
        }

        $this->em($client)->persist($user);
        $this->em($client)->flush();

        return $user;
    }

    /**
     * Der Fünf-Minuten-Lauf, gegen einen Brevo-Nachbau, der Sperren behält wie das Original:
     * PUT auf einen unbekannten Kontakt → 404, POST legt an, PUT überschreibt nur, was im Rumpf steht.
     */
    private function abgleich(KernelBrowser $client): void
    {
        $handler = function (string $method, string $url, array $options): MockResponse {
            $rumpf = json_decode((string) ($options['body'] ?? '{}'), true) ?: [];
            $this->aufrufe[] = [$method, $url, $rumpf];

            if ('POST' === $method) {
                $this->brevo[(string) $rumpf['ext_id']] = [
                    'email' => $rumpf['email'] ?? null,
                    'attributes' => $rumpf['attributes'] ?? [],
                    'blacklisted' => $rumpf['emailBlacklisted'] ?? false,
                ];

                return new MockResponse('{"id":1}', ['http_code' => 201]);
            }

            preg_match('#/contacts/(\d+)#', $url, $treffer);
            $id = $treffer[1] ?? '';

            if (!isset($this->brevo[$id])) {
                return new MockResponse('', ['http_code' => 404]);
            }

            if (\array_key_exists('emailBlacklisted', $rumpf)) {
                $this->brevo[$id]['blacklisted'] = (bool) $rumpf['emailBlacklisted'];
            }

            if (isset($rumpf['attributes'])) {
                $this->brevo[$id]['attributes'] = $rumpf['attributes'];
            }

            return new MockResponse('', ['http_code' => 204]);
        };

        $em = $this->em($client);
        (new MarketingSyncService(
            $client->getContainer()->get(MarketingContactRepository::class),
            new MarketingPayloadMapper('7'),
            new BrevoContactClient(new MockHttpClient($handler), new NullLogger(), 'qa12-key', '7'),
            $em,
            new NullLogger(),
            200,
            0,
        ))->run();
    }

    private function profilSchalter(KernelBrowser $client): void
    {
        $crawler = $client->request('GET', self::LOCALE.'/profile');
        $client->submit($crawler->filter('form[action$="/profile/newsletter"]')->form());
    }

    private function frisch(KernelBrowser $client, string $email): User
    {
        $this->em($client)->clear();

        return $this->user($client, $email);
    }

    private function kontakt(KernelBrowser $client, string $email): ?MarketingContact
    {
        return $client->getContainer()->get(MarketingContactRepository::class)->findOneByEmail($email);
    }

    /**
     * AK-33, AK-40, AK-56 — was nach dem Häkchen in der Einführung tatsächlich an Brevo geht.
     */
    public function testAk33Ak40Ak56RumpfAnBrevoNachEinwilligungInDerEinfuehrung(): void
    {
        $client = $this->client();
        $user = $this->konto($client, 'qa12@example.org');
        $client->loginUser($user);

        // Bild hochladen gehört zum Durchlauf: Es darf im Rumpf ebensowenig auftauchen wie die Einführung.
        $client->request('GET', '/fr/einfuehrung/eintraege');
        $crawler = $client->request('GET', '/fr/einfuehrung/newsletter');
        $client->submit($this->formWithField($crawler, 'onboarding_newsletter[consent]', ['onboarding_newsletter[consent]' => '1']));
        self::assertResponseRedirects('/fr/restaurants');

        $this->abgleich($client);

        self::assertCount(2, $this->aufrufe, 'Erst PUT (404, unbekannt), dann POST zum Anlegen.');
        [$methode, , $rumpf] = $this->aufrufe[1];
        self::assertSame('POST', $methode);
        self::assertSame('qa12@example.org', $rumpf['email']);
        self::assertSame(['CONTACT_NAME', 'FUNNEL_STATUS', 'LOCALE', 'ORGANISATION', 'ORIGIN'], $this->sortiert(array_keys($rumpf['attributes'])));
        self::assertSame('fr', $rumpf['attributes']['LOCALE'], 'AK-40: Sprache der Anfrage, nicht fest de.');
        self::assertSame('ACCOUNT', $rumpf['attributes']['ORIGIN']);
        self::assertSame([7], $rumpf['listIds']);
        self::assertArrayNotHasKey('emailBlacklisted', $rumpf, 'Ohne bekannte Sperre steht das Feld nicht im Rumpf (04/EC-05).');

        $flach = json_encode($rumpf, \JSON_THROW_ON_ERROR);
        foreach (['onboarding', 'einfuehrung', 'avatar', 'uploads', 'shown'] as $verboten) {
            self::assertStringNotContainsStringIgnoringCase($verboten, $flach, 'AK-56: nichts aus der Einführung im Rumpf.');
        }
    }

    /**
     * AK-36 — Abbestellen im Profil nach einem Abgleich: Der Lauf schickt genau die Sperre.
     */
    public function testAk36AbbestellenSchicktNurDieSperre(): void
    {
        $client = $this->client();
        $user = $this->konto($client, 'qa12@example.org');
        $client->loginUser($user);

        $this->profilSchalter($client);
        $this->abgleich($client);
        $this->aufrufe = [];

        $this->profilSchalter($client);
        self::assertSame(MarketingSyncState::BLACKLIST_PENDING, $this->kontakt($client, 'qa12@example.org')?->getSyncState());
        $this->abgleich($client);

        self::assertCount(1, $this->aufrufe);
        self::assertSame('PUT', $this->aufrufe[0][0]);
        self::assertSame(['emailBlacklisted' => true], $this->aufrufe[0][2]);
        self::assertTrue($this->brevo[(string) $this->kontakt($client, 'qa12@example.org')?->getId()]['blacklisted']);
    }

    /**
     * BF-154 — Wer nach dem Abbestellen wieder abonniert, bleibt bei Brevo gesperrt.
     *
     * Der Mapper setzt `emailBlacklisted: false` (Entscheidung 6), `BrevoContactClient::upsert()` übernimmt
     * auf dem Aktualisierungsweg aber nur `attributes` und `listIds`. Der PUT geht ohne das Feld hinaus,
     * Brevo antwortet 204, die Zeile steht auf `synced` — und der Kontakt bleibt gesperrt.
     */
    public function testBf154WiederEinwilligungHebtDieSperreBeiBrevoAuf(): void
    {
        $client = $this->client();
        $user = $this->konto($client, 'qa12@example.org');
        $client->loginUser($user);

        $this->profilSchalter($client);   // abonnieren
        $this->abgleich($client);         // angelegt
        $this->profilSchalter($client);   // abbestellen
        $this->abgleich($client);         // gesperrt
        sleep(1);                         // eine neue Sekunde, damit nichts an BF-160 hängt
        $this->profilSchalter($client);   // wieder abonnieren
        $this->aufrufe = [];
        $this->abgleich($client);

        $id = (string) $this->kontakt($client, 'qa12@example.org')?->getId();
        self::assertSame(MarketingSyncState::SYNCED, $this->kontakt($client, 'qa12@example.org')?->getSyncState());
        self::assertSame(['emailBlacklisted' => false], array_intersect_key($this->aufrufe[0][2], ['emailBlacklisted' => true]), 'Der PUT muss die Sperre aufheben.');
        self::assertFalse($this->brevo[$id]['blacklisted'], 'Profil sagt „abonniert", Brevo stellt nichts zu.');
    }

    /**
     * BF-155 — Ein unbestätigtes Konto nimmt mit „Abbestellen" die Einwilligung eines Fremden zurück.
     *
     * `revokeForAccount()` leert die Einwilligung an allen Quellen der Adresse und stellt den Sperrauftrag —
     * auch wenn das Konto die Adresse nie bestätigt hat. Wer eine fremde Adresse registriert, löscht so den
     * Einwilligungsnachweis des echten Inhabers auf einer Warteliste und lässt ihn bei Brevo sperren.
     */
    public function testBf155UnbestaetigtesKontoNimmtFremdeEinwilligungNicht(): void
    {
        $client = $this->client();
        $em = $this->em($client);

        $eintrag = new AppWaitlistEntry();
        $eintrag->setEmail('qa12@example.org');
        $eintrag->setPlatform(AppPlatform::IOS);
        $eintrag->setLocale('de');
        $eintrag->confirm();
        $eintrag->setMarketingConsentAt(new \DateTimeImmutable('-5 days'));
        $em->persist($eintrag);

        $kontakt = new MarketingContact();
        $kontakt->setEmail('qa12@example.org')
            ->setOrigin(\App\Enum\MarketingOrigin::APP)
            ->setLocale('de')
            ->setConsentAt(new \DateTimeImmutable('-5 days'))
            ->setSyncedAt(new \DateTimeImmutable('-5 days'))
            ->setSyncState(MarketingSyncState::SYNCED);
        $em->persist($kontakt);
        $em->flush();

        // Jemand registriert dieselbe Adresse, bestätigt sie nie und meldet sich an (B01/AK-13 erlaubt das).
        $fremd = $this->konto($client, 'qa12@example.org', bestaetigt: false);
        $client->loginUser($fremd);
        $this->profilSchalter($client);   // abonnieren — wirkt nur am eigenen Konto (AK-38)
        $this->profilSchalter($client);   // abbestellen

        $em->clear();
        $eintrag = $em->getRepository(AppWaitlistEntry::class)->findOneBy(['email' => 'qa12@example.org']);
        self::assertNotNull($eintrag?->getMarketingConsentAt(), 'Die Einwilligung des Inhabers auf der Warteliste muss stehen bleiben.');
        self::assertSame(MarketingSyncState::SYNCED, $this->kontakt($client, 'qa12@example.org')?->getSyncState(), 'Kein Sperrauftrag an Brevo für eine nie bestätigte Adresse.');
        self::assertNull($this->kontakt($client, 'qa12@example.org')?->getRevokedAt());
    }

    /**
     * BF-157 — Ein Adresswechsel kurz nach dem Abbestellen verwirft den Sperrauftrag.
     *
     * `changeEmail()` setzt den Zustand ohne Ansehen auf `pending`. Die Zeile ist gesperrt, also holt
     * `findOpenForSync()` sie nicht mehr ab — die Sperre erreicht Brevo nie, der Kontakt bekommt weiter
     * Kampagnen an die alte Adresse.
     */
    public function testBf157AdresswechselLaesstDenSperrauftragStehen(): void
    {
        $client = $this->client();
        $user = $this->konto($client, 'qa12@example.org');
        $client->loginUser($user);

        $this->profilSchalter($client);   // abonnieren
        $this->abgleich($client);
        $this->profilSchalter($client);   // abbestellen → BLACKLIST_PENDING
        $kontakt = $this->kontakt($client, 'qa12@example.org');
        self::assertSame(MarketingSyncState::BLACKLIST_PENDING, $kontakt?->getSyncState());

        // Adresswechsel bestätigen, bevor der nächste Lauf kam (B04, BF-19).
        $user = $this->frisch($client, 'qa12@example.org');
        $token = $user->requestEmailChange('qa12-neu@example.org');
        $this->em($client)->flush();
        $client->request('GET', self::LOCALE.'/verify/email-change/'.$token);
        self::assertSame('qa12-neu@example.org', $this->frisch($client, 'qa12-neu@example.org')->getEmail(), 'Der Wechsel muss wirksam sein.');
        self::assertSame('qa12-neu@example.org', $this->kontakt($client, 'qa12-neu@example.org')?->getEmail());

        $this->aufrufe = [];
        $this->abgleich($client);

        self::assertContains(['emailBlacklisted' => true], array_column($this->aufrufe, 2), 'Die Sperre muss Brevo trotzdem erreichen.');
    }

    /**
     * BF-158 — Turbo lädt den Link „Einführung ansehen" beim Überfahren vor, und das zählt als Anzeige.
     *
     * Turbo 8 schickt dabei `X-Sec-Purpose: prefetch`. Der Controller vermerkt die erste Anzeige bei jedem
     * GET. Ein Konto, das nach AK-03 noch fällig ist, verliert so die Einführung, ohne sie gesehen zu haben.
     */
    public function testBf158VorabladenVermerktKeineAnzeige(): void
    {
        $client = $this->client();
        $user = $this->konto($client, 'qa12@example.org');
        $client->loginUser($user);

        $client->request('GET', self::LOCALE.'/einfuehrung', server: ['HTTP_X_SEC_PURPOSE' => 'prefetch']);

        $user = $this->frisch($client, 'qa12@example.org');
        self::assertNull($user->getOnboardingShownAt(), 'Ein Vorabladen ist keine Anzeige.');
        self::assertTrue($user->isOnboardingPending());
    }

    /**
     * BF-159 — Ein Bild über `post_max_size` ergibt 422 ohne jede Meldung.
     *
     * PHP verwirft dann den ganzen Rumpf; Symfony hängt den Fehler an das Formular selbst, und die
     * Vorlage zeigt nur Feldfehler. Muster BF-58 (dort für Restaurantbilder gelöst).
     */
    public function testBf159UebergrosserUploadZeigtEineMeldung(): void
    {
        $client = $this->client();
        $user = $this->konto($client, 'qa12@example.org');
        $client->loginUser($user);
        $client->request('GET', self::LOCALE.'/einfuehrung/profilbild');

        $crawler = $client->request('POST', self::LOCALE.'/einfuehrung/profilbild', server: [
            'CONTENT_TYPE' => 'multipart/form-data; boundary=qa12',
            'CONTENT_LENGTH' => (string) (64 * 1024 * 1024),
        ]);

        self::assertResponseStatusCodeSame(422);
        $meldung = trim($crawler->filter('#onboarding_avatar_avatar_error')->text('').' '.$crawler->filter('main [role="alert"]')->text(''));
        self::assertNotSame('', $meldung, 'Der Nutzer muss erfahren, warum nichts geschah.');
    }

    /**
     * BF-160 — Drei Wechsel in einer Sekunde lassen eine Abmeldung wirkungslos.
     *
     * `consentMomentFor()` schiebt die neue Einwilligung eine Sekunde hinter die Sperre. Kommt die nächste
     * Abmeldung noch in derselben Sekunde, liegt `revoked_at` VOR `consent_at`: Das Profil sagt „nicht
     * abonniert", das Auftragsbuch hält die Zeile für eingewilligt und der nächste Lauf trägt sie bei Brevo ein.
     */
    public function testBf160SchnellesUmschaltenEndetGesperrt(): void
    {
        $client = $this->client();
        $user = $this->konto($client, 'qa12@example.org');
        $client->loginUser($user);

        $this->profilSchalter($client);   // an
        $this->profilSchalter($client);   // aus
        $this->profilSchalter($client);   // an  (Einwilligung = Sperre + 1 s)
        $this->profilSchalter($client);   // aus (noch in derselben Sekunde)

        $user = $this->frisch($client, 'qa12@example.org');
        self::assertNull($user->getMarketingConsentAt());
        $kontakt = $this->kontakt($client, 'qa12@example.org');
        self::assertTrue($kontakt?->isBlocked(), 'Nach der letzten Abmeldung muss die Zeile gesperrt sein.');
        // ⚠ Angepasst mit BF-181 (Fehlerauftrag 3): Vorher hieß es „nicht in der offenen Liste" — als Ersatz für „wird
        // nicht als Abonnent übertragen". Seit BF-181 bekommt jede Zeile beim Abbestellen den Sperrauftrag, auch eine
        // nie übertragene; sie steht damit in der Liste, aber nur als Sperre (`PUT {emailBlacklisted:true}`, kein Anlegen).
        if (\in_array($kontakt, $client->getContainer()->get(MarketingContactRepository::class)->findOpenForSync(500), true)) {
            self::assertSame(MarketingSyncState::BLACKLIST_PENDING, $kontakt?->getSyncState(), 'Offen darf die Zeile nur als Sperrauftrag sein.');
        }
    }

    /**
     * BF-156 (B02, vorbestehend) — Nach einem Formular mit Double-Submit scheitert die Anmeldung.
     *
     * Die Registrierung schickt ihr Token per Double-Submit (Controller `csrf-protection`). Symfony merkt
     * sich das in der Sitzung und verlangt es danach für jede zustandslose Prüfung. Die Anmeldeseite lädt
     * den Controller nicht (er ist `lazy`, und auf ihr steht kein Element, das ihn anfordert) — eine frisch
     * geladene Anmeldeseite, etwa nach dem Bestätigungslink, schickt nur die Herkunft. Folge: „Ungültiges
     * CSRF-Token", bis die Sitzung endet. Nachgestellt im Browser, `qa/12/b02-csrf-browser.ausgabe.txt`.
     */
    public function testBf156AnmeldungNachDoubleSubmitInDerselbenSitzung(): void
    {
        // Behoben 2026-10-23 im Browser, nicht am Server: Dieser Fall schickt die Anmeldung OHNE
        // Double-Submit — so, wie es der Browser tat, solange `csrf_protection_controller.ts` verzögert
        // geladen wurde. Dass Symfony das nach einem benutzten Double-Submit ablehnt, ist Absicht
        // (Schutz gegen das Zurückfallen auf die Herkunftsprüfung) und bleibt. Der Controller wird
        // jetzt sofort geladen; belegt in `Bf156CsrfControllerTest` und im Browserlauf (qa-report.md).
        self::markTestSkipped('BF-156 behoben im Browser — dieser Servertest bildet den alten Browser nach, siehe Kommentar.');
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.'/register');

        // Was csrf_protection_controller.ts beim Absenden tut: Zufallswert ins Feld, gleichnamiges Cookie.
        $zufall = 'qa12QA12qa12QA12qa12QA12';
        $client->getCookieJar()->set(new Cookie('csrf-token_'.$zufall, 'csrf-token', null, '/', 'localhost'));
        $client->submit($this->formWithField($crawler, 'registration[email]', [
            'registration[name]' => 'Qa Zwölf',
            'registration[email]' => 'qa12-csrf@example.org',
            'registration[plainPassword][first]' => 'Geheim12345',
            'registration[plainPassword][second]' => 'Geheim12345',
            'registration[_token]' => $zufall,
        ]));
        self::assertResponseRedirects(self::LOCALE.'/verify');

        $crawler = $client->request('GET', self::LOCALE.'/login');
        $client->submit($this->formWithField($crawler, '_username', [
            '_username' => 'user@endlech.lu',
            '_password' => 'user123',
        ]));

        self::assertResponseRedirects();
        self::assertStringNotContainsString('/login', (string) $client->getResponse()->headers->get('Location'), 'Die Anmeldung muss gelingen.');
    }

    /**
     * AK-11 auch für die beiden Absendewege: Ein unbestätigtes Konto ändert über die Einführung nichts.
     */
    public function testAk11AbsendewegeVerlangenEineBestaetigteAdresse(): void
    {
        $client = $this->client();
        $user = $this->konto($client, 'qa12@example.org', bestaetigt: false);
        $client->loginUser($user);

        $client->request('POST', self::LOCALE.'/einfuehrung/newsletter', ['onboarding_newsletter' => ['consent' => '1']]);
        self::assertResponseRedirects(self::LOCALE.'/verify');
        $client->request('POST', self::LOCALE.'/einfuehrung/profilbild');
        self::assertResponseRedirects(self::LOCALE.'/verify');

        $user = $this->frisch($client, 'qa12@example.org');
        self::assertNull($user->getMarketingConsentAt());
        self::assertNull($user->getOnboardingShownAt());
    }

    /**
     * @param list<string> $liste
     *
     * @return list<string>
     */
    private function sortiert(array $liste): array
    {
        sort($liste);

        return $liste;
    }
}
