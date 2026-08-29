<?php

namespace App\Tests\Unit\Open;

use App\Entity\Restaurant;
use App\Open\AccessibilityScore;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AccessibilityScoreTest extends TestCase
{
    #[DataProvider('flagProvider')]
    public function testScoreScalesLinearly(int $met, int $expected): void
    {
        $flags = array_merge(
            array_fill(0, $met, true),
            array_fill(0, AccessibilityScore::CRITERIA_COUNT - $met, false),
        );

        self::assertSame($expected, AccessibilityScore::fromFlags($flags));
    }

    /**
     * @return iterable<string, array{0: int, 1: int}>
     */
    public static function flagProvider(): iterable
    {
        yield 'keins erfüllt' => [0, 0];
        yield 'eins von acht' => [1, 1];
        yield 'die Hälfte' => [4, 5];
        yield 'sieben von acht' => [7, 9];
        yield 'alle' => [8, 10];
    }

    /**
     * BF-67: Ein Haus, über das GAR NICHTS erhoben wurde, bekommt keine
     * Punktzahl — `null`, nicht 0.
     *
     * Vorher zog eine glatte Null den veröffentlichten Durchschnitt nach unten,
     * während dasselbe Haus die Gemeindeabdeckung hob. Gemessen auf `/open`:
     * `communesCovered` 8 → 9 und `averageScore` 5,09 → 4,67 durch einen
     * einzigen leeren Eintrag. Der Unterschied ist sprachlich: „0 von 10" heißt
     * „nichts davon vorhanden", und das hat niemand behauptet.
     */
    public function testNichtBewertetesRestaurantBekommtKeinePunktzahl(): void
    {
        self::assertNull(AccessibilityScore::forRestaurant(new Restaurant()));
    }

    /**
     * Ein bewertetes Haus, bei dem nichts zutrifft, bekommt dagegen sehr wohl
     * eine Null — dort hat jemand hingesehen.
     */
    public function testBewertetesRestaurantOhneMerkmaleBekommtNull(): void
    {
        $restaurant = (new Restaurant())->setAssessedFeatures(Restaurant::assessableFeatures());

        self::assertSame(0, AccessibilityScore::forRestaurant($restaurant));
    }

    /**
     * Kernaussage der Punktzahl: Sie misst dokumentierte Barrierefreiheit.
     * Ein nie ausgemessenes Maß darf nicht wie ein erfülltes Merkmal zählen –
     * sonst belohnte die Zahl fehlende Pflege.
     */
    public function testUnmeasuredDimensionsDoNotCount(): void
    {
        $restaurant = (new Restaurant())
            ->setAssessedFeatures(Restaurant::assessableFeatures())
            ->setIsWheelchairAccessible(true)
            ->setHasAccessibleToilet(true)
            ->setAllowsAssistanceDogs(true)
            ->setHasBrightLighting(true)
            ->setHasChangingTable(true)
            ->setHasDisabledParking(true);

        self::assertNull($restaurant->hasWideDoors());
        self::assertSame(8, AccessibilityScore::forRestaurant($restaurant), 'Sechs von acht Merkmalen entsprechen 8 Punkten.');

        $restaurant->setDoorWidthCm(90)->setTableSpacingCm(95);
        self::assertSame(10, AccessibilityScore::forRestaurant($restaurant));
    }

    public function testDoorNarrowerThanTheMinimumDoesNotCount(): void
    {
        $restaurant = (new Restaurant())
            ->setAssessedFeatures(Restaurant::assessableFeatures())
            ->setDoorWidthCm(Restaurant::MIN_DOOR_WIDTH_CM - 1);

        self::assertFalse($restaurant->hasWideDoors());
        self::assertSame(0, AccessibilityScore::forRestaurant($restaurant));
    }

    public function testExactMinimumCounts(): void
    {
        $restaurant = (new Restaurant())->setDoorWidthCm(Restaurant::MIN_DOOR_WIDTH_CM);

        self::assertTrue($restaurant->hasWideDoors());
    }
}
