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
 * Innerhalb eines bewerteten Hauses zählt ein nicht erfasstes Merkmal weiterhin
 * als nicht erfüllt. Der Wert misst damit *dokumentierte* Barrierefreiheit, nicht
 * vermutete — die einzige Lesart, die nicht heimlich zugunsten schlecht
 * gepflegter Einträge rundet.
 *
 * ⚠ **Ein Haus, über das GAR NICHTS erhoben wurde, bekommt keine Punktzahl,
 * sondern `null`** (BF-67). Vorher bekam es eine glatte Null und zog damit den
 * veröffentlichten Durchschnitt nach unten, während es zugleich die
 * Gemeindeabdeckung hob — zwei Leitzahlen auf derselben Seite, die in
 * gegenläufige Richtungen zeigten. Gemessen: `communesCovered` 8 → 9 und
 * `averageScore` 5,09 → 4,67 durch einen einzigen leeren Eintrag.
 *
 * Der Unterschied ist nicht rechnerisch, sondern sprachlich: „0 von 10" heißt
 * „nichts davon vorhanden", und das hat niemand behauptet.
 */
final class AccessibilityScore
{
    /** Anzahl der bewerteten Merkmale – zugleich der Nenner. */
    public const int CRITERIA_COUNT = 8;

    public const int MAX = 10;

    /**
     * @return int|null `null`, wenn zu diesem Haus nichts erhoben wurde
     */
    public static function forRestaurant(Restaurant $restaurant): ?int
    {
        if (!$restaurant->isAssessed()) {
            return null;
        }

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
