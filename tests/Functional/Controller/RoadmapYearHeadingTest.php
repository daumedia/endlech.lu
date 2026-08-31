<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Roadmap\ChangelogSummary;
use App\Roadmap\ReleaseNote;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

/**
 * BF-115: Ein zugeklapptes Jahr trägt keine Überschrift (AK-38, WCAG 1.3.1).
 *
 * ⚠ **Der Fehler ist heute unsichtbar und tritt sicher ein.** Solange die Registry nur
 * ein Jahr führt, gibt es kein `<details>` — und damit keinen Sprung. Mit dem ersten
 * Release in einem neuen Jahr wird das bisherige zum „früheren Jahr", rutscht in ein
 * `<details>` und verliert seine `<h2>`: Die Einträge darin tragen `h3`, die Kette
 * springt von `h1` auf `h3`.
 *
 * Ein `<summary>` ist für Screenreader **keine Überschrift**. Das laufende Jahr bekommt
 * eine `<h2 id="year-…">`, ein zugeklapptes nur den Text im `<summary>`.
 *
 * Angelegt von der Qualitätssicherung am 2026-08-30, nachdem eine Vollbetriebs-Probe
 * (zwei Jahre in der Registry) den Fall hergestellt hat.
 */
final class RoadmapYearHeadingTest extends KernelTestCase
{
    /**
     * Rendert die Changelog-Seite mit einem frei wählbaren „laufenden Jahr".
     *
     * Damit lässt sich der Zustand herstellen, den der Kalender sonst erst 2027 liefert.
     */
    private function seite(string $laufendesJahr): Crawler
    {
        self::bootKernel();

        $request = Request::create('/de/changelog');
        $request->setLocale('de');
        $request->attributes->set('_route', 'app_changelog_index');
        $request->attributes->set('_route_params', ['_locale' => 'de']);
        self::getContainer()->get('request_stack')->push($request);

        $registry = new \App\Roadmap\ChangelogRegistry();

        return new Crawler(self::getContainer()->get(Environment::class)->render('roadmap/changelog.html.twig', [
            'years' => $registry->byYear(),
            'currentYear' => $laufendesJahr,
            'lastChange' => $registry->latestShownDate(),
        ]));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function jahreslagen(): iterable
    {
        yield 'alle Jahre offen' => ['2026'];
        // Der Fall, den der Kalender erzwingt: Das bisherige Jahr klappt zu.
        yield 'jedes Jahr zugeklappt' => ['2099'];
    }

    /**
     * AK-38: Auch ein zugeklapptes Jahr muss eine Überschrift tragen.
     *
     * ⚠ **Dieser Lauf ist heute rot** und bleibt es, bis das `<details>` eine eigene
     * Überschrift bekommt. Er steht hier, damit der Fehler nicht erst im Januar 2027
     * auffällt — dann nämlich an einer Seite, die längst live ist.
     */
    #[DataProvider('jahreslagen')]
    public function testJedesJahrTraegtEineUeberschrift(string $laufendesJahr): void
    {
        $crawler = $this->seite($laufendesJahr);

        // ⚠ Gefragt ist die Überschrift **des Jahres**, also im <summary> selbst — nicht
        // irgendeine im Abschnitt. Die Artikel darin tragen ohnehin `h3`; wer nur auf
        // deren Vorhandensein prüft, hält den Fehler für behoben, solange ein einziger
        // Eintrag im Jahr steht.
        $ohneUeberschrift = [];
        foreach ($crawler->filter('main details') as $details) {
            $summary = (new Crawler($details))->filter('summary');
            if (0 === $summary->count()) {
                $ohneUeberschrift[] = '(ohne <summary>)';

                continue;
            }
            if (0 === $summary->filter('h1, h2, h3, h4, h5, h6')->count()) {
                $ohneUeberschrift[] = trim($summary->text());
            }
        }

        self::assertSame([], $ohneUeberschrift, sprintf(
            "%d zugeklappte(s) Jahr(e) tragen keine Überschrift, nur ein <summary> — für einen "
            ."Screenreader ist das keine Gliederung, und die Einträge darin (h3) hängen an keiner "
            ."übergeordneten Ebene (BF-115):\n  %s",
            \count($ohneUeberschrift),
            implode("\n  ", $ohneUeberschrift),
        ));
    }

    /**
     * AK-38: Die Überschriftenkette des Inhaltsbereichs bleibt lückenlos — auch wenn
     * Jahre zugeklappt sind.
     */
    #[DataProvider('jahreslagen')]
    public function testDieUeberschriftenketteBleibtLueckenlos(string $laufendesJahr): void
    {
        // ⚠ Nur der Inhaltsbereich: Die Fußzeile der App-Hülle überschreibt ihre
        // Spalten mit <h4> und bricht die Kette auf **jeder** Seite des Projekts
        // (BF-109). Wer die ganze Seite misst, misst diesen Altbestand mit und sieht
        // den eigenen Fehler nicht mehr.
        $ebenen = $this->seite($laufendesJahr)->filter('main h1, main h2, main h3, main h4, main h5, main h6')->each(
            static fn (Crawler $n): int => (int) substr($n->nodeName(), 1),
        );

        $vorher = 0;
        foreach ($ebenen as $index => $ebene) {
            if ($ebene > $vorher) {
                self::assertSame($vorher + 1, $ebene, sprintf(
                    'Bei laufendem Jahr %s springt Überschrift %d von h%d auf h%d.',
                    $laufendesJahr,
                    $index + 1,
                    $vorher,
                    $ebene,
                ));
            }
            $vorher = $ebene;
        }
    }

    /** Die Sammelzeile bleibt Teil der Gruppierung, egal in welcher Lage. */
    public function testDieSammelzeileBleibtErhalten(): void
    {
        $registry = new \App\Roadmap\ChangelogRegistry();
        $alle = array_merge(...array_values($registry->byYear()));

        $summaries = array_filter($alle, static fn (ReleaseNote|ChangelogSummary $e): bool => $e instanceof ChangelogSummary);
        self::assertCount(1, $summaries, 'Es darf genau eine Sammelzeile geben.');
    }
}
