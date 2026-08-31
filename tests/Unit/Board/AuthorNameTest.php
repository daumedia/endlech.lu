<?php

declare(strict_types=1);

namespace App\Tests\Unit\Board;

use App\Board\AuthorName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * AK-51, EC-01, EC-02.
 *
 * `User::$name` ist ein einziges Freitextfeld — was dort steht, ist nicht
 * vorhersagbar. Die Fälle unten sind deshalb keine Kuriositäten, sondern der
 * Normalfall.
 */
final class AuthorNameTest extends TestCase
{
    #[DataProvider('namen')]
    public function testAnzeigename(?string $eingetragen, ?string $erwartet): void
    {
        self::assertSame($erwartet, (new AuthorName())->forName($eingetragen));
    }

    /**
     * @return iterable<string, array{0: ?string, 1: ?string}>
     */
    public static function namen(): iterable
    {
        yield 'Vor- und Nachname' => ['Anna Berg', 'Anna B.'];
        yield 'drei Namensteile — der letzte liefert die Initiale' => ['Anna Katharina Berg', 'Anna B.'];
        yield 'nur ein Name bleibt unverändert' => ['Anna', 'Anna'];
        yield 'Kleinschreibung wird zur Großbuchstaben-Initiale' => ['anna berg', 'anna B.'];
        yield 'mehrfache Leerzeichen' => ['  Anna   Berg  ', 'Anna B.'];
        yield 'Umlaut als Initiale' => ['Jean Öllinger', 'Jean Ö.'];

        // EC-01: kein Name → null. Das Template setzt daraus den übersetzten
        // Platzhalter; hier darf kein deutscher Text entstehen.
        yield 'EC-01 · leer' => ['', null];
        yield 'EC-01 · nur Leerzeichen' => ['   ', null];
        yield 'EC-01 · null' => [null, null];

        // EC-02: ein einzelnes sehr langes Wort wird gekürzt, sonst bricht die
        // Zeile aus der Karte.
        yield 'EC-02 · 60 Zeichen ohne Leerzeichen' => [str_repeat('a', 60), str_repeat('a', 30)];

        // Ein „Nachname", der keine Buchstaben trägt, ergibt keine brauchbare
        // Initiale — dann bleibt es beim Vornamen allein statt bei „Anna ..".
        yield 'letztes Wort ohne Buchstaben' => ['Anna ...', 'Anna'];
    }

    public function testLangerVornameWirdEbenfallsGekuerzt(): void
    {
        $lang = str_repeat('b', 45);

        self::assertSame(str_repeat('b', 30) . ' B.', (new AuthorName())->forName($lang . ' Berg'));
    }
}
