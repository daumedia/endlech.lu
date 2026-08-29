<?php

namespace App\Comparison;

/**
 * Die vier Angebote, mit denen sich Endlech.lu öffentlich vergleicht.
 *
 * Die Auswahl folgt den Wegen, auf denen jemand in Luxemburg heute nach
 * barrierefreier Gastronomie sucht – nicht einer Wettbewerbsanalyse. Google Maps
 * ist der Regelfall, Wheelmap der inhaltlich nächste Verwandte (offene Daten,
 * Crowdsourcing), TripAdvisor steht für die Bewertungsportale.
 *
 * ⚠ **Jaccede war als vierter Vergleich vorgesehen und wurde gestrichen.** Die
 * Recherche am 28. August 2026 ergab, dass die französische Plattform seit dem
 * 2. Juli 2026 nur noch als statischer Abzug erreichbar ist: Suche, Anmeldung und
 * das Anlegen von Orten antworten mit 404, beide Apps sind aus den Stores
 * verschwunden, der `last-modified`-Kopf steht auf diesem Datum. Die Startseite
 * liefert weiterhin HTTP 200 und nennt „78 537 lieux inscrits" — genau deshalb
 * hielt eine erste Gegenprüfung den Dienst für lebendig. **HTTP 200 und sichtbarer
 * Inhalt sind kein Betriebsnachweis.** Wer einen fünften Vergleich anlegt, prüft
 * vorher, ob etwas Dynamisches am fremden Angebot tatsächlich funktioniert.
 *
 * Zwei Belege machen das hart. **Die Domain wurde am 1. Juni 2026 neu registriert**
 * und steht seither anonym auf „Domains By Proxy, LLC" (US) — Registry-RDAP von
 * Verisign, Ereignis `registration` `2026-06-01T18:37:29Z`. Der Bestand gehört
 * damit nicht mehr dem französischen Verein; eine Vergleichsseite beschriebe einen
 * Fremdbestand, als wäre er dessen Angebot. Und **`api.jaccede.com` löst nicht mehr
 * auf** (NXDOMAIN gegen `8.8.8.8` und `1.1.1.1`), während das ausgelieferte
 * JS-Bundle fest auf `https://api.jaccede.com/v4` zeigt — die Oberfläche kann gar
 * keine Daten laden. Genau diese Prüfung, nicht der Statuscode der Startseite,
 * entscheidet die Frage.
 *
 * ⚠ Die Wortmarke steht hier als fester Text und **nicht** im Übersetzungskatalog.
 * Eigennamen werden nicht übersetzt, und ein übersetzbarer Produktname lädt dazu
 * ein, ihn in einer der vier Sprachfassungen falsch zu schreiben – eine falsch
 * geschriebene fremde Marke ist genau die Art Fehler, die auf einer
 * Vergleichsseite niemand bemerkt und jeder ernst nimmt. Dasselbe Muster wie in
 * `WebauthnCredentialRepository::guessDeviceName()`.
 *
 * ⚠ Der Slug ist in allen vier Sprachen derselbe. Das ist Voraussetzung dafür,
 * dass der Sprachumschalter auf einer Vergleichsseite bleibt (AK-05): Er baut
 * seine Zieladresse aus `current_params` – ein übersetzter Slug wäre in der
 * Zielsprache unbekannt, und `path()` würde werfen. Dasselbe gilt für die
 * hreflang-Schleife in `base.html.twig`, und die läuft auf **jeder** Seite.
 */
enum Competitor: string
{
    case GOOGLE_MAPS = 'google_maps';
    case WHEELMAP = 'wheelmap';
    case TRIPADVISOR = 'tripadvisor';

    /**
     * URL-Segment der eigenen Vergleichsseite (/vergleich/{slug}).
     *
     * Weicht bei GOOGLE_MAPS bewusst vom Enum-Wert ab: In einer Adresse ist der
     * Bindestrich üblich, im Übersetzungsschlüssel der Unterstrich.
     */
    public function slug(): string
    {
        return match ($this) {
            self::GOOGLE_MAPS => 'google-maps',
            self::WHEELMAP => 'wheelmap',
            self::TRIPADVISOR => 'tripadvisor',
        };
    }

    public static function fromSlug(string $slug): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->slug() === $slug) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Alle Slugs als Regex-Alternative für das Route-Requirement.
     *
     * Damit ist die Aufzählung im Enum die einzige Quelle: Ein fünfter Vergleich
     * wird hier eingetragen und ist sofort erreichbar, ohne dass jemand daran
     * denken muss, den Regex im Controller nachzuziehen.
     */
    public static function slugPattern(): string
    {
        return implode('|', array_map(static fn (self $c): string => $c->slug(), self::cases()));
    }

    /** Wortmarke des Anbieters – fester Text, siehe Klassenkommentar. */
    public function brand(): string
    {
        return match ($this) {
            self::GOOGLE_MAPS => 'Google Maps',
            self::WHEELMAP => 'Wheelmap',
            self::TRIPADVISOR => 'TripAdvisor',
        };
    }

    /** Präfix aller Textschlüssel dieses Vergleichs in der Domain `comparison`. */
    public function transPrefix(): string
    {
        return 'page.' . $this->value . '.';
    }
}
