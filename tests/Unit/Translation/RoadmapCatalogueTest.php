<?php

declare(strict_types=1);

namespace App\Tests\Unit\Translation;

use App\Roadmap\ChangelogRegistry;
use App\Roadmap\ChangelogSummary;
use App\Roadmap\ReleaseNote;
use App\Roadmap\RoadmapItem;
use App\Roadmap\RoadmapRegistry;
use App\Roadmap\RoadmapStage;
use App\Roadmap\ShelvedItem;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Der blinde Fleck von `CatalogueCompletenessTest` für Feature 07.
 *
 * Jener Lauf scannt literale Schlüssel in Templates und `src/Form/`. Roadmap und
 * Changelog rufen ihre Schlüssel **datengetrieben** über `RoadmapItem::titleKey()`,
 * `ReleaseNote::bodyKey()` und `RoadmapStage::transKey()` auf — die sieht er nicht.
 * Genau daran ging BF-98 durch beide bestehenden Läufe: Ein aus allen vier
 * Katalogen entfernter Schlüssel ließ die Suite grün und zeigte auf der Seite den
 * rohen Schlüsselnamen.
 *
 * ⚠ **Dieser Lauf prüft zugleich zwei Akzeptanzkriterien strukturell.** AK-05
 * („kein Eintrag besteht nur aus einem Titel") und AK-29 („kein Eintrag ohne
 * Herkunft") sind erfüllt, weil `reason` hier für jeden Eintrag in vier Sprachen
 * verlangt wird — ein Vorhaben ohne Begründung erreicht die Produktion nicht.
 */
final class RoadmapCatalogueTest extends TestCase
{
    private const array LOCALES = ['lb', 'de', 'fr', 'en'];

    /**
     * @return array<string, string>
     */
    private static function katalog(string $domain, string $locale): array
    {
        $pfad = \dirname(__DIR__, 3).'/translations/'.$domain.'.'.$locale.'.yaml';
        self::assertFileExists($pfad, sprintf('Katalog %s.%s.yaml fehlt.', $domain, $locale));

        return self::flatten(Yaml::parseFile($pfad) ?? []);
    }

    /**
     * @param array<mixed> $baum
     *
     * @return array<string, string>
     */
    private static function flatten(array $baum, string $praefix = ''): array
    {
        $flach = [];
        foreach ($baum as $schluessel => $wert) {
            $voll = '' === $praefix ? (string) $schluessel : $praefix.'.'.$schluessel;
            if (\is_array($wert)) {
                $flach += self::flatten($wert, $voll);
            } else {
                $flach[$voll] = (string) $wert;
            }
        }

        return $flach;
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function locales(): iterable
    {
        foreach (self::LOCALES as $locale) {
            yield $locale => [$locale];
        }
    }

    #[DataProvider('locales')]
    public function testJedesVorhabenHatTitelUndBegruendung(string $locale): void
    {
        $katalog = self::katalog('roadmap', $locale);
        $fehlend = [];

        foreach ((new RoadmapRegistry())->items() as $item) {
            self::assertInstanceOf(RoadmapItem::class, $item);
            foreach ([$item->titleKey(), $item->reasonKey()] as $key) {
                if (!isset($katalog[$key]) || '' === trim($katalog[$key])) {
                    $fehlend[] = $key;
                }
            }
        }

        self::assertSame([], $fehlend, sprintf(
            "In roadmap.%s.yaml fehlen %d Schlüssel:\n  %s",
            $locale,
            \count($fehlend),
            implode("\n  ", $fehlend),
        ));
    }

    #[DataProvider('locales')]
    public function testJederZurueckgestelltePunktHatTitelUndBegruendung(string $locale): void
    {
        $katalog = self::katalog('roadmap', $locale);
        $fehlend = [];

        foreach ((new RoadmapRegistry())->shelved() as $item) {
            self::assertInstanceOf(ShelvedItem::class, $item);
            foreach ([$item->titleKey(), $item->reasonKey()] as $key) {
                if (!isset($katalog[$key]) || '' === trim($katalog[$key])) {
                    $fehlend[] = $key;
                }
            }
        }

        self::assertSame([], $fehlend, sprintf(
            "In roadmap.%s.yaml fehlen %d Schlüssel des Blocks „Bewusst nicht gebaut\":\n  %s",
            $locale,
            \count($fehlend),
            implode("\n  ", $fehlend),
        ));
    }

    #[DataProvider('locales')]
    public function testJedeSpalteHatUeberschriftUndLeerenZustand(string $locale): void
    {
        $katalog = self::katalog('roadmap', $locale);
        $fehlend = [];

        foreach (RoadmapStage::cases() as $stage) {
            foreach ([$stage->transKey(), $stage->emptyKey()] as $key) {
                if (!isset($katalog[$key]) || '' === trim($katalog[$key])) {
                    $fehlend[] = $key;
                }
            }
        }

        self::assertSame([], $fehlend, sprintf(
            "In roadmap.%s.yaml fehlen %d Spaltenschlüssel:\n  %s",
            $locale,
            \count($fehlend),
            implode("\n  ", $fehlend),
        ));
    }

    #[DataProvider('locales')]
    public function testJederGezeigteReleaseHatTitelUndText(string $locale): void
    {
        $katalog = self::katalog('changelog', $locale);
        $fehlend = [];

        foreach ((new ChangelogRegistry())->notes() as $note) {
            self::assertInstanceOf(ReleaseNote::class, $note);
            if (!$note->isShown()) {
                continue;
            }
            foreach ([$note->titleKey(), $note->bodyKey()] as $key) {
                if (!isset($katalog[$key]) || '' === trim($katalog[$key])) {
                    $fehlend[] = $key;
                }
            }
        }

        self::assertSame([], $fehlend, sprintf(
            "In changelog.%s.yaml fehlen %d Release-Schlüssel:\n  %s",
            $locale,
            \count($fehlend),
            implode("\n  ", $fehlend),
        ));
    }

    #[DataProvider('locales')]
    public function testDieSammelzeileIstVollstaendig(string $locale): void
    {
        $summary = (new ChangelogRegistry())->summary();
        if (!$summary instanceof ChangelogSummary) {
            self::markTestSkipped('Es gibt keine zusammengefassten Releases.');
        }

        $katalog = self::katalog('changelog', $locale);
        $fehlend = [];

        foreach ([$summary->titleKey(), $summary->periodKey(), $summary->bodyKey()] as $key) {
            if (!isset($katalog[$key]) || '' === trim($katalog[$key])) {
                $fehlend[] = $key;
            }
        }

        self::assertSame([], $fehlend, sprintf(
            "In changelog.%s.yaml fehlen %d Schlüssel der Sammelzeile:\n  %s",
            $locale,
            \count($fehlend),
            implode("\n  ", $fehlend),
        ));
    }

    /**
     * ⚠ **Ein stilles Release darf keinen Text tragen.** Sonst stünde ein
     * übersetzter Eintrag im Katalog, den niemand je sieht — und beim nächsten
     * Umbau hielte ihn jemand für einen Fehler und zeigte ihn an.
     */
    #[DataProvider('locales')]
    public function testStilleReleasesTragenKeinenText(string $locale): void
    {
        $katalog = self::katalog('changelog', $locale);
        $ueberzaehlig = [];

        foreach ((new ChangelogRegistry())->notes() as $note) {
            if ($note->isShown()) {
                continue;
            }
            foreach ([$note->titleKey(), $note->bodyKey()] as $key) {
                if (isset($katalog[$key])) {
                    $ueberzaehlig[] = $key;
                }
            }
        }

        self::assertSame([], $ueberzaehlig, sprintf(
            "In changelog.%s.yaml stehen %d Texte für Releases, die nicht gezeigt werden:\n  %s",
            $locale,
            \count($ueberzaehlig),
            implode("\n  ", $ueberzaehlig),
        ));
    }
}
