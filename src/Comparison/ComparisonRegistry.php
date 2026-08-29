<?php

namespace App\Comparison;

/**
 * Die einzige Stelle, an der die Inhalte der Vergleichsseiten stehen.
 *
 * Kein Zustand, keine Datenbank, keine Verwaltungsoberfläche: Die vier Vergleiche
 * ändern sich zweimal im Jahr, und eine Entität samt Formular dafür wäre Aufwand
 * ohne Ertrag. Eine Korrektur ist ein Commit.
 *
 * ⚠ **Jede Aussage über einen Wettbewerber braucht eine Fußnote** (`sourceRef`),
 * und jede Fußnote braucht eine Primärquelle mit Prüfdatum. `ComparisonRegistryTest`
 * setzt das durch. Der Grund ist nicht Pedanterie: Eine unbelegte Behauptung über
 * ein fremdes Unternehmen ist rechtlich angreifbar und inhaltlich wertlos, und sie
 * beschädigt genau die Glaubwürdigkeit, für die es diese Seiten gibt.
 *
 * ⚠ **Was sich nicht belegen ließ, steht nicht in der Tabelle.** Deshalb tragen
 * die vier Seiten nicht dieselbe Zeilenmenge. Eine fehlende Zeile ist ein
 * bewusstes Ergebnis der Recherche, kein Versehen.
 */
final class ComparisonRegistry
{
    /** @return list<ComparisonPage> */
    public function all(): array
    {
        return array_map(fn (Competitor $c): ComparisonPage => $this->page($c), Competitor::cases());
    }

    public function page(Competitor $competitor): ComparisonPage
    {
        return match ($competitor) {
            Competitor::GOOGLE_MAPS => $this->googleMaps(),
            Competitor::WHEELMAP => $this->wheelmap(),
            Competitor::TRIPADVISOR => $this->tripadvisor(),
        };
    }

