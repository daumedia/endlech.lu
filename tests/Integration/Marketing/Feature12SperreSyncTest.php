<?php

declare(strict_types=1);

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
 * Feature 12 — der Sperrauftrag an Brevo (AK-36, AK-46).
 *
 * Geprüft wird, was tatsächlich hinausgeht, und dass der Auftrag einen Fehlschlag überlebt.
 * Ohne Letzteres wäre ein einzelner 429 das Ende: Die Zeile ist gesperrt, und gesperrte Zeilen
 * holt der Lauf sonst nie wieder ab — der Kontakt bekäme weiter Werbung.
 */
final class Feature12SperreSyncTest extends KernelTestCase
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
    private function dienst(callable $handler): MarketingSyncService
    {
        return new MarketingSyncService(
            $this->contacts,
            new MarketingPayloadMapper('7'),
            new BrevoContactClient(new MockHttpClient($handler), new NullLogger(), 'test-key', '7'),
            $this->em,
            new NullLogger(),
            200,
            0,
        );
    }

    private function gesperrt(string $email, MarketingSyncState $state): MarketingContact
    {
        $kontakt = new MarketingContact();
        $kontakt->setEmail($email)
            ->setOrigin(MarketingOrigin::ACCOUNT)
            ->setLocale('de')
            ->setConsentAt(new \DateTimeImmutable('-2 hours'))
            ->setRevokedAt(new \DateTimeImmutable('-1 minute'))
            ->setSyncedAt(new \DateTimeImmutable('-1 hour'))
            ->setSyncState($state);

        $this->em->persist($kontakt);
        $this->em->flush();

        return $kontakt;
    }

    public function testAk36SperrauftragWirdTrotzSperreAbgeholt(): void
    {
        $auftrag = $this->gesperrt('f12-sperre-offen@qa.lu', MarketingSyncState::BLACKLIST_PENDING);
        $ruhend = $this->gesperrt('f12-sperre-ruht@qa.lu', MarketingSyncState::SYNCED);

        $offen = $this->contacts->findOpenForSync(500);

        self::assertContains($auftrag, $offen);
        self::assertNotContains($ruhend, $offen, 'Eine Sperre ohne Auftrag bleibt draußen (04/AK-12).');
    }

    public function testAk36LaufSperrtDenKontaktBeiBrevo(): void
    {
        $kontakt = $this->gesperrt('f12-sperre-lauf@qa.lu', MarketingSyncState::BLACKLIST_PENDING);
        $aufrufe = [];

        $ergebnis = $this->dienst(function (string $method, string $url, array $options) use (&$aufrufe): MockResponse {
            $aufrufe[] = [$method, $url, $options['body'] ?? ''];

            return new MockResponse('', ['http_code' => 204]);
        })->run();

        self::assertCount(1, $aufrufe);
        [$method, $url, $body] = $aufrufe[0];
        self::assertSame('PUT', $method);
        self::assertStringContainsString('/contacts/'.$kontakt->getId(), $url);
        self::assertStringContainsString('identifierType=ext_id', $url);
        self::assertSame(['emailBlacklisted' => true], json_decode((string) $body, true), 'Nur die Sperre, keine Attribute, keine Liste.');

        self::assertSame(MarketingSyncState::SYNCED, $kontakt->getSyncState());
        self::assertTrue($kontakt->isBlocked());
        self::assertSame(1, $ergebnis->synced);
    }

    public function testAk36FehlschlagBehaeltDenAuftrag(): void
    {
        $kontakt = $this->gesperrt('f12-sperre-429@qa.lu', MarketingSyncState::BLACKLIST_PENDING);

        $this->dienst(static fn () => new MockResponse('', ['http_code' => 429]))->run();

        self::assertSame(MarketingSyncState::BLACKLIST_PENDING, $kontakt->getSyncState());
        self::assertSame(1, $kontakt->getAttempts());
        self::assertContains($kontakt, $this->contacts->findOpenForSync(500), 'Der nächste Lauf muss es erneut versuchen (04/AK-19).');
    }

    public function testAk36UnbekannterKontaktBeiBrevoGiltAlsErledigt(): void
    {
        // 404: Den Kontakt gibt es dort nicht (mehr) — er bekommt also auch nichts.
        $kontakt = $this->gesperrt('f12-sperre-404@qa.lu', MarketingSyncState::BLACKLIST_PENDING);

        $this->dienst(static fn () => new MockResponse('', ['http_code' => 404]))->run();

        self::assertSame(MarketingSyncState::SYNCED, $kontakt->getSyncState());
    }

    /**
     * BF-181, Kehrseite: Der Sperrauftrag für eine Zeile, die es bei Brevo nie gab, legt dort nichts an (AK-46). Es geht
     * genau ein `PUT` mit der Sperre hinaus; ein `POST` (anlegen) folgt auf die 404 nicht — anders als beim Abgleich.
     */
    public function testBf181NieUebertragenerKontaktErzeugtNurDenSperraufruf(): void
    {
        $kontakt = $this->gesperrt('f12-sperre-nie@qa.lu', MarketingSyncState::BLACKLIST_PENDING);
        $kontakt->setSyncedAt(null);
        $this->em->flush();
        $aufrufe = [];

        $this->dienst(function (string $method, string $url, array $options) use (&$aufrufe): MockResponse {
            $aufrufe[] = [$method, $options['body'] ?? ''];

            return new MockResponse('', ['http_code' => 404]);
        })->run();

        self::assertSame([['PUT', '{"emailBlacklisted":true}']], $aufrufe, 'Kein Anlegen, keine Adresse, keine Attribute.');
        self::assertSame(MarketingSyncState::SYNCED, $kontakt->getSyncState());
    }

    /**
     * BF-178: Wer erneut abonniert, während der Lauf die Sperre überträgt, darf von dessen `synced` nicht überschrieben
     * werden. Die gleichzeitige Anfrage ist als das nachgebildet, was sie in der Datenbank hinterlässt.
     */
    public function testBf178WiedereinwilligungWaehrendDesLaufsBleibtOffen(): void
    {
        $kontakt = $this->gesperrt('f12-sperre-zurueck@qa.lu', MarketingSyncState::BLACKLIST_PENDING);
        $id = (int) $kontakt->getId();
        $verbindung = $this->em->getConnection();

        $this->dienst(static function () use ($verbindung, $id): MockResponse {
            $verbindung->executeStatement(
                "UPDATE marketing_contact SET consent_at = DATE_ADD(revoked_at, INTERVAL 1 SECOND), sync_state = 'pending' WHERE id = ?",
                [$id],
            );

            return new MockResponse('', ['http_code' => 204]);
        })->run();

        $this->em->clear();
        $nachher = $this->contacts->find($id);
        self::assertSame(MarketingSyncState::PENDING, $nachher?->getSyncState(), 'Sonst bliebe Brevo gesperrt, während das Profil „abonniert" sagt.');
        self::assertNull($nachher->getSyncedAt(), 'Sonst schickte der Mapper die Aufhebung nicht mit (BF-165).');
        self::assertFalse($nachher->isBlocked());
    }

    /**
     * Gegenprobe zu BF-178: Eine gewöhnliche Sperre (keine neue Einwilligung) bleibt nach dem Lauf übertragen.
     */
    public function testBf178GewoehnlicheSperreBleibtUebertragen(): void
    {
        $kontakt = $this->gesperrt('f12-sperre-bleibt@qa.lu', MarketingSyncState::BLACKLIST_PENDING);
        $id = (int) $kontakt->getId();

        $this->dienst(static fn () => new MockResponse('', ['http_code' => 204]))->run();

        $this->em->clear();
        self::assertSame(MarketingSyncState::SYNCED, $this->contacts->find($id)?->getSyncState());
    }
}
