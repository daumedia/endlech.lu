<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Account\NewsletterSubscription;
use App\Entity\User;
use App\Enum\MarketingSyncState;
use App\Marketing\BrevoContactClient;
use App\Marketing\MarketingPayloadMapper;
use App\Marketing\MarketingSyncService;
use App\Repository\MarketingContactRepository;
use App\Service\AvatarUploadService;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * QA Feature 12 — vierter Durchlauf (dritte Nachprüfung, 2026-10-23).
 *
 * Reproduktionen der in diesem Durchlauf gefundenen Befunde. Sie prüfen das richtige Verhalten, waren beim Schreiben
 * rot und überspringen sich, solange der Befund offen ist. Scharf:
 * `QA12_REPRO=1 php bin/phpunit tests/Functional/Controller/Qa12Nachpruefung4Test.php`.
 * Wer den Befund behebt, entfernt den Aufruf `$this->offen(…)` — und ändert sonst nichts an der Prüfung.
 *
 * Wie in den früheren QA-Dateien: Der echte `MarketingSyncService` läuft gegen einen Brevo-Nachbau, der Sperren behält
 * wie das Original; eine gleichzeitige zweite Anfrage ist als das nachgebildet, was sie in die Datenbank schreibt — an
 * der Unit of Work des Laufs vorbei, wie es ein zweiter PHP-Prozess täte.
 */
final class Qa12Nachpruefung4Test extends AbstractWebTestCase
{
    private const string ADRESSE = 'qa12e@example.org';
    private const string AVATARE = __DIR__.'/../../../public/uploads/avatars';

    /** Der nachgebaute Brevo-Speicher: ext_id => ['email' => …, 'blacklisted' => bool]. */
    private array $brevo = [];

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
        foreach (['limiter.newsletter_toggle', 'limiter.email_change'] as $limiter) {
            $client->getContainer()->get($limiter)->create(self::ADRESSE)->reset();
        }