    /**
     * Google Maps.
     *
     * Recherche vom 28. August 2026, ausschließlich aus Googles eigener
     * Dokumentation. Zwei Feststellungen, die den Ton der Seite bestimmen:
     *
     * - Google **dokumentiert seine Kriterien** ("three feet or one meter wide
     *   and has no steps") und dokumentiert sie gut. Die Zeile
     *   `criteria_published` steht deshalb auf beiden Seiten auf Ja. Eine
     *   Vergleichsseite, die das verschwiege, wäre unehrlich.
     * - Googles eigene Zahl „accessibility information for more than 50 million
     *   places" bedeutet **nicht** 50 Millionen barrierefreie Orte — sie sagt nur,
     *   dass irgendeine Angabe vorliegt, auch ein Nein. Die Zahl wird deshalb
     *   nirgends auf dieser Seite verwendet.
     */
    private function googleMaps(): ComparisonPage
    {
        $p = Competitor::GOOGLE_MAPS->transPrefix();

        return new ComparisonPage(
            competitor: Competitor::GOOGLE_MAPS,
            rows: [
                // — Barrierefreiheitsdaten —
                $this->row(ComparisonGroup::ACCESSIBILITY_DATA, 'step_free_entrance', Verdict::YES, Verdict::YES, $p, 1),
                $this->row(ComparisonGroup::ACCESSIBILITY_DATA, 'accessible_toilet', Verdict::YES, Verdict::YES, $p, 1),
                $this->row(ComparisonGroup::ACCESSIBILITY_DATA, 'disabled_parking', Verdict::YES, Verdict::YES, $p, 1),
                $this->row(ComparisonGroup::ACCESSIBILITY_DATA, 'door_width_cm', Verdict::YES, Verdict::NO, $p, 2),
                $this->row(ComparisonGroup::ACCESSIBILITY_DATA, 'table_spacing_cm', Verdict::YES, Verdict::NO, $p, 2),
                $this->row(ComparisonGroup::ACCESSIBILITY_DATA, 'assistance_dogs', Verdict::YES, Verdict::NO, $p, 1),
                $this->row(ComparisonGroup::ACCESSIBILITY_DATA, 'bright_lighting', Verdict::YES, Verdict::NO, $p, 1),
                $this->row(ComparisonGroup::ACCESSIBILITY_DATA, 'changing_table', Verdict::YES, Verdict::NO, $p, 1),
                $this->row(ComparisonGroup::ACCESSIBILITY_DATA, 'filterable', Verdict::YES, Verdict::PARTIAL, $p, 1),

                // — Herkunft und Prüfung —
                $this->row(ComparisonGroup::PROVENANCE, 'criteria_published', Verdict::YES, Verdict::YES, $p, 2),
                $this->row(ComparisonGroup::PROVENANCE, 'editorial_review', Verdict::YES, Verdict::PARTIAL, $p, 2),
                $this->row(ComparisonGroup::PROVENANCE, 'onsite_check', Verdict::YES, Verdict::NO, $p, 2),

                // — Abdeckung und Aktualität —
                $this->row(ComparisonGroup::COVERAGE, 'venues_luxembourg', Verdict::YES, Verdict::YES, $p, 3, 'restaurants'),
                $this->row(ComparisonGroup::COVERAGE, 'worldwide', Verdict::NO, Verdict::YES, $p, 3),
                $this->row(ComparisonGroup::COVERAGE, 'opening_hours', Verdict::YES, Verdict::YES, $p, 4),
                $this->row(ComparisonGroup::COVERAGE, 'route_planning', Verdict::NO, Verdict::YES, $p, 3),

                // — Offenheit und Geschäftsmodell —
                $this->row(ComparisonGroup::OPENNESS, 'open_dataset', Verdict::YES, Verdict::NO, $p, 5),
                $this->row(ComparisonGroup::OPENNESS, 'paid_placement', Verdict::NO, Verdict::YES, $p, 6),
            ],
            sources: [
                new ComparisonSource(1, $p.'source.1', 'https://support.google.com/maps/answer/9882117', new \DateTimeImmutable('2026-08-28')),
                new ComparisonSource(2, $p.'source.2', 'https://support.google.com/business/answer/7298639', new \DateTimeImmutable('2026-08-28')),
                new ComparisonSource(3, $p.'source.3', 'https://blog.google/products-and-platforms/products/maps/20-years-google-maps-20-features/', new \DateTimeImmutable('2026-08-28')),
                new ComparisonSource(4, $p.'source.4', 'https://support.google.com/business/answer/3480441', new \DateTimeImmutable('2026-08-28')),
                new ComparisonSource(5, $p.'source.5', 'https://developers.google.com/maps/documentation/places/web-service/policies', new \DateTimeImmutable('2026-08-28')),
                new ComparisonSource(6, $p.'source.6', 'https://support.google.com/google-ads/answer/3246303', new \DateTimeImmutable('2026-08-28')),
            ],
            advantageKeys: [
                $p.'advantage.1',
                $p.'advantage.2',
                $p.'advantage.3',
                $p.'advantage.4',
            ],
            faqKeys: [
                $p.'faq.1',
                $p.'faq.2',
                $p.'faq.3',
                $p.'faq.4',
            ],
        );
    }

