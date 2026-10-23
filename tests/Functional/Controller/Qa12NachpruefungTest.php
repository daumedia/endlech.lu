<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\MarketingContact;
use App\Entity\User;
use App\Enum\MarketingSyncState;
use App\Marketing\BrevoContactClient;
use App\Marketing\MarketingContactRegistry;
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
 * QA Feature 12 — Nachprüfung nach dem Fehlerauftrag (2026-10-23).
 *
 * Wie `Qa12OnboardingTest`: Der echte `MarketingSyncService` läuft gegen einen Brevo-Nachbau, der Sperren
 * behält wie das Original. Geprüft wird der Rumpf, der tatsächlich hinausgeht.
 *
 * - **Belege** (`testAk…`) laufen immer.
 * - **Reproduktionen** (`testBf…`) prüfen das richtige Verhalten, waren beim Schreiben rot und überspringen
 *   sich, solange der Befund offen ist. Scharf: `QA12_REPRO=1 php bin/phpunit tests/Functional/Controller/Qa12NachpruefungTest.php`.
 *   Wer den Befund behebt, entfernt den Aufruf `$this->offen(…)`.
 */
final class Qa12NachpruefungTest extends AbstractWebTestCase
{
    private const string ADRESSE = 'qa12n@example.org';

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
        $client->disableReboot();
        $client->getContainer()->get('limiter.newsletter_toggle')->create(self::ADRESSE)->reset();

