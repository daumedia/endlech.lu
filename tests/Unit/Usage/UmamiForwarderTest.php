<?php

namespace App\Tests\Unit\Usage;

use App\Usage\UmamiForwarder;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;
use Symfony\Component\Yaml\Yaml;

/** Feature 11 · Weiterleitung an Umami — Kopfzeilen, Unterbrecher, ein Platz, Protokoll (AK-01, AK-04, AK-30, AK-37, BF-149). */
final class UmamiForwarderTest extends TestCase
{
    /** Steht nur hier im Test; eine echte Adresse gehört in kein Repository. */
    private const string UPSTREAM = 'https://203.0.113.77:8443';
    private const string BESUCHER_IP = '198.51.100.23';

    /** @var list<array{string, array<mixed>}> */
    private array $protokoll = [];

    private function protokollierer(): AbstractLogger
    {
        return new class($this->protokoll) extends AbstractLogger {
            /** @param list<array{string, array<mixed>}> $protokoll */
            public function __construct(private array &$protokoll)
            {
            }

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->protokoll[] = [(string) $message, $context];
            }
        };
    }

    private function weiterleitung(MockHttpClient $client, ?ArrayAdapter $cache = null, string $upstream = self::UPSTREAM, string $pin = 'sha256//QUJDREVG', ?LockFactory $sperren = null): UmamiForwarder
    {
        return new UmamiForwarder($client, $cache ?? new ArrayAdapter(), $this->protokollierer(), $sperren ?? new LockFactory(new InMemoryStore()), $upstream, $pin);
    }

    /** @return array{type: string, payload: array<string, mixed>} */
    private static function aufruf(): array
    {
        return ['type' => 'event', 'payload' => ['website' => 'w', 'hostname' => 'endlech.lu', 'url' => '/de/restaurants']];
    }

    public function testSendetGenauDieErlaubtenKopfzeilenUndDenRumpf(): void
    {
        $gesendet = [];
        $client = new MockHttpClient(static function (string $methode, string $url, array $optionen) use (&$gesendet): MockResponse {
            $gesendet = ['methode' => $methode, 'url' => $url, 'optionen' => $optionen];

            return new MockResponse('{"cache":"abc.def"}', ['http_code' => 200]);
        });

        $ergebnis = $this->weiterleitung($client)->forward(self::aufruf(), self::BESUCHER_IP, 'Mozilla/5.0', 'tok.en');

        self::assertSame('POST', $gesendet['methode']);
        self::assertSame(self::UPSTREAM.'/api/send', $gesendet['url']);
        self::assertSame(self::aufruf(), json_decode($gesendet['optionen']['body'], true));

        $namen = array_map(
            static fn (string $zeile): string => strtolower(explode(':', $zeile, 2)[0]),
            $gesendet['optionen']['headers'],
        );
        sort($namen);
        // `accept` und `content-length` ergänzt der HTTP-Client selbst; alles andere muss von hier kommen.
        self::assertSame(['accept', 'content-length', 'content-type', 'user-agent', 'x-endlech-client-ip', 'x-umami-cache'], $namen);
        self::assertContains('X-Endlech-Client-Ip: '.self::BESUCHER_IP, $gesendet['optionen']['headers']);

        self::assertSame(UmamiForwarder::TIMEOUT, (int) $gesendet['optionen']['timeout']);
        self::assertSame(UmamiForwarder::MAX_DURATION, (int) $gesendet['optionen']['max_duration']);
        self::assertSame(['pin-sha256' => ['QUJDREVG']], $gesendet['optionen']['peer_fingerprint']);

        self::assertSame(200, $ergebnis->status);
        self::assertSame('{"cache":"abc.def"}', $ergebnis->body);
    }

    /**
     * AK-04 · Die Weiterleitung übernimmt nichts aus der eingehenden Anfrage außer den übergebenen
     * Werten — eine unterschobene Kopfzeile kann gar nicht ankommen, weil es keinen Weg für sie gibt.
     * Geprüft wird das Ergebnis: kein Cookie, keine Sprache, keine Weiterleitungs- oder Orts-Kopfzeile.
     */
    public function testKeineKopfzeileAusDerAnfrageUndKeineUngueltigenWerte(): void
    {
        $gesendet = [];
        $client = new MockHttpClient(static function (string $m, string $u, array $optionen) use (&$gesendet): MockResponse {
            $gesendet = $optionen['headers'];

            return new MockResponse('{}');
        });

        $this->weiterleitung($client)->forward(self::aufruf(), 'keine-ip, 1.2.3.4', null, "tok\nX-Evil: 1");

        $alles = strtolower(implode("\n", $gesendet));
        foreach (['cookie', 'accept-language', 'x-forwarded-for', 'cf-', 'x-evil', 'x-endlech-client-ip', 'x-umami-cache'] as $verboten) {
            self::assertStringNotContainsString($verboten, $alles);
        }
    }

    /** AK-37 · Nach einem Fehlschlag 60 s lang keine Weiterleitung — und danach wieder. */
    public function testUnterbrecherGreiftNachFehlschlagUndLoestSich(): void
    {
        $aufrufe = 0;
        $client = new MockHttpClient(static function () use (&$aufrufe): MockResponse {
            ++$aufrufe;

            return 1 === $aufrufe
                ? new MockResponse('', ['error' => 'Couldn\'t connect to server '.self::UPSTREAM])
                : new MockResponse('{}');
        });
        $cache = new ArrayAdapter();
        $weiterleitung = $this->weiterleitung($client, $cache);

        self::assertSame(202, $weiterleitung->forward(self::aufruf(), null, null, null)->status);
        self::assertSame(202, $weiterleitung->forward(self::aufruf(), null, null, null)->status);
        self::assertSame(1, $aufrufe, 'Während des Unterbrechers darf nichts gesendet werden.');

        $cache->clear();
        self::assertSame(200, $weiterleitung->forward(self::aufruf(), null, null, null)->status);
        self::assertSame(2, $aufrufe);
    }

    /**
     * BF-149 · Ist der Platz belegt — ein anderer Zählaufruf wartet gerade auf Umami —, wird nichts
     * gesendet, und gewartet wird nur kurz. Vorher wartete jede gleichzeitige Anfrage selbst bis zum
     * Zeitlimit: sechs Zählaufrufe je 2,1–3,7 s, die Restaurantliste daneben 1,8 s.
     */
    public function testBelegterPlatzSendetNichtUndWartetNurKurz(): void
    {
        $aufrufe = 0;
        $client = new MockHttpClient(static function () use (&$aufrufe): MockResponse {
            ++$aufrufe;

            return new MockResponse('{}');
        });
        $sperren = new LockFactory(new InMemoryStore());
        $haenger = $sperren->createLock('umami-weiterleitung');
        self::assertTrue($haenger->acquire(), 'Vorbedingung: Der hängende Aufruf hält den Platz.');

        $start = microtime(true);
        $ergebnis = $this->weiterleitung($client, sperren: $sperren)->forward(self::aufruf(), null, null, null);
        $dauer = microtime(true) - $start;

        self::assertSame(202, $ergebnis->status);
        self::assertSame(0, $aufrufe);
        self::assertGreaterThanOrEqual(UmamiForwarder::PLATZ_WARTEN_MS / 1000, $dauer, 'Ein kurzer Aufruf davor bekommt seine Chance.');
        self::assertLessThan(UmamiForwarder::TIMEOUT / 2, $dauer, 'Gewartet wird auf den Platz, nicht auf Umami.');

        $haenger->release();
        self::assertSame(200, $this->weiterleitung($client, sperren: $sperren)->forward(self::aufruf(), null, null, null)->status);
        self::assertSame(1, $aufrufe, 'Ist der Platz frei, wird wieder gesendet.');
    }

    /**
     * BF-149 · Der Platz wird auch nach einem Fehlschlag wieder frei — sonst zählte nach dem Unterbrecher nichts mehr.
     *
     * ⚠ Gegenprobe ohne `release()` bleibt grün: Die Sperre gibt sich beim Verlassen der Methode selbst
     * frei (`autoRelease`). Geprüft ist die Eigenschaft, nicht die Zeile.
     */
    public function testPlatzWirdNachFehlschlagFrei(): void
    {
        $client = new MockHttpClient(static fn (): MockResponse => new MockResponse('', ['error' => 'Timeout was reached']));
        $sperren = new LockFactory(new InMemoryStore());

        $this->weiterleitung($client, sperren: $sperren)->forward(self::aufruf(), null, null, null);

        self::assertTrue($sperren->createLock('umami-weiterleitung')->acquire());
    }

    /**
     * BF-149 · Wer auf den Platz gewartet hat, während der Aufruf davor scheiterte, sendet nicht noch
     * einmal in denselben Ausfall. Nachgestellt mit einer Sperre, die beim Belegen den Unterbrecher setzt —
     * genau der Stand, den ein gerade gescheiterter Vorgänger hinterlässt.
     */
    public function testNachGescheitertemVorgaengerWirdNichtGesendet(): void
    {
        $aufrufe = 0;
        $client = new MockHttpClient(static function () use (&$aufrufe): MockResponse {
            ++$aufrufe;

            return new MockResponse('{}');
        });
        $cache = new ArrayAdapter();
        $sperren = new LockFactory(new class($cache) extends InMemoryStore {
            public function __construct(private readonly ArrayAdapter $cache)
            {
            }

            public function save(Key $key): void
            {
                parent::save($key);
                $this->cache->save($this->cache->getItem('umami_unterbrecher')->set(true));
            }
        });

        self::assertSame(202, $this->weiterleitung($client, $cache, sperren: $sperren)->forward(self::aufruf(), null, null, null)->status);
        self::assertSame(0, $aufrufe);
    }

    public function testServerfehlerSetztDenUnterbrecher(): void
    {
        $aufrufe = 0;
        $client = new MockHttpClient(static function () use (&$aufrufe): MockResponse {
            ++$aufrufe;

            return new MockResponse('kaputt', ['http_code' => 503]);
        });
        $weiterleitung = $this->weiterleitung($client);

        $weiterleitung->forward(self::aufruf(), null, null, null);
        $weiterleitung->forward(self::aufruf(), null, null, null);

        self::assertSame(1, $aufrufe);
    }

    /**
     * AK-30 · Das Protokoll enthält weder die Adresse des Zähl-Eingangs noch die Besucher-IP.
     *
     * ⚠ Gegenprobe beim Bau gefahren: mit `$fehler->getMessage()` statt `$fehler::class` im Kontext
     * wird dieser Lauf rot — die Transport-Ausnahme trägt die Adresse im Text.
     */
    public function testProtokollVerraetWederZielNochBesucher(): void
    {
        $client = new MockHttpClient(static fn (): MockResponse => throw new TransportException('Failed to connect to '.self::UPSTREAM.' from '.self::BESUCHER_IP));

        $this->weiterleitung($client)->forward(self::aufruf(), self::BESUCHER_IP, null, null);

        self::assertNotSame([], $this->protokoll, 'Vorbedingung: Der Fehlschlag wird protokolliert.');
        $text = print_r($this->protokoll, true);
        self::assertStringNotContainsString('203.0.113.77', (string) $text);
        self::assertStringNotContainsString(self::BESUCHER_IP, (string) $text);
        self::assertStringContainsString(TransportException::class, (string) $text);
    }

    public function testOhneZielOderSchluesselbindungWirdNichtsGesendet(): void
    {
        $aufrufe = 0;
        $client = new MockHttpClient(static function () use (&$aufrufe): MockResponse {
            ++$aufrufe;

            return new MockResponse('{}');
        });

        self::assertSame(202, $this->weiterleitung($client, null, '', 'sha256//QUJD')->forward(self::aufruf(), null, null, null)->status);
        self::assertSame(202, $this->weiterleitung($client, null, self::UPSTREAM, '')->forward(self::aufruf(), null, null, null)->status);
        self::assertSame(0, $aufrufe);
    }

    public function testUngueltigeAntwortWirdNichtDurchgereicht(): void
    {
        $client = new MockHttpClient(new MockResponse('<html>Anmeldung</html>', ['http_code' => 200]));

        self::assertSame('{}', $this->weiterleitung($client)->forward(self::aufruf(), null, null, null)->body);
    }

    /**
     * Der Dienst `app.usage.umami_client` ist der cURL-Client ohne Autokonfiguration — nur dieser
     * versteht `pin-sha256`, und nur ohne Autokonfiguration bekommt er keinen Logger.
     */
    public function testClientDienstIstCurlOhneAutokonfiguration(): void
    {
        $dienste = Yaml::parseFile(__DIR__.'/../../../config/services.yaml')['services'];

        self::assertSame(CurlHttpClient::class, $dienste['app.usage.umami_client']['class']);
        self::assertFalse($dienste['app.usage.umami_client']['autoconfigure']);
    }
}
