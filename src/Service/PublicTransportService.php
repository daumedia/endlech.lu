<?php

namespace App\Service;

use App\DTO\NearbyStop;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PublicTransportService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
        #[Autowire('%app.mobiliteit_api_key%')]
        private readonly string $apiKey,
        #[Autowire('%app.mobiliteit_radius%')]
        private readonly int $radius,
        #[Autowire('%app.mobiliteit_max_stops%')]
        private readonly int $maxStops,
    ) {
    }

    /**
     * @return NearbyStop[]
     */
    public function findNearbyStops(string $lat, string $lng): array
    {
        if ($this->apiKey === '') {
            return [];
        }

        $cacheKey = 'nearby_stops_'.md5(round((float) $lat, 4).'_'.round((float) $lng, 4));

        try {
            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($lat, $lng): array {
                $item->expiresAfter(86400);

                $response = $this->httpClient->request('GET', 'https://cdt.hafas.de/opendata/apiserver/location.nearbystops', [
                    // Ohne diese Zeilen greift der PHP-Standard `default_socket_timeout`
                    // — auf dem Messsystem 60 Sekunden. Genau so lange wartete der
                    // Besucher der Detailseite, wenn die Schnittstelle hängt statt zu
                    // antworten (QA B10, BF-44; gemessen: ohne Vorgabe nach 30 s keine
                    // Antwort, mit 3 s Abbruch nach 3,0 s). Der catch unten fängt den
                    // Ausfall, nicht die Verzögerung.
                    'timeout' => 3,
                    'max_duration' => 5,
                    'query' => [
                        'accessId' => $this->apiKey,
                        'originCoordLat' => $lat,
                        'originCoordLong' => $lng,
                        'r' => $this->radius,
                        'maxNo' => 20,
                        'format' => 'json',
                    ],
                ]);

                $data = $response->toArray();

                return $this->parseResponse($data);
            });
        } catch (\Throwable $e) {
            // NICHT $e->getMessage() durchreichen: Die Meldung von Symfonys HttpClient
            // enthält die vollständige URL, und die trägt den API-Schlüssel als
            // Query-Parameter `accessId` (so sieht HAFAS die Übergabe vor). Im Log
            // standen dadurch Zeilen wie
            //   app.ERROR: HAFAS API error: HTTP/2 401 returned for "…?accessId=…"
            // (QA B10, BF-45). Klasse und Code sagen für die Fehlersuche genug.
            $this->logger->error('HAFAS API error: {class} ({code})', [
                'class' => $e::class,
                'code' => $e->getCode(),
            ]);

            return [];
        }
    }

    /**
     * @return NearbyStop[]
     */
    private function parseResponse(array $data): array
    {
        $stopLocationOrCoordLocation = $data['stopLocationOrCoordLocation'] ?? [];

        $stops = [];
        $seen = [];

        foreach ($stopLocationOrCoordLocation as $entry) {
            $stop = $entry['StopLocation'] ?? null;
            if ($stop === null) {
                continue;
            }

            $name = $stop['name'] ?? '';
            if ($name === '' || isset($seen[$name])) {
                continue;
            }
            $seen[$name] = true;

            $distance = (int) ($stop['dist'] ?? 0);
            $products = (int) ($stop['products'] ?? 0);
            $lines = $this->extractLines($stop);
            $type = $this->determineType($products);

            $stops[] = new NearbyStop(
                name: $name,
                distance: $distance,
                lines: $lines,
                type: $type,
            );
        }

        usort($stops, static fn (NearbyStop $a, NearbyStop $b) => $a->distance <=> $b->distance);

        return array_slice($stops, 0, $this->maxStops);
    }

    /**
     * @return string[]
     */
    private function extractLines(array $stop): array
    {
        $lines = [];

        foreach ($stop['productAtStop'] ?? [] as $product) {
            $line = $product['name'] ?? '';
            if ($line !== '' && !in_array($line, $lines, true)) {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    private function determineType(int $products): string
    {
        $hasTram = ($products & 4) !== 0;
        $hasBus = ($products & 32) !== 0 || ($products & 64) !== 0;

        if ($hasTram && $hasBus) {
            return 'mixed';
        }
        if ($hasTram) {
            return 'tram';
        }

        return 'bus';
    }
}
