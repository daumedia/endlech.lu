<?php

declare(strict_types=1);

namespace App\Tests\Unit\RateLimit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Die Konvention aus `CLAUDE.md` als Test.
 *
 * Sie lautet: Jeder Weg, der eine Mail auslöst, ein Geheimnis prüft oder bei
 * jedem Aufruf den gesamten Bestand lädt, braucht einen Limiter. Sie wurde
 * siebenmal verletzt (M-01) — jedes Mal an einer anderen Stelle, und einmal am
 * selben Tag, an dem sie formuliert wurde.
 *
 * Was dieser Test prüft, ist die mechanische Hälfte davon: Jeder deklarierte
 * Limiter muss auch benutzt werden, und jeder muss seinen `when@test`-Override
 * haben. Ohne den summieren sich die Aufrufe über die Testsuite, und irgendein
 * Test wird rot, der nichts mit Limits zu tun hat.
 */
final class LimiterCoverageTest extends TestCase
{
    /**
     * @return array{prod: array<string, mixed>, test: array<string, mixed>}
     */
    private function limiter(): array
    {
        $yaml = Yaml::parseFile(\dirname(__DIR__, 3).'/config/packages/framework.yaml');

        return [
            'prod' => $yaml['framework']['rate_limiter'] ?? [],
            'test' => $yaml['when@test']['framework']['rate_limiter'] ?? [],
        ];
    }

    public function testJederLimiterHatEinenTestOverride(): void
    {
        ['prod' => $prod, 'test' => $test] = $this->limiter();

        self::assertNotEmpty($prod, 'Es sind keine Limiter konfiguriert.');

        $fehlend = array_values(array_diff(array_keys($prod), array_keys($test)));
        self::assertSame([], $fehlend, sprintf(
            'Ohne when@test-Override summieren sich die Aufrufe über die Suite. Fehlend: %s',
            implode(', ', $fehlend),
        ));
    }

    public function testDieTestOverridesSindGrosszuegig(): void
    {
        ['test' => $test] = $this->limiter();

        foreach ($test as $name => $konfiguration) {
            self::assertGreaterThanOrEqual(
                1000,
                $konfiguration['limit'] ?? 0,
                sprintf('Der Test-Override für "%s" ist zu eng.', $name),
            );
        }
    }

    /**
     * Ein Limiter, den niemand ruft, ist eine Zeile Konfiguration und kein Schutz.
     */
    public function testJederLimiterWirdBenutzt(): void
    {
        ['prod' => $prod] = $this->limiter();
        $wurzel = \dirname(__DIR__, 3);

        $quelltext = '';
        $lauf = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($wurzel.'/src', \FilesystemIterator::SKIP_DOTS));
        foreach ($lauf as $datei) {
            if ($datei instanceof \SplFileInfo && 'php' === $datei->getExtension()) {
                $quelltext .= (string) file_get_contents($datei->getPathname());
            }
        }

        $ungenutzt = [];
        foreach (array_keys($prod) as $name) {
            if (!str_contains($quelltext, "limiter.$name")) {
                $ungenutzt[] = $name;
            }
        }

        self::assertSame([], $ungenutzt, sprintf(
            'Diese Limiter sind konfiguriert, aber nirgends verdrahtet: %s',
            implode(', ', $ungenutzt),
        ));
    }
}
