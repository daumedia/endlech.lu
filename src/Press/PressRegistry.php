<?php

namespace App\Press;

/**
 * Die einzige Stelle, an der die Inhalte des Presse-Kits stehen.
 *
 * Kein Zustand, keine Datenbank, kein Admin-Formular: Die Inhalte ändern sich
 * wenige Male im Jahr, und eine Entität samt Formular dafür wäre Aufwand ohne
 * Ertrag. Eine Änderung ist ein Commit — dasselbe Muster wie
 * `App\Comparison\ComparisonRegistry` (Feature 03).
 *
 * ⚠ **`assets()` speist Seite und Paket zugleich.** Der Konsolenbefehl
 * `app:press:package` packt genau diese Liste; die Vorschauen auf `/presse`
 * entstehen aus derselben. Damit können beide nicht auseinanderlaufen, ohne dass
 * es einen zweiten Ort gäbe, an dem jemand hätte vergessen können.
 */
final readonly class PressRegistry
{
    /**
     * Anzeigename des Gründers in Zitaten und im Personenabschnitt.
     *
     * Der vollständige Name, seit VB-03 am 2026-08-30 entschieden ist. Die
     * Website selbst nennt in `about.founder_bio` weiterhin nur den Vornamen,
     * was dort passt und hier nicht: Ein Zitat ohne Nachnamen ist für eine
     * Redaktion nicht zitierfähig.
     *
     * ⚠ **Deckt sich seit dem 2026-09-05 NICHT mehr mit `app.operator_name`.**
     * Betreiber ist seither die DAUMEDIA S.A.R.L.-S, Gründer und Zitatgeber
     * bleibt die Person. Eine Gesellschaft sagt keine Sätze — wer das hier
     * angleicht, setzt einen Firmennamen unter ein persönliches Zitat.
     */
    public const string FOUNDER_NAME = 'Michael Ferreira';

    /**
     * Die drei Beschreibungstexte.
     *
     * Nur die Längen stehen hier — der Wortlaut liegt in der Domain `press`,
     * damit er in vier Sprachen existiert und `PressCatalogueTest` ihn zählen
     * kann.
     *
     * @return list<BoilerplateLength>
     */
    public function boilerplates(): array
    {
        return BoilerplateLength::cases();
    }

    /**
     * Die fünf Bestandteile des Materialpakets.
     *
     * ⚠ **Das Porträt liegt bewusst weiter unter `uploads/team/`** und wird nicht
     * nach `presse-kit/` kopiert. Die Datei ist 2048 × 1365 px groß und damit
     * drucktauglich (rund 17 cm bei 300 dpi); eine zweite Kopie wäre eine zweite
     * Wahrheit, die beim nächsten Bildwechsel auseinanderfällt. Sie ist committet
     * — `public/uploads/team/` ist per `!`-Regel aus `.gitignore` ausgenommen,
     * und was dort nicht committet ist, löscht `git clean -fd` im Deploy weg.
     *
     * ⚠ **Das Verzeichnis heißt `presse-kit` und nicht `presse`** — siehe
     * `PressPackage::PUBLIC_PATH`, BF-100.
     *
     * ⚠ **Wer eine dieser Dateien ersetzt, erhöht `CACHE_VERSION` in
     * `public/sw.js`.** Der Service Worker cacht Bilder cache-first: Ein
     * wiederkehrender Besucher sähe sonst die alte Vorschau neben dem neuen
     * Paket — das Paket selbst wird nie gecacht. AK-17 bräche damit im Browser,
     * wo kein Prüflauf hinsieht.
     *
     * @return list<PressAsset>
     */
    public function assets(): array
    {
        return [
            new PressAsset(PressAssetKind::WORDMARK_LIGHT, 'presse-kit/endlech-wortbildmarke.svg', 'SVG'),
            new PressAsset(PressAssetKind::WORDMARK_DARK, 'presse-kit/endlech-wortbildmarke-invers.svg', 'SVG', onDark: true),
            new PressAsset(PressAssetKind::SYMBOL_LIGHT, 'presse-kit/endlech-bildmarke.svg', 'SVG'),
            new PressAsset(PressAssetKind::SYMBOL_DARK, 'presse-kit/endlech-bildmarke-invers.svg', 'SVG', onDark: true),
            new PressAsset(
                PressAssetKind::PORTRAIT,
                'uploads/team/michael.jpg',
                'JPG',
                creditKey: 'person.photo_credit',
            ),
        ];
    }

    /**
     * Freigegebene Zitate.
     *
     * ⚠ **Beide sind aus dem, was die Website heute selbst veröffentlicht**
     * (Mission auf `/about`, Produktprinzip „Bewertungen sind nicht käuflich" auf
     * `/partner`) — kein Zitat sagt etwas, das nicht schon öffentlich stünde. Wer
     * ein drittes hinzufügt, holt vorher die ausdrückliche Freigabe der zitierten
     * Person ein: `quotes.release_note` sagt Redaktionen zu, dass sie ohne
     * Rückfrage übernommen werden dürfen, und diese Zusage gilt dann auch.
     *
     * ⚠ Die Funktion zeigt auf `person.role` und nicht auf einen eigenen
     * Schlüssel je Zitat — es ist dieselbe Person und dieselbe Funktion. Ein
     * zweiter Schlüssel wäre eine zweite Stelle, an der „Gründer" anders stünde.
     *
     * @return list<PressQuote>
     */
    public function quotes(): array
    {
        return [
            new PressQuote('quotes.q1', self::FOUNDER_NAME, 'person.role'),
            new PressQuote('quotes.q2', self::FOUNDER_NAME, 'person.role'),
        ];
    }

    /**
     * Meldungen, neueste zuerst.
     *
     * Eine leere Liste ist ein gültiger Zustand und kein Fehler: Solange keine
     * Meldung vorliegt, zeigt der Abschnitt den Hinweis samt Verweis auf den
     * Pressekontakt (AK-27).
     *
     * @return list<PressRelease>
     */
    public function releases(): array
    {
        // Noch keine Meldung veröffentlicht (OF-06). Der leere Zustand ist
        // gebaut und ausdrücklich zugesagt (AK-27) — der Abschnitt bleibt
        // sichtbar und verweist auf den Pressekontakt.
        return [];
    }
}
