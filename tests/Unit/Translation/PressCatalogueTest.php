<?php

declare(strict_types=1);

namespace App\Tests\Unit\Translation;

use App\Press\BoilerplateLength;
use App\Press\PressRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Jeder Textschlüssel des Presse-Kits steht in allen vier Katalogen (Feature 05).
 *
 * ⚠ **Ohne diesen Lauf wäre AK-30 wirkungslos.** Der Scanner in
 * `CatalogueCompletenessTest` erfasst nur Literale in Vorlagen — Materialliste,
 * Zitate und Meldungen rufen ihre Schlüssel aber datengetrieben auf
 * (`asset.labelKey|trans`). Dieselbe Lücke wie bei den Vergleichsseiten.
 *
 * ⚠ **Die Fallback-Kette macht das Fehlen unsichtbar:** `translation.yaml` setzt
 * `fallbacks: ['de', 'en']`, und die gilt auch für eigene Domains. Ein Schlüssel,
 * der auf Luxemburgisch fehlt, erscheint dort still auf Deutsch — niemand meldet
 * das, keine Ausnahme wird geworfen (AK-31).
 *
 * Der dritte Test ist der ungewöhnlichste und der wichtigste: Er hält die
 * Gesundheitsangabe aus AK-37 auf **eine** Textstelle fest. Sie im Boilerplate zu
 * wiederholen wäre naheliegend — es ist das stärkste Argument des Textes — und
 * machte den Widerruf zu einer Suche über vier Kataloge.
 */
final class PressCatalogueTest extends TestCase
{
    private const LOCALES = ['lb', 'de', 'fr', 'en'];
    private const DOMAIN = 'press';

    /** Der einzige Schlüssel, der die Angabe zur Behinderung tragen darf. */
    private const BIO_KEY = 'person.bio';

    /** Wortstämme der Angabe in den vier Sprachen. */
    private const GESUNDHEITSANGABE = '/\bSMA\d?\b|muskelatrophie|amyotrophie|muscular atrophy/iu';

    /**
     * Schlüssel, die **keine** Stelle im Quelltext als Literal nennt (BF-98).
     *
     * ⚠ Die Vorlagen setzen sie aus Bausteinen zusammen —
     * `('material.allowed_' ~ i)|trans` in `_material.html.twig`, die
     * `*_value`-Paare in einer Schleife in `_facts.html.twig`, dieselben
     * `material.*` noch einmal in `PressPackageCommand`. Damit fallen sie durch
     * **beide** Netze: `CatalogueCompletenessTest` erfasst nur literale
     * `'…'|trans`-Aufrufe, und der Sammler unten kennt nur, was `PressRegistry`
     * nennt.
     *
     * Nachgestellt am 2026-08-30: `material.allowed_3` aus allen vier Katalogen
     * entfernt → die gesamte Unit-Suite blieb **grün**, und die Seite zeigte
     * Besuchern den rohen Schlüssel. Dieselbe Bauart wie BF-56.
     *
     * ⚠ **Wer eine Schleifengrenze ändert (`1..4` → `1..5`), trägt den neuen
     * Schlüssel hier nach.** Diese Liste ist von Hand geführt; sie schließt die
     * Lücke für den gemeldeten Fall — das Löschen aus allen vier Katalogen —,
     * nicht für einen Schlüssel, den niemand je angelegt hat.
     */
    private const array ZUSAMMENGESETZTE_SCHLUESSEL = [
        'material.allowed_1', 'material.allowed_2', 'material.allowed_3', 'material.allowed_4',
        'material.forbidden_1', 'material.forbidden_2', 'material.forbidden_3', 'material.forbidden_4',
        'facts.country_value', 'facts.founded_value', 'facts.status_value',
        'facts.license_code_value', 'facts.license_data_value', 'facts.languages_value',
    ];

