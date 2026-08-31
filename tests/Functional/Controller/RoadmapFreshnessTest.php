<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Roadmap\ChangelogRegistry;
use App\Roadmap\ChangelogSummary;
use App\Roadmap\ReleaseNote;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

/**
 * Der Aktualitätshinweis und die Jahresgruppierung (AK-27, AK-28, AK-22, EC-07).
 *
 * ⚠ **Direkt am Partial statt über die Seite.** Der jüngste Changelog-Eintrag steht
 * im Code; über die Seite ließe sich der 61-Tage-Fall nur herstellen, indem man die
 * Registry verfälscht. Das Partial nimmt das Datum als Parameter — damit ist beides
 * prüfbar, ohne an der Wahrheit zu drehen.
 */
final class RoadmapFreshnessTest extends KernelTestCase
{
    private function rendern(?\DateTimeImmutable $lastChange): string
    {
        self::bootKernel();
        $twig = self::getContainer()->get(Environment::class);

        return $twig->render('partials/_freshness.html.twig', ['lastChange' => $lastChange]);
    }

    /** AK-28: Ein zehn Tage alter Eintrag steht im grauen Kleingedruckten. */
    public function testEinFrischerStandWirdNichtHervorgehoben(): void
    {
        $html = $this->rendern(new \DateTimeImmutable('-10 days'));

        self::assertStringContainsString('text-gray-500', $html);
        self::assertStringNotContainsString('bg-amber-50', $html, 'Ein zehn Tage alter Stand darf nicht hervorgehoben werden.');
        self::assertStringNotContainsString('⚠️', $html);
    }

    /** AK-27: Ab 61 Tagen tritt der Hinweis aus dem Kleingedruckten heraus. */
    public function testEinVeralteterStandWirdHervorgehoben(): void
    {
        $html = $this->rendern(new \DateTimeImmutable('-61 days'));

        self::assertStringContainsString('bg-amber-50', $html, 'Ab 60 Tagen gehört der Hinweis in den hervorgehobenen Kasten.');
        self::assertStringContainsString('⚠️', $html);
        self::assertStringContainsString('61', $html, 'Die Zahl der Tage gehört in den Hinweis.');
    }

    /** Die Schwelle liegt bei genau 60 Tagen — 60 ist noch ruhig, 61 nicht. */
    public function testDieSchwelleLiegtBeiSechzigTagen(): void
    {
        self::assertStringNotContainsString('bg-amber-50', $this->rendern(new \DateTimeImmutable('-60 days')));
        self::assertStringContainsString('bg-amber-50', $this->rendern(new \DateTimeImmutable('-61 days')));
    }

    /** Ohne einen einzigen Eintrag steht ein eigener Satz, keine leere Zeile. */
    public function testOhneEintragStehtEinEigenerSatz(): void
    {
        $html = $this->rendern(null);

        self::assertNotSame('', trim(strip_tags($html)));
        self::assertStringNotContainsString('%date%', $html);
    }

    /**
     * AK-22, EC-07: Die Gruppierung nach Jahren stimmt, und die Sammelzeile hängt
     * am Ende ihres Jahres.
     */
    public function testDieGruppierungNachJahrenIstVollstaendig(): void
    {
        $registry = new ChangelogRegistry();
        $jahre = $registry->byYear();

        $gezeigt = \count(array_filter($registry->notes(), static fn (ReleaseNote $n): bool => $n->isShown()));
        $summe = array_sum(array_map('count', $jahre));

        self::assertSame($gezeigt + 1, $summe, 'Jeder gezeigte Eintrag plus die Sammelzeile muss in genau einem Jahr stehen.');

        foreach ($jahre as $jahr => $eintraege) {
            $vorher = null;
            foreach ($eintraege as $eintrag) {
                $datum = $eintrag instanceof ChangelogSummary ? $eintrag->date() : $eintrag->date;
                self::assertSame((string) $jahr, $datum->format('Y'), 'Ein Eintrag steht im falschen Jahr.');

                if (null !== $vorher) {
                    self::assertLessThanOrEqual($vorher->getTimestamp(), $datum->getTimestamp(), 'Innerhalb eines Jahres muss absteigend sortiert sein.');
                }
                $vorher = $datum;
            }

            $letzter = end($eintraege);
            self::assertInstanceOf(ChangelogSummary::class, $letzter, 'Die Sammelzeile muss die älteste Zeile ihres Jahres sein.');
        }
    }

    /**
     * EC-07: Über den Jahreswechsel hinweg klappt das vorherige Jahr zu — ohne dass
     * jemand etwas ändert.
     */
    #[DataProvider('jahre')]
    public function testDasLaufendeJahrIstOffenDasFruehereZugeklappt(string $laufendesJahr, int $erwarteteDetails): void
    {
        self::bootKernel();

        // Das Layout liest `app.request` (Sprache, hreflang). Ohne Request im
        // Stack scheitert schon Zeile 2 von base.html.twig — deshalb einer, der
        // die Route mitbringt, die der Controller auch setzen würde.
        $request = Request::create('/de/changelog');
        $request->setLocale('de');
        $request->attributes->set('_route', 'app_changelog_index');
        $request->attributes->set('_route_params', ['_locale' => 'de']);
        self::getContainer()->get('request_stack')->push($request);

        $twig = self::getContainer()->get(Environment::class);
        $registry = new ChangelogRegistry();

        $html = $twig->render('roadmap/changelog.html.twig', [
            'years' => $registry->byYear(),
            'currentYear' => $laufendesJahr,
            'lastChange' => $registry->latestShownDate(),
        ]);

        // ⚠ Nur der Inhaltsbereich: Die Kopfzeile der App-Hülle führt selbst ein
        // <details> (Navigations-Dropdown). Wer die ganze Seite zählt, misst es mit.
        $imInhalt = (new Crawler($html))->filter('main details')->count();

        self::assertSame(
            $erwarteteDetails,
            $imInhalt,
            sprintf('Bei laufendem Jahr %s wird falsch auf- oder zugeklappt.', $laufendesJahr),
        );
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function jahre(): iterable
    {
        // 2026 ist das einzige Jahr mit Einträgen: offen, kein <details>.
        yield 'laufendes Jahr 2026' => ['2026', 0];
        // Ein Jahr später ist 2026 Geschichte und klappt zu.
        yield 'laufendes Jahr 2027' => ['2027', 1];
    }
}
