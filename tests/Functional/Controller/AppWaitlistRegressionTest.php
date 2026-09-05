<?php

namespace App\Tests\Functional\Controller;

use App\Entity\AppWaitlistEntry;
use App\Enum\AppPlatform;
use App\Repository\AppWaitlistEntryRepository;
use App\Repository\MarketingContactRepository;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

/**
 * Regressionen aus der QA vom 2026-09-05 (BF-117 bis BF-121).
 *
 * Jeder Test bildet die Reproduktion aus `qa-report.md` nach. Wird einer wieder
 * rot, ist derselbe Fehler zurück — nicht ein ähnlicher.
 */
final class AppWaitlistRegressionTest extends AbstractWebTestCase
{
    private const PFAD = self::LOCALE.'/app';

    /**
     * BF-117 — Der bei einem abgelaufenen Vorgang neu ausgestellte Link muss
     * TRAGEN, nicht bloß anders lauten.
     *
     * ⚠ Der frühere Test prüfte nur, dass sich der Token ändert. Genau daran
     * ist der Fehler vorbeigelaufen (dasselbe Muster wie BF-64).
     */
    public function testBf117NeuerLinkNachAblaufLoestTatsaechlichEin(): void
    {
        $client = static::createClient();
        $entry = $this->eintrag($client, 'bf117@example.lu');
        $this->altern($client, $entry, '-8 days');

        $this->absenden($client, 'bf117@example.lu');

        $frisch = $this->repo($client)->findOneByEmail('bf117@example.lu');
        self::assertNotNull($frisch);

        $client->request('GET', self::LOCALE.'/app/confirmation/'.$frisch->getConfirmationToken());

        self::assertResponseIsSuccessful(
            'BF-117: Der neu ausgestellte Link muss einlösbar sein — sonst besteht die Sackgasse aus AK-17 fort.',
        );
        self::assertNotNull(
            $this->repo($client)->findOneByEmail('bf117@example.lu')?->getSelfConfirmedAt(),
        );
    }

    /**
     * BF-118 — Der Dublettenzweig muss Kontingent verbrauchen.
     *
     * Er löst eine Mail aus; ohne Verbrauch ist er ein ungedeckelter Versandweg
     * an eine frei wählbare fremde Adresse.
     */
    public function testBf118DublettenzweigVerbrauchtKontingent(): void
    {
        $client = static::createClient();
        $entry = $this->eintrag($client, 'bf118@example.lu');
        $this->altern($client, $entry, '-8 days');

        $factory = $client->getContainer()->get('limiter.app_waitlist');
        $vorher = $factory->create('127.0.0.1')->consume(0)->getRemainingTokens();

        $this->absenden($client, 'bf118@example.lu');

        $nachher = $factory->create('127.0.0.1')->consume(0)->getRemainingTokens();

        self::assertSame(
            $vorher - 1,
            $nachher,
            'BF-118: Ein Absendevorgang, der eine Mail auslöst, verbraucht Kontingent.',
        );
    }

    /** BF-118 — und ein bereits bestätigter Eintrag löst weder Mail noch Verbrauch aus. */
    public function testBf118BestaetigteDubletteVerbrauchtNichts(): void
    {
        $client = static::createClient();
        $entry = $this->eintrag($client, 'bf118b@example.lu');
        $entry->confirm();
        $client->getContainer()->get(EntityManagerInterface::class)->flush();

        $factory = $client->getContainer()->get('limiter.app_waitlist');
        $vorher = $factory->create('127.0.0.1')->consume(0)->getRemainingTokens();

        $this->absenden($client, 'bf118b@example.lu');

        self::assertSame($vorher, $factory->create('127.0.0.1')->consume(0)->getRemainingTokens());
        self::assertEmailCount(0);
    }

    /**
     * BF-119 — Eine Adresse, die der Mailversand nicht annimmt, muss schon am
     * Formular scheitern: 422, kein 500, und kein Eintrag.
     */
    public function testBf119RfcWidrigeAdresseLiefert422UndLegtNichtsAn(): void
    {
        $client = static::createClient();

        foreach ([
            '../../etc/passwd@example.lu',
            'a"b(c)d@example.lu',
            'jemand@@example.lu',
        ] as $eingabe) {
            $this->absendenMit($client, $eingabe);

            self::assertResponseStatusCodeSame(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                "BF-119: {$eingabe} muss am Formular scheitern, nicht beim Mailversand.",
            );
        }

        self::assertSame(0, $this->repo($client)->count([]));
    }

    /**
     * BF-120 — Die Plattform darf nicht nach Brevo.
     *
     * ⚠ Geprüft wird die tatsächliche Zeile, nicht der Aufruf, der sie schreibt
     * — und gegen BEIDE Schreibweisen, denn der Fehler stand als „iOS" da,
     * während die erste Sonde auf „ios" prüfte.
     */
    public function testBf120PlattformGehtNichtNachBrevo(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $entry = $this->eintrag($client, 'bf120@example.lu');
        $entry->setMarketingConsentAt(new \DateTimeImmutable());
        $em->flush();

        $client->request('GET', self::LOCALE.'/app/confirmation/'.$entry->getConfirmationToken());

        $kontakt = $client->getContainer()->get(MarketingContactRepository::class)
            ->findOneByEmail('bf120@example.lu');
        self::assertNotNull($kontakt, 'Vorbedingung: der Kontakt entsteht');

        self::assertSame('', (string) $kontakt->getOrganisationName(), 'BF-120');
        self::assertSame('', (string) $kontakt->getContactName());

        $roh = strtolower(json_encode(
            $em->getConnection()->fetchAssociative(
                'SELECT * FROM marketing_contact WHERE email = :e',
                ['e' => 'bf120@example.lu'],
            ),
            \JSON_THROW_ON_ERROR,
        ));
        self::assertStringNotContainsString('ios', $roh, 'BF-120: keine Plattform in der Zeile');
        self::assertStringNotContainsString('android', $roh);
    }

