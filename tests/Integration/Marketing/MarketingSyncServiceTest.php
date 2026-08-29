<?php

namespace App\Tests\Integration\Marketing;

use App\Entity\MarketingContact;
use App\Enum\MarketingOrigin;
use App\Enum\MarketingSyncState;
use App\Marketing\BrevoContactClient;
use App\Marketing\MarketingPayloadMapper;
use App\Marketing\MarketingSyncService;
use App\Repository\MarketingContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Der Sync-Lauf (Feature 04) — gegen einen abgefangenen HTTP-Client.
 *
 * Geprüft wird, was der Lauf **tatsächlich** an Brevo schickt und wie er sich
 * bei Ausfall verhält. Der Deckel je Lauf und der Mindestabstand werden
 * gemessen, nicht in der Konfiguration nachgelesen.
 */
final class MarketingSyncServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private MarketingContactRepository $contacts;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->contacts = static::getContainer()->get(MarketingContactRepository::class);
    }

    /**
     * @param callable(string,string,array):MockResponse $handler
     */
    private function dienst(callable $handler, int $batch = 200, int $delayMs = 0, string $key = 'test-key'): MarketingSyncService
    {
        return new MarketingSyncService(
            $this->contacts,
            new MarketingPayloadMapper('7'),
            new BrevoContactClient(new MockHttpClient($handler), new NullLogger(), $key, '7'),
            $this->em,
            new NullLogger(),
            $batch,
            $delayMs,
        );
    }

    private function auftrag(string $email, MarketingSyncState $state = MarketingSyncState::PENDING): MarketingContact
    {
        $kontakt = new MarketingContact();
        $kontakt->setEmail($email)
            ->setOrigin(MarketingOrigin::PARTNER)
            ->setLocale('de')
            ->setSyncState($state);

        $this->em->persist($kontakt);
        $this->em->flush();

        return $kontakt;
    }

    /**
     * AK-47: Ohne Schlüssel wird **nichts angefasst** — die Aufträge warten.
     */
    public function testAk47OhneSchluesselPassiertNichts(): void
    {
        $kontakt = $this->auftrag('ohne-schluessel@qa.lu');

        $ergebnis = $this->dienst(
            static fn () => throw new \LogicException('Es darf kein Aufruf stattfinden'),
            key: '',
        )->run();

        self::assertFalse($ergebnis->configured);
        self::assertSame(MarketingSyncState::PENDING, $kontakt->getSyncState(), 'Der Auftrag wurde verändert');
    }

    /**
     * AK-06: Ein Auftrag geht hinaus und wird als übertragen vermerkt.
     */
    public function testAk06AuftragWirdUebertragen(): void
    {
        $kontakt = $this->auftrag('uebertragen@qa.lu');

        $ergebnis = $this->dienst(static fn () => new MockResponse('', ['http_code' => 204]))->run();

        self::assertSame(1, $ergebnis->synced);
        self::assertSame(MarketingSyncState::SYNCED, $kontakt->getSyncState());
        self::assertNotNull($kontakt->getSyncedAt());
        self::assertNull($kontakt->getLastError());
    }

    /**
     * AK-19: Ein Fehlversuch wird vermerkt und beim nächsten Lauf erneut
     * aufgegriffen — ohne dass jemand ihn von Hand anstößt.
     */
    public function testAk19FehlversuchWirdErneutAufgegriffen(): void
    {
        $kontakt = $this->auftrag('fehler@qa.lu');

        $this->dienst(static fn () => new MockResponse('', ['http_code' => 429]))->run();

        self::assertSame(MarketingSyncState::FAILED, $kontakt->getSyncState());
        self::assertSame('HTTP 429', $kontakt->getLastError());
        self::assertSame(1, $kontakt->getAttempts());

        // Zweiter Lauf: derselbe Auftrag geht erneut hinaus.
        $ergebnis = $this->dienst(static fn () => new MockResponse('', ['http_code' => 204]))->run();

        self::assertSame(1, $ergebnis->synced);
        self::assertSame(MarketingSyncState::SYNCED, $kontakt->getSyncState());
    }

    /**
     * ⚠ AK-31: In `last_error` steht die Kurzform — **nie** die Antwort im
     * Wortlaut, in der Brevo die übermittelte Adresse zurückspiegelt.
     */
    public function testAk31FehlertextTraegtKeineAntwortUndKeineAdresse(): void
    {
        $kontakt = $this->auftrag('leck@qa.lu');

        $this->dienst(static fn () => new MockResponse(
            '{"code":"invalid_parameter","message":"Contact leck@qa.lu already exists with api-key sk-geheim"}',
            ['http_code' => 400],
        ))->run();

        $fehler = (string) $kontakt->getLastError();

        self::assertSame('HTTP 400', $fehler);
        self::assertStringNotContainsString('leck@qa.lu', $fehler);
        self::assertStringNotContainsString('sk-geheim', $fehler);
    }

    /**
     * AK-39: Der Deckel je Lauf greift — gemessen an der Zahl der Aufrufe.
     */
    public function testAk39DeckelJeLaufGreift(): void
    {
        for ($i = 0; $i < 5; ++$i) {
            $this->auftrag("deckel{$i}@qa.lu");
        }

        $aufrufe = 0;
        $ergebnis = $this->dienst(
            function () use (&$aufrufe): MockResponse {
                ++$aufrufe;

                return new MockResponse('', ['http_code' => 204]);
            },
            batch: 2,
        )->run();

        self::assertSame(2, $ergebnis->synced, 'Der Deckel wurde überschritten');
        self::assertSame(2, $aufrufe, 'Es gingen mehr Aufrufe hinaus als der Deckel erlaubt');
    }

    /**
     * AK-39: Der Mindestabstand zwischen zwei Aufrufen wird eingehalten.
     */
    public function testAk39MindestabstandWirdEingehalten(): void
    {
        for ($i = 0; $i < 3; ++$i) {
            $this->auftrag("abstand{$i}@qa.lu");
        }

        $start = microtime(true);
        $this->dienst(static fn () => new MockResponse('', ['http_code' => 204]), delayMs: 120)->run();
        $dauer = (microtime(true) - $start) * 1000;

        // Drei Kontakte, zwei Pausen à 120 ms = mindestens 240 ms.
        self::assertGreaterThanOrEqual(240, $dauer, sprintf('Der Lauf war mit %.0f ms zu schnell – der Abstand greift nicht', $dauer));
    }

    /**
     * AK-13/AK-16: Der Löschauftrag wird ausgeführt, die Zeile verschwindet.
     */
    public function testAk13LoeschauftragEntferntDieZeile(): void
    {
        $this->auftrag('loeschen@qa.lu', MarketingSyncState::REMOVAL_PENDING);

        $methoden = [];
        $ergebnis = $this->dienst(function (string $method) use (&$methoden): MockResponse {
            $methoden[] = $method;

            return new MockResponse('', ['http_code' => 204]);
        })->run();

        self::assertSame(1, $ergebnis->removed);
        self::assertSame(['DELETE'], $methoden);
        self::assertNull($this->contacts->findOneByEmail('loeschen@qa.lu'), 'Die Zeile steht noch im Auftragsbuch');
    }

    /**
     * ⚠ Scheitert die Löschung, bleibt der Auftrag stehen — er darf nicht
     * verloren gehen, sonst bliebe der Kontakt für immer bei Brevo.
     */
    public function testGescheiterteLoeschungBehaeltDenAuftrag(): void
    {
        $kontakt = $this->auftrag('loeschen-fehler@qa.lu', MarketingSyncState::REMOVAL_PENDING);

        $ergebnis = $this->dienst(static fn () => new MockResponse('', ['http_code' => 500]))->run();

        self::assertSame(1, $ergebnis->failed);
        self::assertSame(MarketingSyncState::REMOVAL_PENDING, $kontakt->getSyncState());
        self::assertNotNull($this->contacts->findOneByEmail('loeschen-fehler@qa.lu'));
    }

    /**
     * EC-04: Ist der Kontakt bei Brevo bereits weg (404), gilt die Löschung
     * als erledigt — nicht als Fehler.
     */
    public function testEc04BereitsGeloeschterKontaktGiltAlsErledigt(): void
    {
        $this->auftrag('schon-weg@qa.lu', MarketingSyncState::REMOVAL_PENDING);

        $ergebnis = $this->dienst(static fn () => new MockResponse('', ['http_code' => 404]))->run();

        self::assertSame(1, $ergebnis->removed);
        self::assertSame(0, $ergebnis->failed);
        self::assertNull($this->contacts->findOneByEmail('schon-weg@qa.lu'));
    }

    /**
     * ⚠ EC-02/AK-20: Der Upsert adressiert über `ext_id` und legt nur dann an,
     * wenn dort noch nichts steht. Sonst entstünde bei jeder Adressänderung
     * ein zweiter Kontakt.
     */
    public function testEc02UpsertGehtUeberExtIdUndLegtNurBeiBedarfAn(): void
    {
        $kontakt = $this->auftrag('upsert@qa.lu');
        $id = $kontakt->getId();

        $aufrufe = [];
        $this->dienst(function (string $method, string $url) use (&$aufrufe): MockResponse {
            $aufrufe[] = $method . ' ' . $url;

            // Erster Aufruf (PUT) findet den Kontakt nicht.
            return new MockResponse('', ['http_code' => 1 === \count($aufrufe) ? 404 : 201]);
        })->run();

        self::assertCount(2, $aufrufe);
        self::assertStringStartsWith('PUT https://api.brevo.com/v3/contacts/' . $id . '?identifierType=ext_id', $aufrufe[0]);
        self::assertSame('POST https://api.brevo.com/v3/contacts', $aufrufe[1]);

        // Beim nächsten Mal existiert er – dann bleibt es bei einem Aufruf.
        $kontakt->setSyncState(MarketingSyncState::PENDING);
        $this->em->flush();

        $zweite = [];
        $this->dienst(function (string $method, string $url) use (&$zweite): MockResponse {
            $zweite[] = $method;

            return new MockResponse('', ['http_code' => 204]);
        })->run();

        self::assertSame(['PUT'], $zweite, 'Ein bestehender Kontakt darf nicht erneut angelegt werden');
    }
}
