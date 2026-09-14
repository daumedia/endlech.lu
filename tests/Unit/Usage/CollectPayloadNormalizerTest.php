<?php

namespace App\Tests\Unit\Usage;

use App\Usage\CollectPayloadNormalizer;
use App\Usage\InvalidCollectPayload;
use App\Usage\UsageEventCatalogue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Feature 11 · Die Weiterleitung prüft und kürzt jeden Zählaufruf. Je Regel ein Gutfall und ein
 * Verstoß — ein Prüflauf, der nur Gutfälle kennt, bliebe grün, wenn die Regel fehlte.
 */
final class CollectPayloadNormalizerTest extends TestCase
{
    private const string KENNUNG = '00000000-0000-4000-8000-000000000011';

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
        $payload = [
            'website' => self::KENNUNG,
            'hostname' => 'endlech.lu',
            'url' => 'https://endlech.lu/de/restaurants',
            'screen' => '390x844',
            'language' => 'de-LU',
            'title' => 'Restaurants',
            'referrer' => '',
            ...$abweichung,
        ];

        return ['type' => 'event', 'payload' => array_filter($payload, static fn ($w) => null !== $w)];
    }

    public function testGutfallSeitenaufruf(): void
    {
        $ergebnis = $this->normalizer()->normalize(self::aufruf());

        self::assertSame(['type' => 'event', 'payload' => [
            'website' => self::KENNUNG,
            'hostname' => 'endlech.lu',
            'url' => '/de/restaurants',
            'title' => 'Restaurants',
            'screen' => '390x844',
            'language' => 'de-LU',
        ]], $ergebnis);
    }

    /** AK-03 · Abfrage und Anker verschwinden samt Wert — „Esch" erreicht Umami nicht. */
    public function testAbfrageUndAnkerEntfallen(): void
    {
        $ergebnis = $this->normalizer()->normalize(self::aufruf(['url' => 'https://endlech.lu/de/restaurants?city=Esch&wheelchair=1&page=2#liste']));

        self::assertSame('/de/restaurants', $ergebnis['payload']['url']);
        self::assertStringNotContainsString('Esch', (string) json_encode($ergebnis));
    }

    /** AK-36 · Der Pfad der Detailseite bleibt — erkennbar ist, welches Restaurant. */
    public function testDetailseiteBehaeltIhreNummer(): void
    {
        self::assertSame('/fr/restaurants/7', $this->normalizer()->normalize(self::aufruf(['url' => '/fr/restaurants/7']))['payload']['url']);
    }

    /** @return iterable<string, array{string, string}> */
    public static function herkunft(): iterable
    {
        yield 'Google mit Pfad und Abfrage' => ['https://www.google.com/search?q=barrierefrei+esch', 'https://www.google.com/'];
        yield 'Webmail mit Nachrichtenkennung' => ['https://mail.example.lu/inbox/12345?mid=abc', 'https://mail.example.lu/'];
        yield 'Großschreibung' => ['HTTPS://Example.LU/Pfad', 'https://example.lu/'];
    }

    /** AK-02 · Fremde Herkunft nur als Domain. */
    #[DataProvider('herkunft')]
    public function testFremdeHerkunftNurAlsDomain(string $eingang, string $erwartet): void
    {
        self::assertSame($erwartet, $this->normalizer()->normalize(self::aufruf(['referrer' => $eingang]))['payload']['referrer']);
    }

    /** @return iterable<string, array{string}> */
    public static function herkunftEntfaellt(): iterable
    {
        yield 'eigene Seite' => ['https://endlech.lu/de/'];
        yield 'eigene Seite über www' => ['https://www.endlech.lu/de/'];
        yield 'relativer Pfad' => ['/de/about'];
        yield 'fremdes Schema' => ['android-app://com.google.android.gm/'];
        yield 'kein Wert' => [''];
    }

    #[DataProvider('herkunftEntfaellt')]
    public function testHerkunftEntfaellt(string $eingang): void
    {
        self::assertArrayNotHasKey('referrer', $this->normalizer()->normalize(self::aufruf(['referrer' => $eingang]))['payload']);
    }

    /** AK-21 · Umamis Nutzerkennung und Markierung werden nie übernommen. */
    public function testKennungUndMarkierungEntfallen(): void
    {
        $ergebnis = $this->normalizer()->normalize(self::aufruf(['id' => 'user-42@example.lu', 'tag' => 'konto-42', 'unbekannt' => 'x']));

        self::assertSame([], array_intersect(['id', 'tag', 'unbekannt'], array_keys($ergebnis['payload'])));
    }

    public function testTitelWirdGekuerztUndUngueltigeAngabenEntfallen(): void
    {
        $ergebnis = $this->normalizer()->normalize(self::aufruf([
            'title' => str_repeat('ä', 300),
            'screen' => '<script>',
            'language' => 'de; DROP TABLE',
        ]));

        self::assertSame(CollectPayloadNormalizer::MAX_TITLE, mb_strlen($ergebnis['payload']['title']));
        self::assertArrayNotHasKey('screen', $ergebnis['payload']);
        self::assertArrayNotHasKey('language', $ergebnis['payload']);
    }

    public function testErlaubtesEreignisMitDaten(): void
    {
        $ergebnis = $this->normalizer()->normalize(self::aufruf(['name' => 'kontaktweg_genutzt', 'data' => ['art' => 'telefon']]));

        self::assertSame('kontaktweg_genutzt', $ergebnis['payload']['name']);
        self::assertSame(['art' => 'telefon'], $ergebnis['payload']['data']);
    }

    public function testEreignisOhneDaten(): void
    {
        $ergebnis = $this->normalizer()->normalize(self::aufruf(['name' => 'presse_kit_geladen']));

        self::assertSame('presse_kit_geladen', $ergebnis['payload']['name']);
        self::assertArrayNotHasKey('data', $ergebnis['payload']);
    }

    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function verstoesse(): iterable
    {
        yield 'Typ identify' => [['type' => 'identify'], 'type'];
        yield 'fremde Website-Kennung' => [['payload' => ['website' => '11111111-1111-4111-8111-111111111111']], 'website'];
        yield 'www (AK-09)' => [['payload' => ['hostname' => 'www.endlech.lu']], 'hostname'];
        yield 'localhost (AK-09)' => [['payload' => ['hostname' => 'localhost']], 'hostname'];
        yield 'Adresse auf fremdem Host' => [['payload' => ['url' => 'https://evil.example/de/restaurants']], 'url'];
        yield 'Verwaltung (AK-06)' => [['payload' => ['url' => '/de/admin/restaurants']], 'url'];
        yield 'Profil (AK-06)' => [['payload' => ['url' => 'https://endlech.lu/lb/profile']], 'url'];
        yield 'Token-Link (AK-07)' => [['payload' => ['url' => '/de/verify/'.str_repeat('ab', 32)]], 'url'];
        yield 'Token in erfundenem Pfad (AK-07)' => [['payload' => ['url' => '/de/irgendwas/'.str_repeat('f0', 32)]], 'url'];
        yield 'ohne Adresse' => [['payload' => ['url' => '']], 'url'];
        yield 'relativer Pfad ohne Schrägstrich' => [['payload' => ['url' => 'de/restaurants']], 'url'];
        yield 'unbekanntes Ereignis' => [['payload' => ['name' => 'newsletter_abonniert']], 'event'];
        yield 'E-Mail in Ereignisdaten (AK-17)' => [['payload' => ['name' => 'warteliste_eingetragen', 'data' => ['liste' => 'app', 'email' => 'a@b.lu']]], 'event'];
        yield 'Ortstext im Filter (AK-13)' => [['payload' => ['name' => 'filter_angewandt', 'data' => ['filter' => 'Esch']]], 'event'];
        yield 'Daten am Seitenaufruf' => [['payload' => ['data' => ['art' => 'telefon']]], 'data'];
        yield 'Name kein Text' => [['payload' => ['name' => ['x']]], 'name'];
    }

    /** @param array<string, mixed> $abweichung */
    #[DataProvider('verstoesse')]
    public function testVerstossWirdAbgewiesen(array $abweichung, string $regel): void
    {
        $aufruf = self::aufruf($abweichung['payload'] ?? []);
        if (isset($abweichung['type'])) {
            $aufruf['type'] = $abweichung['type'];
        }

        try {
            $this->normalizer()->normalize($aufruf);
            self::fail('Der Zählaufruf hätte abgewiesen werden müssen.');
        } catch (InvalidCollectPayload $fehler) {
            self::assertSame($regel, $fehler->getMessage());
        }
    }

    /** Die Meldung nennt die Regel, nie den Wert — sonst stünde der Ortstext in einer Fehlermeldung. */
    public function testMeldungEnthaeltKeinenEingangswert(): void
    {
        try {
            $this->normalizer()->normalize(self::aufruf(['url' => '/de/verify/'.str_repeat('ab', 32)]));
        } catch (InvalidCollectPayload $fehler) {
            self::assertStringNotContainsString('abab', $fehler->getMessage());

            return;
        }
        self::fail('Erwartet: InvalidCollectPayload');
    }

    public function testOhneKonfigurierteKennungWirdNichtsDurchgelassen(): void
    {
        $this->expectException(InvalidCollectPayload::class);

        (new CollectPayloadNormalizer(new UsageEventCatalogue(), '', 'https://endlech.lu'))->normalize(self::aufruf(['website' => '']));
    }
}
