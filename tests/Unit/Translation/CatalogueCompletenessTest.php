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
     * @return iterable<string, array{string}>
     */
    public static function domains(): iterable
    {
        yield 'messages' => ['messages'];
        yield 'validators' => ['validators'];
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