    /**
     * Wheelmap.
     *
     * Recherche vom 28. August 2026 aus den eigenen Seiten von Wheelmap und den
     * Sozialhelden, dazu das OSM-Wiki und die offenen Repositorien.
     *
     * ⚠ **Das ist der Vergleich, in dem Endlech.lu am wenigsten gewinnt** — und
     * das gehört genau so in die Tabelle. Wheelmap ist gemeinnützig, quelloffen,
     * veröffentlicht seine Jahreszahlen und stellt seine Daten unter ODbL. In
     * `OPENNESS` steht deshalb dreimal Ja gegen Ja. Ein Vergleich, der einen
     * Gleichstand zum Vorteil umdeutet, wäre auf dieser Plattform der falscheste
     * aller Texte.
     *
     * ⚠ **Die Ortszahlen widersprechen sich zwischen Wheelmaps eigenen Seiten**
     * (3,2 Mio. „verfügbar" gegen „über 1 Million" bewertet). Verwendet wird die
     * datierte Presse-Information und die kleinere, klar definierte Zahl — die
     * größere ist eine Datenbankzahl inklusive unbewerteter Orte.
     *
     * ⚠ **Bezahlte Platzierung ist hier NICHT belegbar** — weder dafür noch
     * dagegen. Die Zeile fehlt deshalb, obwohl sie uns gut zu Gesicht stünde.
     */
    private function wheelmap(): ComparisonPage
    {
        $p = Competitor::WHEELMAP->transPrefix();

        return new ComparisonPage(
            competitor: Competitor::WHEELMAP,
            rows: [
                // — Barrierefreiheitsdaten —
                $this->row(ComparisonGroup::ACCESSIBILITY_DATA, 'step_free_entrance', Verdict::YES, Verdict::YES, $p, 1),
                $this->row(ComparisonGroup::ACCESSIBILITY_DATA, 'accessible_toilet', Verdict::YES, Verdict::YES, $p, 1),
                $this->row(ComparisonGroup::ACCESSIBILITY_DATA, 'assistance_dogs', Verdict::YES, Verdict::YES, $p, 1),
                $this->row(ComparisonGroup::ACCESSIBILITY_DATA, 'disabled_parking', Verdict::YES, Verdict::NO, $p, 1),
                $this->row(ComparisonGroup::ACCESSIBILITY_DATA, 'door_width_cm', Verdict::YES, Verdict::NO, $p, 1),
                $this->row(ComparisonGroup::ACCESSIBILITY_DATA, 'table_spacing_cm', Verdict::YES, Verdict::NO, $p, 1),
                $this->row(ComparisonGroup::ACCESSIBILITY_DATA, 'bright_lighting', Verdict::YES, Verdict::NO, $p, 2),
                $this->row(ComparisonGroup::ACCESSIBILITY_DATA, 'changing_table', Verdict::YES, Verdict::NO, $p, 2),
                $this->row(ComparisonGroup::ACCESSIBILITY_DATA, 'filterable', Verdict::YES, Verdict::PARTIAL, $p, 1),

                // — Herkunft und Prüfung —
                $this->row(ComparisonGroup::PROVENANCE, 'criteria_published', Verdict::YES, Verdict::YES, $p, 1),
                $this->row(ComparisonGroup::PROVENANCE, 'onsite_check', Verdict::YES, Verdict::PARTIAL, $p, 1),
                $this->row(ComparisonGroup::PROVENANCE, 'editorial_review', Verdict::YES, Verdict::NO, $p, 1),

                // — Abdeckung und Aktualität —
                $this->row(ComparisonGroup::COVERAGE, 'venues_luxembourg', Verdict::YES, Verdict::YES, $p, 3, 'restaurants'),
                $this->row(ComparisonGroup::COVERAGE, 'worldwide', Verdict::NO, Verdict::YES, $p, 3),
                $this->row(ComparisonGroup::COVERAGE, 'opening_hours', Verdict::YES, Verdict::NO, $p, 1),
                $this->row(ComparisonGroup::COVERAGE, 'luxembourgish', Verdict::YES, Verdict::NO, $p, 4),

                // — Offenheit und Geschäftsmodell —
                $this->row(ComparisonGroup::OPENNESS, 'open_dataset', Verdict::YES, Verdict::YES, $p, 1),
                $this->row(ComparisonGroup::OPENNESS, 'open_source', Verdict::YES, Verdict::YES, $p, 5),
                $this->row(ComparisonGroup::OPENNESS, 'own_figures_public', Verdict::YES, Verdict::YES, $p, 6),
            ],
            sources: [
                new ComparisonSource(1, $p.'source.1', 'https://news.wheelmap.org/faq/', new \DateTimeImmutable('2026-08-28')),
                new ComparisonSource(2, $p.'source.2', 'https://github.com/sozialhelden/a11yjson', new \DateTimeImmutable('2026-08-28')),
                new ComparisonSource(3, $p.'source.3', 'https://news.wheelmap.org/wheelmap-presse-information/', new \DateTimeImmutable('2026-08-28')),
                new ComparisonSource(4, $p.'source.4', 'https://news.wheelmap.org/ueber-wheelmap/', new \DateTimeImmutable('2026-08-28')),
                new ComparisonSource(5, $p.'source.5', 'https://github.com/sozialhelden/wheelmap-frontend', new \DateTimeImmutable('2026-08-28')),
                new ComparisonSource(6, $p.'source.6', 'https://sozialhelden.de/legal/transparenz', new \DateTimeImmutable('2026-08-28')),
            ],
            advantageKeys: [
                $p.'advantage.1',
                $p.'advantage.2',
                $p.'advantage.3',
                $p.'advantage.4',
            ],
            faqKeys: [
                $p.'faq.1',
                $p.'faq.2',
                $p.'faq.3',
                $p.'faq.4',
            ],
        );
    }

