<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Enum\MarketingSyncState;
use App\Marketing\BrevoContactClient;
use App\Marketing\MarketingPayloadMapper;
use App\Marketing\MarketingSyncService;
use App\Repository\MarketingContactRepository;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * QA Feature 12 — dritter Durchlauf (zweite Nachprüfung, 2026-10-23).
 *
 * Reproduktionen der in diesem Durchlauf gefundenen Befunde. Sie prüfen das richtige Verhalten, waren beim
 * Schreiben rot und überspringen sich, solange der Befund offen ist. Scharf:
 * `QA12_REPRO=1 php bin/phpunit tests/Functional/Controller/Qa12Nachpruefung3Test.php`.
 * Wer den Befund behebt, entfernt den Aufruf `$this->offen(…)`.
 *
 * Wie `Qa12NachpruefungTest`: Der echte `MarketingSyncService` läuft gegen einen Brevo-Nachbau, der Sperren behält
 * wie das Original; eine gleichzeitige zweite Anfrage ist als das nachgebildet, was sie in die Datenbank schreibt —
 * an der Unit of Work des Laufs vorbei, wie es ein zweiter PHP-Prozess täte.
 */
final class Qa12Nachpruefung3Test extends AbstractWebTestCase
{
    private const string ADRESSE = 'qa12d@example.org';
    private const string AVATARE = __DIR__.'/../../../public/uploads/avatars';

    /** Der nachgebaute Brevo-Speicher: ext_id => ['email' => …, 'blacklisted' => bool]. */
    private array $brevo = [];

    /** @var list<array{string, string, array<string, mixed>}> */
    private array $aufrufe = [];

    /** @var list<string> */
    private array $aufzuraeumen = [];

    protected function tearDown(): void
    {
        foreach ($this->aufzuraeumen as $pfad) {
            @unlink($pfad);
        }
        parent::tearDown();
    }

    private function offen(string $befund): void
    {
        if ('1' !== getenv('QA12_REPRO')) {
            self::markTestSkipped($befund.' offen — Reproduktion, siehe features/12-onboarding-neue-nutzer/qa-report.md. Scharf mit QA12_REPRO=1.');
        }
    }

    private function client(): KernelBrowser
    {
        $client = static::createClient();
        $client->disableReboot();
        $client->getContainer()->get('limiter.newsletter_toggle')->create(self::ADRESSE)->reset();

        return $client;
    }

    private function em(KernelBrowser $client): EntityManagerInterface
    {
        return $client->getContainer()->get(EntityManagerInterface::class);
    }

    private function konto(KernelBrowser $client, string $email = self::ADRESSE): User
    {
        $user = new User();
        $user->setName('Qa Dritte Prüfung');
        $user->setEmail($email);
        $user->setPassword('unbenutzt');
        $user->setRoles([]);
        $user->setIsVerified(true);

        $this->em($client)->persist($user);
        $this->em($client)->flush();

        return $user;
    }

    private function png(): string
    {
        $pfad = sys_get_temp_dir().'/qa12d-'.bin2hex(random_bytes(4)).'.png';
        $bild = imagecreatetruecolor(4, 4);
        imagepng($bild, $pfad);
        $this->aufzuraeumen[] = $pfad;

        return $pfad;
    }

