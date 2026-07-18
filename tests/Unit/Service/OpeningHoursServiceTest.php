<?php

namespace App\Tests\Unit\Service;

use App\Entity\OpeningHour;
use App\Entity\Restaurant;
use App\Service\OpeningHoursService;
use PHPUnit\Framework\TestCase;

class OpeningHoursServiceTest extends TestCase
{
    private const TZ = 'Europe/Luxembourg';

    private function slot(int $day, string $open, string $close): OpeningHour
    {
        $oh = new OpeningHour();
        $oh->setDayOfWeek($day);
        $oh->setOpenTime(new \DateTime($open));
        $oh->setCloseTime(new \DateTime($close));

        return $oh;
    }

    private function at(string $datetime): \DateTimeImmutable
    {
        return new \DateTimeImmutable($datetime, new \DateTimeZone(self::TZ));
    }

    public function testOpenDuringLunchSlot(): void
    {
        $restaurant = new Restaurant();
        // Mittwoch (3): Mittag 12:00–14:30, Abend 18:00–22:00
        $restaurant->addOpeningHour($this->slot(3, '12:00', '14:30'));
        $restaurant->addOpeningHour($this->slot(3, '18:00', '22:00'));

        $service = new OpeningHoursService();

        // 2026-06-17 ist ein Mittwoch.
        self::assertTrue($service->isOpenAt($restaurant, $this->at('2026-06-17 13:00')));
    }

    public function testClosedInGapBetweenSlots(): void
    {
        $restaurant = new Restaurant();
        $restaurant->addOpeningHour($this->slot(3, '12:00', '14:30'));
        $restaurant->addOpeningHour($this->slot(3, '18:00', '22:00'));

        $service = new OpeningHoursService();

        self::assertFalse($service->isOpenAt($restaurant, $this->at('2026-06-17 16:00')));
    }

    public function testOpenDuringDinnerSlot(): void
    {
        $restaurant = new Restaurant();
        $restaurant->addOpeningHour($this->slot(3, '12:00', '14:30'));
        $restaurant->addOpeningHour($this->slot(3, '18:00', '22:00'));

        $service = new OpeningHoursService();

        self::assertTrue($service->isOpenAt($restaurant, $this->at('2026-06-17 19:00')));
    }

    public function testOvernightSlotSpillsIntoNextDay(): void
    {
        $restaurant = new Restaurant();
        // Freitag (5): 18:00–01:00 (Nachtschicht)
        $restaurant->addOpeningHour($this->slot(5, '18:00', '01:00'));

        $service = new OpeningHoursService();

        // 2026-06-20 00:30 ist Samstag früh – die Freitag-Nachtschicht läuft noch.
        self::assertTrue($service->isOpenAt($restaurant, $this->at('2026-06-20 00:30')));
        // Samstag 02:00 ist geschlossen.
        self::assertFalse($service->isOpenAt($restaurant, $this->at('2026-06-20 02:00')));
    }

    public function testNextOpeningTimeIsNextSlotSameDay(): void
    {
        $restaurant = new Restaurant();
        $restaurant->addOpeningHour($this->slot(3, '12:00', '14:30'));
        $restaurant->addOpeningHour($this->slot(3, '18:00', '22:00'));

        $service = new OpeningHoursService();

        $next = $service->getNextOpeningTime($restaurant, $this->at('2026-06-17 16:00'));

        self::assertNotNull($next);
        self::assertSame(3, $next['dayOfWeek']);
        self::assertSame('18:00', $next['time']->format('H:i'));
    }

    public function testNextOpeningTimeRollsToFollowingDay(): void
    {
        $restaurant = new Restaurant();
        $restaurant->addOpeningHour($this->slot(3, '12:00', '14:30'));
        $restaurant->addOpeningHour($this->slot(3, '18:00', '22:00'));
        // Donnerstag (4): nur Mittag
        $restaurant->addOpeningHour($this->slot(4, '12:00', '14:30'));

        $service = new OpeningHoursService();

        // Mittwoch 23:00 – heute schon vorbei, nächster offener Slot ist Donnerstag 12:00.
        $next = $service->getNextOpeningTime($restaurant, $this->at('2026-06-17 23:00'));

        self::assertNotNull($next);
        self::assertSame(4, $next['dayOfWeek']);
        self::assertSame('12:00', $next['time']->format('H:i'));
    }

    public function testDayWithoutSlotsIsClosed(): void
    {
        $restaurant = new Restaurant();
        // Nur Mittwoch gepflegt – Donnerstag hat keine Slots.
        $restaurant->addOpeningHour($this->slot(3, '12:00', '14:30'));

        $service = new OpeningHoursService();

        // 2026-06-18 ist Donnerstag.
        self::assertFalse($service->isOpenAt($restaurant, $this->at('2026-06-18 13:00')));
    }
}
