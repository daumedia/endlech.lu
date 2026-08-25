<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Repository\RestaurantRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * BF-59: `%` und `_` sind LIKE-Platzhalter und hebelten den Ortsfilter aus.
 *
 * `?city=%` lieferte ALLE Restaurants statt keiner — der Filter fiel lautlos
 * weg und tat das Gegenteil dessen, wonach gefragt war. Keine Injection (der
 * Parameter ist gebunden), aber ein Filter, dem man nicht trauen kann.
 *
 * Auffällig war die Inkonsequenz: `sort`, `cuisine` und `lang` prüfen ihre
 * Eingabe, der einzige Freitextfilter nicht.
 */
final class CityFilterEscapingTest extends KernelTestCase
{
    private RestaurantRepository $repo;

    protected function setUp(): void
    {
        $this->repo = static::getContainer()->get(RestaurantRepository::class);
    }

    private function treffer(string $stadt): int
    {
        return \count($this->repo->findPaginated('name', 1, 100, ['city' => $stadt]));
    }

    #[DataProvider('platzhalter')]
    public function testPlatzhalterFindenNichts(string $eingabe): void
    {
        self::assertSame(
            0,
            $this->treffer($eingabe),
            sprintf('"%s" wirkt weiterhin als LIKE-Platzhalter.', $eingabe),
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function platzhalter(): iterable
    {
        yield 'Prozentzeichen' => ['%'];
        yield 'Unterstrich' => ['_'];
        yield 'alles maskiert' => ['%%%'];
        yield 'Unterstrich-Muster' => ['_____'];
        yield 'gemischt' => ['%_%'];
    }

    /**
     * Die Teilstringsuche muss weiter funktionieren — sonst wäre der Filter
     * repariert und zugleich kaputt.
     */
    public function testTeilstringsuchenFunktionierenWeiter(): void
    {
        $gesamt = $this->treffer('');
        self::assertGreaterThan(0, $gesamt, 'Ohne Filter muss es Treffer geben.');

        $mitTeilstring = $this->treffer('Luxem');
        self::assertGreaterThan(0, $mitTeilstring, '"Luxem" muss Luxembourg finden.');
        self::assertLessThan($gesamt, $mitTeilstring, 'Der Filter greift nicht mehr.');
    }

    /**
     * Ein Ausrufezeichen ist das Maskierzeichen und muss sich selbst maskieren.
     */
    public function testMaskierzeichenIstSelbstMaskiert(): void
    {
        self::assertSame(0, $this->treffer('!'));
        self::assertSame(0, $this->treffer('!%'));
    }
}