    /**
     * @param (callable(string, string, array<string, mixed>): void)|null $waehrend läuft innerhalb des Aufrufs an Brevo
     */
    private function abgleich(KernelBrowser $client, ?callable $waehrend = null, ?callable $antwort = null): void
    {
        $handler = function (string $method, string $url, array $options) use ($waehrend, $antwort): MockResponse {
            $rumpf = json_decode((string) ($options['body'] ?? '{}'), true) ?: [];
            $this->aufrufe[] = [$method, $url, $rumpf];

            if (null !== $waehrend) {
                $waehrend($method, $url, $rumpf);
            }

            if ('POST' === $method) {
                $this->brevo[(string) $rumpf['ext_id']] = ['email' => $rumpf['email'] ?? null, 'blacklisted' => $rumpf['emailBlacklisted'] ?? false];
            }

            $vorgabe = null === $antwort ? null : $antwort($method, $url, $rumpf);
            if (null !== $vorgabe) {
                return $vorgabe;
            }

            if ('POST' === $method) {
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

            return new MockResponse('', ['http_code' => 204]);
        };

        (new MarketingSyncService(
            $client->getContainer()->get(MarketingContactRepository::class),
            new MarketingPayloadMapper('7'),
            new BrevoContactClient(new MockHttpClient($handler), new NullLogger(), 'qa12-key', '7'),
            $this->em($client),
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

    private function abonniertUndGesperrt(KernelBrowser $client): string
    {
        $client->loginUser($this->konto($client));
        $this->profilSchalter($client);   // abonnieren
        $this->abgleich($client);         // bei Brevo angelegt
        sleep(1);
        $this->profilSchalter($client);   // abbestellen → blacklist_pending
        self::assertSame(MarketingSyncState::BLACKLIST_PENDING, $this->kontaktZustand($client));
        sleep(1);

        return (string) $client->getContainer()->get(MarketingContactRepository::class)->findOneByEmail(self::ADRESSE)?->getId();
    }

    private function kontaktZustand(KernelBrowser $client): ?MarketingSyncState
    {
        $this->em($client)->clear();

        return $client->getContainer()->get(MarketingContactRepository::class)->findOneByEmail(self::ADRESSE)?->getSyncState();
    }

    /**
     * Neuer Befund (BF-178, Feature 12 / 04) — eine Wieder-Einwilligung, die eintrifft, WÄHREND der Lauf die Sperre
     * zu Brevo trägt, geht bei Brevo verloren.
     *
     * Spiegelbild von BF-166. Der Lauf hat die Zeile als `blacklist_pending` geladen; während des Sperr-PUT abonniert
     * das Konto erneut (`record()` schreibt eine jüngere Einwilligung und `pending`). Danach schreibt der Lauf
     * `synced` über `pending` — der Nachtrag aus BF-166 sieht nur übertragene Zeilen, die inzwischen GESPERRT sind.
     * Ergebnis: Profil „Abonniert seit …", Brevo gesperrt, kein Auftrag offen. Wegen BF-165 schickt auch kein späterer
     * Abgleich die Aufhebung (`syncedAt` liegt nach der Einwilligung).
     */
    public function testBf178WiedereinwilligungWaehrendDerSperrUebertragungErreichtBrevo(): void
    {
        $client = $this->client();
        $id = $this->abonniertUndGesperrt($client);

        $verbindung = $this->em($client)->getConnection();
        $this->abgleich($client, static function (string $method, string $url, array $rumpf) use ($verbindung): void {
            if ('PUT' === $method && true === ($rumpf['emailBlacklisted'] ?? null)) {
                // Zweite Anfrage, gleichzeitig: „Newsletter abonnieren" im Profil (consentMomentFor + record()).
                $verbindung->executeStatement("UPDATE marketing_contact SET consent_at = DATE_ADD(revoked_at, INTERVAL 1 SECOND), sync_state = 'pending', attempts = 0 WHERE email = ?", [self::ADRESSE]);
                $verbindung->executeStatement('UPDATE `user` SET marketing_consent_at = NOW() WHERE email = ?', [self::ADRESSE]);
            }
        });

        $this->em($client)->clear();
        self::assertNotNull($this->em($client)->getRepository(User::class)->findOneBy(['email' => self::ADRESSE])?->getMarketingConsentAt(), 'Das Profil sagt „abonniert".');

        $this->abgleich($client);
        $this->abgleich($client);

        self::assertFalse($this->brevo[$id]['blacklisted'], 'Profil „abonniert", Brevo gesperrt — die Einwilligung kommt nie an (AK-35).');
    }

    /**
     * Derselbe Befund ohne Gleichzeitigkeit: Wer in derselben Sekunde wieder abonniert, in der der Lauf die Sperre
     * übertragen hat, bekommt die Aufhebung nie. Die Datenbank speichert Sekunden; nach dem Neuladen gilt
     * `syncedAt == consentAt`, und die Bedingung aus BF-165 (`syncedAt < consentAt`) lässt `emailBlacklisted:false`
     * weg — Muster BF-160 an der nächsten Bedingung.
     */
    public function testBf178WiedereinwilligungInDerselbenSekundeWieDieSperreErreichtBrevo(): void
    {
        $client = $this->client();
        $id = $this->abonniertUndGesperrt($client);

        // Am Anfang einer Sekunde beginnen, damit Lauf und Abonnieren in dieselbe fallen.
        while (fmod(microtime(true), 1.0) > 0.2) {
            usleep(20000);
        }
        $this->abgleich($client);         // Sperre übertragen, synced_at = Sekunde S
        $this->profilSchalter($client);   // wieder abonnieren, consent_at = S

        $this->em($client)->clear();
        $kontakt = $client->getContainer()->get(MarketingContactRepository::class)->findOneByEmail(self::ADRESSE);
        // Behebung BF-178: `consentMomentFor()` legt die Einwilligung hinter die letzte Übertragung. Die Vorbedingung
        // der Reproduktion („dieselbe Sekunde") kann damit nicht mehr eintreten; geprüft wird, dass sie es nicht tut.
        self::assertGreaterThan((int) $kontakt?->getSyncedAt()?->format('U'), (int) $kontakt?->getConsentAt()?->format('U'));
        self::assertTrue($this->brevo[$id]['blacklisted']);

        $this->aufrufe = [];
        $this->abgleich($client);

        self::assertFalse($this->aufrufe[0][2]['emailBlacklisted'] ?? null, 'Der Aktualisierungsweg muss die Sperre aufheben (BF-154).');
        self::assertFalse($this->brevo[$id]['blacklisted']);
    }

    /**
     * Neuer Befund (BF-175, vorbestehend in B04) — ein Adresswechsel wird ohne Bestätigung wirksam, wenn im selben
     * Absenden ein Profilbild hochgeladen wird und der Deckel `email_change` erreicht ist.
     *
     * `handleRequest()` schreibt die neue Adresse in die Entity; `AvatarUploadService::upload()` ruft `flush()` und
     * schreibt sie damit in die Datenbank, BEVOR der Controller sie zurücksetzt. Der Deckel-Zweig kehrt zurück, ohne
     * zurückzusetzen. Hebt BF-19 auf: Wer eine Sitzung hat, schreibt das Konto dauerhaft auf eine eigene Adresse um
     * („Passwort vergessen" geht danach dorthin).
     */
    public function testBf175AdresswechselMitBildUndErreichtemDeckelWirdNichtWirksam(): void
    {
        $client = $this->client();
        $user = $this->konto($client);
        $client->loginUser($user);
        $neu = 'qa12d-uebernahme@example.org';

        // Deckel erschöpfen — an jedem Schlüssel, an dem er zählen könnte (heute die gewünschte Adresse, BF-176).
        foreach ([self::ADRESSE, $neu, (string) $user->getId()] as $schluessel) {
            $client->getContainer()->get('limiter.email_change')->create($schluessel)->consume(10000);
        }

        $crawler = $client->request('GET', self::LOCALE.'/profile');
        $form = $crawler->filter('form[action$="/profile/edit"]')->form();
        $form['profile[email]'] = $neu;
        $form['profile[avatar]']->upload($this->png());
        $client->submit($form);

        $this->em($client)->clear();
        $gespeichert = $this->em($client)->getRepository(User::class)->find($user->getId());
        if (null !== $gespeichert?->getAvatarFilename()) {
            $this->aufzuraeumen[] = self::AVATARE.'/'.$gespeichert->getAvatarFilename();
        }

        self::assertSame(self::ADRESSE, $gespeichert?->getEmail(), 'Die Adresse darf erst mit dem Bestätigungslink wechseln (BF-19).');
    }

    /**
     * Neuer Befund (BF-176, vorbestehend in B04) — der Deckel `email_change` („3 je Stunde, am Konto", BF-21) zählt an
     * der GEWÜNSCHTEN Adresse: `getUserIdentifier()` wird nach `handleRequest()` gelesen, die Entity trägt da schon die
     * Eingabe. Jede neue Zieladresse hat ein eigenes Kontingent — je Absenden zwei Mails (Bestätigung an die neue,
     * Warnung an die bisherige Adresse), ohne Grenze am Konto.
     */
    public function testBf176DeckelZaehltAmKontoNichtAnDerGewuenschtenAdresse(): void
    {
        $client = $this->client();
        $user = $this->konto($client);
        $client->loginUser($user);
        $neu = 'qa12d-ziel@example.org';
        $fabrik = $client->getContainer()->get('limiter.email_change');
        foreach ([self::ADRESSE, $neu, (string) $user->getId()] as $schluessel) {
            $fabrik->create($schluessel)->reset();
        }

        $crawler = $client->request('GET', self::LOCALE.'/profile');
        $form = $crawler->filter('form[action$="/profile/edit"]')->form();
        $form['profile[email]'] = $neu;
        $client->submit($form);
        self::assertResponseRedirects(self::LOCALE.'/profile');

        $voll = 10000;   // when@test
        self::assertSame($voll, $fabrik->create($neu)->consume(0)->getRemainingTokens(), 'Die gewünschte Adresse ist kein Konto.');
        self::assertContains(
            $voll - 1,
            [$fabrik->create(self::ADRESSE)->consume(0)->getRemainingTokens(), $fabrik->create((string) $user->getId())->consume(0)->getRemainingTokens()],
            'Verbraucht werden muss am Konto (bisherige Adresse oder Kennung).',
        );
    }

    /**
     * Neuer Befund (BF-177, Feature 12) — der Messauslöser „Einführung abgeschlossen" aus BF-171 steht als Element mit
     * `on-connect` in der Seite, die Turbo zwischenspeichert. Jede Rückkehr zur Restaurantliste aus dem Speicher
     * (Zurück, Vorschau beim erneuten Aufruf) verbindet ihn neu und zählt erneut — im Browser gemessen: ein Absenden,
     * vier Ereignisse. Turbos Mittel dafür ist `data-turbo-temporary` (vor dem Zwischenspeichern entfernt).
     */
    public function testBf177MessausloeserWirdNichtZwischengespeichert(): void
    {
        $client = $this->client();
        $client->loginUser($this->konto($client));

        $crawler = $client->request('GET', self::LOCALE.'/einfuehrung/newsletter');
        $client->submit($crawler->filter('form#einfuehrung-newsletter')->form());
        $crawler = $client->followRedirect();

        $ausloeser = $crawler->filter('[data-usage-event-name-value="einfuehrung_abgeschlossen"]');
        self::assertCount(1, $ausloeser);
        self::assertNotNull($ausloeser->attr('data-turbo-temporary'), 'Ohne data-turbo-temporary zählt jede Rückkehr aus Turbos Zwischenspeicher erneut.');
    }

    /**
     * Neuer Befund (BF-181, Feature 12 / 04; Hinweis des code-reviewer, nachgestellt) — ob es den Kontakt bei Brevo gibt,
     * lässt sich aus `synced_at`, `attempts` und dem Zustand nicht sicher ablesen; dann stellt Abbestellen keinen
     * Sperrauftrag (Restfall von BF-167).
     *
     * Hier der Weg über einen zurückgesetzten Zähler: Die erste Übertragung legt den Kontakt bei Brevo an, die Antwort geht
     * verloren (`failed`, `attempts = 1`, `synced_at` leer). Ein bestätigter Adresswechsel setzt die Zeile auf `pending`
     * und `attempts = 0` (`changeEmail()`), danach bestellt das Konto ab — `revokeForAccount()` hält die Zeile für nie
     * übertragen und stellt keinen Auftrag; der Lauf lässt die gesperrte `pending`-Zeile liegen. Dieselbe Lage entsteht,
     * wenn der `flush()` am Ende eines Laufs scheitert (alle Zeilen bleiben `pending` ohne Spur) oder ein gleichzeitiges
     * Abbestellen von einem `failed` des Laufs überschrieben wird.
     */
    public function testBf181AbbestellenNachZurueckgesetztemZaehlerErreichtBrevo(): void
    {
        $client = $this->client();
        $user = $this->konto($client);
        $client->loginUser($user);
        $this->profilSchalter($client);   // abonnieren

        // Brevo legt an, die Antwort geht verloren.
        $this->abgleich($client, null, static fn (string $method): ?MockResponse => 'POST' === $method ? new MockResponse('', ['http_code' => 504]) : null);
        $this->em($client)->clear();
        $kontakt = $client->getContainer()->get(MarketingContactRepository::class)->findOneByEmail(self::ADRESSE);
        self::assertSame(MarketingSyncState::FAILED, $kontakt?->getSyncState());
        $id = (string) $kontakt?->getId();
        self::assertArrayHasKey($id, $this->brevo, 'Bei Brevo steht der Kontakt.');

        // Bestätigter Adresswechsel (B04, BF-19) vor dem nächsten Lauf.
        $neu = 'qa12d-neu@example.org';
        $user = $this->em($client)->getRepository(User::class)->findOneBy(['email' => self::ADRESSE]);
        $token = $user->requestEmailChange($neu);
        $this->em($client)->flush();
        $client->request('GET', self::LOCALE.'/verify/email-change/'.$token);

        // Abbestellen, noch vor dem nächsten Lauf — derselbe Dienst wie Profilschalter und Einführung (nach dem
        // Adresswechsel ist die Sitzung der alten Adresse ungültig; der Dienst wird deshalb direkt gerufen).
        $this->em($client)->clear();
        $konto = $this->em($client)->getRepository(User::class)->findOneBy(['email' => $neu]);
        self::assertNotNull($konto);
        self::assertTrue($client->getContainer()->get(\App\Account\NewsletterSubscription::class)->unsubscribe($konto));
        $this->em($client)->clear();
        self::assertNull($this->em($client)->getRepository(User::class)->findOneBy(['email' => $neu])?->getMarketingConsentAt(), 'Das Profil sagt „nicht abonniert".');

        $this->abgleich($client);
        $this->abgleich($client);

        self::assertTrue($this->brevo[$id]['blacklisted'], 'Der bei Brevo angelegte Kontakt muss gesperrt werden — sonst Werbung nach Widerspruch.');
    }

    /**
     * Neuer Befund (BF-180, B04 / 12; Hinweis des code-reviewer, nachgestellt) — „Profilbild entfernen" liegt nicht unter
     * der Sperre aus BF-173 und arbeitet mit dem Namen aus der beim Anfragebeginn geladenen Entity.
     *
     * Läuft ein Upload derselben Kontos dazwischen (am Server mit vier Arbeitsprozessen nachgestellt:
     * `qa/12/nachpruefung2/a13-avatar-loeschen-gleichzeitig.ausgabe.txt`), löscht „Entfernen" den längst ersetzten alten
     * Namen, schreibt `NULL` über den neuen — und dessen Datei bleibt ohne Konto liegen. Hier ist die andere Anfrage als das
     * nachgebildet, was sie hinterlässt: neue Datei, neuer Name in der Datenbank.
     */
    public function testBf180EntfernenNebenEinemUploadHinterlaesstKeineDatei(): void
    {
        $client = $this->client();
        $user = $this->konto($client);
        $alt = 'qa12d-alt-'.bin2hex(random_bytes(3)).'.png';
        $neu = 'qa12d-neu-'.bin2hex(random_bytes(3)).'.png';
        foreach ([$alt, $neu] as $datei) {
            copy($this->png(), self::AVATARE.'/'.$datei);
            $this->aufzuraeumen[] = self::AVATARE.'/'.$datei;
        }
        $user->setAvatarFilename($alt);
        $this->em($client)->flush();

        // Die andere Anfrage hat inzwischen hochgeladen: alte Datei weg, neue Datei und neuer Name gespeichert.
        @unlink(self::AVATARE.'/'.$alt);
        $this->em($client)->getConnection()->executeStatement('UPDATE `user` SET avatar_filename = ? WHERE id = ?', [$neu, $user->getId()]);

        // „Profilbild entfernen" mit der beim Anfragebeginn geladenen Entity.
        $client->getContainer()->get(\App\Service\AvatarUploadService::class)->delete($user);

        clearstatcache();
        $verweis = $this->em($client)->getConnection()->fetchOne('SELECT avatar_filename FROM `user` WHERE id = ?', [$user->getId()]);
        self::assertTrue($verweis === $neu || !is_file(self::AVATARE.'/'.$neu), 'Die Datei des neuen Bildes bleibt ohne Konto liegen (Muster BF-173).');
    }
}
