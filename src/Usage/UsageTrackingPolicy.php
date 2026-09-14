<?php

declare(strict_types=1);

namespace App\Usage;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;

/**
 * Entscheidet, ob eine Seite das Zählskript bekommt (Feature 11).
 *
 * ⚠ **Das ist die erste von drei Stellen, nicht die einzige.** Der Browser (Schalter, „Do Not
 * Track", GPC, Domain) und die Weiterleitung `/api/send` prüfen noch einmal. Diese Regel verhindert,
 * dass auf den ausgenommenen Seiten überhaupt ein Skript liegt — die Weiterleitung verhindert, dass
 * ein selbst gebauter Zählaufruf trotzdem ankommt.
 *
 * ⚠ **Token-Seiten werden am Routenparameter `token` erkannt, nicht über eine Liste** (Entwurf,
 * Entscheidung 13). So fällt jede künftige Bestätigungs- oder Abmelde-Route von selbst heraus. Die
 * Ausschlussliste des Seitenverzeichnisses (Feature 10) passt hier nicht: Sie enthält Anmeldung und
 * Registrierung, die gemessen werden sollen.
 */
final readonly class UsageTrackingPolicy
{
    public function __construct(
        #[Autowire('%app.umami_website_id%')]
        private string $websiteId,
    ) {
    }

    public function websiteId(): string
    {
        return $this->websiteId;
    }

    public function shouldTrack(?Request $request): bool
    {
        if ('' === $this->websiteId || null === $request) {
            return false;
        }

        $route = $request->attributes->get('_route');
        if (!\is_string($route) || '' === $route || str_starts_with($route, 'admin_')) {
            return false;
        }

        // Profil und Verwaltung am Pfad — derselbe Zuschnitt wie die Sperren in robots.txt und
        // die Firewall-Regeln. Die Passkey-Verwaltung liegt unter /profile und fällt mit heraus.
        if (1 === preg_match('#^/[a-z]{2}/(admin|profile)(/|$)#', $request->getPathInfo())) {
            return false;
        }

        $parameter = $request->attributes->get('_route_params', []);

        return !(\is_array($parameter) && \array_key_exists('token', $parameter));
    }
}
