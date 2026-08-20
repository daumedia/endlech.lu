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

    public function testEmptyRestaurantScoresZero(): void
    {
        self::assertSame(0, AccessibilityScore::forRestaurant(new Restaurant()));
    }

    /**
     * Kernaussage der Punktzahl: Sie misst dokumentierte Barrierefreiheit.
     * Ein nie ausgemessenes Maß darf nicht wie ein erfülltes Merkmal zählen –
     * sonst belohnte die Zahl fehlende Pflege.
     */
    public function testUnmeasuredDimensionsDoNotCount(): void
    {
        $restaurant = (new Restaurant())
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
        $restaurant = (new Restaurant())->setDoorWidthCm(Restaurant::MIN_DOOR_WIDTH_CM - 1);

        self::assertFalse($restaurant->hasWideDoors());
        self::assertSame(0, AccessibilityScore::forRestaurant($restaurant));
    }

    public function testExactMinimumCounts(): void
    {
        $restaurant = (new Restaurant())->setDoorWidthCm(Restaurant::MIN_DOOR_WIDTH_CM);

        self::assertTrue($restaurant->hasWideDoors());
    }
}
