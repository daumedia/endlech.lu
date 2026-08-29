<?php

declare(strict_types=1);

namespace App\Tests\Unit\Comparison;

use App\Comparison\ComparisonGroup;
use App\Comparison\ComparisonPage;
use App\Comparison\ComparisonRegistry;
use App\Comparison\ComparisonRow;
use App\Comparison\Competitor;
use App\Comparison\Verdict;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Struktur der Vergleichsseiten (Feature 03).
 *
 * Die Spec verspricht Eigenschaften, die man einer Vergleichsseite nicht ansieht:
 * mindestens drei Punkte, in denen der Wettbewerber besser ist; eine Quelle mit
 * Datum hinter jeder Aussage über ihn; einen erklärenden Halbsatz in jeder Zelle.
 * Ohne diesen Prüflauf verschwindet all das beim ersten Mal, an dem jemand unter
 * Zeitdruck eine fünfte Seite anlegt.
 *
 * ⚠ Der schärfste Test ist `testJedeAussageUeberDenWettbewerberHatEineQuelle`.
 * Eine unbelegte Behauptung über ein fremdes Unternehmen ist der teuerste Fehler,
 * den dieses Feature machen kann — teurer als eine Tabelle mit drei Zeilen weniger.
 */
final class ComparisonRegistryTest extends TestCase
{
    /** @return iterable<string, array{Competitor}> */
    public static function competitors(): iterable
    {
        foreach (Competitor::cases() as $case) {
            yield $case->value => [$case];
        }
    }

    #[DataProvider('competitors')]
    public function testJederVergleichIstErfasst(Competitor $competitor): void
    {
        $page = (new ComparisonRegistry())->page($competitor);

        self::assertSame($competitor, $page->competitor);
        self::assertNotEmpty($page->rows);
    }

    /**
     * AK-08: Alle vier Gruppen sind belegt.
     *
     * Die dritte Gruppe ist die, in der Endlech.lu gegen jeden der vier verliert.
     * Sie wegzulassen wäre die naheliegendste Art, die Tabelle unehrlich zu machen —
     * deshalb wird gerade hier gezählt.
     */
    #[DataProvider('competitors')]
    public function testAlleVierGruppenSindBelegt(Competitor $competitor): void
    {
        $page = (new ComparisonRegistry())->page($competitor);

        foreach (ComparisonGroup::cases() as $group) {
            self::assertNotEmpty(
                $page->rowsIn($group),
                sprintf('%s: Gruppe %s hat keine Zeile.', $competitor->value, $group->value),
            );
        }
    }

    /** AK-09: Kein leerer Halbsatz — ein Symbol allein ist eine Behauptung. */
    #[DataProvider('competitors')]
    public function testJedeZelleHatEinenErklaerendenHalbsatz(Competitor $competitor): void
    {
        foreach ((new ComparisonRegistry())->page($competitor)->rows as $row) {
            self::assertNotSame('', trim($row->labelKey), $competitor->value.': Zeile ohne Bezeichnung.');
            self::assertNotSame('', trim($row->ownNoteKey), $competitor->value.': '.$row->labelKey.' ohne eigenen Halbsatz.');
            self::assertNotSame('', trim($row->theirNoteKey), $competitor->value.': '.$row->labelKey.' ohne Halbsatz zum Wettbewerber.');
        }
    }

    /**
     * AK-12: Jede Aussage über den Wettbewerber trägt eine auflösbare Fußnote,
     * und jede Fußnote hat eine Adresse und ein Prüfdatum.
     */
    #[DataProvider('competitors')]
    public function testJedeAussageUeberDenWettbewerberHatEineQuelle(Competitor $competitor): void
    {
        $page = (new ComparisonRegistry())->page($competitor);

        foreach ($page->rows as $row) {
            self::assertNotNull(
                $row->sourceRef,
                sprintf('%s: Zeile "%s" trifft eine Aussage über den Wettbewerber, nennt aber keine Quelle.', $competitor->value, $row->labelKey),
            );
            self::assertNotNull(
                $page->source($row->sourceRef),
                sprintf('%s: Zeile "%s" verweist auf Fußnote %d, die es nicht gibt.', $competitor->value, $row->labelKey, $row->sourceRef),
            );
        }

        self::assertNotEmpty($page->sources, $competitor->value.': keine einzige Quelle.');

        foreach ($page->sources as $source) {
            self::assertStringStartsWith('https://', $source->url, $competitor->value.': Quelle '.$source->ref.' ohne sichere Adresse.');
            self::assertNotSame('', trim($source->labelKey), $competitor->value.': Quelle '.$source->ref.' ohne Bezeichnung.');
            self::assertLessThanOrEqual(
                (int) (new \DateTimeImmutable('today'))->format('U'),
                (int) $source->checkedAt->format('U'),
                $competitor->value.': Quelle '.$source->ref.' trägt ein Prüfdatum in der Zukunft.',
            );
        }
    }

