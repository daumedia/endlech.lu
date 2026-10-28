<?php

declare(strict_types=1);

namespace App\Seo;

use App\Repository\RestaurantRepository;
use App\Sponsor\Sponsor;
use App\Sponsor\SponsorDirectory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Twig\Environment;

/**
 * Erzeugt die Sitemap unter `/sitemap.xml` (Feature 10).
 *
 * Einträge: die festen Seiten aus dem Seitenverzeichnis, eine Detailseite je Restaurant und
 * (seit Feature 15) eine Seite je sichtbarem Sponsor, jede in allen fünf Sprachen —
 * (21 + n + s) × 5 (AK-02). Jeder Eintrag nennt seine vier
 * Sprachfassungen und die Vorgabe (AK-05). Kein Änderungsdatum, keine Priorität, keine
 * Häufigkeit (AK-07): Restaurants führen kein Änderungsdatum, ein erfundenes wäre falsch, und
 * die beiden anderen wertet Google nicht aus.
 *
 * ⚠⚠ **Hier wird KEIN Datenbankfehler aufgefangen — das ist die tragende Eigenschaft
 * (AK-11).** Der naheliegende „robuste" Umbau — Abfrage in `try`/`catch`, im Fehlerfall nur die
 * festen Seiten ausliefern — liefert Google eine Sitemap, in der alle Restaurantseiten fehlen.
 * Das liest sich als „diese Seiten gibt es nicht mehr" und kostet lautlos Sichtbarkeit. Eine
 * ungefangene Ausnahme wird dagegen zur 5xx, und die heißt nur „später nochmal". Weil
 * `CacheInterface::get()` bei einer Ausnahme im Rückruf nichts speichert, bleibt auch keine
 * halbe Fassung liegen. `SitemapGeneratorTest` lässt die Abfrage absichtlich scheitern.
 *
 * Gespeichert wird im Pool `cache.sitemap` (50 Minuten, Rechnung in `config/packages/cache.yaml`).
 */
final readonly class SitemapGenerator
{
    public const string CACHE_KEY = 'sitemap_xml';

    public function __construct(
        private SeoRegistry $registry,
        private SeoUrlBuilder $urls,
        private RestaurantRepository $restaurants,
        private SponsorDirectory $sponsors,
        private Environment $twig,
        #[Autowire(service: 'cache.sitemap')]
        private CacheInterface $cache,
    ) {
    }

    /** Die gespeicherte Fassung, oder — wenn keine vorliegt — eine vollständig neu erzeugte. */
    public function xml(): string
    {
        return $this->cache->get(self::CACHE_KEY, fn (): string => $this->render());
    }

    /** Erzeugt die Sitemap vollständig, ohne Zwischenspeicher. */
    public function render(): string
    {
        $seiten = [
            ...$this->registry->fixedPages(),
            // ⚠ Diese Zeile darf werfen. Siehe Klassenkommentar.
            ...$this->registry->restaurantPages($this->restaurants->findAllIdsAscending()),
            // Feature 15 · AK-41: aktive und ehemalige Sponsoren, nie künftige oder entfernte.
            // Gespeichert wird die Sitemap höchstens 50 Minuten — ein Sponsor, dessen Zeitraum
            // um Mitternacht beginnt, steht damit binnen einer Stunde drin (AK-23).
            ...$this->registry->sponsorPages(array_map(
                static fn (Sponsor $s): string => $s->slug,
                $this->sponsors->visible(),
            )),
        ];

        $eintraege = [];
        foreach ($seiten as $seite) {
            $verweise = $this->urls->alternates($seite->route, $seite->parameters);
            foreach ($this->urls->locales() as $locale) {
                $eintraege[] = ['loc' => $verweise[$locale], 'alternates' => $verweise];
            }
        }

        return $this->twig->render('seo/sitemap.xml.twig', ['entries' => $eintraege]);
    }
}
