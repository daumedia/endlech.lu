<?php

declare(strict_types=1);

namespace App\Seo;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Bildet die Adressen, unter denen Suchmaschinen eine Seite kennen sollen (Feature 10).
 *
 * Sitemap, canonical-Verweis und Sprachverweise im Seitenkopf holen ihre Adressen alle
 * hier. Deshalb sagen sie dasselbe (AK-14).
 *
 * ⚠ **Der Host kommt aus `app.canonical_base_url`, nie aus der Anfrage.** Erzeugt wird
 * nur der PFAD über den Router; Schema und Host setzt dieser Dienst selbst davor. Ein
 * Aufruf über `www.endlech.lu` oder über `http` ändert daran nichts (AK-03, EC-06) —
 * begründet in `config/services.yaml`.
 *
 * ⚠ **Von den Abfrageparametern überlebt genau einer: `page`, als ganze Zahl ab 2**
 * (AK-13, entschieden zu OF-01). Sortier- und Filterparameter erzeugen keine eigene Seite,
 * Folgeseiten einer Liste dagegen schon — Google rät ausdrücklich davon ab, sie auf Seite 1
 * zu verweisen. `page=1` und ungültige Angaben entfallen, sonst gäbe es für Seite 1 zwei
 * maßgebliche Adressen. Die Regel gilt für canonical UND Sprachverweise gleichermaßen;
 * nennte Seite 2 sich selbst maßgeblich und verwiese in den Sprachen auf Seite 1,
 * widersprächen sich beide.
 *
 * ⚠ **Welche Seiten blättern, entscheidet nicht dieser Dienst**, sondern
 * `SeoRegistry::isPaginatedRoute()`; `SeoExtension` übergibt die Abfrage nur dort (BF-147).
 * Wer hier eine Abfrage für eine Seite übergibt, die nicht blättert, erzeugt wieder eine
 * Dublette, die sich selbst kanonisiert.
 */
final readonly class SeoUrlBuilder
{
    /**
     * Die Sprache für „alle übrigen Sprachen" (`x-default`). Dieselbe, auf die `/` weiterleitet
     * (`app_root` in `config/routes.yaml`) — Seiten und Weiterleitung sagen damit dasselbe.
     */
    public const string X_DEFAULT_LOCALE = 'lb';

    /**
     * @param list<string> $locales in der Reihenfolge der Sprachverweise
     */
    public function __construct(
        private UrlGeneratorInterface $urls,
        #[Autowire('%app.canonical_base_url%')]
        private string $baseUrl,
        #[Autowire('%kernel.enabled_locales%')]
        private array $locales,
    ) {
    }

    /**
     * Die absolute Adresse einer Seite in einer Sprache.
     *
     * @param array<string, string|int> $parameters Pfadparameter, ohne `_locale`
     * @param array<string, mixed>      $query      Abfrageparameter der Anfrage; es bleibt nur `page` ab 2
     */
    public function url(string $route, array $parameters, string $locale, array $query = []): string
    {
        $pfad = $this->urls->generate(
            $route,
            [...$parameters, '_locale' => $locale],
            UrlGeneratorInterface::ABSOLUTE_PATH,
        );
        $behalten = self::retainedQuery($query);

        return rtrim($this->baseUrl, '/').$pfad.([] === $behalten ? '' : '?'.http_build_query($behalten));
    }

    /**
     * Alle Sprachfassungen einer Seite, die Seite selbst eingeschlossen, dazu die Vorgabe.
     *
     * Google verlangt, dass jede Fassung sich selbst mit aufführt
     * (developers.google.com/search/docs/specialty/international/localized-versions).
     *
     * @param array<string, string|int> $parameters Pfadparameter, ohne `_locale`
     * @param array<string, mixed>      $query
     *
     * @return array<string, string> hreflang => Adresse, zuletzt `x-default`
     */
    public function alternates(string $route, array $parameters, array $query = []): array
    {
        $verweise = [];
        foreach ($this->locales as $locale) {
            $verweise[$locale] = $this->url($route, $parameters, $locale, $query);
        }
        $verweise['x-default'] = $this->url($route, $parameters, self::X_DEFAULT_LOCALE, $query);

        return $verweise;
    }

    /** @return list<string> */
    public function locales(): array
    {
        return $this->locales;
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array{page?: int}
     */
    public static function retainedQuery(array $query): array
    {
        $seite = $query['page'] ?? null;

        if (\is_int($seite) && $seite >= 2) {
            return ['page' => $seite];
        }

        // ctype_digit schließt Vorzeichen, Nachkommastellen, Leerzeichen und `2abc` aus.
        if (\is_string($seite) && ctype_digit($seite) && (int) $seite >= 2) {
            return ['page' => (int) $seite];
        }

        return [];
    }
}
