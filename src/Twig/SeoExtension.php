<?php

declare(strict_types=1);

namespace App\Twig;

use App\Seo\SeoRegistry;
use App\Seo\SeoUrlBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * canonical-Verweis und Sprachverweise für den Seitenkopf (Feature 10).
 *
 * Beide kommen aus demselben Adressbildner wie die Sitemap — deshalb nennt eine Seite im
 * Kopf dieselbe Adresse, unter der sie in der Sitemap steht (AK-12, AK-14).
 *
 * ⚠ **canonical nur für Seiten aus dem Seitenverzeichnis.** Eine Anmeldeseite bekommt keinen
 * Verweis: Sie wird nicht angeboten, und ein canonical auf sich selbst wäre ein Signal, das
 * dem Ausschluss widerspricht.
 *
 * Die Sprachverweise erscheinen dagegen auf jeder Seite mit Route, wie bisher — geändert hat
 * sich nur, dass ihr Host nicht mehr aus der Anfrage stammt.
 */
final class SeoExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requests,
        private readonly SeoRegistry $registry,
        private readonly SeoUrlBuilder $urls,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('seo_canonical_url', [$this, 'canonicalUrl']),
            new TwigFunction('seo_alternate_urls', [$this, 'alternateUrls']),
        ];
    }

    public function canonicalUrl(): ?string
    {
        $anfrage = $this->anfrage();
        if (null === $anfrage || !$this->registry->isIndexableRoute($anfrage['route'])) {
            return null;
        }

        return $this->urls->url($anfrage['route'], $anfrage['parameters'], $anfrage['locale'], $anfrage['query']);
    }

    /** @return array<string, string> hreflang => Adresse; leer ohne Route */
    public function alternateUrls(): array
    {
        $anfrage = $this->anfrage();
        // `app_root` ist die Weiterleitung von `/` — keine Seite, also keine Sprachfassungen.
        if (null === $anfrage || 'app_root' === $anfrage['route']) {
            return [];
        }

        return $this->urls->alternates($anfrage['route'], $anfrage['parameters'], $anfrage['query']);
    }

    /**
     * @return array{route: string, parameters: array<string, string|int>, locale: string, query: array<string, mixed>}|null
     */
    private function anfrage(): ?array
    {
        $request = $this->requests->getCurrentRequest();
        $route = $request?->attributes->get('_route');
        if (null === $request || !\is_string($route) || '' === $route) {
            return null;
        }

        /** @var array<string, string|int> $parameter */
        $parameter = (array) $request->attributes->get('_route_params', []);
        $locale = (string) ($parameter['_locale'] ?? $request->getLocale());
        unset($parameter['_locale']);

        return [
            'route' => $route,
            'parameters' => $parameter,
            'locale' => $locale,
            // ⚠ Nur blätternde Seiten reichen ihre Abfrage weiter (BF-147). Sonst behielte der
            // Adressbildner `page` auch dort, wo die Seitenzahl keine eigene Seite ist.
            'query' => $this->registry->isPaginatedRoute($route) ? $request->query->all() : [],
        ];
    }
}
