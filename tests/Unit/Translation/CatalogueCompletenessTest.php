<?php

declare(strict_types=1);

namespace App\Tests\Unit\Translation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Die vier Kataloge müssen dieselbe Schlüsselmenge tragen.
 *
 * Ein Schlüssel, den nur eine Sprache kennt, fällt sonst erst auf, wenn ihn
 * jemand in genau der fehlenden Sprache aufruft — dort steht dann der rohe
 * Schlüssel auf der Seite (QA B24, BF-69). `debug:translation` läuft in keinem
 * Workflow; dieser Test ist der Ersatz dafür.
 */
final class CatalogueCompletenessTest extends TestCase
{
    private const LOCALES = ['lb', 'de', 'fr', 'en'];

    /**
     * Alle geprüften Domains.
     *
     * `comparison` kam mit Feature 03 dazu (Vergleichsseiten). Eigene Domain, weil
     * die Merkmalstabelle ihre Schlüssel datengetrieben aufruft — was der Scanner
     * unten strukturell nicht sehen kann. Die Lücke schließt
     * `ComparisonCatalogueTest`, der die in `App\Comparison\` genannten Schlüssel
     * direkt gegen die Kataloge hält.
     *
     * `press` kam mit Feature 05 dazu (Presse-Kit) — aus demselben Grund: Die
     * Materialliste, die Zitate und die Meldungen rufen ihre Schlüssel über
     * `App\Press\` auf. Die Lücke schließt `PressCatalogueTest`.
     */
    private const DOMAINS = ['messages', 'validators', 'comparison', 'press', 'roadmap', 'changelog'];

    /**
     * @return iterable<string, array{string}>
     */
    public static function domains(): iterable
    {
        foreach (self::DOMAINS as $domain) {
            yield $domain => [$domain];
        }
    }

    #[DataProvider('domains')]
    public function testAlleSprachenTragenDieselbenSchluessel(string $domain): void
    {
        $kataloge = [];
        foreach (self::LOCALES as $locale) {
            $pfad = \dirname(__DIR__, 3).'/translations/'.$domain.'.'.$locale.'.yaml';
            self::assertFileExists($pfad, sprintf('Katalog %s.%s.yaml fehlt.', $domain, $locale));
            $kataloge[$locale] = self::flatten(Yaml::parseFile($pfad) ?? []);
        }

        $alle = [];
        foreach ($kataloge as $katalog) {
            $alle += $katalog;
        }
        $erwartet = array_keys($alle);
        sort($erwartet);

        foreach (self::LOCALES as $locale) {
            $fehlend = array_values(array_diff($erwartet, array_keys($kataloge[$locale])));
            self::assertSame([], $fehlend, sprintf(
                'In %s.%s.yaml fehlen %d Schlüssel: %s',
                $domain,
                $locale,
                \count($fehlend),
                implode(', ', \array_slice($fehlend, 0, 10)),
            ));
        }
    }

    #[DataProvider('domains')]
    public function testKeinSchluesselIstLeer(string $domain): void
    {
        foreach (self::LOCALES as $locale) {
            $katalog = self::flatten(Yaml::parseFile(\dirname(__DIR__, 3).'/translations/'.$domain.'.'.$locale.'.yaml') ?? []);
            $leer = array_keys(array_filter($katalog, static fn ($wert) => '' === trim((string) $wert)));
            self::assertSame([], $leer, sprintf('Leere Werte in %s.%s.yaml: %s', $domain, $locale, implode(', ', $leer)));
        }
    }

