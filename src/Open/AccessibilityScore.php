<?php

namespace App\Open;

use App\Entity\Restaurant;

/**
 * Barrierefreiheits-Punktzahl eines Restaurants auf einer Skala von 0 bis 10.
 *
 * Die Formel ist bewusst stumpf: acht gleichgewichtete Merkmale, Anteil mal
 * zehn. Eine Gewichtung ("Rampe zählt dreifach") wäre fachlich vertretbar,
 * aber auf einer Transparenzseite nicht mehr nachvollziehbar – Leser sollen
 * die Zahl selbst nachrechnen können.
 *
 * Nicht erfasste Türbreiten und Tischabstände zählen als nicht erfüllt. Der
 * Wert misst damit *dokumentierte* Barrierefreiheit, nicht vermutete. Das ist
 * die einzige Lesart, die nicht heimlich zugunsten schlecht gepflegter
 * Einträge rundet.
 */
final class AccessibilityScore
{
    /** Anzahl der bewerteten Merkmale – zugleich der Nenner. */
    public const int CRITERIA_COUNT = 8;

    public const int MAX = 10;

    public static function forRestaurant(Restaurant $restaurant): int
    {
        return self::fromFlags([
            $restaurant->isWheelchairAccessible(),
            $restaurant->hasAccessibleToilet(),
            $restaurant->allowsAssistanceDogs(),
            $restaurant->hasBrightLighting(),
            $restaurant->hasChangingTable(),
            $restaurant->hasDisabledParking(),
            true === $restaurant->hasWideDoors(),
            true === $restaurant->hasWheelchairTableSpacing(),
        ]);
    }

    /**
     * @param list<bool> $flags genau CRITERIA_COUNT Merkmale
     */
    public static function fromFlags(array $flags): int
    {
        $met = \count(array_filter($flags));

        return (int) round($met / self::CRITERIA_COUNT * self::MAX);
    }
}
