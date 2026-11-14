<?php

declare(strict_types=1);

namespace App\Tests\Unit\Usage;

use App\Usage\CollectPayloadNormalizer;
use App\Usage\InvalidCollectPayload;
use App\Usage\UsageEventCatalogue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Feature 17, AK-64, AK-66, AK-67 — Favoriten und Listen in der Nutzungsmessung.
 *
 * Gezählt wird, **dass** ein Herz gesetzt oder eine Liste angelegt wurde — nie welches Lokal,
 * welcher Listenname, welche Nummer. Eine eigene Liste zählt unter einem Pfad ohne Nummer.
 *
 * ⚠ Die Pfadregel steht zweimal (Server und `assets/usage/before_send.ts`, siehe dort). Dieser
 * Prüflauf liest das Muster aus der TypeScript-Datei und wendet es auf dieselben Fälle an wie den
 * Server — läuft eine der beiden Stellen auseinander, wird er rot.
 */
final class Feature17UsageEventsTest extends TestCase
{
    private const string KENNUNG = '00000000-0000-4000-8000-000000000017';
    private const string VOR_VERSAND = __DIR__.'/../../../assets/usage/before_send.ts';

    private function normalizer(): CollectPayloadNormalizer
    {
        return new CollectPayloadNormalizer(new UsageEventCatalogue(), self::KENNUNG, 'https://endlech.lu');
    }

    /**
     * @param array<string, mixed> $abweichung
     *
     * @return array<string, mixed>
     */
    private static function aufruf(array $abweichung = []): array
    {
        return ['type' => 'event', 'payload' => [
            'website' => self::KENNUNG,
            'hostname' => 'endlech.lu',
            'url' => 'https://endlech.lu/de/restaurants',
            'screen' => '390x844',
            'language' => 'de-LU',
            'title' => 'Restaurants',
            ...$abweichung,
        ]];
    }

    /** @return iterable<string, array{string, array<mixed>, bool}> */
    public static function ereignisse(): iterable
    {
        $herz = UsageEventCatalogue::HEART_SET;
        $liste = UsageEventCatalogue::LIST_CREATED;

        yield 'AK-66 Herz gesetzt ohne Daten' => [$herz, [], true];
        yield 'AK-66 Liste angelegt ohne Daten' => [$liste, [], true];
        yield 'AK-66 Herz mit Lokalnummer' => [$herz, ['restaurant' => '42'], false];
        yield 'AK-66 Herz mit Lokalname' => [$herz, ['lokal' => 'Pizzeria Bella Vista'], false];
        yield 'AK-64 Liste mit Listenname' => [$liste, ['liste' => 'Geburtstag Oma'], false];
        yield 'AK-64 Liste mit Listennummer' => [$liste, ['nummer' => '7'], false];
        yield 'AK-66 Liste mit leerem Feld' => [$liste, ['name' => ''], false];
    }

    /** @param array<mixed> $daten */
    #[DataProvider('ereignisse')]
    public function testAk66IsAllowed(string $ereignis, array $daten, bool $erwartet): void
    {
        self::assertSame($erwartet, (new UsageEventCatalogue())->isAllowed($ereignis, $daten));
    }

    public function testAk66BeideStehenImKatalogOhneDatenfelder(): void
    {
        $katalog = new UsageEventCatalogue();

        self::assertContains('herz_gesetzt', $katalog->eventNames());
        self::assertContains('liste_angelegt', $katalog->eventNames());
        self::assertSame([], $katalog->allowedFields('herz_gesetzt'));
        self::assertSame([], $katalog->allowedFields('liste_angelegt'));
    }

    /** AK-66 · Über die Weiterleitung: mit Datenfeld abgewiesen, ohne durchgelassen. */
    public function testAk66WeiterleitungVerwirftEreignisMitDatenfeld(): void
    {
        $ergebnis = $this->normalizer()->normalize(self::aufruf(['name' => 'liste_angelegt']));
        self::assertSame('liste_angelegt', $ergebnis['payload']['name']);
        self::assertArrayNotHasKey('data', $ergebnis['payload']);

        $this->expectException(InvalidCollectPayload::class);
        $this->expectExceptionMessage('event');
        $this->normalizer()->normalize(self::aufruf(['name' => 'liste_angelegt', 'data' => ['liste' => 'Geburtstag Oma']]));
    }

    /**
     * Pfad im Zählaufruf → Pfad, den Umami erhält.
     *
     * @return iterable<string, array{string, string}>
     */
    public static function pfade(): iterable
    {
        yield 'AK-67 Listenseite' => ['/de/gespeichert/listen/42', '/de/gespeichert/listen'];
        yield 'AK-67 Rückfrage zum Löschen' => ['/de/gespeichert/listen/42/loeschen', '/de/gespeichert/listen'];
        yield 'AK-67 Löschen in lb' => ['/lb/gespeichert/listen/7/loeschen', '/lb/gespeichert/listen'];
        yield 'AK-67 Umbenennen' => ['/en/gespeichert/listen/42/umbenennen', '/en/gespeichert/listen'];
        yield 'AK-67 Aus Liste entfernen' => ['/pt/gespeichert/listen/42/lokale/7/entfernen', '/pt/gespeichert/listen'];
        yield 'AK-67 absolut mit Seite und Anker' => ['https://endlech.lu/fr/gespeichert/listen/42?page=2#restaurant-3', '/fr/gespeichert/listen'];
        yield 'Übersicht unverändert' => ['/de/gespeichert', '/de/gespeichert'];
        yield 'Favoriten unverändert' => ['/de/gespeichert/favoriten', '/de/gespeichert/favoriten'];
        yield 'Anlegen ohne Nummer unverändert' => ['/de/gespeichert/listen', '/de/gespeichert/listen'];
        yield 'Detailseite behält ihre Nummer (Feature 11, AK-36)' => ['/de/restaurants/42', '/de/restaurants/42'];
    }

