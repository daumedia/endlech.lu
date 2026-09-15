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
            // SHOWN — jeder Gast merkt es: die Website auf Portugiesisch, eine gegliederte Fußzeile und
            // „Restaurant vorschlagen", das sich erklärt, bevor es ein Konto verlangt. ⚠ Der Text verspricht
            // keine Sprachwahl nach Browsersprache — `/` leitet fest auf /lb/ (beim Release nachgemessen).
            new ReleaseNote('2026.09.15', new \DateTimeImmutable('2026-09-15'), ReleaseVisibility::SHOWN),
            // SHOWN — das Release, das die Nutzungsmessung scharfschaltet (Feature 11, AK-32). Text aus
            // features/11-nutzungsmessung/changelog-text.md, in derselben vorsichtigen Formulierung wie /legal:
            // nur Nachstellbares, weder „anonym" noch „keine Kennung".
            new ReleaseNote('2026.09.14.2', new \DateTimeImmutable('2026-09-14'), ReleaseVisibility::SHOWN),
            // SILENT, Betreiberentscheidung beim Ausliefern (2026-09-14): Die Messung ist ausgeliefert, aber
            // aus — ohne die drei APP_UMAMI_*-Variablen bindet keine Seite ein Zählskript ein. Ein Eintrag
            // „wir messen jetzt cookielos" wäre verfrüht. Angekündigt wird mit dem Release, in dem die Messung
            // scharfgeschaltet wird; Datenschutzabschnitt und Schalter auf /legal stehen schon jetzt.
            new ReleaseNote('2026.09.14.1', new \DateTimeImmutable('2026-09-14'), ReleaseVisibility::SILENT),
            // SILENT, ausdrückliche Betreiberentscheidung beim Ausliefern (2026-09-14) — obwohl
            // die Reparatur merkbar ist: Eintragen von den drei Zielgruppenseiten endete bis
            // dahin in einer 405-Fehlerseite (BF-151). Der Weg richtet sich an Gemeinden,
            // Unternehmen und Vereine, nicht an Restaurantgäste. Nicht nachträglich auf SHOWN
            // „korrigieren", ohne die Entscheidung neu zu stellen.
            new ReleaseNote('2026.09.14', new \DateTimeImmutable('2026-09-14'), ReleaseVisibility::SILENT),
            // SILENT. Ein Gast sieht von Sitemap, robots.txt und canonical-Verweisen nichts —
            // sie wirken nur in Suchmaschinen. Ein Eintrag „wir sind besser auffindbar" wäre
            // eine Behauptung, die erst die Search Console belegen kann (AK-24, AK-25).
            new ReleaseNote('2026.09.13', new \DateTimeImmutable('2026-09-13'), ReleaseVisibility::SILENT),
            // SILENT. Ein Gast bemerkt genau eine Sache: eine neue Karte in der Spalte
            // „Angedacht" auf /roadmap. Die Roadmap ist selbst der Ort, an dem dieses
            // Vorhaben mitgeteilt wird — ein Changelog-Eintrag „wir denken über
            // Nutzungsmessung nach" sagte dasselbe ein zweites Mal und läse sich wie eine
            // Ankündigung. Der Rest (Worker-Puls, Maskierung, Sicherungsprüfung) ist
            // Betrieb und für Gäste unsichtbar. Dasselbe Muster wie bei `v2026.09.11.1`.
            new ReleaseNote('2026.09.12.3', new \DateTimeImmutable('2026-09-12'), ReleaseVisibility::SILENT),
            // SILENT, und die Begründung ist eine Abwägung: Von den sieben Befunden
            // dieser Nachlese sieht ein Gast genau einen — der Sprachumschalter ist auf
            // dem Telefon 16 px höher (BF-144). Das ist kein Changelog-Eintrag wert, und
            // ein Eintrag „wir haben einen Knopf vergrössert" verwässert eine Liste, in
            // der sonst Adressen mit Akzent und Betreiberwechsel stehen. Der gewichtigste
            // Posten (BF-140, ein Profilbild im Cache des eigenen Browsers) ist
            // unsichtbar, und die Reparatur ändert für den Nutzer nichts, was er
            // wahrnimmt. Dasselbe Muster wie bei `v2026.09.11.1`.
            new ReleaseNote('2026.09.12.2', new \DateTimeImmutable('2026-09-12'), ReleaseVisibility::SILENT),
            // Ein Gast merkt von dieser Sammelreparatur zweierlei, und beides
            // gehört gesagt: Die Seite trägt eine andere Schrift, und auf
            // Tablets liess sie sich bisher waagerecht verschieben. Der grosse
            // Rest — Prüfläufe, Spezifikationen, ein Reset-500 für Konten mit
            // RFC-widriger Altadresse — bleibt unerwähnt; ein Changelog, der
            // jeden Befund aufzählt, wird nicht gelesen.
            new ReleaseNote('2026.09.12.1', new \DateTimeImmutable('2026-09-12'), ReleaseVisibility::SHOWN),
            // Betrifft zwei Wege, die ein Nutzer selbst geht: das Löschen des
            // eigenen Kontos und das Zurücksetzen des Passworts. Beide sind
            // jetzt gegen Missbrauch gedeckelt — das gehört gesagt, weil es
            // erklärt, warum dort auf einmal eine Wartezeit auftauchen kann.
            new ReleaseNote('2026.09.12', new \DateTimeImmutable('2026-09-12'), ReleaseVisibility::SHOWN),
            // Eine Fehlerbehebung, die ein Gast sehr wohl merkt: Adressen mit
            // Akzent oder Umlaut in der Domain wurden bisher abgewiesen und
            // gehen jetzt durch. Deshalb SHOWN — der 500er allein wäre still
            // geblieben.
            new ReleaseNote('2026.09.11.2', new \DateTimeImmutable('2026-09-11'), ReleaseVisibility::SHOWN),
            // Rein technisch: Sicherheits-Kopfzeilen und `expose_php`. Ein Gast
            // sieht davon nichts — deshalb SILENT und kein Text in den vier
            // changelog.*.yaml.
            new ReleaseNote('2026.09.11.1', new \DateTimeImmutable('2026-09-11'), ReleaseVisibility::SILENT),
            // Wer die Plattform betreibt, steht auf /legal — und dort hat sich
            // mit der Gesellschaft der Verantwortliche nach Art. 4 Nr. 7 DSGVO
            // geändert. Das sieht ein Gast, und es geht ihn an: Seine
            // Betroffenenrechte richten sich ab jetzt gegen sie. Deshalb SHOWN,
            // obwohl der übrige Inhalt des Releases (Warteschlangen-Wache,
            // Datenschutzunterlagen) für sich genommen still geblieben wäre.
            new ReleaseNote('2026.09.11', new \DateTimeImmutable('2026-09-11'), ReleaseVisibility::SHOWN),
            // Feature 08: eine neue öffentliche Seite unter /app. Ein Gast
            // sieht sie — deshalb SHOWN mit Text in allen vier changelog.*.yaml.
            new ReleaseNote('2026.09.05', new \DateTimeImmutable('2026-09-05'), ReleaseVisibility::SHOWN),
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
