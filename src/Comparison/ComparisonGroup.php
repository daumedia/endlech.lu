<?php

namespace App\Comparison;

/**
 * Die vier Gruppen der Merkmalstabelle, in Reihenfolge der Darstellung.
 *
 * Die Reihenfolge ist eine Aussage: Erst was erfasst wird, dann woher es kommt,
 * dann wie viel davon da ist, dann unter welchen Bedingungen. COVERAGE steht
 * an dritter Stelle und nicht am Ende – es ist die Gruppe, in der Endlech.lu
 * gegen jeden der vier Wettbewerber verliert, und sie gehört mitten in die
 * Tabelle statt in eine Fußnote (Produktprinzip 2: „Lücken werden gezeigt,
 * nicht versteckt").
 */
enum ComparisonGroup: string
{
    case ACCESSIBILITY_DATA = 'accessibility_data';
    case PROVENANCE = 'provenance';
    case COVERAGE = 'coverage';
    case OPENNESS = 'openness';

    public function transKey(): string
    {
        return 'group.' . $this->value;
    }
}
