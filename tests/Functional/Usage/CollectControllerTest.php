<?php

namespace App\Tests\Functional\Usage;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Feature 11 · `POST /api/send` über HTTP — mit nachgebildetem Umami (`MockHttpClient`).
 *
 * Der Zähl-Eingang wird für diese Prüfläufe über die Umgebung gesetzt; in `.env.test` bleibt er leer,
 * damit kein anderer Test je etwas weiterleitet.
 */
final class CollectControllerTest extends WebTestCase
{
    private const string KENNUNG = '00000000-0000-4000-8000-000000000011';

    /** @var list<array{string, string, array<mixed>}> */
    private array $gesendet = [];

    protected function tearDown(): void
    {
        foreach (['APP_UMAMI_UPSTREAM', 'APP_UMAMI_UPSTREAM_PIN'] as $name) {
            $_ENV[$name] = $_SERVER[$name] = '';
        }
        parent::tearDown();
    }

    private function client(bool $mitZaehlEingang = true): KernelBrowser
    {
        $_ENV['APP_UMAMI_UPSTREAM'] = $_SERVER['APP_UMAMI_UPSTREAM'] = $mitZaehlEingang ? 'https://umami.test:8443' : '';
        $_ENV['APP_UMAMI_UPSTREAM_PIN'] = $_SERVER['APP_UMAMI_UPSTREAM_PIN'] = 'sha256//QUJDREVG';

        $client = static::createClient();
        $client->disableReboot();

        /** @var MockHttpClient $umami */
        $umami = static::getContainer()->get('app.usage.umami_client');
        $umami->setResponseFactory(function (string $methode, string $url, array $optionen): MockResponse {
            $this->gesendet[] = [$methode, $url, $optionen];

            return new MockResponse('{"cache":"abc.def"}', ['http_code' => 200]);
        });

        return $client;
    }

    /**
     * @param array<string, mixed> $abweichung
     * @param array<string, string> $kopf
     */
    private function zaehle(KernelBrowser $client, array $abweichung = [], array $kopf = [], ?string $rumpf = null): void
    {
        $payload = [
            'website' => self::KENNUNG,
            'hostname' => 'endlech.lu',
            'url' => 'https://endlech.lu/de/restaurants?city=Esch',
            'referrer' => 'https://www.google.com/search?q=barrierefrei',
            'title' => 'Restaurants',
            'id' => 'konto-42',
            ...$abweichung,
        ];

        $client->request('POST', '/api/send', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh)',
            'REMOTE_ADDR' => '198.51.100.23',
            ...$kopf,
        ], content: $rumpf ?? json_encode(['type' => 'event', 'payload' => $payload]));
    }

    private function keinCookie(KernelBrowser $client): void
    {
        self::assertFalse($client->getResponse()->headers->has('Set-Cookie'), 'AK-20: Der Zählweg setzt nie ein Cookie.');
    }

    public function testGeprueftWeitergeleitetUndAntwortDurchgereicht(): void
    {
        $client = $this->client();
        $this->zaehle($client);

        self::assertResponseStatusCodeSame(200);
        self::assertSame('{"cache":"abc.def"}', $client->getResponse()->getContent());
        $this->keinCookie($client);

        self::assertCount(1, $this->gesendet);
        [$methode, $url, $optionen] = $this->gesendet[0];
        self::assertSame('POST', $methode);
        self::assertSame('https://umami.test:8443/api/send', $url);

        $rumpf = json_decode($optionen['body'], true);
        self::assertSame('/de/restaurants', $rumpf['payload']['url']);
        self::assertSame('https://www.google.com/', $rumpf['payload']['referrer']);
        self::assertArrayNotHasKey('id', $rumpf['payload']);
        self::assertContains('X-Endlech-Client-Ip: 198.51.100.23', $optionen['headers']);
    }

    /** @return iterable<string, array{array<string, string>}> */
    public static function widerspruch(): iterable
    {
        yield 'Do Not Track' => [['HTTP_DNT' => '1']];
        yield 'Global Privacy Control' => [['HTTP_SEC_GPC' => '1']];
    }

    /** AK-22 · Rückhalt: Mit DNT oder GPC wird nichts weitergeleitet. */
    #[\PHPUnit\Framework\Attributes\DataProvider('widerspruch')]
    public function testWiderspruchWirdNieWeitergeleitet(array $kopf): void
    {
        $client = $this->client();
        $this->zaehle($client, [], $kopf);

        self::assertResponseStatusCodeSame(204);
        self::assertSame([], $this->gesendet);
        $this->keinCookie($client);
    }

    public function testVerstossWirdAbgewiesenOhneGrund(): void
    {
        $client = $this->client();
        $this->zaehle($client, ['hostname' => 'www.endlech.lu']);

        self::assertResponseStatusCodeSame(400);
        self::assertSame('{}', $client->getResponse()->getContent());
        self::assertSame([], $this->gesendet);
    }

    public function testKaputtesJsonUndUebergroesserRumpf(): void
    {
        $client = $this->client();

        $this->zaehle($client, rumpf: '{kein json');
        self::assertResponseStatusCodeSame(400);

        $this->zaehle($client, ['title' => str_repeat('x', 9000)]);
        self::assertResponseStatusCodeSame(413);

        self::assertSame([], $this->gesendet);
    }

    /** Ohne eingerichteten Zähl-Eingang: 202, nichts gesendet — dieselbe Antwort wie bei Ausfall. */
    public function testOhneZaehlEingangNichtsWeitergeleitet(): void
    {
        $client = $this->client(false);
        $this->zaehle($client);

        self::assertResponseStatusCodeSame(202);
        self::assertSame('{}', $client->getResponse()->getContent());
        self::assertSame([], $this->gesendet);
    }

    /** @return iterable<string, array{string, string, int}> */
    public static function andereWege(): iterable
    {
        yield 'GET auf den Zählweg' => ['GET', '/api/send', 405];
        yield 'Umami-Anmeldung über den Zählweg' => ['GET', '/api/send/login', 404];
        yield 'Umami-Schnittstelle' => ['GET', '/api/websites', 404];
        yield 'Umami-Anmeldung' => ['POST', '/api/auth/login', 404];
        yield 'Umami-Oberfläche' => ['GET', '/login', 404];
    }

    /**
     * AK-25 · Über endlech.lu ist nur der Zählweg erreichbar. Umamis Pfade (`/login`,
     * `/api/websites`, `/api/auth/login`) gibt es hier nicht — die eigene Anmeldung liegt unter
     * einem Sprachpräfix.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('andereWege')]
    public function testNurDerZaehlwegFuehrtZuUmami(string $methode, string $pfad, int $status): void
    {
        $client = $this->client();
        $client->request($methode, $pfad);

        self::assertResponseStatusCodeSame($status);
        self::assertSame([], $this->gesendet);
    }
}
