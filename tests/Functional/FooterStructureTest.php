<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\AbstractWebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Die Fußzeile bleibt nach Themen gegliedert (Umbau vom 2026-09-15).
 *
 * Sie war Feature für Feature gewachsen: eine Spalte „Links" mit zwölf ungeordneten
 * Einträgen, Impressum zwischen Suche und Presse, und unter „Kontakt" eine Liste ohne
 * Überschrift. Jeder einzelne Schritt war begründet, das Ergebnis unübersichtlich. Diese
 * Prüfung hält die zwei Regeln fest, an denen es gekippt war.
 */
final class FooterStructureTest extends AbstractWebTestCase
{
    /** @return iterable<string, array{string}> */
    public static function sprachen(): iterable
    {
        foreach (['lb', 'de', 'fr', 'en', 'pt'] as $sprache) {
            yield $sprache => [$sprache];
        }
    }

    /**
     * Jede Linkliste steht unter einer Überschrift — außer der Rechtsleiste, die ihren
     * Namen als `aria-label` trägt.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('sprachen')]
    public function testJedeLinklisteHatEineUeberschrift(string $sprache): void
    {
        $client = static::createClient();
        $footer = $client->request('GET', "/$sprache/about")->filter('footer');
        self::assertResponseIsSuccessful();

        $ohneTitel = [];
        $footer->filter('ul')->each(function (Crawler $liste) use (&$ohneTitel): void {
            $knoten = $liste->getNode(0);
            $nav = $liste->closest('nav[aria-label]');
            if (null !== $nav && '' !== trim((string) $nav->attr('aria-label'))) {
                return;
            }
            $gruppe = $knoten?->parentNode;
            $titel = $gruppe instanceof \DOMElement ? (new Crawler($gruppe))->filter('h2') : null;
            if (null === $titel || 0 === $titel->count() || '' === trim($titel->first()->text())) {
                $ohneTitel[] = trim(preg_replace('/\s+/', ' ', $liste->text()) ?? '');
            }
        });

        self::assertSame([], $ohneTitel, 'Linkliste ohne Überschrift in der Fußzeile.');
    }

    /**
     * Rechtliches steht in der Rechtsleiste und NUR dort — nicht zwischen den Inhalten.
     */
    public function testRechtlichesStehtNurInDerRechtsleiste(): void
    {
        $client = static::createClient();
        $footer = $client->request('GET', self::LOCALE.'/about')->filter('footer');

        $leiste = $footer->filter('nav[aria-label]');
        self::assertCount(1, $leiste, 'Die Fußzeile trägt genau eine Rechtsleiste.');

        foreach (['/legal', '/legal#datenschutz', '/accessibility'] as $ende) {
            $ziel = 'a[href$="'.$ende.'"]';
            self::assertCount(1, $leiste->filter($ziel), "Rechtsleiste ohne Link auf …$ende.");
            self::assertCount(1, $footer->filter($ziel), "Link auf …$ende steht zusätzlich außerhalb der Rechtsleiste.");
        }
        self::assertCount(1, $leiste->filter('[data-action="cookie-consent#openSettings"]'));
    }

    /** Die Wortmarke liest sich als „Endlech.lu", nicht als „Endlech .lu". */
    public function testWortmarkeOhneLuecke(): void
    {
        $client = static::createClient();
        $marke = $client->request('GET', self::LOCALE.'/about')->filter('footer a[href$="/de/"]')->first();

        self::assertSame('Endlech.lu', preg_replace('/\s+/', '', $marke->text()));
        self::assertStringNotContainsString('gap-', (string) $marke->attr('class'));
    }
}
