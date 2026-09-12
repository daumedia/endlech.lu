<?php

namespace App\Tests\Unit\Seo;

use App\Seo\IndexablePage;
use App\Seo\SeoRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Feature 10 · Das Seitenverzeichnis ist die einzige Quelle für Sitemap, canonical-Verweis
 * und Ausschluss. Geprüft wird gegen die Tabelle in `features/10-sitemap-robots/spec.md`.
 */
final class SeoRegistryTest extends TestCase
{
    /**
     * Die 21 festen Seiten aus der Tabelle der Spec — hier ausgeschrieben, NICHT aus dem
     * Verzeichnis abgeleitet. Ein Prüflauf, der seine Erwartung aus dem Prüfling ableitet,
     * prüft gegen sich selbst.
     *
     * @var list<array{string, array<string, string>}>
     */
    private const array SPEC_TABELLE = [
        ['app_home', []],
        ['app_about', []],
        ['app_kriterien', []],
        ['app_accessibility', []],
        ['app_impressum', []],
        ['app_open', []],
        ['app_press_index', []],
        ['app_roadmap_index', []],
        ['app_changelog_index', []],
        ['app_comparison_index', []],
        ['app_comparison_show', ['slug' => 'google-maps']],
        ['app_comparison_show', ['slug' => 'tripadvisor']],
        ['app_comparison_show', ['slug' => 'wheelmap']],
        ['app_restaurant_index', []],
        ['app_partner', []],
        ['app_organisations', []],
        ['app_organisations_type', ['slug' => 'gemeinden']],
        ['app_organisations_type', ['slug' => 'unternehmen']],
        ['app_organisations_type', ['slug' => 'vereine']],
        ['app_app_waitlist', []],
        ['app_board_index', []],
    ];

    /** AK-02 · genau die 21 festen Seiten der Spec, keine mehr und keine weniger. */
    public function testDieFestenSeitenDeckenSichMitDerTabelleDerSpec(): void
    {
        $ist = array_map(
            static fn (IndexablePage $p): string => $p->route.' '.json_encode($p->parameters),
            (new SeoRegistry())->fixedPages(),
        );
        $soll = array_map(
            static fn (array $zeile): string => $zeile[0].' '.json_encode($zeile[1]),
            self::SPEC_TABELLE,
        );
        sort($ist);
        sort($soll);

        self::assertCount(21, $ist, 'Die Spec nennt 21 feste Seiten. Ändert sich die Zahl, zieht die Tabelle in spec.md mit.');
        self::assertSame($soll, $ist);
    }

    /** AK-15 · genau die sechzehn Wege aus der Spec, jeder einmal. */
    public function testSechzehnAusschluesseOhneDoppelung(): void
    {
        $aus = (new SeoRegistry())->excludedRoutes();

        self::assertCount(16, $aus);
        self::assertSame($aus, array_values(array_unique($aus)));
    }

    /** AK-16 · Keine Route ist zugleich angeboten und ausgeschlossen — und keine steht zweimal. */
    public function testDieDreiListenSindDisjunkt(): void
    {
        $r = new SeoRegistry();
        $angeboten = array_unique([
            ...array_map(static fn (IndexablePage $p): string => $p->route, $r->fixedPages()),
            SeoRegistry::RESTAURANT_ROUTE,
        ]);

        self::assertSame([], array_values(array_intersect($angeboten, $r->excludedRoutes())));
        self::assertSame([], array_values(array_intersect($angeboten, array_keys($r->unlistedRoutes()))));
        self::assertSame([], array_values(array_intersect($r->excludedRoutes(), array_keys($r->unlistedRoutes()))));
    }

    /** AK-06, AK-22 · Nichts aus Verwaltung, Profil, Schnittstelle oder Passkey-Weg wird angeboten. */
    public function testKeineVerwaltungsProfilOderSchnittstellenRouteWirdAngeboten(): void
    {
        foreach ((new SeoRegistry())->fixedPages() as $page) {
            self::assertDoesNotMatchRegularExpression('/^(admin_|app_profile|api_|webauthn|app_passkey)/', $page->route);
            foreach ($page->parameters as $wert) {
                self::assertStringNotContainsString('@', (string) $wert);
            }
        }
    }

    /** AK-02, AK-22 · Eine Detailseite trägt nur ihre Nummer — keinen Namen, keinen Token. */
    public function testRestaurantseitenTragenNurIhreNummer(): void
    {
        $seiten = (new SeoRegistry())->restaurantPages([3, 17]);

        self::assertCount(2, $seiten);
        self::assertSame(SeoRegistry::RESTAURANT_ROUTE, $seiten[0]->route);
        self::assertSame(['id' => 3], $seiten[0]->parameters);
        self::assertSame(['id' => 17], $seiten[1]->parameters);
        self::assertSame([], (new SeoRegistry())->restaurantPages([]));
    }

    public function testEinordnungEinzelnerRouten(): void
    {
        $r = new SeoRegistry();

        self::assertTrue($r->isIndexableRoute('app_home'));
        self::assertTrue($r->isIndexableRoute('app_restaurant_show'));
        self::assertFalse($r->isIndexableRoute('app_login'));
        self::assertFalse($r->isIndexableRoute('app_board_show'), 'Einzelne Ideen werden nicht angeboten.');

        self::assertTrue($r->isExcludedRoute('app_verify_email'));
        self::assertFalse($r->isExcludedRoute('app_home'));
        self::assertFalse($r->isExcludedRoute('app_board_show'), 'Einzelne Ideen werden auch nicht ausgeschlossen.');
    }
}
