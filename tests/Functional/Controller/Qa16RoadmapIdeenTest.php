<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Board\BoardModerator;
use App\Entity\BoardIdea;
use App\Entity\User;
use App\Enum\BoardIdeaStatus;
use App\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Feature 16 · Prüfläufe aus der QA vom 2026-11-07 (`features/16-roadmap-und-ideen/qa-report.md`).
 *
 * Ergänzt die Prüfläufe des Baus dort, wo die QA eine Kehrseite oder einen Randfall geprüft hat,
 * den der Bau nicht abdeckt. Die Reproduktionen von BF-199 und BF-201 sind übersprungen, solange die
 * Befunde offen sind (BF-201 hängt zudem an einer Entscheidung zu OF-01); scharf mit `QA16_REPRO=1`.
 */
final class Qa16RoadmapIdeenTest extends AbstractWebTestCase
{
    use MailerAssertionsTrait;

    private const string KENNUNG = '00000000-0000-4000-8000-000000000011';

    /** @var list<array{string, string, array<mixed>}> */
    private array $gesendet = [];

    protected function tearDown(): void
    {
        $_ENV['APP_UMAMI_UPSTREAM'] = $_SERVER['APP_UMAMI_UPSTREAM'] = '';
        Request::setTrustedProxies([], Request::getTrustedHeaderSet());
        parent::tearDown();
    }

    private static function offen(string $befund): void
    {
        if ('1' !== getenv('QA16_REPRO')) {
            self::markTestSkipped($befund . ' offen — Reproduktion, siehe features/16-roadmap-und-ideen/qa-report.md. Scharf mit QA16_REPRO=1.');
        }
    }

    private function idee(KernelBrowser $client, string $titel, string $slug, ?User $verfasser, bool $veroeffentlicht): BoardIdea
    {
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $idee = (new BoardIdea())
            ->setTitle($titel)
            ->setDescription('Beschreibung QA16 ' . $titel)
            ->setSlug($slug)
            ->setLocale('de')
            ->setStatus(BoardIdeaStatus::NEW)
            ->setSubmittedBy($verfasser);

        if ($veroeffentlicht) {
            $idee->setPublishedAt(new \DateTimeImmutable('-1 day'));
        }

        $em->persist($idee);
        $em->flush();

        return $idee;
    }

