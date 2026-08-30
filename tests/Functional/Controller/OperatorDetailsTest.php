<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\AbstractWebTestCase;

/**
 * Betreiberangaben stehen auf /presse und /legal gleichlautend (Feature 05, AK-15).
 *
 * ⚠ **Der Sinn ist, zwei Wahrheiten zu verhindern.** Ein Journalist zitiert die
 * Angabe aus dem Presse-Kit, eine Behörde die aus dem Impressum; weichen sie
 * voneinander ab, ist eine von beiden falsch, und niemand merkt welche. Deshalb
 * liegt der Wert in einem Parameter und nicht in vier Übersetzungskatalogen —
 * `CatalogueCompletenessTest` prüft Vollständigkeit, nicht Gleichheit.
 *
 * ⚠ **Der Lauf überspringt, solange die Angaben nicht eingetragen sind**
 * (VB-03). Das ist der ehrliche Zustand: Es gibt nichts zu vergleichen, und ein
 * harter Fehlschlag blockierte die Suite für eine Betreiberentscheidung. Die
 * Meldung nennt die Vorbedingung, damit der übersprungene Lauf nicht als
 * „geprüft" durchgeht.
 */
final class OperatorDetailsTest extends AbstractWebTestCase
{
    public function testPresseUndImpressumNennenDieselbenBetreiberangaben(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        $name = (string) $container->getParameter('app.operator_name');
        $anschrift = (string) $container->getParameter('app.operator_address');
        $verantwortlich = (string) $container->getParameter('app.operator_responsible');

        if ('' === trim($name)) {
            self::markTestSkipped(
                'Vorbedingung VB-03: Die Betreiberangaben (app.operator_name, '
                .'app.operator_address, app.operator_responsible in config/services.yaml) '
                .'sind noch nicht eingetragen. Bis dahin gibt es nichts zu vergleichen.',
            );
        }

        $presse = $client->request('GET', self::LOCALE.'/presse')->filter('main')->text();
        $legal = $client->request('GET', self::LOCALE.'/legal')->filter('main')->text();

        foreach (['Name' => $name, 'Anschrift' => $anschrift, 'Verantwortlicher' => $verantwortlich] as $was => $wert) {
            if ('' === trim($wert)) {
                continue;
            }

            foreach (preg_split('/\R/', $wert) ?: [] as $zeile) {
                $zeile = trim($zeile);
                if ('' === $zeile) {
                    continue;
                }

                self::assertStringContainsString($zeile, $presse, sprintf('%s fehlt auf /presse.', $was));
                self::assertStringContainsString($zeile, $legal, sprintf('%s fehlt auf /legal.', $was));
            }
        }
    }

    /**
     * Solange die Angaben fehlen, sagt keine der beiden Seiten etwas Falsches.
     *
     * /presse weist den Stand als ausstehend aus, /legal behält den bisherigen
     * Text — ein Impressum darf durch dieses Feature nicht weniger aussagen als
     * vorher.
     */
    public function testOhneAngabenSagtKeineSeiteEtwasFalsches(): void
    {
        $client = static::createClient();
        $name = (string) static::getContainer()->getParameter('app.operator_name');

        if ('' !== trim($name)) {
            self::markTestSkipped('Die Betreiberangaben sind eingetragen — dieser Lauf prüft den Zustand davor.');
        }

        $presse = $client->request('GET', self::LOCALE.'/presse')->filter('section#fakten')->text();
        self::assertStringContainsString('Wird derzeit ergänzt', $presse, 'Das Faktenblatt behauptet eine Angabe, die es nicht gibt.');

        $client->request('GET', self::LOCALE.'/legal');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', 'Endlech.lu', 'Das Impressum ist durch das Feature leerer geworden als vorher.');
    }
}
