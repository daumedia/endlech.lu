<?php

namespace App\Tests\Unit\Open;

use App\Enum\Canton;
use App\Open\CantonResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CantonResolverTest extends TestCase
{
    private CantonResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new CantonResolver();
    }

    public function testCountsAllHundredCommunes(): void
    {
        self::assertSame(100, $this->resolver->totalCommunes(), 'Luxemburg hat seit den Fusionen von 2024 genau 100 Gemeinden.');
    }

    /**
     * Der Nenner der Kantonsquote steht im Enum, die Gemeindeliste im
     * Resolver. Laufen beide auseinander, zeigt die Seite Quoten über 100 %
     * oder verschluckt Gemeinden – ohne dass irgendwo ein Fehler auftritt.
     */
    public function testCantonCommuneCountsMatchTheCommuneList(): void
    {
        $counted = array_fill_keys(array_map(static fn (Canton $c) => $c->value, Canton::cases()), 0);

        foreach ($this->allCommunes() as $commune) {
            ++$counted[$this->resolver->resolveCanton($commune)->value];
        }

        foreach (Canton::cases() as $canton) {
            self::assertSame(
                $canton->communeCount(),
                $counted[$canton->value],
                sprintf('Kanton %s: Enum sagt %d Gemeinden, die Liste enthält %d.', $canton->label(), $canton->communeCount(), $counted[$canton->value]),
            );
        }
    }

    #[DataProvider('cityProvider')]
    public function testResolvesCityToCommuneAndCanton(string $city, ?string $commune, ?Canton $canton): void
    {
        self::assertSame($commune, $this->resolver->resolveCommune($city));
        self::assertSame($canton, $this->resolver->resolveCanton($city));
    }

    /**
     * @return iterable<string, array{0: string, 1: string|null, 2: Canton|null}>
     */
    public static function cityProvider(): iterable
    {
        yield 'Gemeinde direkt' => ['Strassen', 'Strassen', Canton::LUXEMBOURG];
        yield 'Akzent' => ['Pétange', 'Pétange', Canton::ESCH_SUR_ALZETTE];
        yield 'ohne Akzent geschrieben' => ['Petange', 'Pétange', Canton::ESCH_SUR_ALZETTE];
        yield 'luxemburgisch' => ['Lëtzebuerg', 'Luxembourg', Canton::LUXEMBOURG];
        yield 'Stadtteil' => ['Bonnevoie', 'Luxembourg', Canton::LUXEMBOURG];
        yield 'zusammengesetzt' => ['Luxembourg-Grund', 'Luxembourg', Canton::LUXEMBOURG];
        yield 'Ortschaft in anderer Gemeinde' => ['Belval', 'Sanem', Canton::ESCH_SUR_ALZETTE];
        yield 'Schrägstrich' => ['Esch/Alzette', 'Esch-sur-Alzette', Canton::ESCH_SUR_ALZETTE];
        yield 'fusionierte Gemeinde' => ['Bous', 'Bous-Waldbredimus', Canton::REMICH];
        yield 'Kleinschreibung' => ['strassen', 'Strassen', Canton::LUXEMBOURG];
        yield 'unbekannt' => ['Atlantis', null, null];
        yield 'leer' => ['', null, null];
    }

    /**
     * "Gare" ist ein Stadtteil der Stadt Luxemburg. Stünde er im selben Index
     * wie die Gemeinden, landete jede Adresse mit "rue de la Gare" in
     * Luxemburg statt in ihrer echten Gemeinde.
     */
    public function testStreetNoiseDoesNotOutweighARealCommune(): void
    {
        self::assertSame('Strassen', $this->resolver->resolveCommune('1 rue de la Gare, Strassen'));
    }

    /**
     * Kayl ist eine eigene Gemeinde und steht zugleich in der Alias-Tabelle
     * ("kayl-tetange"). Der Gemeindeeintrag muss gewinnen.
     */
    public function testCommuneWinsOverAlias(): void
    {
        self::assertSame('Kayl', $this->resolver->resolveCommune('Kayl'));
    }

    /**
     * @return list<string>
     */
    private function allCommunes(): array
    {
        $reflection = new \ReflectionClass(CantonResolver::class);
        /** @var array<string, list<string>> $byCanton */
        $byCanton = $reflection->getConstant('COMMUNES_BY_CANTON');

        return array_merge(...array_values($byCanton));
    }
}
