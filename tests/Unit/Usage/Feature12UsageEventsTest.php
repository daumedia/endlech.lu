<?php

declare(strict_types=1);

namespace App\Tests\Unit\Usage;

use App\Usage\UsageEventCatalogue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Feature 12, AK-50 und AK-51 — die drei Ereignisse der Einführung.
 *
 * Sie verraten, **wo** Leute aussteigen, nie **wer** und nie, was er getan hat: Ob jemand den
 * Newsletter abonniert oder ein Bild hochgeladen hat, darf kein Ereignis tragen.
 */
final class Feature12UsageEventsTest extends TestCase
{
    /** @return iterable<string, array{string, array<mixed>, bool}> */
    public static function faelle(): iterable
    {
        $begonnen = UsageEventCatalogue::ONBOARDING_STARTED;
        $fertig = UsageEventCatalogue::ONBOARDING_COMPLETED;
        $beendet = UsageEventCatalogue::ONBOARDING_LEFT;

        yield 'AK-50 begonnen ohne Daten' => [$begonnen, [], true];
        yield 'AK-50 abgeschlossen ohne Daten' => [$fertig, [], true];
        yield 'AK-50 vorzeitig beendet auf Bildschirm 1' => [$beendet, ['schritt' => '1'], true];
        yield 'AK-50 vorzeitig beendet auf Bildschirm 4' => [$beendet, ['schritt' => '4'], true];
        yield 'beendet ohne Bildschirm' => [$beendet, [], false];
        yield 'beendet auf Bildschirm 5' => [$beendet, ['schritt' => '5'], false];
        yield 'beendet mit Schrittname statt Nummer' => [$beendet, ['schritt' => 'newsletter'], false];
        yield 'AK-51 Adresse als Schritt' => [$beendet, ['schritt' => 'a@b.lu'], false];
        yield 'AK-51 abgeschlossen mit Abo-Angabe' => [$fertig, ['newsletter' => 'ja'], false];
        yield 'AK-51 begonnen mit Konto' => [$begonnen, ['user' => '42'], false];
        yield 'AK-51 beendet mit Zusatzschlüssel' => [$beendet, ['schritt' => '2', 'bild' => 'ja'], false];
    }

    /** @param array<mixed> $daten */
    #[DataProvider('faelle')]
    public function testIsAllowed(string $ereignis, array $daten, bool $erwartet): void
    {
        self::assertSame($erwartet, (new UsageEventCatalogue())->isAllowed($ereignis, $daten));
    }

    public function testAk50DieDreiStehenImKatalog(): void
    {
        $namen = (new UsageEventCatalogue())->eventNames();

        self::assertContains('einfuehrung_begonnen', $namen);
        self::assertContains('einfuehrung_abgeschlossen', $namen);
        self::assertContains('einfuehrung_beendet', $namen);
    }
}