    /**
     * Jeder im Code verwendete Schlüssel muss in allen vier Katalogen stehen.
     *
     * BF-69: Elf Schlüssel wurden verwendet und waren nirgends definiert — sie
     * standen als roher Text auf der Seite (`admin.restaurant.cancel` als
     * Beschriftung eines Knopfes, `suggestion.email_invalid` als Fehlermeldung).
     * `debug:translation` hätte sie gezeigt, läuft aber in keinem Workflow.
     * Dieser Test ist der Ersatz dafür.
     */
    public function testJederVerwendeteSchluesselIstDefiniert(): void
    {
        $wurzel = \dirname(__DIR__, 3);
        $verwendet = self::verwendeteSchluessel($wurzel);
        self::assertNotEmpty($verwendet, 'Es wurden keine Schlüssel gefunden — der Scanner greift ins Leere.');

        $definiert = [];
        foreach (self::DOMAINS as $domain) {
            foreach (self::LOCALES as $locale) {
                $definiert += self::flatten(Yaml::parseFile($wurzel.'/translations/'.$domain.'.'.$locale.'.yaml') ?? []);
            }
        }

        $fehlend = array_values(array_diff($verwendet, array_keys($definiert)));
        sort($fehlend);

        self::assertSame([], $fehlend, sprintf(
            "%d verwendete Schlüssel sind in keinem Katalog definiert:\n  %s",
            \count($fehlend),
            implode("\n  ", $fehlend),
        ));
    }

    /**
     * Sammelt Schlüssel aus `|trans`-Aufrufen in Templates und aus den
     * Constraint-Meldungen der Formularklassen.
     *
     * Bewusst konservativ: erfasst werden nur Literale in einfachen
     * Anführungszeichen mit mindestens einem Punkt — dynamisch zusammengesetzte
     * Schlüssel (`'status.' ~ eintrag.status`) kann kein Scanner auflösen, und ein
     * Fehlalarm wäre schlimmer als eine Lücke.
     *
     * @return list<string>
     */
    private static function verwendeteSchluessel(string $wurzel): array
    {
        $treffer = [];

        foreach (self::dateien($wurzel.'/templates', 'twig') as $datei) {
            preg_match_all("/'([a-z][a-z0-9_]*(?:\.[a-z0-9_]+)+)'\s*\|\s*trans/i", (string) file_get_contents($datei), $m);
            $treffer = array_merge($treffer, $m[1]);
        }

        foreach (self::dateien($wurzel.'/src/Form', 'php') as $datei) {
            $inhalt = (string) file_get_contents($datei);

            // Constraint-Meldungen (`message:`, `maxMessage:` …).
            preg_match_all(
                "/(?:message|maxMessage|minMessage|notInRangeMessage|invalidMessage):\s*'([a-z][a-z0-9_]*(?:\.[a-z0-9_]+)+)'/i",
                $inhalt,
                $m,
            );
            $treffer = array_merge($treffer, $m[1]);

            // Beschriftungen und Hilfetexte (`'label' => '…'`, `'help' => '…'`).
            // ⚠ Ohne diese Zeile fiel BF-56 durch: Zwei neue Felder trugen Labels,
            // die in keinem Katalog standen, und der Test blieb grün.
            preg_match_all(
                "/'(?:label|help|placeholder)'\s*=>\s*'([a-z][a-z0-9_]*(?:\.[a-z0-9_]+)+)'/i",
                $inhalt,
                $m,
            );
            $treffer = array_merge($treffer, $m[1]);
        }

        return array_values(array_unique($treffer));
    }

    /**
     * @return list<string>
     */
    private static function dateien(string $verzeichnis, string $endung): array
    {
        if (!is_dir($verzeichnis)) {
            return [];
        }

        $gefunden = [];
        $lauf = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($verzeichnis, \FilesystemIterator::SKIP_DOTS));
        foreach ($lauf as $datei) {
            if ($datei instanceof \SplFileInfo && $endung === $datei->getExtension()) {
                $gefunden[] = $datei->getPathname();
            }
        }

        return $gefunden;
    }

    /**
     * @param array<string, mixed> $daten
     *
     * @return array<string, scalar>
     */
    private static function flatten(array $daten, string $praefix = ''): array
    {
        $flach = [];
        foreach ($daten as $schluessel => $wert) {
            $voll = '' === $praefix ? (string) $schluessel : $praefix.'.'.$schluessel;
            if (\is_array($wert)) {
                $flach += self::flatten($wert, $voll);
            } else {
                $flach[$voll] = $wert;
            }
        }

        return $flach;
    }
}
