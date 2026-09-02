<?php

namespace App\Roadmap;

/**
 * Alle Releases der Plattform mit ihrer öffentlichen Sichtbarkeit (Feature 07).
 *
 * ⚠ **Diese Liste ist gegenüber `CHANGELOG.md` vollständig.** Jede dort verzeichnete
 * Version steht hier mit genau einer `ReleaseVisibility` — das prüft
 * `ChangelogCompletenessTest` (AK-26). Wer ein Release ausliefert und hier nichts
 * einträgt, bekommt einen roten Prüflauf statt einer veralteten Seite.
 */
final readonly class ChangelogRegistry
{
    /**
     * Alle Releases, absteigend nach Datum.
     *
     * ⚠ **Die Reihenfolge dieser Liste entscheidet bei gleichem Datum.** Am
     * 8. März 2026 wurde fünfmal ausgeliefert; PHPs Sortierung ist stabil, also
     * bleibt hier die Reihenfolge stehen, in der die Einträge notiert sind.
     *
     * Die Zuordnung der Sichtbarkeit ist eine Betreiberentscheidung (OF-01, am
     * 2026-08-30 getroffen): neun Releases mit merkbarer Wirkung für Gäste, drei
     * rein technische bleiben still, die Aufbauphase fasst eine Sammelzeile
     * zusammen.
     *
     * @return list<ReleaseNote>
     */
    public function notes(): array
    {
        return [
            // Rein technisch: Container-Image, /health und trusted_proxies.
            // Ein Gast der Website merkt davon nichts — deshalb SILENT und
            // kein Text in den vier changelog.*.yaml.
            new ReleaseNote('2026.09.02', new \DateTimeImmutable('2026-09-02'), ReleaseVisibility::SILENT),
            new ReleaseNote('2026.08.31', new \DateTimeImmutable('2026-08-31'), ReleaseVisibility::SHOWN),
            new ReleaseNote('2026.08.30.2', new \DateTimeImmutable('2026-08-30'), ReleaseVisibility::SHOWN),
            new ReleaseNote('2026.08.30.1', new \DateTimeImmutable('2026-08-30'), ReleaseVisibility::SILENT),
            new ReleaseNote('2026.08.30', new \DateTimeImmutable('2026-08-30'), ReleaseVisibility::SHOWN),
            new ReleaseNote('2026.08.29.1', new \DateTimeImmutable('2026-08-29'), ReleaseVisibility::SILENT),
            new ReleaseNote('2026.08.29', new \DateTimeImmutable('2026-08-29'), ReleaseVisibility::SHOWN),
            new ReleaseNote('2026.08.09', new \DateTimeImmutable('2026-08-09'), ReleaseVisibility::SHOWN),
            new ReleaseNote('2026.08.06', new \DateTimeImmutable('2026-08-06'), ReleaseVisibility::SILENT),
            new ReleaseNote('2026.06.19', new \DateTimeImmutable('2026-06-19'), ReleaseVisibility::SHOWN),
            new ReleaseNote('2026.03.22', new \DateTimeImmutable('2026-03-22'), ReleaseVisibility::SHOWN),
            new ReleaseNote('2026.03.17', new \DateTimeImmutable('2026-03-17'), ReleaseVisibility::SHOWN),
            new ReleaseNote('2026.03.08e', new \DateTimeImmutable('2026-03-08'), ReleaseVisibility::SHOWN),
            new ReleaseNote('2026.03.08d', new \DateTimeImmutable('2026-03-08'), ReleaseVisibility::SHOWN),
            new ReleaseNote('2026.03.08c', new \DateTimeImmutable('2026-03-08'), ReleaseVisibility::SUMMARISED),
            new ReleaseNote('2026.03.08b', new \DateTimeImmutable('2026-03-08'), ReleaseVisibility::SUMMARISED),
            new ReleaseNote('2026.03.08', new \DateTimeImmutable('2026-03-08'), ReleaseVisibility::SUMMARISED),
            new ReleaseNote('2026.03.01', new \DateTimeImmutable('2026-03-01'), ReleaseVisibility::SUMMARISED),
            new ReleaseNote('2026.02.28', new \DateTimeImmutable('2026-02-28'), ReleaseVisibility::SUMMARISED),
            new ReleaseNote('2026.02.27', new \DateTimeImmutable('2026-02-27'), ReleaseVisibility::SUMMARISED),
            new ReleaseNote('2026.02.25', new \DateTimeImmutable('2026-02-25'), ReleaseVisibility::SUMMARISED),
            new ReleaseNote('2026.01.13', new \DateTimeImmutable('2026-01-13'), ReleaseVisibility::SUMMARISED),
        ];
    }

    /**
     * Die Sammelzeile für die Aufbauphase, sofern es zusammengefasste Releases gibt.
     */
    public function summary(): ?ChangelogSummary
    {
        $summarised = array_values(array_filter(
            $this->notes(),
            static fn (ReleaseNote $n): bool => ReleaseVisibility::SUMMARISED === $n->visibility,
        ));

        if ([] === $summarised) {
            return null;
        }

        $daten = array_map(static fn (ReleaseNote $n): \DateTimeImmutable => $n->date, $summarised);

        return new ChangelogSummary(min($daten), max($daten));
    }

    /**
     * Die öffentlich gezeigten Einträge, nach Jahr gruppiert, jeweils absteigend
     * nach Datum (AK-22). Die Sammelzeile hängt am Ende ihres Jahres.
     *
     * @return array<string, list<ReleaseNote|ChangelogSummary>>
     */
    public function byYear(): array
    {
        $eintraege = array_values(array_filter(
            $this->notes(),
            static fn (ReleaseNote $n): bool => $n->isShown(),
        ));

        $summary = $this->summary();
        if (null !== $summary) {
            $eintraege[] = $summary;
        }

        usort(
            $eintraege,
            static fn (ReleaseNote|ChangelogSummary $a, ReleaseNote|ChangelogSummary $b): int
                => ($b instanceof ReleaseNote ? $b->date : $b->date())
                <=> ($a instanceof ReleaseNote ? $a->date : $a->date()),
        );

        $jahre = [];
        foreach ($eintraege as $eintrag) {
            $jahre[$eintrag->year()][] = $eintrag;
        }

        return $jahre;
    }

    /**
     * Das Datum des jüngsten gezeigten Eintrags — Grundlage des
     * Aktualitätshinweises (AK-27, AK-28).
     *
     * ⚠ **Nur gezeigte Einträge zählen.** Ein stilles Release ist für den Besucher
     * nicht passiert; es dürfte die Seite nicht frisch aussehen lassen.
     */
    public function latestShownDate(): ?\DateTimeImmutable
    {
        $daten = array_map(
            static fn (ReleaseNote $n): \DateTimeImmutable => $n->date,
            array_filter($this->notes(), static fn (ReleaseNote $n): bool => $n->isShown()),
        );

        return [] === $daten ? null : max($daten);
    }
}
