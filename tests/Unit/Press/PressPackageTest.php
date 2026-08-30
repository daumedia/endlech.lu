<?php

declare(strict_types=1);

namespace App\Tests\Unit\Press;

use App\Press\PressPackage;
use App\Press\PressRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Das ausgelieferte Paket enthält genau das, was die Seite als Vorschau zeigt.
 *
 * ⚠ **Das ist der einzige Nachweis für AK-17.** Die Seite und das Paket entstehen
 * zwar aus derselben Liste, aber das Paket ist eine *committete Datei* — es kann
 * hinter der Liste zurückbleiben, sobald jemand eine Datei austauscht und
 * `app:press:package` nicht neu laufen lässt. Genau dieser Fall fällt hier auf.
 *
 * ⚠ **Braucht `ext-zip`.** Lokal vorhanden; in der CI seit Feature 05 in der
 * Extension-Liste von `.github/workflows/ci.yml`. Fehlt sie, überspringt dieser
 * Lauf — und der Fall ist damit benannt statt still.
 *
 * ⚠ **Der Lauf überspringt auch, solange die Paketdatei fehlt** (VB-01: die vier
 * Vektormarken existieren noch nicht). Das ist gewollt: Ein harter Fehlschlag
 * würde die gesamte Suite für ein Material blockieren, das außerhalb des
 * Quelltexts entsteht. Die Meldung nennt die Vorbedingung, damit der übersprungene
 * Lauf nicht als „läuft ja" durchgeht.
 */
final class PressPackageTest extends TestCase
{
    private PressPackage $package;
    private PressRegistry $registry;

    protected function setUp(): void
    {
        $this->package = new PressPackage(\dirname(__DIR__, 3));
        $this->registry = new PressRegistry();

        if (!class_exists(\ZipArchive::class)) {
            self::markTestSkipped('Die PHP-Erweiterung „zip" fehlt — siehe .github/workflows/ci.yml.');
        }
        if (!$this->package->exists()) {
            self::markTestSkipped(sprintf(
                'Das Paket %s fehlt. Vorbedingung VB-01: Die vier Vektormarken existieren noch nicht, '
                .'deshalb schreibt `app:press:package` bewusst kein halbes Paket.',
                PressPackage::PUBLIC_PATH,
            ));
        }
    }

    /** AK-17/AK-18: Paketinhalt und Materialliste stimmen überein — keine Datei mehr, keine weniger. */
    public function testDerPaketinhaltEntsprichtDerMaterialliste(): void
    {
        $erwartet = array_map(static fn ($a) => $a->fileName(), $this->registry->assets());
        $erwartet[] = PressPackage::TERMS_ENTRY;
        sort($erwartet);

        $tatsaechlich = $this->eintraege();
        sort($tatsaechlich);

        self::assertSame($erwartet, $tatsaechlich, sprintf(
            'Paket und Vorschau laufen auseinander. Fehlt: %s · Zu viel: %s. '
            .'`make press-kit` erzeugt das Paket neu.',
            implode(', ', array_diff($erwartet, $tatsaechlich)) ?: '—',
            implode(', ', array_diff($tatsaechlich, $erwartet)) ?: '—',
        ));
    }

    /** AK-19: Die Datei trägt einen sprechenden Namen und lässt sich öffnen. */
    public function testDasPaketTraegtEinenSprechendenNamenUndLaesstSichOeffnen(): void
    {
        self::assertStringContainsString('endlech', $this->package->fileName());
        self::assertStringEndsWith('.zip', $this->package->fileName());
        self::assertGreaterThan(0, $this->package->sizeBytes());
        self::assertNotEmpty($this->eintraege(), 'Das Paket ist leer.');
    }

    /** AK-22: Die Nutzungsbedingungen liegen im Paket und sind nicht leer. */
    public function testDieNutzungsbedingungenLiegenImPaket(): void
    {
        $zip = new \ZipArchive();
        self::assertTrue(true === $zip->open($this->package->absolutePath()));

        $inhalt = $zip->getFromName(PressPackage::TERMS_ENTRY);
        $zip->close();

        self::assertIsString($inhalt, 'Die Bedingungsdatei fehlt im Paket.');
        self::assertGreaterThan(200, \strlen($inhalt), 'Die Bedingungsdatei ist verdächtig kurz.');
        foreach (['LB', 'DE', 'FR', 'EN'] as $sprache) {
            self::assertStringContainsString($sprache.' — ', $inhalt, sprintf(
                'Der Sprachabschnitt %s fehlt in der Bedingungsdatei.', $sprache,
            ));
        }
    }

    /** @return list<string> */
    private function eintraege(): array
    {
        $zip = new \ZipArchive();
        self::assertTrue(true === $zip->open($this->package->absolutePath()), 'Das Paket ließ sich nicht öffnen.');

        $namen = [];
        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $name = $zip->getNameIndex($i);
            if (false !== $name) {
                $namen[] = $name;
            }
        }
        $zip->close();

        return $namen;
    }
}
