<?php

declare(strict_types=1);

namespace App\Tests\Unit\Press;

use App\Press\BoilerplateLength;
use App\Press\PressAssetKind;
use App\Press\PressRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Struktur des Presse-Kits (Feature 05).
 *
 * Die Spec verspricht Eigenschaften, die man der Seite nicht ansieht: drei
 * Textlängen, jede Markenvariante genau einmal, mindestens zwei freigegebene
 * Zitate, ein Fotocredit am Porträt. Ohne diesen Lauf verschwindet all das beim
 * ersten Mal, an dem jemand unter Zeitdruck eine Datei austauscht.
 *
 * ⚠ Der schärfste Test ist `testJedeMaterialartGenauEinmal`. Ein Paket ohne
 * Dunkelvariante zwingt eine Redaktion, die auf dunklem Grund layoutet, das Logo
 * selbst umzufärben — und genau das verbieten die Nutzungsbedingungen, die im
 * selben Paket liegen.
 */
final class PressRegistryTest extends TestCase
{
    private PressRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new PressRegistry();
    }

    /** AK-07: drei Längen, jede genau einmal. */
    public function testDreiBeschreibungstexteInDreiLaengen(): void
    {
        $laengen = $this->registry->boilerplates();

        self::assertCount(3, $laengen);
        self::assertSame(
            [BoilerplateLength::SHORT, BoilerplateLength::MEDIUM, BoilerplateLength::LONG],
            $laengen,
            'Die Reihenfolge ist die Leserichtung: kurz vor mittel vor lang.',
        );
    }

    /** AK-08: Die Grenzen stehen im Enum und sind aufsteigend und disjunkt-sinnvoll. */
    public function testDieWortgrenzenSindPlausibel(): void
    {
        foreach ($this->registry->boilerplates() as $laenge) {
            self::assertLessThan(
                $laenge->maxWords(),
                $laenge->minWords(),
                sprintf('%s: Untergrenze liegt nicht unter der Obergrenze.', $laenge->value),
            );
            self::assertGreaterThanOrEqual($laenge->minWords(), $laenge->approxWords());
            self::assertLessThanOrEqual($laenge->maxWords(), $laenge->approxWords());
        }
    }

    /** AK-18: jede der fünf Materialarten genau einmal — keine fehlt, keine doppelt. */
    public function testJedeMaterialartGenauEinmal(): void
    {
        $arten = array_map(static fn ($a) => $a->kind, $this->registry->assets());

        self::assertCount(\count(PressAssetKind::cases()), $arten);
        foreach (PressAssetKind::cases() as $art) {
            self::assertSame(
                1,
                \count(array_filter($arten, static fn ($a) => $a === $art)),
                sprintf('Materialart %s ist nicht genau einmal vertreten.', $art->value),
            );
        }
    }

    /** AK-16/AK-35: jede Datei trägt Pfad, Format und eine Bezeichnung. */
    public function testJedesMaterialTraegtPfadFormatUndBezeichnung(): void
    {
        foreach ($this->registry->assets() as $asset) {
            self::assertNotSame('', trim($asset->publicPath));
            self::assertStringStartsNotWith('/', $asset->publicPath, 'Pfade sind relativ zu public/.');
            self::assertNotSame('', trim($asset->format));
            self::assertNotSame('', trim($asset->labelKey()));
            self::assertNotSame('', trim($asset->fileName()));
        }
    }

    /** AK-24: Ein Porträt ohne Urheberangabe ist nicht freigabefähig. */
    public function testDasPortraetTraegtEinenBildnachweis(): void
    {
        foreach ($this->registry->assets() as $asset) {
            if ($asset->kind->isPortrait()) {
                self::assertNotNull($asset->creditKey, 'Das Porträt hat keinen Fotocredit.');
                self::assertNotSame('', trim((string) $asset->creditKey));

                return;
            }
        }

        self::fail('Es gibt kein Porträt im Material.');
    }

    /** AK-25: mindestens zwei Zitate, jedes mit Name und Funktion. */
    public function testMindestensZweiZitateMitNameUndFunktion(): void
    {
        $zitate = $this->registry->quotes();

        self::assertGreaterThanOrEqual(2, \count($zitate));
        foreach ($zitate as $zitat) {
            self::assertNotSame('', trim($zitat->textKey));
            self::assertNotSame('', trim($zitat->personName));
            self::assertNotSame('', trim($zitat->roleKey));
        }
    }

    /** AK-26: Meldungen stehen absteigend — die neueste zuerst. */
    public function testMeldungenStehenAbsteigend(): void
    {
        $daten = array_map(
            static fn ($r) => $r->date->getTimestamp(),
            $this->registry->releases(),
        );
        $sortiert = $daten;
        rsort($sortiert);

        self::assertSame($sortiert, $daten, 'Die Meldungen stehen nicht mit der neuesten zuerst.');
    }
}
