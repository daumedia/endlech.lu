<?php

namespace App\Tests\Integration\Seo;

use App\Seo\IndexablePage;
use App\Seo\SeoRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

/**
 * Feature 10, Entwurf Entscheidung 3 · Jede öffentliche Route ist GENAU EINER Klasse
 * zugeordnet: angeboten, ausgeschlossen oder bewusst keins von beidem.
 *
 * ⚠ **Der Grund für diesen Prüflauf ist eine neue Seite, an die niemand denkt.** Ohne ihn
 * fehlte sie lautlos in der Sitemap, trüge keinen canonical-Verweis und fiele nicht auf.
 * Mit ihm wird der Lauf rot, bis sie eingeordnet ist. Dasselbe Muster wie
 * `LimiterCoverageTest` und `RouteDirectoryCollisionTest`: geprüft wird die Ursache, nicht
 * das Symptom.
 */
final class SeoRouteCoverageTest extends KernelTestCase
{
    /**
     * Öffentlich heißt: unter dem Sprachpräfix, per GET erreichbar, nicht in Verwaltung,
     * Profil oder Schnittstelle. Diese drei stehen in `public/robots.txt` gesperrt und hinter
     * der Anmeldung.
     *
     * @return array<string, string> Routenname => Pfad
     */
    private function oeffentlicheRouten(): array
    {
        self::bootKernel();
        /** @var RouterInterface $router */
        $router = static::getContainer()->get('router');

        $routen = [];
        foreach ($router->getRouteCollection()->all() as $name => $route) {
            $pfad = $route->getPath();
            $methoden = $route->getMethods();
            if (!str_starts_with($pfad, '/{_locale}')) {
                continue;
            }
            if ([] !== $methoden && !\in_array('GET', $methoden, true)) {
                continue;
            }
            if (1 === preg_match('#^/\{_locale\}/(admin|profile|api)(/|$)#', $pfad)) {
                continue;
            }
            $routen[$name] = $pfad;
        }

        return $routen;
    }

    public function testJedeOeffentlicheRouteIstGenauEinerKlasseZugeordnet(): void
    {
        $r = new SeoRegistry();
        $angeboten = array_unique([
            ...array_map(static fn (IndexablePage $p): string => $p->route, $r->fixedPages()),
            SeoRegistry::RESTAURANT_ROUTE,
        ]);
        $ausgeschlossen = $r->excludedRoutes();
        $uebergangen = array_keys($r->unlistedRoutes());

        $ohneKlasse = [];
        $mehrfach = [];
        foreach ($this->oeffentlicheRouten() as $name => $pfad) {
            $treffer = (int) \in_array($name, $angeboten, true)
                + (int) \in_array($name, $ausgeschlossen, true)
                + (int) \in_array($name, $uebergangen, true);
            if (0 === $treffer) {
                $ohneKlasse[] = "$name ($pfad)";
            } elseif ($treffer > 1) {
                $mehrfach[] = $name;
            }
        }

        self::assertSame([], $ohneKlasse, "Diese öffentlichen Routen sind im Seitenverzeichnis (App\\Seo\\SeoRegistry) nicht eingeordnet:\n  "
            .implode("\n  ", $ohneKlasse)."\nEntweder anbieten, ausschließen oder mit Begründung als übergangen eintragen.");
        self::assertSame([], $mehrfach);
    }

    /**
     * BF-147 · Die Liste der blätternden Seiten stimmt mit den Controllern überein, die `page`
     * aus der Abfrage lesen — in beide Richtungen.
     *
     * ⚠ **Ohne diesen Abgleich veraltete die Liste lautlos.** Eine neue blätternde Seite ohne
     * Eintrag verwiese mit Seite 2 auf Seite 1; ein Eintrag für eine Seite, die nicht mehr
     * blättert, brächte die Dubletten aus BF-147 zurück. Geprüft wird am Quelltext der
     * Controller-Methode, weil das die Stelle ist, an der die Seitenzahl wirklich hereinkommt.
     */
    public function testBlaetterndeSeitenStimmenMitDenControllernUeberein(): void
    {
        self::bootKernel();
        /** @var RouterInterface $router */
        $router = static::getContainer()->get('router');
        $collection = $router->getRouteCollection();

        $r = new SeoRegistry();
        $angeboten = array_unique([
            ...array_map(static fn (IndexablePage $p): string => $p->route, $r->fixedPages()),
            SeoRegistry::RESTAURANT_ROUTE,
        ]);

        $lesenSeitenzahl = [];
        foreach (array_keys($this->oeffentlicheRouten()) as $name) {
            if (!\in_array($name, $angeboten, true)) {
                continue;
            }
            $controller = (string) $collection->get($name)?->getDefault('_controller');
            if (!str_contains($controller, '::')) {
                continue;
            }
            [$klasse, $methode] = explode('::', $controller, 2);
            if (!method_exists($klasse, $methode)) {
                continue;
            }
            $m = new \ReflectionMethod($klasse, $methode);
            $zeilen = \array_slice(file((string) $m->getFileName()), $m->getStartLine() - 1, $m->getEndLine() - $m->getStartLine() + 1);
            if (1 === preg_match('/->query->(?:getInt|get|all)\(\s*[\'"]page[\'"]/', implode('', $zeilen))) {
                $lesenSeitenzahl[] = $name;
            }
        }

        $liste = $r->paginatedRoutes();
        sort($lesenSeitenzahl);
        sort($liste);

        self::assertNotSame([], $lesenSeitenzahl, 'Vorbedingung: Mindestens die Restaurantliste liest `page`.');
        self::assertSame($lesenSeitenzahl, $liste, 'SeoRegistry::PAGINATED_ROUTES weicht von den Controllern ab, die `page` lesen.');
    }

    /** Ein Tippfehler im Verzeichnis wäre sonst eine Route, die es nicht gibt — und nichts würde rot. */
    public function testJederEingetrageneNameIstEineRoute(): void
    {
        self::bootKernel();
        /** @var RouterInterface $router */
        $router = static::getContainer()->get('router');
        $collection = $router->getRouteCollection();

        $r = new SeoRegistry();
        $namen = array_unique([
            ...array_map(static fn (IndexablePage $p): string => $p->route, $r->fixedPages()),
            SeoRegistry::RESTAURANT_ROUTE,
            ...$r->excludedRoutes(),
            ...array_keys($r->unlistedRoutes()),
        ]);

        $unbekannt = array_values(array_filter($namen, static fn (string $n): bool => null === $collection->get($n)));
        self::assertSame([], $unbekannt);
    }
}
