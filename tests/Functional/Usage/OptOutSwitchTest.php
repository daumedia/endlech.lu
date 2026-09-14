<?php

namespace App\Tests\Functional\Usage;

use App\Tests\AbstractWebTestCase;

/**
 * Feature 11 · Widerspruchsschalter und Datenschutztext in /legal (AK-20, AK-23, AK-31, EC-02, EC-07).
 *
 * Das Umschalten selbst geschieht im Browser (Browserspeicher) und ist mit PHPUnit nicht ausführbar —
 * geprüft wird hier das Markup, auf dem der Controller arbeitet, und dass der Text die zugesagten
 * Angaben trägt. Den Browserlauf fährt die QA.
 */
final class OptOutSwitchTest extends AbstractWebTestCase
{
    public function testSchalterIstOhneJavaScriptVerborgenUndBarrierefrei(): void
    {
        $client = static::createClient();
        $seite = $client->request('GET', self::LOCALE.'/legal');
        self::assertResponseIsSuccessful();

        $bereich = $seite->filter('#datenschutz [data-controller="usage-opt-out"]');
        self::assertCount(1, $bereich);
        self::assertSame('an', $bereich->attr('data-usage-opt-out-on-text-value'));
        self::assertSame('aus', $bereich->attr('data-usage-opt-out-off-text-value'));

        $knopf = $bereich->filter('button[data-usage-opt-out-target="button"]');
        self::assertCount(1, $knopf);
        self::assertNotNull($knopf->attr('hidden'), 'EC-02: Ohne JavaScript darf kein wirkungsloser Knopf erscheinen.');
        self::assertSame('true', $knopf->attr('aria-pressed'));
        self::assertSame('button', $knopf->attr('type'));
        self::assertSame('true', $bereich->filter('[data-usage-opt-out-target="state"]')->attr('aria-hidden'));

        self::assertNotNull($bereich->filter('[data-usage-opt-out-target="unavailable"]')->attr('hidden'));
        self::assertStringContainsString('Ohne JavaScript findet keine Messung statt', $bereich->filter('noscript')->html());

        // AK-20: Die Messung setzt kein Cookie. Das Sitzungs-Cookie der Anwendung gab es auf dieser
        // Seite schon vor Feature 11 — geprüft wird, dass kein weiteres dazukommt.
        $namen = array_map(static fn ($c): string => $c->getName(), $client->getResponse()->headers->getCookies());
        foreach ($namen as $name) {
            self::assertDoesNotMatchRegularExpression('/umami|nutzung|usage/i', $name);
        }
    }

    /** AK-31 · Der Text nennt, was die Spec verlangt — und behauptet keine Anonymität (OF-01). */
    public function testDatenschutztextNenntDieZugesagtenAngaben(): void
    {
        $client = static::createClient();
        $text = $client->request('GET', self::LOCALE.'/legal')->filter('#datenschutz')->text();

        foreach ([
            'Umami', 'Hostinger', 'Deutschland', 'ohne Suchparameter', 'Domain der Seite', 'das Land, die Region und die Stadt',
            'Sitzungskennung', 'monatlich wechselt', 'nicht gespeichert', 'keine Cookies',
            'ohne zeitliche Grenze', 'Do Not Track', 'Global Privacy Control', 'ohne JavaScript',
            'Browserspeicher leert',
        ] as $angabe) {
            self::assertStringContainsString($angabe, $text, 'Fehlt in /legal: '.$angabe);
        }

        self::assertStringNotContainsStringIgnoringCase('anonym', $text);
        // Umamis Vorgabe seit 2026-09-14 (Entwurf, Entscheidung 9): monatlich, nicht mehr täglich.
        self::assertStringNotContainsString('täglich', $text);
        self::assertStringNotContainsString('keine Kennung', $text);
    }
}
