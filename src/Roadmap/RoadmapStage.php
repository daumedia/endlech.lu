<?php

namespace App\Roadmap;

/**
 * Die drei Spalten der öffentlichen Roadmap (Feature 07, AK-04).
 *
 * ⚠ **Die Reihenfolge der Fälle IST die Reihenfolge der Spalten.** Das Template
 * iteriert über `cases()`; eine vierte Spalte gäbe es nur mit einem vierten Fall.
 * Wer hier umsortiert, sortiert die Seite um.
 *
 * ⚠ **Kein Fall trägt einen Zeitbezug.** „Bewusst nicht gebaut" ist ausdrücklich
 * KEIN Fall dieses Enums, sondern ein eigener Typ (`ShelvedItem`) und ein eigener
 * Abschnitt — sonst könnte ein zurückgestellter Punkt durch eine einzige geänderte
 * Zeile in einer Spalte landen und wie eine Zusage aussehen (AK-08).
 */
enum RoadmapStage: string
{
    case IN_PROGRESS = 'in_progress';
    case PLANNED = 'planned';
    case CONSIDERED = 'considered';

    /**
     * ⚠ **Flache, in PHP zusammengesetzte Schlüssel** — derselbe Fall wie bei
     * `BoardIdeaStatus::transKey()`. Der Scanner in `CatalogueCompletenessTest`
     * sieht nur literale Schlüssel in Templates und Formularen und findet diese
     * hier nicht. Die Lücke schließt `RoadmapCatalogueTest` (T13); ohne ihn fiele
     * ein fehlender Schlüssel erst im Browser auf, als roher Schlüsselname.
     */
    public function transKey(): string
    {
        return 'stage.'.$this->value.'.title';
    }

    /** Der erklärende Satz, wenn die Spalte keinen Eintrag trägt (AK-09). */
    public function emptyKey(): string
    {
        return 'stage.'.$this->value.'.empty';
    }

    public function emoji(): string
    {
        return match ($this) {
            self::IN_PROGRESS => '🔨',
            self::PLANNED => '📌',
            self::CONSIDERED => '💭',
        };
    }

    /**
     * ⚠ **Dieselben Farbstufen wie `BoardIdeaStatus`**, damit eine übernommene
     * Community-Idee sich nicht anders liest als ein eigenes Vorhaben: `planned`
     * ist dort wie hier purple. Bernstein bleibt der Warnfarbe des
     * Aktualitätshinweises vorbehalten (Diagramm-Regeln in CLAUDE.md).
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::IN_PROGRESS => 'bg-cyan-100 text-cyan-800',
            self::PLANNED => 'bg-purple-100 text-purple-700',
            self::CONSIDERED => 'bg-gray-200 text-gray-700',
        };
    }
}
