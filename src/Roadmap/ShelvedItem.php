<?php

namespace App\Roadmap;

/**
 * Ein bewusst zurückgestelltes Vorhaben — der Block „Bewusst nicht gebaut" (AK-07).
 *
 * ⚠ **Eigener Typ, nicht ein vierter Fall von `RoadmapStage`.** Ein zurückgestellter
 * Punkt darf strukturell in keiner Spalte landen können (AK-08): Er trägt keine
 * Stufe, also gibt es keine Zuweisung, mit der er versehentlich zu einer Zusage
 * würde. Wer ihn doch bauen will, legt einen `RoadmapItem` an und entfernt ihn hier
 * — zwei bewusste Handgriffe statt eines geänderten Enum-Werts (EC-05).
 *
 * ⚠ **Der Schlüsselstamm heißt `shelved_item.`, nicht `shelved.`.** Unter `shelved.`
 * liegen bereits Überschrift und Einleitung des Abschnitts als Skalare; ein Eintrag
 * gleichen Namens wäre dort ein Baum neben einem Wert im selben Knoten.
 */
final readonly class ShelvedItem
{
    public function __construct(
        public string $key,
    ) {
    }

    public function titleKey(): string
    {
        return 'shelved_item.'.$this->key.'.title';
    }

    public function reasonKey(): string
    {
        return 'shelved_item.'.$this->key.'.reason';
    }
}
