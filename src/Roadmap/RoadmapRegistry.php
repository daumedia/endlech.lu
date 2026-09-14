<?php

namespace App\Roadmap;

/**
 * Die einzige Stelle, an der die kuratierten Vorhaben und die zurückgestellten
 * Punkte stehen (Feature 07).
 *
 * Kein Zustand, keine Datenbank, kein Admin-Formular: Die Liste ändert sich im Takt
 * von Releases, und ein Release ist ohnehin ein Deploy. Dasselbe Muster wie
 * `App\Press\PressRegistry` (Feature 05) und `App\Comparison\ComparisonRegistry`
 * (Feature 03).
 *
 * ⚠ **Die Zuordnung zu den Spalten ist eine Betreiberentscheidung** (VB-02, am
 * 2026-08-30 entschieden) und folgt dem Abschnitt „Vorschlag: Reihenfolge" in
 * `docs/prd.md`. Wer hier etwas verschiebt, verschiebt eine öffentliche Zusage —
 * und prüft die Roadmap laut OF-03 bei jedem Release ohnehin durch.
 */
final readonly class RoadmapRegistry
{
    /**
     * Die kuratierten Vorhaben, in der Reihenfolge, in der sie erscheinen.
     *
     * Herkunft: die Tabelle „Belegt offen" in `docs/prd.md`. Die Zuordnung zu den
     * Spalten folgt dem dortigen Abschnitt „Vorschlag: Reihenfolge".
     *
     * @return list<RoadmapItem>
     */
    public function items(): array
    {
        return [
            new RoadmapItem('roadmap_changelog', RoadmapStage::IN_PROGRESS),

            new RoadmapItem('reviews', RoadmapStage::PLANNED),
            new RoadmapItem('map', RoadmapStage::PLANNED),
            new RoadmapItem('favourites', RoadmapStage::PLANNED),

            new RoadmapItem('ios_app', RoadmapStage::CONSIDERED),
            new RoadmapItem('chat_widget', RoadmapStage::CONSIDERED),
            new RoadmapItem('ai_filter', RoadmapStage::CONSIDERED),
            new RoadmapItem('android_and_logins', RoadmapStage::CONSIDERED),
        ];
    }

    /**
     * Die Vorhaben einer Spalte, in der Reihenfolge dieser Liste.
     *
     * @return list<RoadmapItem>
     */
    public function itemsFor(RoadmapStage $stage): array
    {
        return array_values(array_filter(
            $this->items(),
            static fn (RoadmapItem $item): bool => $item->stage === $stage,
        ));
    }

    /**
     * Die acht bewusst zurückgestellten Punkte aus `CLAUDE.md`.
     *
     * Sie stehen dort seit Feature-Entscheidungen, die jeweils begründet wurden;
     * hier werden sie zum ersten Mal öffentlich sichtbar (AK-07).
     *
     * @return list<ShelvedItem>
     */
    public function shelved(): array
    {
        return [
            new ShelvedItem('passkey_conditional_ui'),
            new ShelvedItem('passkey_signup'),
            new ShelvedItem('passkey_api'),
            new ShelvedItem('passkey_attestation'),
            new ShelvedItem('apple_splash'),
            new ShelvedItem('pull_to_refresh'),
            new ShelvedItem('push_notifications'),
            new ShelvedItem('mobile_audit'),
        ];
    }
}
