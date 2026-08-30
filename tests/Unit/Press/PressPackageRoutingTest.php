<?php

declare(strict_types=1);

namespace App\Tests\Unit\Press;

use App\Press\PressPackage;
use PHPUnit\Framework\TestCase;

/**
 * Das Materialpaket wird statisch ausgeliefert (Feature 05, AK-40).
 *
 * ⚠ **Der Nachweis ist eine Abwesenheit**, und die ist leicht zu übersehen: Es
 * darf keine Controller-Methode geben, die das Paket zusammenstellt oder
 * ausliefert. Sobald eine existierte, fiele der Weg unter die Projektkonvention
 * „ein Weg, der bei jedem Aufruf den gesamten Bestand lädt, braucht einen
 * Deckel" — und niemand hätte einen gesetzt, weil die Aufgabe nie gestellt
 * wurde.
 *
 * Geprüft wird deshalb der Quelltext selbst: Kein `#[Route]` im Projekt nennt
 * den Pfad des Pakets, und der Pressecontroller reicht die Datei nirgends durch
 * (`BinaryFileResponse`, `readfile`, `file_get_contents`).
 */
final class PressPackageRoutingTest extends TestCase
{
    public function testKeineRouteZeigtAufDasPaket(): void
    {
        $treffer = [];
        foreach ($this->controllerDateien() as $datei) {
            $inhalt = (string) file_get_contents($datei);
            if (str_contains($inhalt, PressPackage::PUBLIC_PATH) || str_contains($inhalt, '.zip')) {
                $treffer[] = basename($datei);
            }
        }

        self::assertSame([], $treffer, sprintf(
            'Diese Controller nennen die Paketdatei: %s. Das Paket wird vom Webserver ausgeliefert, nicht von PHP.',
            implode(', ', $treffer),
        ));
    }

    public function testDerPressecontrollerReichtKeineDateiDurch(): void
    {
        $inhalt = (string) file_get_contents(\dirname(__DIR__, 3).'/src/Controller/PressController.php');

        foreach (['BinaryFileResponse', 'readfile', 'file_get_contents', 'StreamedResponse'] as $verboten) {
            self::assertStringNotContainsString($verboten, $inhalt, sprintf(
                'Der Pressecontroller liefert eine Datei aus (%s) — dann bräuchte der Weg einen Deckel.',
                $verboten,
            ));
        }
    }

    /** @return list<string> */
    private function controllerDateien(): array
    {
        $verzeichnis = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(\dirname(__DIR__, 3).'/src/Controller'),
        );

        $dateien = [];
        foreach ($verzeichnis as $datei) {
            if ($datei instanceof \SplFileInfo && 'php' === $datei->getExtension()) {
                $dateien[] = $datei->getPathname();
            }
        }

        return $dateien;
    }
}
