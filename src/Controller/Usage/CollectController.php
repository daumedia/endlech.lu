<?php

declare(strict_types=1);

namespace App\Controller\Usage;

use App\Usage\CollectPayloadNormalizer;
use App\Usage\InvalidCollectPayload;
use App\Usage\UmamiForwarder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Nimmt Zählaufrufe des Zählskripts an und reicht sie geprüft an Umami weiter (Feature 11).
 *
 * ⚠ **Sprachfrei und zustandslos, eigener Routenblock und eigene Firewall** — dasselbe Muster wie
 * `/health`. Ohne `stateless: true` legte der LocaleSubscriber bei jedem Zählaufruf eine Sitzung an,
 * und die Antwort trüge ein Cookie (AK-20). Ohne eigene Firewall liefe der Aufruf durch `main`.
 *
 * ⚠ **Der Pfad ist `/api/send` und nicht beliebig**: Das Zählskript liegt als `/zaehler.js` in der
 * Wurzel und schickt an `/api/send` neben sich (Entwurf, Entscheidung 12). Unter `/api/` nimmt der
 * Service Worker jede Anfrage aus, und die robots.txt sperrt den Pfad bereits.
 *
 * Der Deckel (300 je Stunde je Adresse) greift vorher im `RouteRateLimitSubscriber`.
 *
 * ⚠ Außer diesem einen Weg reicht die Anwendung **nichts** an Umami durch (AK-25): keine Anmeldung,
 * keine Schnittstelle, kein Skript.
 */
final class CollectController
{
    /** Ein Zählaufruf ist wenige hundert Byte groß; 8 KB lassen Titel und Daten reichlich Platz. */
    public const int MAX_BODY = 8192;

    public function __construct(
        private readonly CollectPayloadNormalizer $normalizer,
        private readonly UmamiForwarder $forwarder,
    ) {
    }

    #[Route('/api/send', name: 'app_usage_collect', methods: ['POST'], stateless: true)]
    public function __invoke(Request $request): Response
    {
        // AK-22: Rückhalt für Browser, die „Do Not Track" oder „Global Privacy Control" senden, den
        // Tracker aber trotzdem schicken lassen. Nichts wird weitergeleitet.
        if ('1' === $request->headers->get('DNT') || '1' === $request->headers->get('Sec-GPC')) {
            return $this->antwort(Response::HTTP_NO_CONTENT, '');
        }

        $rumpf = $request->getContent();
        if (\strlen($rumpf) > self::MAX_BODY) {
            return $this->antwort(Response::HTTP_REQUEST_ENTITY_TOO_LARGE, '{}');
        }

        try {
            $daten = json_decode($rumpf, true, 16, \JSON_THROW_ON_ERROR);
            if (!\is_array($daten)) {
                throw new InvalidCollectPayload('json');
            }
            $geprueft = $this->normalizer->normalize($daten);
        } catch (\JsonException|InvalidCollectPayload) {
            // Kein Grund in der Antwort: Wer Zählaufrufe baut, soll die Regeln nicht abfragen können.
            return $this->antwort(Response::HTTP_BAD_REQUEST, '{}');
        }

        $ergebnis = $this->forwarder->forward(
            $geprueft,
            $request->getClientIp(),
            $request->headers->get('User-Agent'),
            $request->headers->get('x-umami-cache'),
        );

        return $this->antwort($ergebnis->status, $ergebnis->body);
    }

    private function antwort(int $status, string $rumpf): Response
    {
        $antwort = new Response($rumpf, $status, [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-store, private',
        ]);

        return $antwort;
    }
}