    #[DataProvider('pfade')]
    public function testAk67PfadImZaehlaufruf(string $eingang, string $erwartet): void
    {
        self::assertSame($erwartet, $this->normalizer()->normalize(self::aufruf(['url' => $eingang]))['payload']['url']);
    }

    /** AK-66, AK-67 · Ein Herz, gesetzt auf einer Listenseite, kommt an — mit gekürztem Pfad. */
    public function testAk67HerzAufListenseiteKommtMitGekuerztemPfadAn(): void
    {
        $ergebnis = $this->normalizer()->normalize(self::aufruf([
            'url' => 'https://endlech.lu/de/gespeichert/listen/42',
            'name' => 'herz_gesetzt',
        ]));

        self::assertSame('herz_gesetzt', $ergebnis['payload']['name']);
        self::assertSame('/de/gespeichert/listen', $ergebnis['payload']['url']);
        self::assertArrayNotHasKey('data', $ergebnis['payload']);
    }

    /**
     * AK-64 · Der Seitentitel einer Liste kann ihren Namen tragen — auf Listenseiten entfällt er,
     * gleich, was die Vorlage hineinschreibt. Anderswo bleibt er.
     */
    public function testAk64TitelDerListenseiteEntfaellt(): void
    {
        $ergebnis = $this->normalizer()->normalize(self::aufruf([
            'url' => '/de/gespeichert/listen/42/loeschen',
            'title' => 'Geburtstag Oma löschen? – Endlech.lu',
        ]));

        self::assertArrayNotHasKey('title', $ergebnis['payload']);
        self::assertStringNotContainsString('Oma', (string) json_encode($ergebnis));
        self::assertStringNotContainsString('/42', (string) json_encode($ergebnis));

        $uebersicht = $this->normalizer()->normalize(self::aufruf(['url' => '/de/gespeichert', 'title' => 'Gespeichert']));
        self::assertSame('Gespeichert', $uebersicht['payload']['title']);
    }

    /** AK-64 · Wer von einer Liste weiternavigiert, nimmt ihre Nummer nicht als Herkunft mit. */
    public function testAk64HerkunftAusEinerListeEntfaellt(): void
    {
        foreach (['/de/gespeichert/listen/42', 'https://endlech.lu/de/gespeichert/listen/42'] as $herkunft) {
            $ergebnis = $this->normalizer()->normalize(self::aufruf(['referrer' => $herkunft]));

            self::assertArrayNotHasKey('referrer', $ergebnis['payload'], $herkunft);
        }
    }

    /** Verwaltung und Profil werden weiterhin verworfen, nicht gekürzt (Feature 11, AK-06). */
    #[DataProvider('ausgenommen')]
    public function testAusgenommenePfadeBleibenVerworfen(string $pfad): void
    {
        $this->expectException(InvalidCollectPayload::class);
        $this->expectExceptionMessage('url');

        $this->normalizer()->normalize(self::aufruf(['url' => $pfad]));
    }

    /** @return iterable<string, array{string}> */
    public static function ausgenommen(): iterable
    {
        yield 'Profil' => ['/de/profile'];
        yield 'Verwaltung' => ['/de/admin/restaurants'];
        yield 'Token unter einer Liste (AK-07)' => ['/de/gespeichert/listen/42/'.str_repeat('ab', 32)];
    }

    /**
     * AK-67 · Der Browser kürzt nach demselben Muster wie der Server. Das Muster wird aus
     * `before_send.ts` gelesen — ein JavaScript-Literal ohne Flags ist zugleich ein gültiges
     * PCRE-Muster mit `/` als Begrenzer.
     */
    public function testAk67BrowserKuerztNachDerselbenRegel(): void
    {
        $quelle = (string) file_get_contents(self::VOR_VERSAND);
        self::assertSame(1, preg_match('#^const LISTENSEITE = (/.+/);$#m', $quelle, $treffer), 'before_send.ts kennt keine Konstante LISTENSEITE.');
        $muster = $treffer[1];

        foreach (self::pfade() as $fall => [$eingang, $erwartet]) {
            $pfad = (string) parse_url($eingang, \PHP_URL_PATH);
            $gekuerzt = 1 === preg_match($muster, $pfad, $teile);

            self::assertSame($erwartet !== $pfad, $gekuerzt, $fall);
            if ($gekuerzt) {
                self::assertSame($erwartet, '/'.$teile[1].'/gespeichert/listen', $fall);
            }
        }

        // Ersetzt wird durch den Pfad ohne Nummer, und der Titel fällt weg (AK-64).
        self::assertStringContainsString('/gespeichert/listen`', $quelle);
        self::assertMatchesRegularExpression('#delete \w+\.title;#', $quelle);
    }
}
