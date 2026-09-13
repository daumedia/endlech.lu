<?php

namespace App\Tests\Integration\Usage;

use App\Usage\UsageEventCatalogue;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Feature 11 · Die Trichter in `growth/config.json` treffen die echten Seiten in jeder Sprache (AK-14, AK-15, BF-150).
 *
 * Umami 3.3.1 wertet einen Pfadschritt so aus (`src/queries/sql/reports/getFunnel.ts`, Z. 116–118):
 * Beginnt oder endet er mit `*`, wird genau dieser Stern zu `%` und verglichen wird mit `LIKE`; sonst auf
 * Gleichheit. `umamiTrifft()` bildet das nach. Der Nachbau ist gegen eine Umami-3.3.1-Instanz abgeglichen
 * (`/*\/restaurants` zählte dort 0, `*\/restaurants` 1 — QA Feature 11) und prüft deshalb nicht bloß gegen
 * die eigene Erwartung.
 *
 * ⚠ Bis BF-150 standen die Schritte als `/*\/restaurants` in der Konfiguration: jeder Trichter zählte null,
 * und `Qa11TrichterMusterTest` war der einzige Lauf, der das sah. Dieser hier prüft zusätzlich die
 * Gegenrichtung — ein fester Sprachpräfix (`/de/restaurants`) hätte den anderen Lauf bestanden und AK-14
 * trotzdem verfehlt.
 */
final class GrowthTrichterTest extends KernelTestCase
{
    private static array $config;

    public static function setUpBeforeClass(): void
    {
        self::$config = json_decode((string) file_get_contents(__DIR__.'/../../../growth/config.json'), true, 16, \JSON_THROW_ON_ERROR);
    }

    /** Umamis Abgleich eines Pfadschritts, nachgebildet aus `getFunnel.ts`. */
    private static function umamiTrifft(string $schritt, string $pfad): bool
    {
        if (!str_starts_with($schritt, '*') && !str_ends_with($schritt, '*')) {
            return $schritt === $pfad;
        }

        $muster = '';
        foreach (mb_str_split((string) preg_replace('/^\*|\*$/', '%', $schritt)) as $zeichen) {
            $muster .= match ($zeichen) {
                '%' => '.*',
                '_' => '.',
                default => preg_quote($zeichen, '#'),
            };
        }

        return 1 === preg_match('#^'.$muster.'$#su', $pfad);
    }

    /**
     * @param array<string, string|int> $parameter
     *
     * @return array<string, string> Sprache → Pfad
     */
    private function pfade(string $route, array $parameter = []): array
    {
        $router = self::getContainer()->get(UrlGeneratorInterface::class);
        $pfade = [];
        foreach (self::getContainer()->getParameter('kernel.enabled_locales') as $sprache) {
            $pfade[$sprache] = $router->generate($route, ['_locale' => $sprache, ...$parameter]);
        }

        return $pfade;
    }

    private function assertTrifftAlle(string $schritt, array $pfade, string $was): void
    {
        foreach ($pfade as $sprache => $pfad) {
            self::assertTrue(self::umamiTrifft($schritt, $pfad), sprintf('%s: Schritt „%s" trifft %s (%s) nicht.', $was, $schritt, $pfad, $sprache));
        }
    }

    private function assertTrifftKeinen(string $schritt, array $pfade, string $was): void
    {
        foreach ($pfade as $pfad) {
            self::assertFalse(self::umamiTrifft($schritt, $pfad), sprintf('%s: Schritt „%s" trifft auch %s.', $was, $schritt, $pfad));
        }
    }

    public function testNachbauEntsprichtDerMessungGegenUmami(): void
    {
        self::assertFalse(self::umamiTrifft('/*/restaurants', '/de/restaurants'), 'gemessen: 0');
        self::assertTrue(self::umamiTrifft('*/restaurants', '/de/restaurants'), 'gemessen: 1');
        self::assertTrue(self::umamiTrifft('/de/restaurants/*', '/de/restaurants/1'), 'gemessen: 1');
        self::assertFalse(self::umamiTrifft('/*/app', '/fr/app'), 'gemessen: 0');
    }

    /** AK-11, AK-14 · Liste → Filter → Detailseite → Kontaktweg, sprachübergreifend. */
    public function testSuchtrichter(): void
    {
        self::bootKernel();
        [$liste, $filter, $detail, $kontakt] = self::$config['umami']['conversion_events'];
        $listen = $this->pfade('app_restaurant_index');
        $details = $this->pfade('app_restaurant_show', ['id' => 1]);

        $this->assertTrifftAlle($liste, $listen, 'Liste');
        $this->assertTrifftKeinen($liste, $details, 'Liste');
        $this->assertTrifftAlle($detail, $details, 'Detailseite');
        $this->assertTrifftKeinen($detail, $listen, 'Detailseite');
        self::assertSame([UsageEventCatalogue::FILTER_APPLIED, UsageEventCatalogue::CONTACT_USED], [$filter, $kontakt]);
    }

    /** AK-15 · Seite geöffnet → erfolgreich abgesendet, je Warteliste getrennt, samt Zielgruppenseiten. */
    public function testWartelistenTrichter(): void
    {
        self::bootKernel();
        $trichter = self::$config['weitere_trichter'];
        $seiten = [
            'warteliste_app' => $this->pfade('app_app_waitlist'),
            'warteliste_partner' => $this->pfade('app_partner'),
            'warteliste_organisationen' => [
                ...array_values($this->pfade('app_organisations')),
                ...array_values($this->pfade('app_organisations_type', ['slug' => 'gemeinden'])),
                ...array_values($this->pfade('app_organisations_type', ['slug' => 'unternehmen'])),
                ...array_values($this->pfade('app_organisations_type', ['slug' => 'vereine'])),
            ],
        ];
        self::assertSame(array_keys($seiten), array_keys($trichter));

        foreach ($seiten as $name => $pfade) {
            [$seite, $ereignis] = $trichter[$name];
            $this->assertTrifftAlle($seite, $pfade, $name);
            self::assertSame(UsageEventCatalogue::WAITLIST_JOINED, $ereignis, $name);

            foreach ($seiten as $anderer => $fremdePfade) {
                if ($anderer !== $name) {
                    $this->assertTrifftKeinen($seite, $fremdePfade, $name.' gegen '.$anderer);
                }
            }
        }
    }
}
