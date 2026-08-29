<?php

declare(strict_types=1);

namespace App\Tests\Unit\Translation;

use App\Comparison\ComparisonGroup;
use App\Comparison\ComparisonRegistry;
use App\Comparison\Competitor;
use App\Comparison\Verdict;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Jeder Textschlüssel der Vergleichsseiten steht in allen vier Katalogen.
 *
 * ⚠ **Ohne diesen Lauf wäre AK-28 wirkungslos.** Der Scanner in
 * `CatalogueCompletenessTest` erfasst nur Literale in Vorlagen — die
 * Merkmalstabelle ruft ihre Schlüssel aber datengetrieben auf
 * (`row.labelKey|trans`). Genau diese Lücke ist im Aufgabenplan als Falle
 * benannt; sie wird hier geschlossen, indem die Schlüssel direkt aus
 * `App\Comparison\` geholt werden.
 *
 * ⚠ **Die Fallback-Kette macht das Fehlen unsichtbar.** `translation.yaml` setzt
 * `fallbacks: ['de', 'en']`, und die gilt auch für eigene Domains: Ein Schlüssel,
 * der auf Luxemburgisch fehlt, erscheint dort still auf Deutsch. Kein Besucher
 * meldet das, und keine Ausnahme wird geworfen. Dieser Lauf ist die einzige
 * Stelle, an der es auffällt.
 */
final class ComparisonCatalogueTest extends TestCase
{
    private const LOCALES = ['lb', 'de', 'fr', 'en'];
    private const DOMAIN = 'comparison';

    public function testJederSchluesselAusDerRegistryStehtInAllenVierKatalogen(): void
    {
        $verwendet = self::schluesselAusDerRegistry();
        self::assertNotEmpty($verwendet, 'Es wurden keine Schlüssel gefunden — die Registry ist leer oder der Sammler greift ins Leere.');

        foreach (self::LOCALES as $locale) {
            $katalog = self::katalog($locale);
            $fehlend = array_values(array_diff($verwendet, array_keys($katalog)));
            sort($fehlend);

            self::assertSame([], $fehlend, sprintf(
                "%d Schlüssel fehlen in comparison.%s.yaml:\n  %s",
                \count($fehlend),
                $locale,
                implode("\n  ", $fehlend),
            ));
        }
    }

    /**
     * Gegenprobe: Ein erfundener Schlüssel muss auffallen.
     *
     * Ohne sie wäre nicht zu erkennen, ob der Sammler oben tatsächlich etwas
     * findet oder nur eine leere Menge mit einer leeren Menge vergleicht — genau
     * der blinde Fleck, den `CatalogueCompletenessTest` im zweiten Anlauf bei sich
     * selbst gefunden hat.
     */
    public function testEinErfundenerSchluesselWuerdeAuffallen(): void
    {
        $katalog = self::katalog('de');

        self::assertArrayNotHasKey('page.google_maps.note.gibt_es_nicht', $katalog);
        self::assertArrayHasKey('page.google_maps.heading', $katalog, 'Der Sammler liest den Katalog nicht richtig.');
    }

    /** Die vier Seitenpräfixe müssen tatsächlich belegt sein. */
    public function testJedeSeiteHatKopfUndKurzfazit(): void
    {
        $katalog = self::katalog('de');

        foreach (Competitor::cases() as $competitor) {
            foreach (['meta_title', 'meta_description', 'heading', 'subheading', 'fit_own', 'fit_theirs'] as $teil) {
                self::assertArrayHasKey(
                    $competitor->transPrefix().$teil,
                    $katalog,
                    sprintf('%s: %s fehlt.', $competitor->value, $teil),
                );
            }
        }
    }

    /**
     * Sammelt alle Textschlüssel, die die Registry benennt.
     *
     * @return list<string>
     */
    private static function schluesselAusDerRegistry(): array
    {
        $treffer = [];

        // ⚠ BF-78: Diese beiden Aufzählungen bauen ihre Schlüssel ebenfalls
        // dynamisch (`{{ group.transKey|trans }}`), stehen aber in keinem
        // ComparisonPage-Objekt — sie fielen deshalb durch beide Prüfläufe.
        // Nachgestellt: `group.coverage` aus ALLEN VIER Katalogen entfernt, alle
        // 594 Tests blieben grün, und auf der Seite stand der rohe Schlüssel
        // `group.coverage`. Genau der BF-69-Fehlertyp.
        foreach (ComparisonGroup::cases() as $group) {
            $treffer[] = $group->transKey();
        }

        foreach (Verdict::cases() as $verdict) {
            $treffer[] = $verdict->transKey();
        }

        foreach ((new ComparisonRegistry())->all() as $page) {
            $p = $page->competitor->transPrefix();

            foreach (['meta_title', 'meta_description', 'heading', 'subheading', 'fit_own', 'fit_theirs'] as $teil) {
                $treffer[] = $p.$teil;
            }

            foreach ($page->rows as $row) {
                $treffer[] = $row->labelKey;
                $treffer[] = $row->ownNoteKey;
                $treffer[] = $row->theirNoteKey;
            }

            foreach ($page->sources as $source) {
                $treffer[] = $source->labelKey;
            }

            $treffer = array_merge($treffer, $page->advantageKeys);

            foreach ($page->faqKeys as $key) {
                $treffer[] = $key.'.q';
                $treffer[] = $key.'.a';
            }
        }

        return array_values(array_unique($treffer));
    }

    /** @return array<string, mixed> */
    private static function katalog(string $locale): array
    {
        $daten = Yaml::parseFile(\dirname(__DIR__, 3).'/translations/'.self::DOMAIN.'.'.$locale.'.yaml') ?? [];

        return self::flatten($daten);
    }

    /**
     * @param array<mixed> $daten
     *
     * @return array<string, mixed>
     */
    private static function flatten(array $daten, string $praefix = ''): array
    {
        $flach = [];

        foreach ($daten as $schluessel => $wert) {
            $name = '' === $praefix ? (string) $schluessel : $praefix.'.'.$schluessel;

            if (\is_array($wert)) {
                $flach += self::flatten($wert, $name);
                continue;
            }

            $flach[$name] = $wert;
        }

        return $flach;
    }
}