        return $client;
    }

    private function em(KernelBrowser $client): EntityManagerInterface
    {
        return $client->getContainer()->get(EntityManagerInterface::class);
    }

    private function konto(KernelBrowser $client): User
    {
        $user = new User();
        $user->setName('Qa Nachprüfung');
        $user->setEmail(self::ADRESSE);
        $user->setPassword('unbenutzt');
        $user->setRoles([]);
        $user->setIsVerified(true);
        $user->markOnboardingDue();

        $this->em($client)->persist($user);
        $this->em($client)->flush();

        return $user;
    }

    private function abgleich(KernelBrowser $client): void
    {
        $handler = function (string $method, string $url, array $options): MockResponse {
            $rumpf = json_decode((string) ($options['body'] ?? '{}'), true) ?: [];
            $this->aufrufe[] = [$method, $url, $rumpf];

            if ('POST' === $method) {
                $this->brevo[(string) $rumpf['ext_id']] = [
                    'email' => $rumpf['email'] ?? null,
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

    private function kontakt(KernelBrowser $client, string $email): ?MarketingContact
    {
        return $client->getContainer()->get(MarketingContactRepository::class)->findOneByEmail($email);
    }

    /**
     * BF-154 bleibt behoben — Beleg am Rumpf: Nach Abbestellen und erneutem Abonnieren hebt der nächste
     * Lauf die Sperre bei Brevo auf, und zwar genau einmal im Rumpf des Aktualisierungswegs.
     */
    public function testAk35WiederEinwilligungHebtDieSperreAuf(): void
    {
        $client = $this->client();
        $client->loginUser($this->konto($client));

        $this->profilSchalter($client);   // abonnieren
        $this->abgleich($client);
        $this->profilSchalter($client);   // abbestellen
        $this->abgleich($client);
        sleep(1);
        $this->profilSchalter($client);   // wieder abonnieren
        $this->aufrufe = [];
        $this->abgleich($client);

        $id = (string) $this->kontakt($client, self::ADRESSE)?->getId();
        self::assertSame('PUT', $this->aufrufe[0][0]);
        self::assertFalse($this->aufrufe[0][2]['emailBlacklisted'] ?? null);
        self::assertFalse($this->brevo[$id]['blacklisted']);
    }

    /**
     * Neuer Befund der Nachprüfung — die Aufhebung der Sperre geht bei JEDEM späteren Abgleich wieder mit.
     *
     * design.md, Entscheidung 6: `emailBlacklisted: false` nur dann, „wenn die Zeile eine Sperre trägt und die
     * Einwilligung jünger ist" — ausdrücklich NICHT bei jedem Abgleich, weil das „eine Abmeldung aufhübe, deren
     * Webhook noch nicht angekommen ist — gegen den erklärten Willen" (ebenso 04/EC-05). Die Bedingung im Mapper
     * (`revokedAt` gesetzt, `consentAt` jünger) bleibt nach der einmal übertragenen Aufhebung aber für immer wahr:
     * `revokedAt` bleibt als Historie stehen. Jeder weitere Abgleich dieses Kontakts — hier ausgelöst durch einen
     * bestätigten Adresswechsel (B04), ebenso durch einen Statuswechsel einer Warteliste derselben Adresse in der
     * Verwaltung — schickt die Aufhebung erneut und macht eine Abmeldung rückgängig, die bisher nur Brevo kennt.
     */
    public function testBf165SpaetererAbgleichHebtEineAbmeldungBeiBrevoNichtAuf(): void
    {
        $client = $this->client();
        $user = $this->konto($client);
        $client->loginUser($user);

        $this->profilSchalter($client);   // abonnieren
        $this->abgleich($client);         // angelegt
        $this->profilSchalter($client);   // abbestellen
        $this->abgleich($client);         // gesperrt
        sleep(1);
        $this->profilSchalter($client);   // wieder abonnieren
        $this->abgleich($client);         // Sperre aufgehoben (BF-154, richtig)

        $id = (string) $this->kontakt($client, self::ADRESSE)?->getId();
        self::assertFalse($this->brevo[$id]['blacklisted']);

        // Die Person klickt den Abmeldelink in einer Brevo-Mail. Brevo sperrt sofort; der Webhook ist noch
        // unterwegs (oder scheitert) — die Anwendung weiß davon noch nichts.
        $this->brevo[$id]['blacklisted'] = true;

        // Im selben Fenster bestätigt sie einen Adresswechsel (B04, BF-19).
        $this->em($client)->clear();
        $user = $this->em($client)->getRepository(User::class)->findOneBy(['email' => self::ADRESSE]);
        $token = $user->requestEmailChange('qa12n-neu@example.org');
        $this->em($client)->flush();
        $client->request('GET', self::LOCALE.'/verify/email-change/'.$token);
        self::assertSame(MarketingSyncState::PENDING, $this->kontakt($client, 'qa12n-neu@example.org')?->getSyncState());

        $this->aufrufe = [];
        $this->abgleich($client);

        self::assertArrayNotHasKey('emailBlacklisted', $this->aufrufe[0][2] ?? [], 'Ein Abgleich ohne neue Einwilligung darf eine Sperre bei Brevo nicht aufheben (design.md Entscheidung 6, 04/EC-05).');
        self::assertTrue($this->brevo[$id]['blacklisted'], 'Die Abmeldung über den Link der Brevo-Mail muss bestehen bleiben.');
    }

    /**
     * Folge desselben Befunds, wenn der Webhook danach doch noch ankommt: Die Anwendung sperrt die Zeile,
     * Brevo bleibt aber entsperrt — niemand überträgt die Sperre ein zweites Mal.
     */
    public function testBf165VerspaeteterWebhookHinterlaesstKeinenEntsperrtenKontakt(): void
    {
        $client = $this->client();
        $user = $this->konto($client);
        $client->loginUser($user);

        $this->profilSchalter($client);
        $this->abgleich($client);
        $this->profilSchalter($client);
        $this->abgleich($client);
        sleep(1);
        $this->profilSchalter($client);
        $this->abgleich($client);

        $id = (string) $this->kontakt($client, self::ADRESSE)?->getId();
        $this->brevo[$id]['blacklisted'] = true;   // Abmeldelink in Brevo

        $this->em($client)->clear();
        $user = $this->em($client)->getRepository(User::class)->findOneBy(['email' => self::ADRESSE]);
        $token = $user->requestEmailChange('qa12n-neu@example.org');
        $this->em($client)->flush();
        $client->request('GET', self::LOCALE.'/verify/email-change/'.$token);
        $this->abgleich($client);

        // Jetzt erst kommt der Webhook an (Brevo meldet die Abmeldung).
        $client->getContainer()->get(MarketingContactRegistry::class)->blockByEmail('qa12n-neu@example.org');
        $this->em($client)->flush();
        $this->abgleich($client);

        self::assertTrue($this->kontakt($client, 'qa12n-neu@example.org')?->isBlocked());
        self::assertTrue($this->brevo[$id]['blacklisted'], 'Lokal gesperrt, bei Brevo entsperrt: Der Kontakt bekäme weiter Kampagnen.');
    }

    /**
     * Der Lauf mit einem Brevo-Nachbau, dessen Antwort sich je Aufruf vorgeben lässt.
     *
     * @param callable(string, string, array<string, mixed>): ?MockResponse $vorher darf eine Antwort vorwegnehmen
     */
    private function abgleichMit(KernelBrowser $client, callable $vorher): void
    {
        $handler = function (string $method, string $url, array $options) use ($vorher): MockResponse {
            $rumpf = json_decode((string) ($options['body'] ?? '{}'), true) ?: [];
            $this->aufrufe[] = [$method, $url, $rumpf];

            if ('POST' === $method) {
                $this->brevo[(string) $rumpf['ext_id']] = ['email' => $rumpf['email'] ?? null, 'blacklisted' => $rumpf['emailBlacklisted'] ?? false];
            }

            $antwort = $vorher($method, $url, $rumpf);

            if (null !== $antwort) {
                return $antwort;
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

    /**
     * Neuer Befund — eine Abmeldung, die WÄHREND eines laufenden Abgleichs eintrifft, erreicht Brevo nie.
     *
     * Der Lauf lädt die offenen Zeilen am Anfang, ruft Brevo und schreibt alle Zustände mit EINEM `flush()` am
     * Ende (BF-87). Bestellt das Konto in dieser Zeit in einer zweiten Anfrage ab, steht die Zeile noch auf
     * `pending` ohne `synced_at` — `revokeForAccount()` stellt deshalb keinen Sperrauftrag, nur `revoked_at`.
     * Danach schreibt der Lauf `synced`: Brevo hat einen frisch angelegten, nicht gesperrten Kontakt (der die
     * Willkommens-Mail auslöst), die Zeile ist lokal gesperrt, und kein Auftrag ist mehr offen.
     *
     * Die zweite Anfrage ist hier als das nachgebildet, was sie in die Datenbank schreibt — an der Unit of Work
     * des Laufs vorbei, wie es ein zweiter PHP-Prozess täte.
     */
    public function testBf166AbmeldungWaehrendDesAbgleichsErreichtBrevo(): void
    {
        $client = $this->client();
        $client->loginUser($this->konto($client));
        $this->profilSchalter($client);   // abonnieren → pending, synced_at leer

        $verbindung = $this->em($client)->getConnection();
        $this->abgleichMit($client, function (string $method) use ($verbindung): ?MockResponse {
            if ('POST' === $method) {
                // Zweite Anfrage, gleichzeitig: „Newsletter abbestellen" im Profil (revokeForAccount bei synced_at = NULL).
                $verbindung->executeStatement('UPDATE marketing_contact SET revoked_at = DATE_ADD(consent_at, INTERVAL 1 SECOND) WHERE email = ?', [self::ADRESSE]);
                $verbindung->executeStatement('UPDATE `user` SET marketing_consent_at = NULL WHERE email = ?', [self::ADRESSE]);
            }

            return null;
        });

        $this->em($client)->clear();
        $kontakt = $this->kontakt($client, self::ADRESSE);
        $id = (string) $kontakt?->getId();
        self::assertTrue($kontakt?->isBlocked(), 'Lokal ist die Abmeldung angekommen.');
        self::assertFalse($this->brevo[$id]['blacklisted']);

        // Die nächsten Läufe müssen die Abmeldung nachtragen.
        $this->abgleich($client);
        $this->abgleich($client);
        self::assertTrue($this->brevo[$id]['blacklisted'], 'Brevo hat den Kontakt angelegt und nie gesperrt — er bekommt Willkommens-Mail und Kampagnen.');
    }

    /**
     * Neuer Befund — scheitert die erste Übertragung NACH dem Anlegen bei Brevo (Zeitüberschreitung), zählt die
     * Zeile als nie übertragen; eine Abmeldung danach stellt keinen Sperrauftrag.
     */
    public function testBf167AbmeldungNachGescheiterterErstuebertragungErreichtBrevo(): void
    {
        $client = $this->client();
        $client->loginUser($this->konto($client));
        $this->profilSchalter($client);   // abonnieren

        // Brevo legt an, die Antwort kommt nicht an (hier: 504 nach dem Anlegen).
        $this->abgleichMit($client, static fn (string $method): ?MockResponse => 'POST' === $method ? new MockResponse('', ['http_code' => 504]) : null);
        $kontakt = $this->kontakt($client, self::ADRESSE);
        self::assertSame(MarketingSyncState::FAILED, $kontakt?->getSyncState());
        self::assertNull($kontakt?->getSyncedAt());
        $id = (string) $kontakt?->getId();
        self::assertArrayHasKey($id, $this->brevo, 'Bei Brevo steht der Kontakt.');

        $this->profilSchalter($client);   // abbestellen, vor dem Nachlauf
        $this->abgleich($client);
        $this->abgleich($client);

        self::assertTrue($this->brevo[$id]['blacklisted'], 'Der bei Brevo angelegte Kontakt muss gesperrt werden.');
    }

    /**
     * Neuer Befund — ein Sperrauftrag, der fünfmal scheitert (z. B. abgelaufener API-Schlüssel, 401), wird nie
     * wieder versucht. Der Zustand `blacklist_pending` soll Fehlversuche überleben (CLAUDE.md, Feature 12);
     * `findOpenForSync()` lässt die Zeile aber ab `attempts = MAX_ATTEMPTS` dauerhaft draußen.
     */
    public function testBf168GescheiterterSperrauftragWirdWeiterVersucht(): void
    {
        $client = $this->client();
        $client->loginUser($this->konto($client));
        $this->profilSchalter($client);   // abonnieren
        $this->abgleich($client);         // angelegt
        $this->profilSchalter($client);   // abbestellen → blacklist_pending

        for ($i = 0; $i < MarketingContact::MAX_ATTEMPTS; ++$i) {
            $this->abgleichMit($client, static fn (): MockResponse => new MockResponse('', ['http_code' => 401]));
        }

        $kontakt = $this->kontakt($client, self::ADRESSE);
        self::assertSame(MarketingSyncState::BLACKLIST_PENDING, $kontakt?->getSyncState());
        self::assertSame(MarketingContact::MAX_ATTEMPTS, $kontakt?->getAttempts());

        // Brevo ist wieder erreichbar.
        $this->aufrufe = [];
        $this->abgleich($client);

        self::assertTrue($this->brevo[(string) $kontakt?->getId()]['blacklisted'], 'Die Abmeldung erreicht Brevo nie; das Profil sagt „nicht abonniert".');
    }

    /**
     * Vorbestehend (Feature 04), beim Prüfen gefunden — ein Adresswechsel auf eine Adresse, die schon als
     * Wartelisten-Kontakt im Auftragsbuch steht, lässt den Kontakt der alten Adresse bei Brevo stehen.
     *
     * `changeEmail()` ruft im Kollisionszweig `scheduleRemoval(alt)`; `sourcesFor(alt)` findet das Konto noch
     * über die Datenbank (der `flush()` folgt erst danach), hält es für eine aktive Quelle der alten Adresse und
     * schreibt stattdessen die Zeile der neuen fort. Die alte bleibt `synced` und wird weiter bespielt.
     */
    public function testBf169AdresswechselAufVorhandenenKontaktEntferntDenAlten(): void
    {
        $this->offen('BF-169');
        $client = $this->client();
        $em = $this->em($client);
        $user = $this->konto($client);
        $client->loginUser($user);
        $this->profilSchalter($client);   // abonnieren
        $this->abgleich($client);         // Kontakt der alten Adresse übertragen

        $neu = 'qa12n-warteliste@example.org';
        $eintrag = new \App\Entity\AppWaitlistEntry();
        $eintrag->setEmail($neu);
        $eintrag->setPlatform(\App\Enum\AppPlatform::IOS);
        $eintrag->setLocale('de');
        $eintrag->confirm();
        $eintrag->setMarketingConsentAt(new \DateTimeImmutable('-3 days'));
        $em->persist($eintrag);
        $kontakt = (new MarketingContact())->setEmail($neu)->setOrigin(\App\Enum\MarketingOrigin::APP)->setLocale('de')
            ->setConsentAt(new \DateTimeImmutable('-3 days'))->setSyncedAt(new \DateTimeImmutable('-3 days'))->setSyncState(MarketingSyncState::SYNCED);
        $em->persist($kontakt);
        $em->flush();

        $em->clear();
        $user = $em->getRepository(User::class)->findOneBy(['email' => self::ADRESSE]);
        $token = $user->requestEmailChange($neu);
        $em->flush();
        $client->request('GET', self::LOCALE.'/verify/email-change/'.$token);
        self::assertSame($neu, $em->getRepository(User::class)->find($user->getId())?->getEmail());

        $em->clear();
        // Spur der Ursache: `scheduleRemoval(alt)` hat das Konto als aktive Quelle der ALTEN Adresse gesehen und
        // über `recordUser()` die Zeile der NEUEN fortgeschrieben — deren Herkunft steht danach auf „Konto".
        self::assertSame(\App\Enum\MarketingOrigin::ACCOUNT, $this->kontakt($client, $neu)?->getOrigin());
        self::assertSame(MarketingSyncState::REMOVAL_PENDING, $this->kontakt($client, self::ADRESSE)?->getSyncState(), 'Der Kontakt der aufgegebenen Adresse muss gelöscht werden (Kommentar in changeEmail()).');
    }
}
