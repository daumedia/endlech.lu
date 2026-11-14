<?php

declare(strict_types=1);

namespace App\Tests\Unit\Saved;

use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Sentry\Event;
use Sentry\Integration\RequestFetcherInterface;
use Sentry\Integration\RequestIntegration;
use Sentry\Options;
use Symfony\Component\Yaml\Yaml;

/**
 * Feature 17 · QA vom 2026-11-14 (`features/17-favoriten-und-listen/qa-report.md`), AK-65.
 *
 * `SentryRequestBodyTest` aus dem Bau liest nur die Konfigurationszeile. Dieser Prüflauf schickt
 * eine Notiz-Anfrage durch die echte `RequestIntegration` von sentry/sentry — mit dem Wert, der in
 * `when@prod` steht — und sieht im entstandenen Ereignis nach, ob der Notiztext darin steht.
 * Die Gegenprobe mit der Vorgabe der Bibliothek (`medium`) zeigt, dass der Prüflauf den Rumpf
 * überhaupt sehen würde.
 */
final class Qa17SentryAnfragerumpfTest extends TestCase
{
    private const string NOTIZ = 'Seiteneingang links, Rampe steil — für Papas Rollstuhl zu eng';

    private static function produktionswert(): string
    {
        $konfiguration = Yaml::parseFile(\dirname(__DIR__, 3).'/config/packages/sentry.yaml');

        return (string) $konfiguration['when@prod']['sentry']['options']['max_request_body_size'];
    }

    /**
     * @return array<string, mixed> der Abschnitt `request` des Ereignisses
     */
    private static function ereignisFuer(string $maxRequestBodySize): array
    {
        $anfrage = (new ServerRequest('POST', 'https://endlech.lu/de/restaurants/12/notiz', ['Content-Type' => 'application/x-www-form-urlencoded']))
            ->withParsedBody(['restaurant_note' => ['text' => self::NOTIZ, '_token' => 'csrf-token']]);
        $rumpf = http_build_query((array) $anfrage->getParsedBody());
        $anfrage = $anfrage->withBody(\GuzzleHttp\Psr7\Utils::streamFor($rumpf))->withHeader('Content-Length', (string) \strlen($rumpf));

        $holer = new class($anfrage) implements RequestFetcherInterface {
            public function __construct(private readonly ServerRequestInterface $anfrage)
            {
            }

            public function fetchRequest(): ?ServerRequestInterface
            {
                return $this->anfrage;
            }
        };

        $integration = new RequestIntegration($holer);
        $ereignis = Event::createEvent();
        $verarbeiten = new \ReflectionMethod($integration, 'processEvent');
        $verarbeiten->invoke($integration, $ereignis, new Options([
            'send_default_pii' => false,
            'max_request_body_size' => $maxRequestBodySize,
        ]));

        return $ereignis->getRequest();
    }

    public function testAk65MitDemProduktionswertStehtDerNotiztextNichtImEreignis(): void
    {
        $request = self::ereignisFuer(self::produktionswert());

        self::assertArrayNotHasKey('data', $request);
        self::assertStringNotContainsString('Seiteneingang', (string) json_encode($request, \JSON_UNESCAPED_UNICODE));
        // Adresse und Methode bleiben — sie reichen zur Fehlersuche.
        self::assertSame('POST', $request['method']);
    }

    public function testAk65GegenprobeMitDerVorgabeDerBibliothekStuendeDerTextDarin(): void
    {
        $request = self::ereignisFuer('medium');

        self::assertStringContainsString('Seiteneingang', (string) json_encode($request, \JSON_UNESCAPED_UNICODE));
    }
}
