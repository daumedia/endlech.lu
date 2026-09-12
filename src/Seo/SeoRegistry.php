<?php

declare(strict_types=1);

namespace App\Seo;

use App\Comparison\Competitor;
use App\Enum\OrganisationType;

/**
 * Die EINZIGE Stelle, die festlegt, was Suchmaschinen angeboten wird und was nicht
 * (Feature 10).
 *
 * Aus diesem Verzeichnis entstehen drei Dinge: die Sitemap, der canonical-Verweis im
 * Seitenkopf und die Kopfzeile `X-Robots-Tag: noindex`. Weil alle drei hier nachschlagen,
 * können sie nicht auseinanderlaufen — eine Seite in der Sitemap trägt immer ihren
 * canonical-Verweis und nie den Ausschluss.
 *
 * ⚠ **Wer eine öffentliche Seite anlegt, trägt sie hier ein** — als angeboten, als
 * ausgeschlossen oder ausdrücklich als keins von beidem. `SeoRouteCoverageTest` wird rot,
 * solange eine öffentliche Route in keiner der drei Listen steht. Die Tabelle der festen
 * Seiten in `features/10-sitemap-robots/spec.md` und die Zahl in AK-02 ziehen mit.
 *
 * ⚠ **Die Vergleichsseiten und die Organisationsseiten werden abgeleitet, nicht
 * abgeschrieben.** Ein neuer Wettbewerber im Enum steht damit sofort in der Sitemap — und
 * färbt `SeoRegistryTest` rot, weil sich die Zahl der festen Seiten ändert. So ist es
 * gewollt: Die Spec nennt 21, und eine stille 22 wäre eine Abweichung, die niemand
 * beschlossen hat.
 */
final readonly class SeoRegistry
{
    /** Die Detailseite, deren Einträge aus dem Restaurantbestand kommen. */
    public const string RESTAURANT_ROUTE = 'app_restaurant_show';

    /**
     * Wege, die Suchmaschinen nicht in Ergebnisse aufnehmen sollen (AK-15).
     *
     * ⚠ Sie stehen bewusst **nicht** in `public/robots.txt`. Eine Sperre dort verhinderte,
     * dass Suchmaschinen den Ausschluss überhaupt lesen — die Adresse könnte dann ohne Inhalt
     * in den Ergebnissen erscheinen (AK-20; developers.google.com/search/docs/crawling-indexing/block-indexing).
     *
     * @var list<string>
     */
    private const array EXCLUDED_ROUTES = [
        'app_login',
        'app_register',
        'app_password_reset_request',
        'app_password_reset',
        // ⚠ Diese beiden rendern nie eine Seite, sie leiten nur weiter. Deshalb wird der
        // Ausschluss als Kopfzeile gesetzt und nicht als Meta-Element (OF-04).
        'app_verify_email',
        'app_email_change_confirm',
        'app_partner_confirm',
        'app_organisations_confirm',
        'app_app_waitlist_confirm',
        'app_partner_revoke',
        'app_organisations_revoke',
        'app_app_waitlist_revoke',
        'community_vorschlagen',
        'community_danke',
        'app_board_new',
        'app_board_thanks',
    ];

    /**
     * Öffentliche Routen, die bewusst **weder** angeboten **noch** ausgeschlossen werden.
     *
     * Diese dritte Liste gibt es, damit `SeoRouteCoverageTest` eine neue Route von einer
     * bewusst übergangenen unterscheiden kann. Ohne sie wäre „nicht eingetragen" dasselbe wie
     * „vergessen".
     *
     * @var array<string, string> Routenname => Begründung
     */
    private const array UNLISTED_ROUTES = [
        'app_board_show' => 'Einzelne Board-Ideen: laut Spec nicht in der Sitemap (Decision Log #1), '
            .'über die verlinkte Übersicht aber auffindbar und deshalb nicht ausgeschlossen',
        'app_logout' => 'Leitet nur weiter; die Firewall beantwortet den Aufruf, bevor eine Seite entsteht',
        'app_verify_notice' => 'Nicht in der Liste von AK-15 — offene Frage OF-05 in spec.md',
        'app_verify_resend' => 'Nicht in der Liste von AK-15 — offene Frage OF-05 in spec.md',
    ];

    /**
     * Die festen Seiten in der Reihenfolge der Tabelle in `spec.md`.
     *
     * @return list<IndexablePage>
     */
    public function fixedPages(): array
    {
        return [
            new IndexablePage('app_home'),
            new IndexablePage('app_about'),
            new IndexablePage('app_kriterien'),
            new IndexablePage('app_accessibility'),
            new IndexablePage('app_impressum'),
            new IndexablePage('app_open'),
            new IndexablePage('app_press_index'),
            new IndexablePage('app_roadmap_index'),
            new IndexablePage('app_changelog_index'),
            new IndexablePage('app_comparison_index'),
            ...array_map(
                static fn (Competitor $c): IndexablePage => new IndexablePage('app_comparison_show', ['slug' => $c->slug()]),
                Competitor::cases(),
            ),
            new IndexablePage('app_restaurant_index'),
            new IndexablePage('app_partner'),
            new IndexablePage('app_organisations'),
            ...array_map(
                static fn (OrganisationType $t): IndexablePage => new IndexablePage('app_organisations_type', ['slug' => $t->slug()]),
                OrganisationType::cases(),
            ),
            new IndexablePage('app_app_waitlist'),
            new IndexablePage('app_board_index'),
        ];
    }

    /**
     * Eine Detailseite je Restaurantnummer.
     *
     * @param iterable<int> $restaurantIds
     *
     * @return list<IndexablePage>
     */
    public function restaurantPages(iterable $restaurantIds): array
    {
        $pages = [];
        foreach ($restaurantIds as $id) {
            $pages[] = new IndexablePage(self::RESTAURANT_ROUTE, ['id' => $id]);
        }

        return $pages;
    }

    /**
     * Wird eine Anfrage an diese Route Suchmaschinen angeboten? Maßgeblich für den
     * canonical-Verweis im Seitenkopf.
     */
    public function isIndexableRoute(string $route): bool
    {
        if (self::RESTAURANT_ROUTE === $route) {
            return true;
        }

        foreach ($this->fixedPages() as $page) {
            if ($page->route === $route) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    public function excludedRoutes(): array
    {
        return self::EXCLUDED_ROUTES;
    }

    public function isExcludedRoute(string $route): bool
    {
        return \in_array($route, self::EXCLUDED_ROUTES, true);
    }

    /** @return array<string, string> Routenname => Begründung */
    public function unlistedRoutes(): array
    {
        return self::UNLISTED_ROUTES;
    }
}