    /**
     * AK-31: Der Zählaufruf des Reiters Ideen kommt bei der Nutzungsmessung mit dem neuen Pfad an,
     * ohne Abfrageteil — und mit genau denselben Feldern wie ein Aufruf der alten Adresse.
     *
     * Gemessen wird, was Umami tatsächlich bekommt (der nachgebildete Eingang), nicht der Quelltext,
     * der den Rumpf baut.
     */
    public function testAk31ZaehlaufrufNeuerPfadOhneAbfrageteilUndOhneNeuesFeld(): void
    {
        $_ENV['APP_UMAMI_UPSTREAM'] = $_SERVER['APP_UMAMI_UPSTREAM'] = 'https://umami.test';
        $client = static::createClient();
        $client->disableReboot();

        /** @var MockHttpClient $umami */
        $umami = static::getContainer()->get('app.usage.umami_client');
        $umami->setResponseFactory(function (string $methode, string $url, array $optionen): MockResponse {
            $this->gesendet[] = [$methode, $url, $optionen];

            return new MockResponse('{"cache":"abc.def"}', ['http_code' => 200]);
        });

        foreach (['/de/community/ideen', '/de/roadmap/ideen'] as $pfad) {
            $client->request('POST', '/api/send', server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh)',
                'REMOTE_ADDR' => '198.51.100.16',
            ], content: (string) json_encode(['type' => 'event', 'payload' => [
                'website' => self::KENNUNG,
                'hostname' => 'endlech.lu',
                'url' => 'https://endlech.lu' . $pfad . '?sort=newest&status=planned&page=2',
                'referrer' => 'https://endlech.lu/de/roadmap?utm_source=x',
                'title' => 'Ideen für Endlech.lu',
                'screen' => '1280x800',
                'language' => 'de-DE',
            ]]));
            self::assertResponseStatusCodeSame(200, $pfad);
        }

        self::assertCount(2, $this->gesendet);
        $alt = json_decode((string) $this->gesendet[0][2]['body'], true)['payload'];
        $neu = json_decode((string) $this->gesendet[1][2]['body'], true)['payload'];

        self::assertSame('/de/roadmap/ideen', $neu['url'], 'AK-31: neuer Pfad, ohne Abfrageteil.');
        self::assertStringNotContainsString('?', (string) $neu['url']);
        self::assertStringNotContainsString('sort', (string) json_encode($neu));
        self::assertSame(array_keys($alt), array_keys($neu), 'AK-31: kein Feld, das der Aufruf vor der Umstellung nicht enthielt.');
    }

    /**
     * AK-21: Die Sprache der alten Adresse gewinnt gegen die Sprache der Sitzung.
     * Wer zuletzt Französisch gewählt hat und einen deutschen Mail-Link öffnet, landet auf Deutsch.
     */
    public function testAk21AlteAdresseBehaeltIhreSpracheGegenDieSitzung(): void
    {
        $client = static::createClient();
        $client->request('GET', '/fr/roadmap');
        self::assertResponseIsSuccessful();

        foreach (['/de/community/ideen' => '/de/roadmap/ideen', '/de/community/ideen/7-titel' => '/de/roadmap/ideen/7-titel', '/pt/community/ideen/neu' => '/pt/roadmap/ideen/neu'] as $alt => $neu) {
            $client->request('GET', $alt);
            self::assertResponseStatusCodeSame(301, $alt);
            self::assertSame($neu, $client->getResponse()->headers->get('Location'), $alt);
        }
    }

    /**
     * AK-24, Pfadbestandteil: Steuerzeichen und Rückstriche im Titelteil landen kodiert im Ziel —
     * kein Kopfzeilenbruch, kein protokollrelativer Verweis.
     *
     * @return iterable<string, array{string}>
     */
    public static function boeseTitelteile(): iterable
    {
        yield 'CRLF' => ['94-%0d%0aSet-Cookie:x=1'];
        yield 'Rückstriche' => ['94-%5C%5Cexample.com'];
        yield 'Unicode-Umkehr' => ['94-%E2%80%AE'];
        yield 'Punkt-Punkt' => ['94-..'];
    }

    #[DataProvider('boeseTitelteile')]
    public function testAk24TitelteilBleibtImZielKodiert(string $teil): void
    {
        $client = static::createClient();
        $client->request('GET', '/de/community/ideen/' . $teil);

        self::assertResponseStatusCodeSame(301);
        $ziel = (string) $client->getResponse()->headers->get('Location');
        self::assertStringStartsWith('/de/roadmap/ideen/94-', $ziel);
        self::assertDoesNotMatchRegularExpression('/[\r\n\\\\]/', $ziel, 'kein Steuerzeichen, kein Rückstrich im Ziel');
        // Zweite QA: Vorher stand hier `headers->has('x')` — die eingeschleuste Kopfzeile hieße aber `Set-Cookie`,
        // die Zusicherung konnte nie anschlagen (Hinweis des code-reviewer). Geprüft wird jetzt deren Wert.
        self::assertStringNotContainsString('x=1', implode("\n", $client->getResponse()->headers->all('set-cookie')), 'keine eingeschleuste Kopfzeile');
    }

    /**
     * AK-29 mit Parametern und in allen Sprachen: Der Titel einer wartenden Idee erscheint weder auf
     * den Reitern noch hinter den alten Adressen — als Gast und als fremdes Konto.
     */
    public function testAk29WartendeIdeeAuchMitParameternUndFremdemKontoUnsichtbar(): void
    {
        $client = static::createClient();
        $verfasser = $this->user($client, 'user@endlech.lu');
        $wartend = $this->idee($client, 'QA16 Zebrafink wartet geheim', 'qa16-zebrafink-wartet-geheim', $verfasser, false);
        $pfade = ['/roadmap', '/changelog', '/roadmap/ideen', '/community/ideen', sprintf('/community/ideen/%d-%s', $wartend->getId(), $wartend->getSlug()), sprintf('/roadmap/ideen/%d-%s', $wartend->getId(), $wartend->getSlug())];
        $parameter = ['', '?status=new', '?sort=newest&page=1', '?status=new&sort=newest'];

        foreach ([null, 'admin@endlech.lu', 'unverified@endlech.lu'] as $konto) {
            if (null !== $konto) {
                $this->loginAs($client, $konto);
            }
            foreach (['lb', 'de', 'fr', 'en', 'pt'] as $sprache) {
                foreach ($pfade as $pfad) {
                    foreach ($parameter as $abfrage) {
                        $client->request('GET', '/' . $sprache . $pfad . $abfrage);
                        if ($client->getResponse()->isRedirect()) {
                            $client->followRedirect();
                        }
                        self::assertStringNotContainsString('Zebrafink', (string) $client->getResponse()->getContent(), sprintf('[%s] /%s%s%s', $konto ?? 'Gast', $sprache, $pfad, $abfrage));
                    }
                }
            }
        }
    }

    /**
     * EC-01: Ohne freigegebene Idee zeigt der Reiter Ideen den Leerzustand mit Weg zum Formular und
     * zusätzlich den Knopf im Kopf; der Reiter Roadmap zeigt keine leere Community-Gruppe (07/EC-01).
     */
    public function testEc01OhneFreigegebeneIdee(): void
    {
        $client = static::createClient();
        $veroeffentlicht = (int) $client->getContainer()->get(EntityManagerInterface::class)
            ->createQuery('SELECT COUNT(i.id) FROM App\Entity\BoardIdea i WHERE i.publishedAt IS NOT NULL')
            ->getSingleScalarResult();
        self::assertSame(0, $veroeffentlicht, 'Voraussetzung: Die Test-Datenbank führt keine freigegebene Idee.');

        $crawler = $client->request('GET', '/de/roadmap/ideen');
        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('[data-area-header] a[href="/de/roadmap/ideen/neu"]'), 'Knopf im Kopf');
        self::assertGreaterThanOrEqual(1, $crawler->filter('main a[href="/de/roadmap/ideen/neu"]')->reduce(
            static fn ($a) => null === $a->closest('[data-area-header]'),
        )->count(), 'Weg zum Formular im Leerzustand, außerhalb des Kopfs');

        $client->request('GET', '/de/roadmap');
        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('Aus den Ideen der Community', (string) $client->getResponse()->getContent());
    }

    /**
     * BF-199 (Reproduktion): Ein Zustimmen mit falschem Token auf eine **wartende fremde** Idee
     * leitet auf deren Einzeladresse weiter — der `Location`-Kopf trägt den Titelteil (aus dem Titel
     * gebildet). Jedes angemeldete Konto, auch ein unbestätigtes, liest so die Titel der
     * Moderationsschlange mit; der Deckel greift nicht, weil die CSRF-Prüfung vorher zurückkehrt.
     * Erwartet wie beim gültigen Token: 404, ohne Titelteil.
     */
    public function testBf199ZustimmenVerraetWartendeIdeeNicht(): void
    {
        // Scharf seit der Behebung am 2026-11-07 (Fehlerauftrag 16) — Rückfallschutz.

        $client = static::createClient();
        $verfasser = $this->user($client, 'user@endlech.lu');
        $wartend = $this->idee($client, 'QA16 Geheimer Titel in der Warteschlange', 'qa16-geheimer-titel-in-der-warteschlange', $verfasser, false);

        foreach (['unverified@endlech.lu', 'admin@endlech.lu'] as $konto) {
            $this->loginAs($client, $konto);
            foreach (['', 'falsch'] as $token) {
                $client->request('POST', sprintf('/de/roadmap/ideen/%d/zustimmen', $wartend->getId()), ['_token' => $token]);
                $ort = (string) $client->getResponse()->headers->get('Location');
                self::assertStringNotContainsString('geheimer-titel', $ort, sprintf('[%s, Token „%s“] Location verrät den Titelteil: %s', $konto, $token, $ort));
                self::assertResponseStatusCodeSame(404, sprintf('[%s, Token „%s“]', $konto, $token));
            }
        }
    }

    /**
     * BF-201 (Reproduktion): Nach OF-01 heißt der Ort „Ideen“, nicht „Board“. Drei Texte, die auf den
     * Ort verweisen, sagen es weiter: der Leerzustand des Reiters Ideen, die Bestätigungsseite nach dem
     * Einreichen und die Mail nach der Freigabe.
     */
    public function testBf201OrtHeisstAuchInLeerzustandBestaetigungUndMailIdeen(): void
    {
        // Scharf seit der Behebung am 2026-11-07 (Fehlerauftrag 16) — Rückfallschutz.

        $client = static::createClient();
        $client->request('GET', '/de/roadmap/ideen');
        self::assertStringNotContainsString('Board', (string) $client->getResponse()->getContent(), 'Leerzustand');

        $verfasser = $this->loginAs($client, 'user@endlech.lu');
        $client->request('GET', '/de/roadmap/ideen/eingereicht');
        self::assertStringNotContainsString('Board', (string) $client->getResponse()->getContent(), 'Bestätigungsseite');

        // Nach den Anfragen hat der Client den Kernel neu gestartet — das Konto frisch laden, sonst
        // gehört es nicht zum aktuellen Entity-Manager (beim Scharfschalten am 2026-11-07 gefunden).
        $verfasser = $this->user($client, 'user@endlech.lu');
        $idee = $this->idee($client, 'QA16 Mailtext', 'qa16-mailtext', $verfasser, false);
        $client->getContainer()->get(BoardModerator::class)->publish($idee);
        $mail = self::getMailerMessage();
        self::assertNotNull($mail);
        self::assertStringNotContainsString('Board', (string) $mail->getSubject() . $mail->getHtmlBody() . $mail->getTextBody(), 'Mail nach der Freigabe');
    }
}
