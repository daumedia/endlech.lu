<?php

declare(strict_types=1);

namespace App\Tests\Unit\Sponsor;

use App\Sponsor\Sponsor;
use App\Sponsor\SponsorRegistry;
use App\Sponsor\SponsorRules;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Die echte Sponsorenliste im Quelltext ist gültig (Feature 15, AK-27).
 *
 * ⚠ Dieser Prüflauf ist der einzige, der die **Produktionsquelle** liest. Alle
 * anderen arbeiten mit Testsponsoren (`InMemorySponsorSource`). Wer einen
 * Sponsor einträgt, lässt ihn laufen: `php bin/phpunit tests/Unit/Sponsor`.
 */
final class SponsorRegistryTest extends TestCase
{
    public function testAk27JederEintragDerProduktionsquelleIstGueltig(): void
    {
        $registry = new SponsorRegistry();

        $verstoesse = SponsorRules::violations(
            $registry->entries(),
            $registry->removedSlugs(),
            \dirname(__DIR__, 3).'/assets/'.SponsorRegistry::LOGO_DIRECTORY,
            // Die Sprachen der Website, nicht abgeschrieben (Nachtrag design.md, Entscheidung #15).
            Yaml::parseFile(\dirname(__DIR__, 3).'/config/packages/translation.yaml')['framework']['enabled_locales'],
        );

        self::assertSame([], $verstoesse, "Ungültige Sponsoreneinträge:\n".implode("\n", $verstoesse));
    }

    /**
     * AK-14, AK-44: Ein Sponsor hat kein Feld für Betrag, Laufzeitart oder
     * Person. Wer eines ergänzt, ändert, was die Website über Sponsoren sagen
     * kann — das soll nicht nebenbei passieren.
     */
    public function testAk14Ak44SponsorTraegtNurDieVereinbartenFelder(): void
    {
        $felder = array_map(
            static fn (\ReflectionProperty $p): string => $p->getName(),
            (new \ReflectionClass(Sponsor::class))->getProperties(),
        );

        // Überarbeitung 2026-10-28: Kurzbeschreibung je Sprache und Profile (AK-49, AK-52).
        self::assertSame(['slug', 'name', 'logo', 'periods', 'website', 'quote', 'quoteLocale', 'description', 'profiles'], $felder);
    }
}