        return $client;
    }

    private function em(KernelBrowser $client): EntityManagerInterface
    {
        return $client->getContainer()->get(EntityManagerInterface::class);
    }

    private function konto(KernelBrowser $client): User
    {
        $user = new User();
        $user->setName('Qa Vierte Prüfung');
        $user->setEmail(self::ADRESSE);
        $user->setPassword('unbenutzt');
        $user->setRoles([]);
        $user->setIsVerified(true);

        $this->em($client)->persist($user);
        $this->em($client)->flush();

        return $user;
    }

    /**
     * @param (callable(string, string, array<string, mixed>): void)|null       $waehrend läuft innerhalb des Aufrufs an Brevo
     * @param (callable(string, string, array<string, mixed>): ?MockResponse)|null $antwort  eigene Antwort statt der des Nachbaus
     */
    private function abgleich(KernelBrowser $client, ?callable $waehrend = null, ?callable $antwort = null): void
    {
        $handler = function (string $method, string $url, array $options) use ($waehrend, $antwort): MockResponse {
            $rumpf = json_decode((string) ($options['body'] ?? '{}'), true) ?: [];

            if (null !== $waehrend) {
                $waehrend($method, $url, $rumpf);
            }

            $vorgabe = null === $antwort ? null : $antwort($method, $url, $rumpf);
            if (null !== $vorgabe) {
                return $vorgabe;
            }

            if ('POST' === $method) {
                $this->brevo[(string) $rumpf['ext_id']] = ['email' => $rumpf['email'] ?? null, 'blacklisted' => $rumpf['emailBlacklisted'] ?? false];

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

    /**
     * Neuer Befund (BF-182, vorbestehend in B04) — ein Name aus Leerzeichen im Profil erzeugt HTTP 500 statt der Meldung.
     *
     * `TextType` kürzt „   " auf „" und gibt `null` weiter; `User::setName(string)` wirft, bevor `NotBlank` greifen kann.
     * Ein Browser schickt den Wert ab (das Feld ist `required`, Leerzeichen gelten dafür als Inhalt). Konvention
     * „Die Prüfung gehört dorthin, wo der Wert hereinkommt": `'empty_data' => ''` fehlt (wie BF-51 in B20).
     */
    public function testBf182NameAusLeerzeichenZeigtEineMeldungStattHttp500(): void
    {
        $this->offen('BF-182');
        $client = $this->client();
        $client->loginUser($this->konto($client));

        $crawler = $client->request('GET', self::LOCALE.'/profile');
        $form = $crawler->filter('form[action$="/profile/edit"]')->form(['profile[name]' => '   ']);
        $client->submit($form);

        self::assertSame(422, $client->getResponse()->getStatusCode(), 'Ein leerer Name ist eine Eingabe, die eine Meldung bekommt, kein Serverfehler.');
    }

    /**
     * Neuer Befund (BF-184, vorbestehend in B04; Hinweis des code-reviewer, am Server nachgestellt) — ein Adresswechsel im
     * Profil auf eine Adresse, die `Email` im HTML5-Modus durchlässt, die `Mime\Address` (RFC 2822) aber ablehnt, endet in
     * HTTP 500 — und die Vormerkung bleibt gespeichert. Muster BF-119: Die vier anderen Adresseingänge prüfen seit
     * 2026-09-11 strikt, das Profil nicht. Ebenso eine Adresse über 180 Zeichen (Spalte `pending_email`).
     */
    public function testBf184AdresswechselAufRfcWidrigeAdresseZeigtEineMeldung(): void
    {
        $this->offen('BF-184');
        $client = $this->client();
        $client->loginUser($this->konto($client));

        foreach (['../../etc/passwd@example.lu', str_repeat('a', 169).'@example.org'] as $neu) {
            $crawler = $client->request('GET', self::LOCALE.'/profile');
            $client->submit($crawler->filter('form[action$="/profile/edit"]')->form(['profile[email]' => $neu]));

            self::assertSame(422, $client->getResponse()->getStatusCode(), 'Meldung am Feld statt Serverfehler: '.$neu);
            $this->em($client)->clear();
            self::assertNull(
                $this->em($client)->getRepository(User::class)->findOneBy(['email' => self::ADRESSE])?->getPendingEmail(),
                'Eine abgewiesene Adresse darf nicht vorgemerkt bleiben.',
            );
        }
    }

    /**
     * Neuer Befund (BF-185, Feature 12 / 04; Hinweis des code-reviewer, hier nachgestellt) — scheitert im Lauf die
     * Übertragung einer Zeile, während das Konto abbestellt, überschreibt der Lauf den Sperrauftrag mit `failed`.
     *
     * Restfall von BF-181 („… oder ein gleichzeitiges Abbestellen von einem `failed` des Laufs überschrieben wird"), den
     * der dritte Fehlerauftrag nicht erreicht hat: Der Nachtrag nach dem Lauf sieht nur gelungene Übertragungen. Danach
     * steht die Zeile auf `failed`, gesperrt (`consent_at <= revoked_at`) — `findOpenForSync()` holt sie nie wieder ab.
     * Das Profil sagt „nicht abonniert", bei Brevo bleibt der Kontakt offen: Werbung nach Widerspruch.
     */
    public function testBf185GescheiterterAbgleichUeberschreibtGleichzeitigesAbbestellenNicht(): void
    {
        $this->offen('BF-185');
        $client = $this->client();
        $client->loginUser($this->konto($client));
        $this->profilSchalter($client);   // abonnieren
        $this->abgleich($client);         // bei Brevo angelegt
        $id = (string) $client->getContainer()->get(MarketingContactRepository::class)->findOneByEmail(self::ADRESSE)?->getId();
        self::assertArrayHasKey($id, $this->brevo);
        self::assertFalse($this->brevo[$id]['blacklisted']);

        // Etwas verlangt eine Neuübertragung (Adress- oder Namenswechsel, neue Sprache) — die Zeile steht auf `pending`.
        $verbindung = $this->em($client)->getConnection();
        $verbindung->executeStatement("UPDATE marketing_contact SET sync_state = 'pending' WHERE id = ?", [$id]);
        sleep(1);

        // Der Lauf überträgt, Brevo antwortet 504. WÄHREND des Aufrufs bestellt das Konto ab (was `revokeForAccount()` und
        // `NewsletterSubscription::unsubscribe()` in die Datenbank schreiben).
        $this->em($client)->clear();
        $this->abgleich(
            $client,
            static function () use ($verbindung, $id): void {
                $verbindung->executeStatement("UPDATE marketing_contact SET sync_state = 'blacklist_pending', revoked_at = NOW(), attempts = 0, last_error = NULL WHERE id = ?", [$id]);
                $verbindung->executeStatement('UPDATE `user` SET marketing_consent_at = NULL WHERE email = ?', [self::ADRESSE]);
            },
            static fn (): MockResponse => new MockResponse('', ['http_code' => 504]),
        );

        $this->em($client)->clear();
        self::assertNull($this->em($client)->getRepository(User::class)->findOneBy(['email' => self::ADRESSE])?->getMarketingConsentAt(), 'Das Profil sagt „nicht abonniert".');

        $this->abgleich($client);
        $this->abgleich($client);

        self::assertTrue($this->brevo[$id]['blacklisted'], 'Der Kontakt bei Brevo muss gesperrt werden — sonst Werbung nach Widerspruch.');
    }

    /**
     * Neuer Befund (BF-186, B04 / 12; Hinweis des code-reviewer, hier nachgestellt) — „Profilbild entfernen" mit einer
     * Entity, die beim Anfragebeginn noch kein Bild trug, löscht die Datei des inzwischen gespeicherten Bildes, lässt den
     * Verweis aber stehen: `setAvatarFilename(null)` ist für Doctrine keine Änderung, der `flush()` schreibt nichts.
     *
     * Die andere Anfrage (ein Upload, der kurz vorher fertig wurde) ist als das nachgebildet, was sie hinterlässt.
     * Folge: Konto verweist auf eine Datei, die es nicht mehr gibt — Kopfzeile und Profil zeigen ein kaputtes Bild.
     */
    public function testBf186EntfernenMitLeeremEntityNamenHinterlaesstKeinenTotenVerweis(): void
    {
        $this->offen('BF-186');
        $client = $this->client();
        $user = $this->konto($client);
        self::assertNull($user->getAvatarFilename());

        $neu = 'qa12e-neu-'.bin2hex(random_bytes(3)).'.png';
        $pfad = sys_get_temp_dir().'/'.$neu;
        imagepng(imagecreatetruecolor(4, 4), $pfad);
        copy($pfad, self::AVATARE.'/'.$neu);
        @unlink($pfad);
        $this->aufzuraeumen[] = self::AVATARE.'/'.$neu;
        $this->em($client)->getConnection()->executeStatement('UPDATE `user` SET avatar_filename = ? WHERE id = ?', [$neu, $user->getId()]);

        $client->getContainer()->get(AvatarUploadService::class)->delete($user);

        clearstatcache();
        $verweis = $this->em($client)->getConnection()->fetchOne('SELECT avatar_filename FROM `user` WHERE id = ?', [$user->getId()]);
        self::assertTrue(null === $verweis || is_file(self::AVATARE.'/'.$verweis), 'Das Konto verweist auf eine gelöschte Datei: '.var_export($verweis, true));
    }

    /**
     * Gegenprobe zu BF-182 und BF-184, ohne Überspringen: Die gültigen Eingaben laufen wie bisher (Name speichern, Adresse
     * vormerken) — damit eine Reparatur der beiden Befunde den Hauptweg von B04 nicht mitnimmt.
     */
    public function testGegenprobeGueltigeEingabenImProfilLaufenWieBisher(): void
    {
        $client = $this->client();
        $client->loginUser($this->konto($client));

        $crawler = $client->request('GET', self::LOCALE.'/profile');
        $client->submit($crawler->filter('form[action$="/profile/edit"]')->form(['profile[name]' => 'Qa Neuer Name']));
        self::assertResponseRedirects(self::LOCALE.'/profile');

        $crawler = $client->request('GET', self::LOCALE.'/profile');
        $client->submit($crawler->filter('form[action$="/profile/edit"]')->form(['profile[email]' => 'qa12e-neu@example.org']));
        self::assertResponseRedirects(self::LOCALE.'/profile');

        $this->em($client)->clear();
        $konto = $this->em($client)->getRepository(User::class)->findOneBy(['email' => self::ADRESSE]);
        self::assertSame('Qa Neuer Name', $konto?->getName());
        self::assertSame('qa12e-neu@example.org', $konto?->getPendingEmail());
    }
}
