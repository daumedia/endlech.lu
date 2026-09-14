<?php

declare(strict_types=1);

namespace App\Twig;

use App\Usage\UsageTrackingPolicy;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Zählskript im Seitenkopf (Feature 11): ob es erscheint und mit welcher Kennung.
 *
 * Die Entscheidung trifft `UsageTrackingPolicy`; diese Erweiterung reicht nur die laufende Anfrage
 * hinein. Maßgeblich ist die **Hauptanfrage** — eine eingebettete Unteranfrage soll nicht anders
 * entscheiden als die Seite, in der sie steht.
 */
final class UsageExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requests,
        private readonly UsageTrackingPolicy $policy,
        #[Autowire('%app.umami_tracker_version%')]
        private readonly string $trackerVersion,
        #[Autowire('%app.canonical_base_url%')]
        private readonly string $canonicalBaseUrl,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('usage_tracking_enabled', [$this, 'isEnabled']),
            new TwigFunction('usage_website_id', [$this->policy, 'websiteId']),
            new TwigFunction('usage_tracker_version', fn (): string => $this->trackerVersion),
            // Die einzige Domain, auf der gezählt wird — aus der Hauptadresse, nicht aus der Anfrage
            // (AK-09: `www.endlech.lu` zählt nicht mit).
            new TwigFunction('usage_domain', fn (): string => (string) parse_url($this->canonicalBaseUrl, \PHP_URL_HOST)),
        ];
    }

    public function isEnabled(): bool
    {
        return $this->policy->shouldTrack($this->requests->getMainRequest());
    }
}
