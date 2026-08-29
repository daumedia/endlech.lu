<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Comparison\Competitor;
use App\Tests\AbstractWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Randfälle der Vergleichsseiten (Feature 03, EC-02 bis EC-07).
 *
 * EC-01 (abweichende Schreibweise) steht in ComparisonControllerTest, weil er
 * dort zum Zugriffsverhalten gehört.
 */
final class ComparisonEdgeCaseTest extends AbstractWebTestCase
{
    /** @return iterable<string, array{string}> */
    public static function alleSeiten(): iterable
    {
        yield 'index' => ['/vergleich'];
        foreach (Competitor::cases() as $case) {
            yield $case->value => ['/vergleich/'.$case->slug()];
        }
    }

    /**
     * EC-03: Die Seiten sind ohne JavaScript vollständig lesbar.
     *
     * Prüfbar gemacht als: Es gibt nichts, das JavaScript bräuchte. Kein
     * Stimulus-Controller, kein Aufklapp-Mechanismus in Skript — die häufigen
     * Fragen sind <details>, und das arbeitet ohne.
     */
    #[DataProvider('alleSeiten')]
    public function testKeinJavaScriptNoetig(string $pfad): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.$pfad);

        self::assertResponseIsSuccessful();

        $main = $crawler->filter('main');
        self::assertSame(0, $main->filter('[data-controller]')->count(), 'Die Seite hängt an einem Stimulus-Controller.');
        self::assertSame(0, $main->filter('script')->count(), 'Inline-Skript im Hauptinhalt.');
        // Die Übersicht ist naturgemäß kürzer als eine Vergleichsseite — vier
        // Karten und ein Methodikabsatz gegen Tabelle, Fußnoten und Fragen.
        $mindestens = '/vergleich' === $pfad ? 800 : 3000;
        self::assertGreaterThan(
            $mindestens,
            mb_strlen($main->text()),
            'Ohne JavaScript bleibt zu wenig Text übrig.',
        );
    }

    /**
     * EC-04: Das Farbband des Kopfbereichs druckt nicht weiß auf weiß.
     *
     * Der @media-print-Block in assets/styles/app.css greift auf den Selektor
     * `section.bg-linear-to-r`. Ein Kopfband als <div> — wie auf der
     * Barrierefreiheitsseite — wird davon nicht erfasst.
     */
    #[DataProvider('alleSeiten')]
    public function testDasKopfbandIstEinSectionElement(string $pfad): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.$pfad);

        self::assertGreaterThan(
            0,
            $crawler->filter('section.bg-linear-to-r')->count(),
            'Kein <section> mit Verlaufsband — im Druck bliebe der Text weiß auf weiß.',
        );
    }

    /**
     * EC-06: Der erste Aufruf nach dem Ausliefern findet einen leeren
     * Zwischenspeicher vor und muss trotzdem eine vollständige Seite liefern.
     */
    #[DataProvider('alleSeiten')]
    public function testErsterAufrufOhneZwischenspeicher(string $pfad): void
    {
        $client = static::createClient();
        static::getContainer()->get('cache.open_stats')->clear();

        $client->request('GET', self::LOCALE.$pfad);

        self::assertResponseIsSuccessful();
    }

    /**
     * EC-02: Die Abdeckungszeile trägt eine Zahl, auch wenn es keine wäre.
     *
     * Geprüft wird die Stelle, an der eine Null durchschlagen würde: Die Zeile
     * darf nie leer bleiben und nie den rohen Platzhalter zeigen.
     */
    #[DataProvider('alleSeiten')]
    public function testDieAbdeckungszeileZeigtEineZahlKeinenPlatzhalter(string $pfad): void
    {
        if ('/vergleich' === $pfad) {
            self::markTestSkipped('Die Übersicht trägt keine Merkmalstabelle.');
        }

        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.$pfad);
        $text = $crawler->filter('table')->text();

        self::assertStringNotContainsString('%figure%', $text, 'Der Platzhalter steht ungefüllt auf der Seite.');
        self::assertMatchesRegularExpression('/\d/', $text, 'In der Tabelle steht keine einzige Zahl.');
    }

    /**
     * EC-05: Die Tabelle scrollt in ihrem eigenen Bereich, nicht die Seite.
     *
     * AK-25 verlangt, dass bei 320 px keine waagerechte Scrollleiste für die
     * Seite entsteht. Das ist im Test nicht messbar — prüfbar ist die Bedingung
     * dafür: Der Scrollbereich existiert, ist per Tastatur erreichbar und trägt
     * eine Beschriftung.
     */
    #[DataProvider('alleSeiten')]
    public function testDerScrollbereichDerTabelleIstBedienbar(string $pfad): void
    {
        if ('/vergleich' === $pfad) {
            self::markTestSkipped('Die Übersicht trägt keine Merkmalstabelle.');
        }

        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.$pfad);

        $bereich = $crawler->filter('div.overflow-x-auto[role="region"]');
        self::assertCount(1, $bereich, 'Kein eigener Scrollbereich um die Tabelle.');
        self::assertSame('0', $bereich->attr('tabindex'), 'Der Scrollbereich ist per Tastatur nicht erreichbar.');
        self::assertNotSame('', trim((string) $bereich->attr('aria-label')), 'Der Scrollbereich trägt keine Beschriftung.');
        self::assertCount(1, $crawler->filter('table caption.sr-only'), 'Die Tabelle hat keine Beschriftung.');
    }

    /**
     * BF-77: Der Merkmalsvergleich hat zwei Darstellungen, und genau eine ist je
     * Breite sichtbar.
     *
     * Die Tabelle trägt in beiden Wertspalten einen erklärenden Halbsatz (AK-09)
     * und wird damit 525 px breit. Bei 320 px scrollte deshalb die ganze Seite
     * waagerecht (`scrollX=212`) — AK-25 verletzt. Ein erzwungener Umbruch behob
     * das Scrollen, zerlegte aber Wörter mitten im Wort.
     *
     * Die Breite selbst lässt sich hier nicht messen; geprüft wird die Bedingung,
     * die sie herstellt: Beide Darstellungen sind da, sie schließen sich über
     * `hidden md:block` und `md:hidden` gegenseitig aus, und beide tragen
     * dieselben Merkmale.
     */
    #[DataProvider('alleSeiten')]
    public function testZweiDarstellungenDieSichAusschliessen(string $pfad): void
    {
        if ('/vergleich' === $pfad) {
            self::markTestSkipped('Die Übersicht trägt keinen Merkmalsvergleich.');
        }

        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.$pfad);

        $tabelle = $crawler->filter('div.overflow-x-auto[role="region"]');
        self::assertCount(1, $tabelle, 'Keine Tabellendarstellung.');
        self::assertStringContainsString('hidden', (string) $tabelle->attr('class'), 'Die Tabelle ist auf schmalen Anzeigen nicht ausgeblendet.');
        self::assertStringContainsString('md:block', (string) $tabelle->attr('class'), 'Die Tabelle erscheint ab md: nicht.');

        $karten = $crawler->filter('div.md\:hidden');
        self::assertGreaterThan(0, $karten->count(), 'Keine Kartendarstellung für schmale Anzeigen.');

        // Beide zeigen dieselben Merkmale — sonst fehlt auf einer Breite etwas.
        $inTabelle = $crawler->filter('table th[scope="row"]')->each(static fn ($n): string => trim($n->text()));
        $inKarten = $crawler->filter('div.md\:hidden li > p')->each(static fn ($n): string => trim($n->text()));
        sort($inTabelle);
        sort($inKarten);

        self::assertNotEmpty($inTabelle);
        self::assertSame($inTabelle, $inKarten, 'Tabelle und Karten zeigen verschiedene Merkmale.');
    }

    /**
     * BF-77: Auch die Kartendarstellung sagt Ja/Nein/Teilweise als Text an.
     *
     * Ohne diese Prüfung könnte die Screenreader-Mechanik in einer der beiden
     * Darstellungen verlorengehen, ohne dass es auffällt — sichtbar ist je Breite
     * immer nur eine.
     */
    #[DataProvider('alleSeiten')]
    public function testAuchDieKartenSagenDieBewertungAn(string $pfad): void
    {
        if ('/vergleich' === $pfad) {
            self::markTestSkipped('Die Übersicht trägt keinen Merkmalsvergleich.');
        }

        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.$pfad);
        $werte = $crawler->filter('div.md\:hidden dd');

        self::assertGreaterThan(0, $werte->count());

        foreach ($werte as $wert) {
            $html = $wert->ownerDocument->saveHTML($wert);
            self::assertMatchesRegularExpression(
                '/class="sr-only">(Ja|Nein|Teilweise)</',
                (string) $html,
                'Kartenwert ohne Ansage für Screenreader.',
            );
        }
    }

    /**
     * BF-79: Die beiden Landmarks im Hauptinhalt tragen verschiedene Namen.
     *
     * Vorher hießen der Brotkrümelpfad und die Querverweisliste beide „Weitere
     * Vergleiche" — wer per Landmark navigierte, landete im Brotkrümelpfad.
     */
    #[DataProvider('alleSeiten')]
    public function testLandmarksTragenVerschiedeneNamen(string $pfad): void
    {
        if ('/vergleich' === $pfad) {
            self::markTestSkipped('Die Übersicht trägt nur eine Navigation.');
        }

        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.$pfad);

        $namen = [];

        foreach ($crawler->filter('main nav') as $nav) {
            $label = $nav->getAttribute('aria-label');

            if ('' === $label) {
                $id = $nav->getAttribute('aria-labelledby');
                $label = '' === $id ? '' : trim($crawler->filter('#'.$id)->text());
            }

            self::assertNotSame('', $label, 'Ein Navigationsbereich trägt keinen Namen.');
            $namen[] = $label;
        }

        self::assertGreaterThanOrEqual(2, \count($namen), 'Weniger als zwei Navigationsbereiche gefunden.');
        self::assertSame(\count($namen), \count(array_unique($namen)), 'Zwei Navigationsbereiche heißen gleich: '.implode(' / ', $namen));
    }

    /**
     * Jede Fußnote in der Tabelle führt zu einer Quelle, die es auf der Seite gibt.
     *
     * Ein Verweis auf [4], wo nur drei Quellen stehen, wäre für einen Leser eine
     * Sackgasse und für die Belegpflicht (AK-12) eine Lücke.
     */
    #[DataProvider('alleSeiten')]
    public function testJedeFussnoteFuehrtZuEinerQuelle(string $pfad): void
    {
        if ('/vergleich' === $pfad) {
            self::markTestSkipped('Die Übersicht trägt keine Fußnoten.');
        }

        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.$pfad);

        $ziele = $crawler->filter('[id^="quelle-"]')->each(static fn ($n): string => (string) $n->attr('id'));
        self::assertNotEmpty($ziele, 'Keine einzige Quelle auf der Seite.');

        foreach ($crawler->filter('a[href^="#quelle-"]')->each(static fn ($n): string => (string) $n->attr('href')) as $href) {
            self::assertContains(ltrim($href, '#'), $ziele, 'Fußnote '.$href.' führt ins Leere.');
        }
    }

    /**
     * AK-12: **Beide** Darstellungen belegen ihre Aussagen.
     *
     * ⚠ In der QA vom 2026-08-29 gefunden: Der Test oben filterte auf
     * `table a[href^="#quelle-"]` und sah damit nur die Tabelle. Nachgestellt —
     * `source: row.sourceRef` in `_cards.html.twig` auf `null` gesetzt: Die
     * Kartendarstellung verlor alle 18 Fußnotenlinks, und **alle 606 Tests
     * blieben grün**. Auf einer schmalen Anzeige stünden die Aussagen über den
     * Wettbewerber dann unbelegt da — genau das, was AK-12 verhindern soll.
     */
    #[DataProvider('alleSeiten')]
    public function testBeideDarstellungenBelegenIhreAussagen(string $pfad): void
    {
        if ('/vergleich' === $pfad) {
            self::markTestSkipped('Die Übersicht trägt keine Fußnoten.');
        }

        $client = static::createClient();
        $crawler = $client->request('GET', self::LOCALE.$pfad);

        $inTabelle = $crawler->filter('table a[href^="#quelle-"]')
            ->each(static fn ($n): string => (string) $n->attr('href'));
        $inKarten = $crawler->filter('div.md\:hidden a[href^="#quelle-"]')
            ->each(static fn ($n): string => (string) $n->attr('href'));

        self::assertNotEmpty($inTabelle, 'Die Tabelle belegt keine einzige Aussage.');
        self::assertNotEmpty($inKarten, 'Die Kartendarstellung belegt keine einzige Aussage.');

        sort($inTabelle);
        sort($inKarten);

        self::assertSame(
            $inTabelle,
            $inKarten,
            'Tabelle und Karten verweisen auf verschiedene Quellen — eine Breite zeigt unbelegte Aussagen.',
        );
    }
}
