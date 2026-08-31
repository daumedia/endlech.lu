<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\AbstractWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Regressionsschutz für BF-101 und BF-102 (QA 06 vom 2026-08-30).
 *
 * **BF-101:** Die Meldung beim erschöpften Deckel nannte keine Wartezeit,
 * obwohl AK-59 sie ausdrücklich verlangt — der Nutzer musste raten, ob er in
 * einer Minute oder in einer Stunde wiederkommen soll.
 *
 * **BF-102:** Beide Wege benutzten denselben Übersetzungsschlüssel. Wer
 * zugestimmt hatte, las „Zu viele **Einreichungen**".
 *
 * ⚠ **Geprüft werden die Meldungen, nicht das Auslösen des Deckels.** Der
 * Deckel selbst ist in der QA live gegen den laufenden Server belegt (fünf
 * Einreichungen durch, die sechste 429, kein Datensatz). Im Test-Kernel lässt er
 * sich nicht auslösen: `KernelBrowser` startet den Kernel bei jedem Request neu,
 * und der Limiter-Zustand überlebt das dort nicht.
 *
 * Beide Befunde waren Textfehler — und genau die deckt dieser Prüflauf ab:
 * Existenz beider Schlüssel in vier Sprachen, Platzhalter für die Wartezeit,
 * und dass die beiden Texte **verschieden** sind. Ein einziger Text für zwei
 * Vorgänge benennt zwangsläufig einen davon falsch.
 */
final class BoardRateLimitMessageTest extends AbstractWebTestCase
{
    /**
     * Beide Schlüssel sind in allen vier Katalogen vorhanden, tragen den
     * Platzhalter und sind voneinander verschieden.
     */
    #[DataProvider('sprachen')]
    public function testBeideMeldungenSindEigenstaendigUndNennenDieWartezeit(string $locale): void
    {
        $client = static::createClient();
        $uebersetzer = $client->getContainer()->get('translator');

        $einreichen = $uebersetzer->trans('flash.board_rate_limited', ['%minutes%' => 7], null, $locale);
        $zustimmen = $uebersetzer->trans('flash.board_vote_rate_limited', ['%minutes%' => 7], null, $locale);

        self::assertStringContainsString('7', $einreichen, "[$locale] Einreich-Meldung ohne Wartezeit.");
        self::assertStringContainsString('7', $zustimmen, "[$locale] Zustimm-Meldung ohne Wartezeit.");
        self::assertNotSame($einreichen, $zustimmen, "[$locale] Ein Text für zwei Vorgänge (BF-102).");
        self::assertStringNotContainsString('flash.board', $einreichen, "[$locale] roher Schlüssel.");
        self::assertStringNotContainsString('flash.board', $zustimmen, "[$locale] roher Schlüssel.");
    }

    /** @return iterable<string, array{0: string}> */
    public static function sprachen(): iterable
    {
        yield 'de' => ['de'];
        yield 'en' => ['en'];
        yield 'fr' => ['fr'];
        yield 'lb' => ['lb'];
    }
}
