<?php

namespace App\Comparison;

/**
 * Eine Zeile der Merkmalstabelle.
 *
 * ⚠ `ownNoteKey` und `theirNoteKey` sind **Pflicht**, nicht optional (AK-09).
 * Ohne den erklärenden Halbsatz stünde in der Zelle nur ein Symbol, und die
 * Tabelle wäre eine Behauptung statt einer Aussage. „✓" beantwortet nicht, was
 * genau erfüllt ist.
 *
 * ⚠ `sourceRef` ist Pflicht, sobald die Zeile eine Aussage über den Wettbewerber
 * trifft – also immer, außer die Zeile beschreibt ausschließlich Endlech.lu.
 * `ComparisonRegistryTest` erzwingt das; eine unbelegte Behauptung über ein
 * fremdes Unternehmen ist der teuerste Fehler, den dieses Feature machen kann.
 */
final readonly class ComparisonRow
{
    /**
     * @param string      $labelKey     Textschlüssel des Merkmals, Domain `comparison`
     * @param string      $ownNoteKey   erklärender Halbsatz in der eigenen Spalte
     * @param string      $theirNoteKey erklärender Halbsatz in der Spalte des Wettbewerbers
     * @param int|null    $sourceRef    Nummer der Fußnote, die die Aussage belegt
     * @param string|null $figure       Name einer Kennzahl aus ComparisonFigures, die
     *                                  als Platzhalter %figure% in den eigenen
     *                                  Halbsatz eingesetzt wird
     */
    public function __construct(
        public ComparisonGroup $group,
        public string $labelKey,
        public Verdict $own,
        public Verdict $theirs,
        public string $ownNoteKey,
        public string $theirNoteKey,
        public ?int $sourceRef = null,
        public ?string $figure = null,
    ) {
    }
}
