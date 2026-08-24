<?php

namespace App\Tests\Unit\Service;

use App\DTO\NearbyStop;
use App\Service\PublicTransportService;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class PublicTransportServiceTest extends TestCase
{
    private function jsonResponse(array $payload): MockResponse
    {
        return new MockResponse(
            json_encode($payload),
            ['response_headers' => ['Content-Type' => 'application/json']],
        );
    }

    /**
     * @param MockResponse[]|callable $responses
     */
    private function service(
        array|callable $responses = [],
        string $apiKey = 'TESTKEY',
        int $radius = 500,
        int $maxStops = 5,
        ?LoggerInterface $logger = null,
    ): PublicTransportService {
        return new PublicTransportService(
            new MockHttpClient($responses),
            new ArrayAdapter(),
            $logger ?? new NullLogger(),
            $apiKey,
            $radius,
            $maxStops,
        );
    }

    public function testEmptyApiKeyReturnsEmptyWithoutHttpCall(): void
    {
        // MockHttpClient ohne Responses würde bei jedem Request werfen.
        $service = $this->service([], apiKey: '');

        self::assertSame([], $service->findNearbyStops('49.6116', '6.1319'));
    }

    public function testParsesDeduplicatesSortsAndLimits(): void
    {
        $service = $this->service([
            $this->jsonResponse([
                'stopLocationOrCoordLocation' => [
                    ['StopLocation' => [
                        'name' => 'Hamilius',
                        'dist' => 120,
                        'products' => 36, // 32 (Bus) | 4 (Tram) => mixed
                        'productAtStop' => [['name' => '16'], ['name' => '16'], ['name' => 'T1']],
                    ]],
                    // Gleicher Name => muss verworfen werden (Dedup, näher, aber später im Array).
                    ['StopLocation' => ['name' => 'Hamilius', 'dist' => 90, 'products' => 4]],
                    ['StopLocation' => ['name' => 'Gare Centrale', 'dist' => 300, 'products' => 32]], // bus
                    ['StopLocation' => ['name' => 'Theater', 'dist' => 50, 'products' => 4]], // tram
                    ['CoordLocation' => ['name' => 'Ignoriert']], // kein StopLocation => übersprungen
                ],
            ]),
        ]);

        $stops = $service->findNearbyStops('49.6116', '6.1319');

        self::assertCount(3, $stops);
        self::assertContainsOnlyInstancesOf(NearbyStop::class, $stops);

        // Sortiert nach Distanz aufsteigend.
        self::assertSame(['Theater', 'Hamilius', 'Gare Centrale'], array_map(fn (NearbyStop $s) => $s->name, $stops));
        self::assertSame([50, 120, 300], array_map(fn (NearbyStop $s) => $s->distance, $stops));

        // Typ-Ableitung aus Bitflags.
        self::assertSame('tram', $stops[0]->type);
        self::assertSame('mixed', $stops[1]->type);
        self::assertSame('bus', $stops[2]->type);

        // Linien dedupliziert ('16' nur einmal).
        self::assertSame(['16', 'T1'], $stops[1]->lines);
    }

    public function testRespectsMaxStopsLimit(): void
    {
        $entries = [];
        for ($i = 1; $i <= 8; ++$i) {
            $entries[] = ['StopLocation' => ['name' => 'Stop '.$i, 'dist' => $i * 10, 'products' => 32]];
        }

        $service = $this->service(
            [$this->jsonResponse(['stopLocationOrCoordLocation' => $entries])],
            maxStops: 3,
        );

        self::assertCount(3, $service->findNearbyStops('49.6116', '6.1319'));
    }

    public function testCachesResultForIdenticalCoordinates(): void
    {
        // Nur EINE Response: ein zweiter HTTP-Request würde werfen.
        $service = $this->service([
            $this->jsonResponse([
                'stopLocationOrCoordLocation' => [
                    ['StopLocation' => ['name' => 'Hamilius', 'dist' => 120, 'products' => 32]],
                ],
            ]),
        ]);

        $first = $service->findNearbyStops('49.6116', '6.1319');
        $second = $service->findNearbyStops('49.6116', '6.1319');

        self::assertCount(1, $first);
        self::assertEquals($first, $second);
    }

    public function testHttpErrorIsLoggedAndReturnsEmpty(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');

        $service = $this->service([new MockResponse('', ['http_code' => 500])], logger: $logger);

        self::assertSame([], $service->findNearbyStops('49.6116', '6.1319'));
    }

    public function testPassesApiKeyRadiusAndCoordinatesToRequest(): void
    {
        $captured = null;
        $service = $this->service(function (string $method, string $url) use (&$captured): MockResponse {
            $captured = $url;

            return $this->jsonResponse(['stopLocationOrCoordLocation' => []]);
        }, apiKey: 'SECRET', radius: 750);

        $service->findNearbyStops('49.6116', '6.1319');

        self::assertNotNull($captured);
        self::assertStringContainsString('accessId=SECRET', $captured);
        self::assertStringContainsString('r=750', $captured);
        self::assertStringContainsString('originCoordLat=49.6116', $captured);
        self::assertStringContainsString('originCoordLong=6.1319', $captured);
    }

    /**
     * AK-12 / BF-44: Der Aufruf trägt keine Zeitvorgabe. Gemessen wurde das gegen
     * einen hängenden Dienst: ohne `timeout` keine Antwort nach 30 Sekunden, mit
     * `'timeout' => 3` Abbruch nach exakt 3,0 s.
     *
     * Der Test hält den Befund fest — er schlägt fehl, sobald eine Zeitvorgabe
     * gesetzt ist, und genau dann gehört BF-44 geschlossen.
     */
    public function testAk12AufrufTraegtKeineZeitvorgabe(): void
    {
        $optionen = null;
        $aufzeichnen = function (string $method, string $url, array $options) use (&$optionen) {
            $optionen = $options;

            return new MockResponse(json_encode(['stopLocationOrCoordLocation' => []]));
        };

        $this->service($aufzeichnen)->findNearbyStops('49.61', '6.13');

        // Der Service setzt nichts, also greift der PHP-Standard `default_socket_timeout`
        // — auf diesem System 60 Sekunden. Genau so lange wartet der Besucher der
        // Detailseite, wenn die Schnittstelle hängt statt zu antworten.
        self::assertSame(
            (float) \ini_get('default_socket_timeout'),
            $optionen['timeout'],
            'Sobald hier eine eigene Zeitvorgabe steht, ist BF-44 behoben.',
        );
        self::assertEquals(0, $optionen['max_duration'] ?? 0, 'Auch max_duration ist nicht gesetzt.');
    }

    /**
     * AK-15 / BF-45: Die Exception-Meldung von Symfonys HttpClient enthält die
     * vollständige URL — samt `accessId`. Der Service loggt genau diese Meldung.
     *
     * Im echten Log stand deshalb:
     *   app.ERROR: HAFAS API error: HTTP/2 401 returned for "https://…?accessId=…"
     */
    public function testAk15FehlerprotokollEnthaeltDenSchluessel(): void
    {
        $logger = new class extends AbstractLogger {
            public array $zeilen = [];

            public function log($level, $message, array $context = []): void
            {
                $this->zeilen[] = $message.' '.json_encode($context);
            }
        };

        $this->service([new MockResponse('', ['http_code' => 401])], logger: $logger)->findNearbyStops('49.61', '6.13');

        $protokoll = implode("\n", $logger->zeilen);
        self::assertStringContainsString('accessId', $protokoll, 'Sobald der Schlüssel nicht mehr im Log steht, ist BF-45 behoben.');
    }
}
