<?php

namespace App\Api;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Baut absolute URLs für hochgeladene Assets (Bilder, Avatare), damit native
 * Clients (iOS) ohne Kenntnis des API-Hosts direkt `https://…`-URLs erhalten.
 *
 * Standardmäßig wird Scheme+Host des aktuellen Requests verwendet; hinter einem
 * Proxy/CDN kann `APP_API_BASE_URL` (z. B. `https://api.endlech.lu`) gesetzt werden.
 */
final class AssetUrlBuilder
{
    public function __construct(
        private readonly RequestStack $requestStack,
        #[Autowire(env: 'APP_API_BASE_URL')]
        private readonly string $baseUrl = '',
    ) {
    }

    public function absolute(?string $relativePath): ?string
    {
        if ($relativePath === null || $relativePath === '') {
            return null;
        }

        $base = $this->resolveBase();

        // Ohne Request-Kontext (z. B. CLI) und ohne Override bleibt der Pfad relativ.
        return $base === '' ? $relativePath : $base . $relativePath;
    }

    private function resolveBase(): string
    {
        if ($this->baseUrl !== '') {
            return rtrim($this->baseUrl, '/');
        }

        return $this->requestStack->getCurrentRequest()?->getSchemeAndHttpHost() ?? '';
    }
}
