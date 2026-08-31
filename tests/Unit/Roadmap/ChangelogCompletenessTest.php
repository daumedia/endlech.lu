<?php

declare(strict_types=1);

namespace App\Tests\Unit\Roadmap;

use App\Roadmap\ChangelogRegistry;
use App\Roadmap\ReleaseNote;
use PHPUnit\Framework\TestCase;

/**
 * Hält `ChangelogRegistry` gegen `CHANGELOG.md` (Feature 07, AK-26).
 *
 * ⚠ **Das ist die Absicherung des fünften Punkts der Release-Checkliste.** Ohne
 * diesen Lauf wäre „bei jedem Release einen öffentlichen Eintrag schreiben" eine
 * Bitte — und die vier bestehenden Punkte der Checkliste wurden laut `CLAUDE.md`
 * bereits zweimal vergessen (Badge, Fußzeile).
 *
 * ⚠ **Die Dreiteilung der Sichtbarkeit ist der Grund, warum das prüfbar ist.**
 * Ein Release ohne öffentlichen Text ist entweder still, zusammengefasst oder
 * vergessen. Mit einem `bool` wären die ersten beiden vom dritten nicht zu
 * unterscheiden, und der Lauf könnte nur behaupten statt zu prüfen.
 */
final class ChangelogCompletenessTest extends TestCase
{
    private const string CHANGELOG = __DIR__.'/../../../CHANGELOG.md';

    /**
     * @return list<string>
     */
    private static function versionenAusDatei(): array
    {
        $inhalt = file_get_contents(self::CHANGELOG);
        self::assertIsString($inhalt, 'CHANGELOG.md ist nicht lesbar.');

        preg_match_all('/^## \[([^\]]+)\]/m', $inhalt, $treffer);

        // [Unreleased] ist noch nicht ausgeliefert und trägt deshalb keinen Eintrag.
        return array_values(array_filter(
            $treffer[1],
            static fn (string $v): bool => 'Unreleased' !== $v,
        ));
    }

    public function testJedeAusgelieferteVersionStehtInDerRegistry(): void
    {
        $ausDatei = self::versionenAusDatei();
        self::assertNotEmpty($ausDatei, 'Aus CHANGELOG.md wurde keine Version gelesen — der Lauf greift ins Leere.');

        $bekannt = array_map(
            static fn (ReleaseNote $n): string => $n->version,
            (new ChangelogRegistry())->notes(),
        );

        $fehlend = array_values(array_diff($ausDatei, $bekannt));

        self::assertSame([], $fehlend, sprintf(
            "%d in CHANGELOG.md ausgelieferte Version(en) tragen weder einen öffentlichen Eintrag "
            ."noch den Vermerk „still\" oder „zusammengefasst\":\n  %s\n"
            ."Nachzutragen in App\\Roadmap\\ChangelogRegistry::notes() — fünfter Punkt der "
            .'Release-Checkliste in CLAUDE.md.',
            \count($fehlend),
            implode("\n  ", $fehlend),
        ));
    }

    public function testDieRegistryErfindetKeineVersion(): void
    {
        $ausDatei = self::versionenAusDatei();
        $bekannt = array_map(
            static fn (ReleaseNote $n): string => $n->version,
            (new ChangelogRegistry())->notes(),
        );

        $ueberzaehlig = array_values(array_diff($bekannt, $ausDatei));

        self::assertSame([], $ueberzaehlig, sprintf(
            "Die Registry führt %d Version(en), die in CHANGELOG.md nicht vorkommen — "
            ."vermutlich ein Tippfehler in der Versionsnummer:\n  %s",
            \count($ueberzaehlig),
            implode("\n  ", $ueberzaehlig),
        ));
    }

    public function testDieSammelzeileDecktGenauDieZusammengefasstenReleases(): void
    {
        $registry = new ChangelogRegistry();
        $summary = $registry->summary();

        self::assertNotNull($summary, 'Es gibt zusammengefasste Releases, aber keine Sammelzeile.');
        self::assertLessThanOrEqual(
            $summary->to->getTimestamp(),
            $summary->from->getTimestamp(),
            'Die Sammelzeile beginnt nach ihrem Ende.',
        );
    }

    public function testDerJuengsteGezeigteEintragBestimmtDieAktualitaet(): void
    {
        $registry = new ChangelogRegistry();
        $juengster = $registry->latestShownDate();

        self::assertNotNull($juengster);

        // Ein stilles Release darf die Seite nicht frisch aussehen lassen.
        foreach ($registry->notes() as $note) {
            if ($note->isShown()) {
                self::assertLessThanOrEqual($juengster->getTimestamp(), $note->date->getTimestamp());
            }
        }
    }
}
