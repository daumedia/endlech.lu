<?php

namespace App\Service;

use App\Entity\Restaurant;

class OpeningHoursService
{
    private const TIMEZONE = 'Europe/Luxembourg';

    public function isOpenNow(Restaurant $restaurant): bool
    {
        return $this->isOpenAt($restaurant, new \DateTimeImmutable('now', new \DateTimeZone(self::TIMEZONE)));
    }

    public function isOpenAt(Restaurant $restaurant, \DateTimeInterface $dateTime): bool
    {
        $tz = new \DateTimeZone(self::TIMEZONE);
        $dt = \DateTimeImmutable::createFromInterface($dateTime)->setTimezone($tz);

        $currentDay = (int) $dt->format('N');
        $currentTime = $dt->format('H:i:s');

        foreach ($restaurant->getOpeningHoursForDay($currentDay) as $slot) {
            if ($slot->getOpenTime() === null || $slot->getCloseTime() === null) {
                continue;
            }

            $open = $slot->getOpenTime()->format('H:i:s');
            $close = $slot->getCloseTime()->format('H:i:s');

            if ($open <= $close) {
                if ($currentTime >= $open && $currentTime < $close) {
                    return true;
                }
            } else {
                if ($currentTime >= $open) {
                    return true;
                }
            }
        }

        $previousDay = $currentDay === 1 ? 7 : $currentDay - 1;

        foreach ($restaurant->getOpeningHoursForDay($previousDay) as $slot) {
            if ($slot->getOpenTime() === null || $slot->getCloseTime() === null) {
                continue;
            }

            $open = $slot->getOpenTime()->format('H:i:s');
            $close = $slot->getCloseTime()->format('H:i:s');

            if ($open > $close && $currentTime < $close) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{dayOfWeek: int, time: \DateTimeInterface}|null
     */
    public function getNextOpeningTime(Restaurant $restaurant, ?\DateTimeInterface $now = null): ?array
    {
        $tz = new \DateTimeZone(self::TIMEZONE);
        $reference = $now !== null
            ? \DateTimeImmutable::createFromInterface($now)->setTimezone($tz)
            : new \DateTimeImmutable('now', $tz);

        if ($this->isOpenAt($restaurant, $reference)) {
            return null;
        }

        $currentDay = (int) $reference->format('N');
        $currentTime = $reference->format('H:i:s');

        // Heute: frühester Slot, der noch öffnet.
        $nextToday = null;
        foreach ($restaurant->getOpeningHoursForDay($currentDay) as $slot) {
            if ($slot->getOpenTime() === null) {
                continue;
            }
            $openTime = $slot->getOpenTime()->format('H:i:s');
            if ($openTime > $currentTime && ($nextToday === null || $openTime < $nextToday->format('H:i:s'))) {
                $nextToday = $slot->getOpenTime();
            }
        }
        if ($nextToday !== null) {
            return ['dayOfWeek' => $currentDay, 'time' => $nextToday];
        }

        // Folgetage: erster Tag mit Slots, dessen frühester Slot.
        //
        // ⚠ BF-61: Die Schleife läuft bis 7, nicht bis 6. Der siebte Durchlauf ist
        // wieder der heutige Wochentag – eine Woche später. Bei einem Haus, das nur
        // an einem Tag öffnet (Sonntagsbrunch, Wochenmarkt, Vereinslokal), waren
        // dessen Slots im Schritt davor als „schon vorbei" verworfen worden, und die
        // Seite zeigte danach weder „geöffnet" noch eine nächste Öffnung. Sie sah
        // dabei nicht kaputt aus — niemand vermisst, was er nicht kennt.
        for ($i = 1; $i <= 7; ++$i) {
            $day = (($currentDay - 1 + $i) % 7) + 1;
            $earliest = null;
            foreach ($restaurant->getOpeningHoursForDay($day) as $slot) {
                if ($slot->getOpenTime() === null) {
                    continue;
                }
                if ($earliest === null || $slot->getOpenTime()->format('H:i:s') < $earliest->format('H:i:s')) {
                    $earliest = $slot->getOpenTime();
                }
            }
            if ($earliest !== null) {
                return ['dayOfWeek' => $day, 'time' => $earliest];
            }
        }

        return null;
    }
}
