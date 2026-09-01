<?php

namespace App\Controller\Health;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Lebendigkeitsprüfung für Container-Orchestrierung (Docker HEALTHCHECK,
 * Coolify, Load Balancer).
 *
 * ⚠ Sprachfrei, deshalb liegt der Controller in einem eigenen Verzeichnis:
 * Der `controllers`-Loader in `config/routes.yaml` hängt alles unter
 * `/{_locale}`, und ein Healthcheck darf nicht von einer Sprachwahl abhängen —
 * derselbe Grund wie bei `Api/V1/`, `Open/` und `Marketing/`.
 *
 * ⚠ Bewusst OHNE Datenbankabfrage. Der Endpunkt beantwortet „läuft der
 * PHP-Prozess", nicht „ist das Gesamtsystem gesund". Hinge er an der Datenbank,
 * nähme ein kurzer Ausfall dort den Container mit — Docker würde ihn neu
 * starten und der Neustart hülfe nichts, weil die Ursache außerhalb liegt.
 */
class HealthController extends AbstractController
{
    // `stateless: true` ist hier kein Schmuck: Ohne den Marker legte der
    // LocaleSubscriber fuer jeden Aufruf eine Sitzung an — bei einem
    // 30-Sekunden-Takt rund 100 000 Dateien im Jahr, die niemand liest.
    #[Route('/health', name: 'app_health', methods: ['GET'], stateless: true)]
    public function health(): JsonResponse
    {
        $response = new JsonResponse([
            'status' => 'ok',
            'version' => $this->getParameter('app.version'),
        ]);

        // Kein Zwischenspeicher darf einen Gesundheitszustand festhalten.
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