    public function testJederSchluesselAusAppPressStehtInAllenVierKatalogen(): void
    {
        $verwendet = self::schluesselAusDemQuelltext();
        self::assertNotEmpty($verwendet, 'Es wurden keine Schlüssel gefunden — der Sammler greift ins Leere.');

        foreach (self::LOCALES as $locale) {
            $katalog = self::katalog($locale);
            $fehlend = array_values(array_diff($verwendet, array_keys($katalog)));
            sort($fehlend);

            self::assertSame([], $fehlend, sprintf(
                "%d Schlüssel fehlen in press.%s.yaml:\n  %s",
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
     * der blinde Fleck, den `CatalogueCompletenessTest` im zweiten Anlauf bei
     * sich selbst gefunden hat.
     */
    public function testEinErfundenerSchluesselWuerdeAuffallen(): void
    {
        $katalog = self::katalog('de');

        self::assertArrayNotHasKey('material.gibt_es_nicht', $katalog);
        self::assertArrayHasKey('material.terms_intro', $katalog, 'Der Sammler prüft gegen einen leeren Katalog.');
    }

    /** AK-08: Die Wortzahlen liegen in allen vier Sprachen in den Grenzen des Enums. */
    public function testDieBeschreibungstexteHaltenIhreWortgrenzenInJederSprache(): void
    {
        foreach (self::LOCALES as $locale) {
            $katalog = self::katalog($locale);

            foreach (BoilerplateLength::cases() as $laenge) {
                $text = $katalog[$laenge->transKey()] ?? null;
                self::assertIsString($text, sprintf('%s fehlt in press.%s.yaml.', $laenge->transKey(), $locale));

                $woerter = self::woerter($text);
                self::assertGreaterThanOrEqual($laenge->minWords(), $woerter, sprintf(
                    '%s ist in %s mit %d Wörtern zu kurz (mindestens %d).',
                    $laenge->value, $locale, $woerter, $laenge->minWords(),
                ));
                self::assertLessThanOrEqual($laenge->maxWords(), $woerter, sprintf(
                    '%s ist in %s mit %d Wörtern zu lang (höchstens %d). Französisch braucht regelmäßig 15–20 %% mehr Wörter als Deutsch.',
                    $laenge->value, $locale, $woerter, $laenge->maxWords(),
                ));
            }
        }
    }

    /**
     * AK-37: Die Angabe zur Behinderung steht in genau einem Schlüssel.
     *
     * Damit ist ihr Widerruf eine Textstelle und keine Suche — die Zusage, die
     * die Spec im Datenschutzteil gibt.
     */
    public function testDieGesundheitsangabeStehtNurInDerKurzvita(): void
    {
        foreach (self::LOCALES as $locale) {
            $treffer = [];
            foreach (self::katalog($locale) as $key => $wert) {
                if (\is_string($wert) && preg_match(self::GESUNDHEITSANGABE, $wert)) {
                    $treffer[] = $key;
                }
            }

            self::assertSame([self::BIO_KEY], $treffer, sprintf(
                'In press.%s.yaml steht die Angabe zur Behinderung in %s statt nur in %s.',
                $locale,
                implode(', ', $treffer) ?: '(keinem Schlüssel)',
                self::BIO_KEY,
            ));
        }
    }

    /**
     * Alle Schlüssel, die der Quelltext des Presse-Kits aufruft.
     *
     * @return list<string>
     */
    private static function schluesselAusDemQuelltext(): array
    {
        $registry = new PressRegistry();
        $keys = [];

        foreach ($registry->boilerplates() as $laenge) {
            $keys[] = $laenge->transKey();
            $keys[] = $laenge->labelKey();
        }
        foreach ($registry->assets() as $asset) {
            $keys[] = $asset->labelKey();
            if (null !== $asset->creditKey) {
                $keys[] = $asset->creditKey;
            }
        }
        foreach ($registry->quotes() as $zitat) {
            $keys[] = $zitat->textKey;
            $keys[] = $zitat->roleKey;
        }
        foreach ($registry->releases() as $meldung) {
            $keys[] = $meldung->titleKey;
            $keys[] = $meldung->bodyKey;
        }

        return array_values(array_unique([...$keys, ...self::ZUSAMMENGESETZTE_SCHLUESSEL]));
    }

    /** @return array<string, string> */
    private static function katalog(string $locale): array
    {
        $pfad = \dirname(__DIR__, 3).'/translations/'.self::DOMAIN.'.'.$locale.'.yaml';
        self::assertFileExists($pfad);

        return self::flach(Yaml::parseFile($pfad) ?? []);
    }

    /**
     * @param array<mixed> $daten
     *
     * @return array<string, string>
     */
    private static function flach(array $daten, string $praefix = ''): array
    {
        $flach = [];
        foreach ($daten as $key => $wert) {
            $voll = '' === $praefix ? (string) $key : $praefix.'.'.$key;
            if (\is_array($wert)) {
                $flach += self::flach($wert, $voll);
            } else {
                $flach[$voll] = (string) $wert;
            }
        }

        return $flach;
    }

    /**
     * Wörter = durch Leerraum getrennte Stücke mit mindestens einem Buchstaben
     * oder einer Ziffer. Ein alleinstehender Gedankenstrich zählt damit nicht mit,
     * „Endlech.lu" dagegen als ein Wort — so, wie ein Mensch es zählen würde.
     */
    private static function woerter(string $text): int
    {
        $stuecke = preg_split('/\s+/u', trim($text), -1, \PREG_SPLIT_NO_EMPTY) ?: [];

        return \count(array_filter($stuecke, static fn (string $s): bool => 1 === preg_match('/[\p{L}\p{N}]/u', $s)));
    }
}