    /**
     * TripAdvisor.
     *
     * Recherche vom 28. August 2026. Die belastbarsten Quellen des ganzen
     * Features stehen hier: der Jahresbericht an die US-Börsenaufsicht und die
     * Entwicklerdokumentation.
     *
     * ⚠ **Eine erste Recherche behauptete, TripAdvisor habe gar keinen
     * Barrierefreiheitsfilter. Das war falsch.** Es gibt ihn — er erscheint in der
     * sichtbaren Filtervorschau nur dort, wo das Merkmal örtlich häufig ist, und
     * in Luxemburg fällt er hinter „Alle anzeigen". Die Fehlannahme entstand aus
     * drei Großstadt-Stichproben. Die belegbare Aussage ist eine andere und
     * schärfere: TripAdvisor kennt **ein Bit**, während Unterkünfte dort eine
     * ganze Rubrik „Barrierefreiheit" mit Unterpunkten bekommen.
     *
     * ⚠ **Zwei Zahlen wurden bewusst nicht verwendet**: die Zahl der Restaurants
     * in Luxemburg (einmal gesehen, danach nur noch HTTP 403) und Bewertungszahlen
     * für London (nie abgerufen, nur aus einem Suchmaschinen-Auszug). Was nicht
     * zweimal gelesen wurde, steht nicht in der Tabelle.
     */
    private function tripadvisor(): ComparisonPage
    {
        $p = Competitor::TRIPADVISOR->transPrefix();

        return new ComparisonPage(
            competitor: Competitor::TRIPADVISOR,
            rows: [
                // — Barrierefreiheitsdaten —
                $this->row(ComparisonGroup::ACCESSIBILITY_DATA, 'step_free_entrance', Verdict::YES, Verdict::PARTIAL, $p, 1),
                $this->row(ComparisonGroup::ACCESSIBILITY_DATA, 'accessible_toilet', Verdict::YES, Verdict::NO, $p, 1),
                $this->row(ComparisonGroup::ACCESSIBILITY_DATA, 'disabled_parking', Verdict::YES, Verdict::NO, $p, 1),
                $this->row(ComparisonGroup::ACCESSIBILITY_DATA, 'door_width_cm', Verdict::YES, Verdict::NO, $p, 1),
                $this->row(ComparisonGroup::ACCESSIBILITY_DATA, 'table_spacing_cm', Verdict::YES, Verdict::NO, $p, 1),
                $this->row(ComparisonGroup::ACCESSIBILITY_DATA, 'assistance_dogs', Verdict::YES, Verdict::NO, $p, 1),
                $this->row(ComparisonGroup::ACCESSIBILITY_DATA, 'bright_lighting', Verdict::YES, Verdict::NO, $p, 1),
                $this->row(ComparisonGroup::ACCESSIBILITY_DATA, 'changing_table', Verdict::YES, Verdict::NO, $p, 1),
                $this->row(ComparisonGroup::ACCESSIBILITY_DATA, 'filterable', Verdict::YES, Verdict::PARTIAL, $p, 2),

                // — Herkunft und Prüfung —
                $this->row(ComparisonGroup::PROVENANCE, 'criteria_published', Verdict::YES, Verdict::NO, $p, 1),
                $this->row(ComparisonGroup::PROVENANCE, 'editorial_review', Verdict::YES, Verdict::NO, $p, 1),
                $this->row(ComparisonGroup::PROVENANCE, 'onsite_check', Verdict::YES, Verdict::NO, $p, 1),

                // — Abdeckung und Aktualität —
                $this->row(ComparisonGroup::COVERAGE, 'venues_luxembourg', Verdict::YES, Verdict::YES, $p, 3, 'restaurants'),
                $this->row(ComparisonGroup::COVERAGE, 'worldwide', Verdict::NO, Verdict::YES, $p, 3),
                $this->row(ComparisonGroup::COVERAGE, 'luxembourgish', Verdict::YES, Verdict::NO, $p, 4),

                // — Offenheit und Geschäftsmodell —
                $this->row(ComparisonGroup::OPENNESS, 'open_dataset', Verdict::YES, Verdict::NO, $p, 5),
                $this->row(ComparisonGroup::OPENNESS, 'paid_placement', Verdict::NO, Verdict::YES, $p, 6),
            ],
            sources: [
                new ComparisonSource(1, $p.'source.1', 'https://www.tripadvisorsupport.com/en-US/hc/owner/articles/611', new \DateTimeImmutable('2026-08-28')),
                new ComparisonSource(2, $p.'source.2', 'https://www.tripadvisor.com/Restaurants-g42758-Traverse_City_Michigan.html', new \DateTimeImmutable('2026-08-28')),
                new ComparisonSource(3, $p.'source.3', 'https://www.sec.gov/Archives/edgar/data/1526520/000119312526051281/trip-20251231.htm', new \DateTimeImmutable('2026-08-28')),
                new ComparisonSource(4, $p.'source.4', 'https://www.tripadvisorsupport.com/en-US/hc/', new \DateTimeImmutable('2026-08-28')),
                new ComparisonSource(5, $p.'source.5', 'https://docs.terra.tripadvisor.com/docs/caching-policy', new \DateTimeImmutable('2026-08-28')),
                new ComparisonSource(6, $p.'source.6', 'https://www.tripadvisorsupport.com/en-US/hc/owner/articles/372', new \DateTimeImmutable('2026-08-28')),
            ],
            advantageKeys: [
                $p.'advantage.1',
                $p.'advantage.2',
                $p.'advantage.3',
                $p.'advantage.4',
            ],
            faqKeys: [
                $p.'faq.1',
                $p.'faq.2',
                $p.'faq.3',
                $p.'faq.4',
            ],
        );
    }


    /**
     * Baut eine Zeile aus den Namenskonventionen der Kataloge.
     *
     * `label` und `own` liegen unter `row.<name>` und gelten für alle vier
     * Vergleiche — was Endlech.lu erfasst, hängt nicht davon ab, mit wem
     * verglichen wird. Nur der Halbsatz über den Wettbewerber ist seitenspezifisch.
     */
    private function row(
        ComparisonGroup $group,
        string $name,
        Verdict $own,
        Verdict $theirs,
        string $pagePrefix,
        ?int $sourceRef = null,
        ?string $figure = null,
    ): ComparisonRow {
        return new ComparisonRow(
            group: $group,
            labelKey: 'row.'.$name.'.label',
            own: $own,
            theirs: $theirs,
            ownNoteKey: 'row.'.$name.'.own',
            theirNoteKey: $pagePrefix.'note.'.$name,
            sourceRef: $sourceRef,
            figure: $figure,
        );
    }
}
