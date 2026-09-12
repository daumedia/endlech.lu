<?php

namespace App\Tests\Integration\Seo;

use App\Seo\SeoRegistry;
use App\Seo\SeoUrlBuilder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

/**
 * Feature 10 · `public/robots.txt`.
 *
 * Ausgewertet wird wie bei Google: Präfixe, Groß- und Kleinschreibung zählt, die längste
 * passende Regel gewinnt, bei Gleichstand die freizügigere
 * (developers.google.com/search/docs/crawling-indexing/robots/robots_txt). Weil die Datei keine
 * Platzhalter enthält, ist diese Auswertung exakt — `testKeinPlatzhalter` hält das fest.
 *
 * ⚠ **Ob die Datei mit 200 als Klartext ausgeliefert wird (AK-17), prüft dieser Lauf nicht.**
 * Sie ist statisch; der Symfony-Testclient erreicht sie nicht, weil der Webserver sie liefert,
 * nicht die Anwendung. Das ist ein Abruf gegen den laufenden Server, siehe `qa-report.md`.
 */
final class RobotsTxtTest extends KernelTestCase
{
    private const string DATEI = __DIR__.'/../../../public/robots.txt';
    private const array SPRACHEN = ['lb', 'de', 'fr', 'en'];
    private const string PROBE_TOKEN = 'abababababababababababababababababababababababababababababababab';

    /** @return list<array{string, string}> [Allow|Disallow, Pfad] für `User-agent: *` */
    private function regeln(): array
    {
        $regeln = [];
        $fuerAlle = false;
        foreach (file(self::DATEI, \FILE_IGNORE_NEW_LINES) ?: [] as $zeile) {
            $zeile = trim(preg_replace('/#.*$/', '', $zeile) ?? '');
            if ('' === $zeile || !str_contains($zeile, ':')) {
                continue;
            }
            [$feld, $wert] = array_map('trim', explode(':', $zeile, 2));
            $feld = strtolower($feld);
            if ('user-agent' === $feld) {
                $fuerAlle = '*' === $wert;
            } elseif ($fuerAlle && \in_array($feld, ['allow', 'disallow'], true) && '' !== $wert) {
                $regeln[] = [$feld, $wert];
            }
        }

        return $regeln;
    }

    private function erlaubt(string $pfad): bool
    {
        $beste = null;
        foreach ($this->regeln() as [$art, $praefix]) {
            if (!str_starts_with($pfad, $praefix)) {
                continue;
            }
            if (null === $beste || \strlen($praefix) > \strlen($beste[1])
                || (\strlen($praefix) === \strlen($beste[1]) && 'allow' === $art)) {
                $beste = [$art, $praefix];
            }
        }

        return null === $beste || 'allow' === $beste[0];
    }

    /** AK-17 · Die Sitemap-Zeile mit vollständiger Adresse. */
    public function testDieDateiNenntDieSitemap(): void
    {
        self::assertFileIsReadable(self::DATEI);
        self::assertContains('Sitemap: https://endlech.lu/sitemap.xml', file(self::DATEI, \FILE_IGNORE_NEW_LINES));
        self::assertLessThan(500 * 1024, filesize(self::DATEI), 'Google liest höchstens 500 KiB.');
        self::assertTrue(mb_check_encoding((string) file_get_contents(self::DATEI), 'UTF-8'));
    }

    /** AK-18 · Verwaltung, Profil und Schnittstelle sind in allen vier Sprachen gesperrt. */
    public function testVerwaltungProfilUndSchnittstelleSindGesperrt(): void
    {
        $pfade = ['/api/v1/restaurants', '/api/v1/auth/login', '/api/docs'];
        foreach (self::SPRACHEN as $s) {
            array_push($pfade, "/$s/admin", "/$s/admin/restaurants", "/$s/profile", "/$s/profile/edit", "/$s/api/cuisines/search");
        }

        foreach ($pfade as $pfad) {
            self::assertFalse($this->erlaubt($pfad), "$pfad müsste gesperrt sein");
        }
    }

    /** AK-19 · Jede Seite der Sitemap und der offene Datensatz sind erlaubt. */
    public function testSitemapSeitenUndOffeneDatenSindErlaubt(): void
    {
        self::bootKernel();
        $builder = new SeoUrlBuilder(static::getContainer()->get('router'), 'https://endlech.lu', self::SPRACHEN);
        $r = new SeoRegistry();

        $pfade = ['/open.json', '/open/dataset.csv', '/open/dataset.json', '/sitemap.xml'];
        foreach ([...$r->fixedPages(), ...$r->restaurantPages([1, 42])] as $seite) {
            foreach (self::SPRACHEN as $s) {
                $pfade[] = substr($builder->url($seite->route, $seite->parameters, $s), \strlen('https://endlech.lu'));
            }
        }

        self::assertCount(4 + (21 + 2) * 4, $pfade);
        foreach ($pfade as $pfad) {
            self::assertTrue($this->erlaubt($pfad), "$pfad müsste erlaubt sein");
        }
    }

    /**
     * AK-20 · Die Ausschlusswege sind ERLAUBT. Gesperrt, läse Google ihre `noindex`-Kopfzeile nie,
     * und die Adresse könnte ohne Inhalt in den Ergebnissen erscheinen.
     */
    public function testAusschlusswegeSindNichtGesperrt(): void
    {
        self::bootKernel();
        /** @var RouterInterface $router */
        $router = static::getContainer()->get('router');

        foreach ((new SeoRegistry())->excludedRoutes() as $route) {
            $variablen = $router->getRouteCollection()->get($route)?->compile()->getPathVariables() ?? [];
            foreach (self::SPRACHEN as $s) {
                $parameter = \in_array('token', $variablen, true) ? ['token' => self::PROBE_TOKEN] : [];
                $pfad = $router->generate($route, [...$parameter, '_locale' => $s]);
                self::assertTrue($this->erlaubt($pfad), "$pfad ($route) darf nicht gesperrt sein");
            }
        }
    }

    /** AK-21 · Keine Regel sperrt die ganze Seite. */
    public function testKeineSperreDerGanzenSeite(): void
    {
        foreach ($this->regeln() as [$art, $pfad]) {
            self::assertFalse('disallow' === $art && '/' === $pfad, 'Disallow: / sperrt die gesamte Seite');
        }
        self::assertTrue($this->erlaubt('/'));
        self::assertTrue($this->erlaubt('/lb/'));
    }

    /** Die Präfixauswertung oben ist nur exakt, solange die Datei keine Platzhalter enthält. */
    public function testKeinPlatzhalter(): void
    {
        foreach ($this->regeln() as [, $pfad]) {
            self::assertDoesNotMatchRegularExpression('/[*$]/', $pfad);
        }
    }
}