    /** Eine Fußnote, auf die keine Zeile verweist, ist entweder Rest oder ein Versehen. */
    #[DataProvider('competitors')]
    public function testKeineUnbenutzteQuelle(Competitor $competitor): void
    {
        $page = (new ComparisonRegistry())->page($competitor);
        $benutzt = array_unique(array_filter(array_map(static fn (ComparisonRow $r): ?int => $r->sourceRef, $page->rows)));

        foreach ($page->sources as $source) {
            self::assertContains(
                $source->ref,
                $benutzt,
                sprintf('%s: Fußnote %d wird von keiner Zeile benutzt.', $competitor->value, $source->ref),
            );
        }
    }

    /**
     * AK-10: Mindestens drei Punkte, in denen der Wettbewerber besser ist.
     *
     * Das ist der Abschnitt, der die Seite glaubwürdig macht. Ohne ihn ist sie
     * Werbung, und Werbung liest niemand als Auskunft.
     */
    #[DataProvider('competitors')]
    public function testMindestensDreiVorteileDesWettbewerbers(Competitor $competitor): void
    {
        $page = (new ComparisonRegistry())->page($competitor);

        self::assertGreaterThanOrEqual(
            3,
            \count($page->advantageKeys),
            sprintf('%s: nur %d Vorteile des Wettbewerbers genannt.', $competitor->value, \count($page->advantageKeys)),
        );
    }

    /** AK-11: Mindestens vier häufige Fragen. */
    #[DataProvider('competitors')]
    public function testMindestensVierFragen(Competitor $competitor): void
    {
        $page = (new ComparisonRegistry())->page($competitor);

        self::assertGreaterThanOrEqual(4, \count($page->faqKeys), $competitor->value.': zu wenige Fragen.');
    }

    /**
     * AK-13: Die Abdeckungszeile steht in jeder Tabelle und trägt die eigene Zahl.
     *
     * Sie ist die Zeile, in der Endlech.lu gegen jeden der vier verliert. Genau
     * deshalb ist ihr Fehlen ein Testfehler und keine Geschmacksfrage.
     */
    #[DataProvider('competitors')]
    public function testDieAbdeckungszeileNenntDieEigeneZahl(Competitor $competitor): void
    {
        $page = (new ComparisonRegistry())->page($competitor);
        $zeilen = array_filter(
            $page->rowsIn(ComparisonGroup::COVERAGE),
            static fn (ComparisonRow $r): bool => 'restaurants' === $r->figure,
        );

        self::assertCount(1, $zeilen, $competitor->value.': keine Abdeckungszeile mit der eigenen Zahl.');
    }

    /**
     * Nicht jede Zeile darf für uns günstig ausfallen.
     *
     * Eine Tabelle, in der Endlech.lu ausnahmslos gewinnt, ist keine Auskunft,
     * sondern eine Anzeige — und sie wäre bei diesen vier Wettbewerbern
     * nachweislich falsch.
     */
    #[DataProvider('competitors')]
    public function testJedeSeiteRaeumtMindestensEinenNachteilEin(Competitor $competitor): void
    {
        $page = (new ComparisonRegistry())->page($competitor);
        $unterlegen = array_filter(
            $page->rows,
            static fn (ComparisonRow $r): bool => Verdict::YES === $r->theirs && Verdict::YES !== $r->own,
        );

        self::assertNotEmpty(
            $unterlegen,
            sprintf('%s: keine einzige Zeile, in der der Wettbewerber besser abschneidet.', $competitor->value),
        );
    }

    public function testAllLiefertAlleVierSeiten(): void
    {
        $alle = (new ComparisonRegistry())->all();

        self::assertCount(\count(Competitor::cases()), $alle);
        self::assertContainsOnlyInstancesOf(ComparisonPage::class, $alle);
    }
}
