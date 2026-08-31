<?php

namespace App\Roadmap;

/**
 * Wie ein Release im öffentlichen Changelog erscheint (Feature 07, AK-21, AK-26).
 *
 * ⚠ **Drei Fälle, kein `bool`.** `design.md` sah ein Feld `public: bool` vor. Das
 * trägt die Entscheidung aus OF-01 nicht: Neben „eigener Eintrag" und „still" gibt
 * es die **Sammelzeile** für die Aufbauphase Januar–März 2026, und die ist weder
 * das eine noch das andere. Mit einem Bool müsste ein Feld zwei Bedeutungen tragen
 * — genau das Muster, das dieses Projekt mit BF-89 schon einmal teuer bezahlt hat
 * („Wenn ein Feld zwei Bedeutungen trägt, ist jede Reparatur an der Reihenfolge
 * ein Aufschub").
 *
 * Für `ChangelogCompletenessTest` ist die Dreiteilung der eigentliche Gewinn: Jede
 * Version aus `CHANGELOG.md` trägt genau einen dieser Fälle, und „vergessen" ist
 * damit von „bewusst still" und „zusammengefasst" unterscheidbar.
 */
enum ReleaseVisibility: string
{
    /** Eigener Eintrag mit Titel und Text in vier Sprachen. */
    case SHOWN = 'shown';

    /** Durch die Sammelzeile „Aufbau der Plattform" abgedeckt (OF-01). */
    case SUMMARISED = 'summarised';

    /** Rein technisch, ohne merkbare Wirkung für Gäste — erscheint nicht (AK-21). */
    case SILENT = 'silent';
}
