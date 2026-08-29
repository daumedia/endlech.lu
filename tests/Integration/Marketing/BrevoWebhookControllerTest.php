<?php

namespace App\Tests\Integration\Marketing;

use App\Controller\Marketing\BrevoWebhookController;
use App\Entity\PartnerWaitlistEntry;
use App\Marketing\MarketingContactRegistry;
use App\Repository\MarketingContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * Der Brevo-Webhook (Feature 04) — der einzige Weg von außen nach innen.
 *
 * ⚠ Der Controller wird hier **von Hand gebaut**, weil der Bearer-Token als
 * Container-Parameter aufgelöst wird und lokal wie in der Testumgebung leer
 * ist. Über den HTTP-Weg ließe sich nur der Abweisungsfall prüfen (der ist in
 * `qa-report.md` per curl belegt: 401 ohne und mit falschem Token); der
 * Erfolgsweg braucht ein gesetztes Geheimnis.
 */
final class BrevoWebhookControllerTest extends KernelTestCase
{
    private const TOKEN = 'qa-test-geheimnis';

    private EntityManagerInterface $em;
    private MarketingContactRegistry $registry;
    private MarketingContactRepository $contacts;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->registry = static::getContainer()->get(MarketingContactRegistry::class);
        $this->contacts = static::getContainer()->get(MarketingContactRepository::class);
    }

    private function controller(string $token = self::TOKEN): BrevoWebhookController
    {
        $controller = new BrevoWebhookController(
            $this->registry,
            $this->em,
            $token,
            static::getContainer()->get(RateLimiterFactoryInterface::class . ' $marketingWebhookLimiter'),
        );
        $controller->setContainer(static::getContainer());

        return $controller;
    }

    private function anfrage(array $rumpf, ?string $token = self::TOKEN): Request
    {
        $server = ['CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => '203.0.113.' . random_int(1, 254)];

        if (null !== $token) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        }

        return new Request([], [], [], [], [], $server, json_encode($rumpf));
    }

    private function eingetragenerKontakt(string $email): PartnerWaitlistEntry
    {
        $entry = new PartnerWaitlistEntry();
        $entry->setRestaurantName('Haus')
            ->setContactName('Kontakt')
            ->setEmail($email)
            ->setLocality('Esch-Uelzecht')
            ->setMarketingConsentAt(new \DateTimeImmutable());
        $entry->confirm();

        $this->em->persist($entry);
        $this->em->flush();

        $this->registry->recordWaitlistEntry($entry);
        $this->em->flush();

        return $entry;
    }

    /**
     * ⚠ AK-11: Die Abmeldung nimmt die Einwilligung zurück — an **beiden**
     * Stellen: als Sperre im Auftragsbuch und an der Quelle.
     */
    public function testAk11AbmeldungSperrtUndLoeschtDieEinwilligung(): void
    {
        $entry = $this->eingetragenerKontakt('abmelden@qa.lu');

        $antwort = $this->controller()->webhook($this->anfrage([
            'event' => 'unsubscribed',
            'email' => 'abmelden@qa.lu',
        ]));

        self::assertSame(Response::HTTP_OK, $antwort->getStatusCode());
        self::assertNotNull($this->contacts->findOneByEmail('abmelden@qa.lu')->getRevokedAt(), 'Die Sperre fehlt');
        self::assertNull($entry->getMarketingConsentAt(), 'Die Einwilligung steht noch an der Quelle');
    }

    /**
     * AK-12: Nach der Abmeldung trägt der nächste Lauf die Adresse nicht erneut ein.
     */
    public function testAk12NachAbmeldungKeineErneuteEintragung(): void
    {
        $this->eingetragenerKontakt('nicht-erneut@qa.lu');

        $this->controller()->webhook($this->anfrage(['event' => 'unsubscribed', 'email' => 'nicht-erneut@qa.lu']));

        self::assertNotContains(
            'nicht-erneut@qa.lu',
            array_map(static fn ($c): string => $c->getEmail(), $this->contacts->findOpenForSync(100)),
        );
    }

    /**
     * Bounce und Beschwerde wirken wie eine Abmeldung.
     */
    public function testHartBounceUndSpamSperrenEbenfalls(): void
    {
        foreach (['hardBounce' => 'bounce@qa.lu', 'spam' => 'spam@qa.lu', 'contactDeleted' => 'geloescht@qa.lu'] as $event => $email) {
            $this->eingetragenerKontakt($email);
            $this->controller()->webhook($this->anfrage(['event' => $event, 'email' => $email]));

            self::assertNotNull(
                $this->contacts->findOneByEmail($email)->getRevokedAt(),
                "Ereignis {$event} hat nicht gesperrt",
            );
        }
    }

    /**
     * ⚠ Der Webhook antwortet **immer 200**, auch bei unbekannter Adresse.
     *
     * Eine unterschiedliche Antwort verriete, ob eine Adresse im Bestand ist.
     */
    public function testUnbekannteAdresseVerraetNichts(): void
    {
        $bekannt = $this->controller()->webhook($this->anfrage(['event' => 'unsubscribed', 'email' => 'egal@qa.lu']));
        $unbekannt = $this->controller()->webhook($this->anfrage(['event' => 'unsubscribed', 'email' => 'gibt-es-nicht@qa.lu']));

        self::assertSame($bekannt->getStatusCode(), $unbekannt->getStatusCode());
        self::assertSame($bekannt->getContent(), $unbekannt->getContent());
    }

    /**
     * ⚠ **Der Rumpf ist kein Schreibzugang.** Aus ihm werden ausschließlich
     * Adresse und Ereignistyp gelesen; alles andere wird verworfen. Sonst wäre
     * ein kompromittiertes Brevo-Konto ein Schreibzugang in die eigene Datenbank.
     */
    public function testUntergeschobeneFelderWirkenNicht(): void
    {
        $entry = $this->eingetragenerKontakt('unterschieben@qa.lu');
        $kontakt = $this->contacts->findOneByEmail('unterschieben@qa.lu');
        $urspruenglicheHerkunft = $kontakt->getOrigin();

        $this->controller()->webhook($this->anfrage([
            'event' => 'unsubscribed',
            'email' => 'unterschieben@qa.lu',
            // Nichts davon darf ankommen:
            'origin' => 'account',
            'contact_name' => 'Fremdgesetzt',
            'organisation_name' => 'Fremdgesetzt',
            'sync_state' => 'synced',
            'attempts' => 999,
            'id' => 1,
        ]));

        self::assertSame($urspruenglicheHerkunft, $kontakt->getOrigin());
        self::assertNotSame('Fremdgesetzt', $kontakt->getContactName());
        self::assertNotSame(999, $kontakt->getAttempts());
        self::assertSame('Haus', $entry->getRestaurantName());
    }

    /**
     * Kaputte Rumpfformen dürfen keinen Serverfehler erzeugen.
     */
    public function testKaputteRuempfeErzeugenKeinenServerfehler(): void
    {
        foreach ([[], ['event' => 'unsubscribed'], ['email' => 'a@b.lu'], ['event' => 'unbekannt', 'email' => 'a@b.lu']] as $rumpf) {
            $antwort = $this->controller()->webhook($this->anfrage($rumpf));

            self::assertSame(Response::HTTP_OK, $antwort->getStatusCode());
        }

        $roh = new Request([], [], [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'REMOTE_ADDR' => '203.0.113.9',
            'HTTP_AUTHORIZATION' => 'Bearer ' . self::TOKEN,
        ], 'kein json');

        self::assertSame(Response::HTTP_OK, $this->controller()->webhook($roh)->getStatusCode());
    }

    /**
     * ⚠ Ein **leeres** Geheimnis lehnt jede Anfrage ab.
     *
     * „Still aus" heißt beim Empfangen: nichts kommt herein. Ein Endpunkt, der
     * bei fehlender Konfiguration alles durchließe, wäre offen.
     */
    public function testLeeresGeheimnisLehntAllesAb(): void
    {
        $antwort = $this->controller('')->webhook($this->anfrage(['event' => 'unsubscribed', 'email' => 'a@b.lu'], null));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $antwort->getStatusCode());
    }
}
