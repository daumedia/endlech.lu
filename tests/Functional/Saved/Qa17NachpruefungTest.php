<?php

declare(strict_types=1);

namespace App\Tests\Functional\Saved;

use App\Repository\RestaurantRepository;
use App\Tests\AbstractWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Feature 17 · Reproduktionen aus der zweiten QA vom 2026-11-14 (`features/17-favoriten-und-listen/qa-report.md`,
 * Nachprüfung des Fehlerauftrags).
 *
 * Alle Fälle hier sind **offene Befunde** und deshalb übersprungen, solange sie offen sind (scharf mit
 * `QA17_REPRO=1`, dann rot). Wer einen Befund behebt, nimmt den `offen()`-Aufruf heraus — der Fall läuft
 * danach immer als Rückfallschutz (Muster wie `Qa17PruefungTest`).
 *
 * ⚠ BF-218 und die beiden Kontrastfälle prüfen nur die **Klasse**, die am Pixel als zu schwach gemessen
 * wurde. Der Nachweis ist die Messung im Browser (`qa/17/QA2-*`), nicht dieser Prüflauf: Eine andere
 * Klasse muss ebenso am Pixel gemessen werden.
 */
final class Qa17NachpruefungTest extends AbstractWebTestCase
{
    private static function offen(string $befund): void
    {
        if ('1' !== getenv('QA17_REPRO')) {
            self::markTestSkipped($befund.' offen — Reproduktion, siehe features/17-favoriten-und-listen/qa-report.md. Scharf mit QA17_REPRO=1.');
        }
    }

    /** @return iterable<string, array{string}> */
    public static function seitenMitBlaettern(): iterable
    {
        yield 'Ideen (öffentlich)' => ['/de/roadmap/ideen?page=9223372036854775807'];
        yield 'REST-API (öffentlich)' => ['/api/v1/restaurants?page=9223372036854775807'];
        yield 'Verwaltung' => ['/de/admin/restaurants?page=9223372036854775807'];
    }

    /**
     * BF-221 (vorbestehend, Muster BF-212 bis BF-214): Dieselbe Seitenzahl, die in der Suche seit BF-214 eine
     * 404 ergibt, läuft an drei weiteren Stellen in `($page - 1) * $limit` zu `float` über —
     * `TypeError` in `setFirstResult()`, HTTP 500 (Entwicklungsserver, 2026-11-14).
     */
    #[DataProvider('seitenMitBlaettern')]
    public function testBf221RiesigeSeitenzahlIstNirgendsEinServerfehler(string $pfad): void
    {
        self::offen('BF-221');

        $client = static::createClient();
        if (str_contains($pfad, '/admin/')) {
            $this->loginAs($client, 'admin@endlech.lu');
        }
        $client->request('GET', $pfad);

        self::assertLessThan(500, $client->getResponse()->getStatusCode(), $pfad);
    }

    /**
     * BF-218 (AK-49, 17/OF-05): Das Herz im Kopf der Detailseite trägt `focus:outline-cyan-700`. Am Pixel gemessen
     * (Chromium, Tastaturfokus, 1280/375 px): auf dem Verlauf 1,05–1,33 : 1, über dem Titelfoto (`from-black/70`)
     * bis 2,48 : 1 — kein Pixel ändert sich mit 3 : 1. Diese Klasse darf dort nicht zurückkommen.
     */
    public function testBf218HerzImKopfDerDetailseiteNichtMitDerAmBandUnsichtbarenFokusfarbe(): void
    {
        // Scharf seit der Behebung am 2026-11-14 (zweiter Fehlerauftrag 17) — Rückfallschutz.

        $client = static::createClient();
        $id = static::getContainer()->get(RestaurantRepository::class)->findAllIdsAscending()[0];
        $crawler = $client->request('GET', self::LOCALE.'/restaurants/'.$id);
        self::assertResponseIsSuccessful();

        $knopf = $crawler->filter('#favorit-knopf-'.$id);
        self::assertCount(1, $knopf);
        self::assertStringNotContainsString('focus:outline-cyan-700', (string) $knopf->attr('class'));
    }

    /**
     * BF-222 (vorbestehend, B10): Die Gehweite der Haltestellen steht in `text-xs text-gray-400` auf `bg-gray-50`,
     * axe 2,48 : 1 (WCAG 1.4.3 verlangt 4,5 : 1).
     */
    public function testBf222GehweiteDerHaltestellenNichtInZuHellerSchrift(): void
    {
        self::offen('BF-222');

        $vorlage = (string) file_get_contents(\dirname(__DIR__, 3).'/templates/partials/_nearby_stops.html.twig');
        self::assertStringNotContainsString('text-xs text-gray-400', $vorlage);
    }

    /**
     * BF-223 (vorbestehend, B03): Der Knopf „Passkey hinzufügen“ im Profil ist weiß auf `bg-cyan-600`, axe 3,61 : 1
     * (16 px fett gilt nicht als große Schrift, verlangt sind 4,5 : 1).
     */
    public function testBf223PasskeyKnopfNichtWeissAufCyan600(): void
    {
        self::offen('BF-223');

        $vorlage = (string) file_get_contents(\dirname(__DIR__, 3).'/templates/partials/_passkey_manage.html.twig');
        self::assertDoesNotMatchRegularExpression('/class="[^"]*\bbg-cyan-600\b[^"]*\btext-white\b[^"]*"/', $vorlage);
    }
}
