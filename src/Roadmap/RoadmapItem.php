<?php

namespace App\Roadmap;

/**
 * Ein kuratiertes Vorhaben des Betreibers auf der Roadmap (Feature 07).
 *
 * ⚠ **Der Begründungssatz ist Bestandteil des Wertobjekts, nicht optional.**
 * `reasonKey()` gehört zu jedem Eintrag, und `RoadmapCatalogueTest` verlangt ihn in
 * vier Sprachen. Damit ist AK-05 („kein Eintrag besteht nur aus einem Titel") und
 * AK-29 („es gibt keinen Eintrag ohne Herkunft") strukturell erzwungen statt
 * erbeten — ein Eintrag ohne Begründung färbt den Prüflauf rot, bevor ihn jemand
 * sieht.
 *
 * ⚠ **Kein Datumsfeld, und das ist der Punkt.** AK-06 verlangt, dass an keinem
 * Eintrag ein Datum, ein Quartal oder eine Fortschrittsangabe steht. Was es als
 * Feld nicht gibt, kann kein Template versehentlich rendern.
 */
final readonly class RoadmapItem
{
    public function __construct(
        public string $key,
        public RoadmapStage $stage,
    ) {
    }

    public function titleKey(): string
    {
        return 'item.'.$this->key.'.title';
    }

    public function reasonKey(): string
    {
        return 'item.'.$this->key.'.reason';
    }
}