    /**
     * BF-121 — Das Wettrennen muss abgefangen sein.
     *
     * Nachgebildet, indem die konkurrierende Zeile per rohem SQL entsteht,
     * nachdem der Controller sein Formular ausgeliefert hat. Der einprozessige
     * Testclient kann echte Gleichzeitigkeit nicht erzeugen; entscheidend ist,
     * dass die Ausnahme aus dem Unique-Index eine saubere Antwort ergibt.
     */
    public function testBf121WettlaufErgibtDieErfolgsantwort(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $crawler = $client->request('GET', self::PFAD);
        $form = $this->formWithField($crawler, 'app_waitlist[email]');
        $form['app_waitlist[email]'] = 'bf121@example.lu';
        $form['app_waitlist[platform]']->select('ios');
        $form['app_waitlist[consent]']->tick();

        // Die schnellere Anfrage hat ihre Zeile bereits geschrieben. Der
        // Identity Map dieses Vorgangs kennt sie nicht.
        $em->getConnection()->executeStatement(
            "INSERT INTO app_waitlist_entry (email, platform, status, consent_at, locale, created_at, updated_at)
             VALUES ('bf121@example.lu', 'ios', 'pending', NOW(), 'de', NOW(), NOW())"
        );

        $client->submit($form);

        // BF-121: Ein Wettlauf endet in derselben Erfolgsantwort, nicht in einem 500er.
        self::assertResponseRedirects(self::PFAD);
        self::assertSame(1, $this->repo($client)->count([]));
    }

    /**
     * BF-120, zweite Ebene — der **tatsächliche Brevo-Rumpf**, nicht die Zeile
     * davor.
     *
     * Der erste QA-Durchlauf prüfte nur `marketing_contact`. Zwischen Tabelle
     * und Versand liegt aber `MarketingPayloadMapper`, und dessen Negativliste
     * ist der eigentliche Vertrag mit dem Drittdienst.
     */
    public function testBf120PlattformStehtAuchNichtImBrevoRumpf(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $entry = $this->eintrag($client, 'rumpf@example.lu');
        $entry->setMarketingConsentAt(new \DateTimeImmutable());
        $em->flush();

        $client->request('GET', self::LOCALE.'/app/confirmation/'.$entry->getConfirmationToken());

        $kontakt = $client->getContainer()->get(MarketingContactRepository::class)
            ->findOneByEmail('rumpf@example.lu');
        self::assertNotNull($kontakt);

        $rumpf = $client->getContainer()->get(\App\Marketing\MarketingPayloadMapper::class)
            ->toBrevoPayload($kontakt);
        $roh = strtolower(json_encode($rumpf, \JSON_THROW_ON_ERROR));

        self::assertStringNotContainsString('ios', $roh, 'AK-54: die Plattform geht nicht mit');
        self::assertStringNotContainsString('android', $roh);
        self::assertSame('', $rumpf['attributes']['ORGANISATION'] ?? null);
        self::assertStringNotContainsString(
            (string) $entry->getConfirmationToken(),
            $roh,
            'Der Token gehört unter keinen Umständen zu einem Drittdienst.',
        );
    }

    // ---------------------------------------------------------------- Hilfen

    private function repo(KernelBrowser $c): AppWaitlistEntryRepository
    {
        return $c->getContainer()->get(AppWaitlistEntryRepository::class);
    }

    private function absenden(KernelBrowser $client, string $email): void
    {
        $this->absendenMit($client, $email);
    }

    private function absendenMit(KernelBrowser $client, string $email): void
    {
        $crawler = $client->request('GET', self::PFAD);
        $form = $this->formWithField($crawler, 'app_waitlist[email]');
        $form['app_waitlist[email]'] = $email;
        $form['app_waitlist[platform]']->select('ios');
        $form['app_waitlist[consent]']->tick();
        $client->submit($form);
    }

    private function eintrag(KernelBrowser $client, string $email): AppWaitlistEntry
    {
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $e = new AppWaitlistEntry();
        $e->setEmail($email);
        $e->setPlatform(AppPlatform::IOS);
        $e->setLocale('de');
        $e->generateConfirmationToken();
        $em->persist($e);
        $em->flush();

        return $e;
    }

    private function altern(KernelBrowser $client, AppWaitlistEntry $e, string $v): void
    {
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $em->getConnection()->executeStatement(
            'UPDATE app_waitlist_entry SET created_at = :d WHERE id = :id',
            ['d' => (new \DateTimeImmutable($v))->format('Y-m-d H:i:s'), 'id' => $e->getId()],
        );
        $em->refresh($e);
    }
}
