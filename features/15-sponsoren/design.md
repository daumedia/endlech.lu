# 15 · Sponsoren — Systemdesign

Status: `architected` · Stand: 2026-10-28 · Stack-Profil: `symfony-doctrine`

**Kein Code in diesem Dokument.** Es wird gelesen und freigegeben, nicht ausgeführt.

## Überblick

Die Sponsoren stehen **im Quelltext**, als Liste von Einträgen in einer Registry, so wie Presse-Kit,
Vergleiche und Changelog. Ein Eintrag nennt Name, Adresse (Slug), eine Logodatei, optional Website
und Zitat samt Sprache und einen oder mehrere Zeiträume. Ob ein Sponsor gerade **aktiv**,
**ehemalig** oder **noch nicht sichtbar** ist, wird bei jeder Anfrage aus diesen Zeiträumen und dem
heutigen Datum (Luxemburger Zeit) berechnet. Deshalb greifen Beginn und Ende von selbst, ohne
Ausrollen. Footer, Übersicht, Sponsorenseite, `/open`, Faktenblatt, Unternehmensseite und Sitemap
lesen alle aus **einem** Verzeichnisdienst. Auf der Angebotsseite für Unternehmen ersetzt ein
Paketblock die vier Formate, und das Formular fragt statt sechs Häkchen nur noch „monatlich“ oder
„jährlich“. Dafür wird das bestehende Feld der Warteliste weiterbenutzt; eine Migration entsteht
nicht.

## Seiten und Routen

| Route | Pfad | Zweck | Zugang |
|---|---|---|---|
| `app_sponsor_index` | `/{_locale}/sponsoren` | Paket, Regeln, aktive und ehemalige Sponsoren (AK-11 bis AK-15) | öffentlich |
| `app_sponsor_show` | `/{_locale}/sponsoren/{slug}` | Seite eines aktiven oder ehemaligen Sponsors (AK-16 bis AK-22) | öffentlich; unbekannter oder noch nicht begonnener Sponsor → 404; entfernter → **301** auf `app_sponsor_index` |
| `app_organisations_type` | `/{_locale}/organisationen/unternehmen` | **geändert**: Paketblock statt Formate, Logoreihe über dem Formular, neues Formularfeld | öffentlich, wie bisher |
| `app_open` | `/{_locale}/open` | **geändert**: Link „Wer uns finanziert →“ im Block Finanzen | öffentlich, wie bisher |
| `app_press_index` | `/{_locale}/presse` | **geändert**: Zeile „Sponsoren“ im Faktenblatt | öffentlich, wie bisher |
| `app_sitemap` | `/sitemap.xml` | **geändert**: `/sponsoren` als feste Seite plus eine Seite je sichtbarem Sponsor | öffentlich, wie bisher |

- `{slug}` mit Requirement `[a-z0-9]+(?:-[a-z0-9]+)*` (EC-05). Ein Slug, der das Muster verfehlt, ist
  keine Route und damit ebenfalls eine 404.
- Beide neuen Routen liegen im gewöhnlichen `controllers`-Loader unter `/{_locale}`; kein eigener
  Block in `routes.yaml`, kein sprachfreier Kurzlink (die Spec verlangt keinen).
- ⚠ **Kein Verzeichnis `public/sponsoren`** (BF-100). Die Logos liegen unter `assets/`, nicht unter
  `public/` (Entscheidung #3). `RouteDirectoryCollisionTest` prüft das ohnehin.
- Die Seite nach dem Erfolg: Ein Klick auf „Sponsor werden →“ führt auf
  `/{_locale}/organisationen/unternehmen#anfrage`. Das Ziel gibt es schon.

## Komponentenstruktur

Server-gerendertes Twig, kein JavaScript. Ein **Ladezustand** entfällt überall, weil nichts
nachgeladen wird; die Logos laden nativ verzögert (Entscheidung #6).

```
Footer (partials/_footer.html.twig, jede Seite)
└── Bereich „Sponsoren“          eigene Zeile unter dem Spaltenraster, über der Rechtsleiste; h2 wie die Gruppentitel
    ├── Logoliste                bis zu 12 Logos auf weißer Kachel, je ein Link auf die Sponsorenseite, alt = Name
    └── Linkzeile                „Alle Sponsoren“ (sobald es einen aktiven oder ehemaligen gibt) · „Sponsor werden →“
    Zustände: leer (nie einer) → nur „Sponsor werden →“ · nur ehemalige → beide Links, keine Logos ·
              gefüllt → Logos + beide Links · ab 13 aktiven → die 12 ältesten, der Rest nur auf /sponsoren

Übersicht /sponsoren (sponsor/index.html.twig)
├── Kopf                         h1, Satz zum Paket (monatlich oder jährlich, Preis auf Anfrage)
├── Was ein Sponsor bekommt      Nennung im Footer jeder Seite, eigene Seite
├── Was er nicht bekommt         geteiltes Teilstück (siehe unten)
├── Ausschlussregel              derselbe Wortlaut wie auf der Unternehmensseite (Katalogschlüssel wiederverwendet)
├── Verwendung                   ein Satz + Link auf den Block Finanzen in /open
├── Aktive Sponsoren             Logo, Name, „Sponsor seit <Monat Jahr>“, Link; alle, nicht nur 12
├── Bisher unterstützt von       Name + Zeiträume, Link; jüngstes Ende zuerst
└── Handlungsaufruf              „Sponsor werden →“
    Zustände: leer → „Endlech.lu hat derzeit keine Sponsoren.“, beide Listen fehlen ·
              nur ehemalige → aktive Liste fehlt, „derzeit keine“ bleibt stehen · gefüllt

Sponsorenseite /sponsoren/{slug} (sponsor/show.html.twig)
├── Kopf                         Logo auf weißer Kachel, h1 Name, „Sponsor seit …“ bzw. „Ehemaliger Sponsor (…)“
├── Zitat                        blockquote mit lang-Attribut der Originalsprache, gezeichnet „— <Name>“; fehlt ohne Zitat
├── Verwendung                   derselbe Satz wie auf der Übersicht + Link auf /open
├── Was <Name> dafür nicht bekommt   geteiltes Teilstück
├── Website von <Name>           nur aktiv und nur, wenn vereinbart; rel="sponsored noopener", kein target
└── Fußleiste der Seite          „Sponsor werden →“ · „Alle Sponsoren“
    Zustände: gefüllt (aktiv) · ehemalig (ohne Website-Link) · Fehler → gewöhnliche 404-Seite

Geteilte Teilstücke (templates/sponsor/)
├── _logo.html.twig              eine Kachel: feste Fläche, Bild mit object-contain, loading="lazy", width/height gesetzt
├── _logo_row.html.twig          Reihe für Footer und Unternehmensseite (dieselbe Auswahl, AK-31)
├── _not_included.html.twig      vier Punkte: keine bessere Bewertung, keine Platzierung, kein Einfluss auf Einträge,
│                                kein Zugriff auf einzelne Bewertungen (AK-11, AK-16)
└── _periods.html.twig           Zeiträume als Text, Monat und Jahr in der Seitensprache

Unternehmensseite /organisationen/unternehmen (geändert)
├── organisation/_section_company.html.twig
│   ├── Paketblock               ersetzt das Raster „Formate“: monatlich oder jährlich, Preis auf Anfrage, Gegenleistung, Link /sponsoren
│   ├── Auch ohne Geld           unverändert (AK-35)
│   └── Ausschlussregel          unverändert (AK-36)
├── Logoreihe                    über der Formularkarte im Abschnitt #anfrage, nur Typ Unternehmen, nur mit aktivem Sponsor
└── organisation/_form.html.twig Block Unternehmen: fieldset „Interesse an einem Sponsoring“ mit zwei Radios statt sechs Häkchen

Weitere geänderte Stellen
├── open/index.html.twig         Block Finanzen: Link „Wer uns finanziert →“, außerhalb der Bedingung der Quartalssperre
├── press/_facts.html.twig       Zeile „Sponsoren“ in der Beschreibungsliste: Zahl oder „Derzeit keine“, Link /sponsoren
├── admin/waitlist/organisation_show.html.twig   Laufzeit als Abzeichen; alte Werte als „frühere Angabe“
└── email/organisation/internal_notification.html.twig   Laufzeit übersetzt (de); alte Werte mit Vermerk
```

## Datenmodell

**Keine neue Tabelle, keine Migration.** Die Sponsoren sind Wertobjekte im Quelltext.

### Wertobjekt `Sponsor` (Namensraum `App\Sponsor\`)

| Feld | Typ | Pflicht | Bedeutung |
|---|---|---|---|
| `slug` | Text | ja | letzter Teil der Adresse; nur `a–z`, `0–9`, Bindestrich; im Verzeichnis eindeutig (EC-05) |
| `name` | Text | ja | Anzeigename, höchstens 80 Zeichen; Akzente, Umlaute, „&“ erlaubt |
| `logo` | Dateiname | ja | Datei unter `assets/images/sponsors/`; **nur PNG oder WebP**, Inhalt geprüft, höchstens 25 KB (AK-10, AK-28) |
| `website` | URL | nein | `https://…`; wird nur bei aktiven Sponsoren verlinkt (AK-21) |
| `quote` | Text | nein | höchstens 300 Zeichen, unübersetzt (AK-18, AK-19) |
| `quoteLocale` | Sprachcode | nur mit `quote` | ISO 639-1, z. B. `fr`; landet als `lang`-Attribut am Zitat |
| `periods` | Liste von `SponsorPeriod` | ja, mindestens einer | zeitlich geordnet, ohne Überlappung; nur der letzte darf ohne Ende sein |

### Wertobjekt `SponsorPeriod`

| Feld | Typ | Pflicht | Bedeutung |
|---|---|---|---|
| `start` | Kalendertag | ja | erster Tag der Nennung |
| `end` | Kalendertag | nein | letzter Tag der Nennung, **einschließlich** (EC-01, EC-12); leer = läuft |

### Registry und Verzeichnis

| Baustein | Aufgabe |
|---|---|
| `SponsorSource` (Schnittstelle) | liefert alle Einträge und die Liste entfernter Slugs |
| `SponsorRegistry` | die Produktionsquelle: die Einträge im Quelltext, heute **leer**; dazu `removedSlugs()` für AK-43 |
| `SponsorDirectory` | rechnet mit dem heutigen Datum: aktiv · ehemalig · unsichtbar; Footer-Auswahl (≤ 12); Sortierung; Suche nach Slug |
| `SponsorRules` | prüft einen Eintrag gegen alle Regeln aus AK-27 und AK-28 und liefert lesbare Verstöße („Sponsor `x`: Zitat 312 Zeichen, erlaubt 300“) |
| `SponsorExtension` (Twig) | stellt dem Footer und den Teilstücken die Auswahl bereit (Muster `ComparisonExtension`: der Footer steht auf jeder Seite, kein Controller darf es vergessen) |

**Zustandsregeln** (heute = Kalendertag in `Europe/Luxembourg`, EC-02):
- **aktiv**: Es gibt einen Zeitraum mit `start ≤ heute` und (`end` leer oder `heute ≤ end`).
- **ehemalig**: nicht aktiv, aber mindestens ein Zeitraum mit `end < heute`.
- **unsichtbar**: weder noch — nur künftige Zeiträume (AK-23). Unsichtbare erscheinen nirgends,
  ihre Adresse ist eine 404.
- **Reihenfolge** (AK-02, AK-25, EC-03): aktive nach `start` des **laufenden** Zeitraums, ältester
  zuerst; Gleichstand nach dem Namen, verglichen in ASCII-gefalteter Kleinschreibung (gleich in allen
  Sprachen). Ehemalige nach dem Ende ihres letzten Zeitraums, jüngstes zuerst (AK-15).
- **Footer und Unternehmensseite** zeigen die ersten zwölf aktiven (AK-06, AK-31, EC-07).
- **Entfernt** (AK-43, EC-08): Der Eintrag wird gelöscht, die Logodatei ebenso, und der Slug kommt
  in `removedSlugs()`. Ein entfernter Sponsor ist weder aktiv noch ehemalig.

### Änderung an `organisation_waitlist_entry` (bestehende Tabelle)

| Feld | Änderung |
|---|---|
| `sponsorship_interests` (JSON, Liste von Texten) | **Bedeutung geändert, Spalte unverändert.** Neue Einträge tragen höchstens **einen** Wert, `monthly` oder `yearly` (neues Enum `SponsorshipTerm`). Bestehende Zeilen behalten ihre alten Werte (`inclusion_boxes`, `employee_engagement` …); sie werden nicht umgeschrieben (AK-39). Das Enum `SponsorshipInterest` bleibt als **Lesehilfe für Altdaten** bestehen, das Formular benutzt es nicht mehr. |

Die Entity bekommt zwei Zugriffe: die gewählte Laufzeit (der erste Wert, wenn er ein
`SponsorshipTerm` ist) und die alten Werte (alle übrigen). Die Prüfregel im Typ `company` lautet:
höchstens ein Wert, und nur `monthly` oder `yearly`. Alte Zeilen werden nirgends erneut geprüft —
die Verwaltung ändert nur den Status, nicht die Angaben (`AdminWaitlistController`, Statuswechsel
ohne Formular-Bindung an diese Felder).

Löschregel: unverändert gegenüber B15. Sponsoren haben keinen Fremdschlüssel und hängen an keinem
Konto.

## Zugriffsregeln

| Wer | Darf lesen | Darf schreiben | Erzwungen durch |
|---|---|---|---|
| Besucher (anonym oder angemeldet) | aktive und ehemalige Sponsoren; nie unsichtbare oder entfernte | nichts | `SponsorDirectory` entscheidet serverseitig, bevor eine Seite entsteht; `SponsorController::show()` antwortet 404 bzw. 301. **Nicht** das Template — ein `{% if %}` verbärge nur |
| Verwaltung (`ROLE_ADMIN`) | wie Besucher; dazu Wartelisten-Einträge wie bisher | nichts an Sponsoren | Es gibt **keinen** Schreibweg in der Anwendung |
| Betreiber | — | Einträge und Logos | Schreibrecht auf das Repository und das Ausrollen in Coolify |
| Interessent im Formular | — | eine eigene Anfrage, wie bisher | Formular + Prüfgruppe `company` (B15), Deckel unverändert |

Die beiden neuen Routen sind öffentlich wie jede Inhaltsseite unter `/{_locale}`; in
`access_control` ändert sich nichts. Die Seiten lesen keine Sitzung über das Übliche hinaus.

## Missbrauchsschutz

| Endpunkt | Limit | Verhalten bei Überschreitung | Wo konfiguriert |
|---|---|---|---|
| `/{_locale}/sponsoren`, `/{_locale}/sponsoren/{slug}` | **keins** | — | Begründung: keine Mail, kein Geheimnis, kein Datenbankzugriff. Die Einträge liegen im Speicher, ihre Zahl ist klein und nicht von außen vergrößerbar. Damit greift die Projektkonvention („jeder Weg, der bei jedem Aufruf den gesamten Bestand lädt“) nicht, die sich auf wachsende Bestände in der Datenbank bezieht |
| Footer (jede Seite) | keins | — | wie oben; zusätzlich begrenzt AK-10 das Gewicht |
| `/sitemap.xml` | 60 je Stunde und Adresse, **unverändert** | 429 | `RouteRateLimitSubscriber` (Feature 10) |
| Formular `/organisationen/*` | Deckel der Organisations-Warteliste, **unverändert** | 429, wie bisher (B15) | `framework.yaml` + `OrganisationController` |

`LimiterCoverageTest` bleibt grün: Es entsteht kein neuer Limiter.

## Externe Dienste

| Dienst | Wofür | Was geht hin | Was wird vorher entfernt |
|---|---|---|---|
| Umami (Feature 11) | Seitenaufrufe der neuen Seiten, wie bei jeder Seite | Pfad `/xx/sponsoren/<slug>` ohne Abfrage, Herkunftsdomain, Sprache | alles, was `CollectPayloadNormalizer` schon entfernt; **kein neues Ereignis**, insbesondere keins am Website-Link (AK-46) |
| Website des Sponsors | Ziel des Links „Website von <Name>“ | nur, was ein Browser beim Folgen eines Links sendet | Herkunft auf den Ursprung gekürzt durch die bestehende `Referrer-Policy: strict-origin-when-cross-origin` (`SecurityHeadersSubscriber`, AK-42); kein angehängter Parameter (AK-17) |
| Brevo | — | **nichts Neues**: Die Laufzeit geht nicht in den Kontakt (heute geht keins der beiden Interessenfelder mit; geprüft in `src/Marketing/`) | — |

## Technische Entscheidungen

| # | Entscheidung | Alternative | Warum so |
|---|---|---|---|
| 1 | **Eine Quellen-Schnittstelle `SponsorSource`**: in Produktion die Registry im Quelltext, im Test eine austauschbare Quelle im Speicher (`when@test`) | Prüfläufe gegen die echte Registry | Die echte Liste ist heute leer und ändert sich mit jedem Vertrag; Prüfläufe, die von ihr abhängen, würden mit jedem Sponsor rot. Die echte Liste prüft `SponsorRules` in einem eigenen Prüflauf (AK-27) |
| 2 | **Das Datum kommt aus einem eigenen Uhr-Dienst** (`app.sponsor_clock`, in Produktion die Systemuhr, im Test eine verstellbare Uhr) | `new \DateTimeImmutable()` im Code; oder die globale Uhr im Test ersetzen | Ohne verstellbare Uhr sind AK-23, AK-24 und EC-02 nicht prüfbar. Die globale Uhr zu ersetzen träfe Dienste anderer Features (Rate Limiter, Zeitpläne) |
| 3 | **Logos unter `assets/images/sponsors/`, ausgeliefert durch AssetMapper** | `public/images/sponsors/` | Der Service Worker liefert `/images/` **cache-first** — ein getauschtes Logo bliebe bei wiederkehrenden Besuchern stehen (EC-06), und jeder Tausch verlangte, `CACHE_VERSION` zu erhöhen (die Falle aus Feature 05). Unter `/assets/` trägt jede Datei ihre Prüfsumme im Namen; ein neues Logo hat eine neue Adresse. Eine gelöschte Datei fehlt nach dem nächsten Ausrollen in `public/assets/` → 404 (AK-43) |
| 4 | **Nur PNG und WebP**, geprüft am Dateiinhalt | SVG (schärfer, kleiner) | Eine SVG-Datei, direkt aufgerufen, führt eingebettete Skripte im Ursprung `endlech.lu` aus (AK-28). Sie sicher zu machen hieße, Fremddateien zu bereinigen — Aufwand und ein eigener Prüfgegenstand. Raster in doppelter Anzeigegröße ist scharf genug |
| 5 | **Weiße Kachel im Footer** statt einer zweiten Logofassung für dunklen Grund | Feld `logoOnDark` je Sponsor | Eine Datei je Sponsor; die Gefahr „dunkel auf dunkel“ (AK-29, VB-02) entfällt strukturell, statt an jedem Eintrag geprüft werden zu müssen |
| 6 | **Natives verzögertes Laden** (`loading="lazy"`, feste `width`/`height`) | eigenes Nachladen per JavaScript | Mit JavaScript-Nachladen blieben die Logos ohne JavaScript unsichtbar (EC-09). ⚠ Browser laden innerhalb einer Vorlaufzone vorab (Chrome 1250–2500 px); auf sehr kurzen Seiten liegt der Footer darin. AK-10 wird deshalb an Startseite und Restaurantliste gemessen, siehe Abdeckung |
| 7 | **Die Laufzeit steht im bestehenden JSON-Feld** `sponsorship_interests` | neue Spalte + Migration | Ohne Migration kein Fenster zwischen Containertausch und Post-Deployment-Befehl, in dem das Formular scheitert; die Altwerte bleiben ohnehin in genau diesem Feld lesbar (AK-39). Preis: Der Spaltenname passt nicht mehr ganz — dokumentiert in `docs/data-model.md` |
| 8 | **Zwei Radios ohne dritte Antwort** im Formular | eine dritte Antwort „kein Sponsoring“ | AK-37 verlangt genau zwei Antworten und „darf leer bleiben“. Preis: Ein einmal gewähltes Radio lässt sich ohne Neuladen nicht abwählen; wer nur Zusammenarbeit ohne Geld will, wählt keins |
| 9 | **Texte im Katalog `messages`**, Block `sponsor:` (Faktenblatt-Zeile in `press`) | eigene Domain `sponsors` | `CatalogueCompletenessTest` prüft `messages` schon in allen fünf Sprachen; eine neue Domain bräuchte fünf neue Dateien und einen eigenen Vollständigkeitstest |
| 10 | **Zitat und Name nicht im Katalog**, sondern im Eintrag | Zitat je Sprache im Katalog | Das Zitat bleibt in der Originalsprache (AK-18); im Katalog müsste es in allen fünf Dateien stehen, und AK-26 („keine Übersetzungsdatei anfassen“) fiele |
| 11 | **Footer-Daten über eine Twig-Erweiterung** | Variable aus jedem Controller | Muster `ComparisonExtension`: Der Footer steht auf jeder Seite; der erste Controller, der die Liste vergäße, lieferte einen halben Footer |
| 12 | **`/sponsoren` als feste Seite in `SeoRegistry`, die Sponsorenseiten abgeleitet** (wie Restaurantseiten) | jede Sponsorenseite von Hand eintragen | AK-26 verbietet „Sitemap anfassen“. Folge: Die Zahl der festen Seiten steigt von 20 auf **21** — `SeoRegistryTest`, die Zählprüfläufe der Sitemap und die Tabelle in `features/10-sitemap-robots/spec.md` ziehen mit |
| 13 | **Keine Zwischenspeicherung** der Auswahl im Footer | Cache je Tag | Die Auswahl ist eine Rechnung über eine Handvoll Einträge; ein Cache brächte nur die Frage, wann er am Tageswechsel verfällt (AK-23, AK-24). Die Sitemap bleibt gespeichert (50 Min. + 10 Min. HTTP, Feature 10) — innerhalb der Stunde aus AK-23/AK-41 |

## Was außerhalb des Codes mitzieht

Keine Funktion, aber Teil des Baus — sonst laufen Code und Referenz auseinander:

| Datei | Was |
|---|---|
| `docs/prd.md` | Zielgruppe 4 und Geschäftsmodell: ein Paket statt der Formate, Preis auf Anfrage, „Mitarbeitende als Testpersonen“ entfällt |
| `docs/data-model.md` | Bedeutung von `sponsorship_interests`, Enum `SponsorshipTerm`, Altwerte |
| `docs/datenschutz.md` | neue Verarbeitung „Nennung von Sponsoren“ (OF-03): Zweck, Daten, Rechtsgrundlage Vertrag, Entfernung binnen 30 Tagen, Quelltext-Verlauf (OF-01) |
| `docs/app-shell.md` | Footer: Bereich „Sponsoren“ |
| `CLAUDE.md` | Abschnitt zu Feature 15 mit der **Anleitung „Sponsor eintragen / beenden / entfernen“** (AK-26, AK-43) |
| `features/10-sitemap-robots/spec.md` | Vermerk: 21 feste Seiten seit Feature 15 |
| `features/B15-organisations-wartelisten/spec.md` | Vermerk „Geändert 2026-10-28 durch Feature 15“ an AK zu den Sponsoring-Interessen |

## Abdeckung der Akzeptanzkriterien

Abgegangen an `spec.md`, Kriterium für Kriterium.

| AK | Erfüllt durch | Anmerkung |
|---|---|---|
| AK-01 | Footer-Bereich „Sponsoren“ in `partials/_footer.html.twig`, Auswahl aus `SponsorExtension` | auf jeder Seite, weil im Footer-Partial |
| AK-02 | `_logo.html.twig` (feste Fläche, `object-contain`), Sortierung in `SponsorDirectory` | |
| AK-03 | Link der Kachel auf `app_sponsor_show` mit aktueller Sprache, ohne `target` | |
| AK-04 | `alt` = Name, Fokusrahmen wie Footer-Links, Kachel ≥ 44 × 44 px | Prüfung mit dem Werkzeug aus Feature 02 |
| AK-05 | Linkzeile im Footer-Bereich, Bedingung „aktiv oder ehemalig vorhanden“ aus `SponsorDirectory` | |
| AK-06 | Footer-Auswahl: die ersten zwölf aktiven | |
| AK-07 | leerer Zustand des Footer-Bereichs | |
| AK-08 | strukturell: kein Baustein dieses Features wird von Restaurantliste, Detailseite, Startseite oder Datensatz gelesen; Funktionstest vergleicht Hauptinhalt mit und ohne Sponsoren | |
| AK-09 | Sponsoren erscheinen nur im Footer-Partial; Funktionstest prüft den Bereich `<main>` der Detailseite | |
| AK-10 | `loading="lazy"` + `width`/`height`; `SponsorRules`: je Datei ≤ 25 KB **und** die zwölf größten zusammen ≤ 150 KB | Gemessen auf Startseite und Restaurantliste bei 390 px; auf sehr kurzen Seiten laden Browser den Footer in ihrer Vorlaufzone vorab (Entscheidung #6) |
| AK-11 | `sponsor/index.html.twig`: Kopf, „bekommt“, `_not_included`, Ausschlussregel, Verwendung, Handlungsaufruf | Ausschlussregel über die bestehenden Schlüssel `organisation.company.exclusion_*` |
| AK-12 | Liste aktiver Sponsoren auf der Übersicht, ohne Begrenzung | |
| AK-13 | leerer Zustand der Übersicht | |
| AK-14 | strukturell: `Sponsor` hat kein Feld für Betrag oder Laufzeitart; Funktionstest durchsucht Footer, Sponsorenseiten und Faktenblatt in fünf Sprachen | Der allgemeine Paketsatz auf Übersicht und Unternehmensseite ist ausgenommen — er nennt keinen Sponsor |
| AK-15 | Abschnitt „Bisher unterstützt von“, Sortierung in `SponsorDirectory` | |
| AK-16 | `sponsor/show.html.twig` mit `_periods`, Zitat, Verwendung, `_not_included`, Links | |
| AK-17 | Website-Link: `href` = Feld `website` unverändert, `rel="sponsored noopener"` | |
| AK-18 | `blockquote` mit `lang` = `quoteLocale`; Zitat nicht übersetzt | |
| AK-19 | Zitat mit Zeichnung „— Name“; Länge durch `SponsorRules` | |
| AK-20 | Zitat und Website-Link nur bei vorhandenem Feld | |
| AK-21 | `SponsorDirectory`: Zustand „ehemalig“; Template ohne Website-Link in diesem Zustand | |
| AK-22 | `SponsorController::show()`: unbekannter Slug → 404; Requirement am Pfad | |
| AK-23 | Zustand „unsichtbar“ in `SponsorDirectory`; Sitemap wird höchstens 60 Min. gespeichert | |
| AK-24 | Zustandsregel mit Ende einschließlich, Uhr in `Europe/Luxembourg`, keine Zwischenspeicherung im Footer (Entscheidung #13) | Prüfbar mit der verstellbaren Uhr (Entscheidung #2) |
| AK-25 | Sortierung nach laufendem Zeitraum; `_periods.html.twig` | |
| AK-26 | Ein Eintrag in `SponsorRegistry` + eine Logodatei; Sitemap, Footer und alle Seiten leiten ab; Name und Zitat stehen nicht im Katalog (Entscheidung #10, #12); Anleitung in `CLAUDE.md` | Nachweis im QA-Lauf: Probeeintrag mit gestoppter Zeit bis „bereit zum Ausrollen“ + gemessene Ausrolldauer |
| AK-27 | `SponsorRules` + Prüflauf über alle Einträge der Produktionsregistry | Gegenprobe mit absichtlich fehlerhaftem Eintrag |
| AK-28 | `SponsorRules`: Endung und Dateisignatur PNG/WebP, keine SVG (Entscheidung #4) | |
| AK-29 | weiße Kachel in `_logo.html.twig` (Entscheidung #5) | |
| AK-30 | Link im Block Finanzen von `open/index.html.twig`, außerhalb der Sperrbedingung | |
| AK-31 | `_logo_row.html.twig` über der Formularkarte, nur Typ Unternehmen, dieselbe Auswahl wie der Footer | |
| AK-32 | Zeile im Faktenblatt `press/_facts.html.twig`, Zahl aus `SponsorExtension` | Schlüssel in fünf `press.*.yaml` |
| AK-33 | Paketblock in `_section_company.html.twig` | |
| AK-34 | Formatraster und Schlüssel `organisation.company.format_*` entfernt; `organisation.selector.company` und `organisation.company.intro` in fünf Sprachen umformuliert | Prüflauf durchsucht alle öffentlichen Seiten nach den alten Formatnamen |
| AK-35 | Block „Auch ohne Geld“ unverändert | |
| AK-36 | Ausschlussblock unverändert | |
| AK-37 | Feld „Interesse an einem Sponsoring“: zwei Radios, nicht Pflicht, ohne JavaScript bedienbar | Entscheidung #8 |
| AK-38 | Auswahlprüfung des Formulars (ungültige Wahl → 422) + Prüfregel `company` an der Entity; interne Meldung und Verwaltung zeigen die Laufzeit | |
| AK-39 | Entity-Zugriff „alte Werte“; Verwaltung zeigt sie mit Vermerk „frühere Angabe“; keine Migration | |
| AK-40 | `SeoRegistry::isIndexableRoute()` kennt beide Routen → canonical + Sprachverweise über `SeoExtension` | |
| AK-41 | `SeoRegistry`: feste Seite `/sponsoren` + abgeleitete Sponsorenseiten aus `SponsorDirectory` (aktiv + ehemalig) in `SitemapGenerator` | Entscheidung #12 |
| AK-42 | Logos über AssetMapper vom eigenen Ursprung; bestehende `Referrer-Policy` | |
| AK-43 | Ablauf „entfernen“: Eintrag + Datei löschen, Slug in `removedSlugs()` → 301; Anleitung in `CLAUDE.md` | Ablauf beim Betreiber; der Zustand am 30. Tag ist der Nachweis |
| AK-44 | strukturell: `Sponsor` hat kein Feld für eine Person; Zitat gezeichnet mit `name` | |
| AK-45 | strukturell: kein Bezug zwischen Sponsor und Wartelisten-Eintrag in beiden Richtungen | |
| AK-46 | kein Messauslöser am Website-Link | Netzwerkmitschnitt im Browser |
| AK-47 | eigener Pfad je Sponsor; `CollectPayloadNormalizer` lässt öffentliche Pfade durch | |
| AK-48 | Umami-Abfrage nach Pfad `*/sponsoren/<slug>` | Plattformeinstellung, kein Code |

---

## Nachtrag · Überarbeitung 2026-10-28 — Kurzbeschreibung und Profillinks (AK-49 bis AK-55)

Status: `architected` (Nachtrag) · Grundlage: Spec-Überarbeitung vom 2026-10-28, Decision Log #29–#33.

### Überblick

Der `Sponsor` bekommt zwei optionale Felder: eine **Kurzbeschreibung je Sprache** und **Profile bei Instagram,
Facebook und LinkedIn**. Beide stehen — wie Name und Zitat — **im Eintrag**, nicht im Katalog: Ein neuer Sponsor
braucht weiterhin keine Übersetzungsdatei (AK-26 bleibt wahr), obwohl seine Beschreibung in fünf Sprachen
vorliegt. Die Sponsorenseite zeigt die Beschreibung in der Seitensprache unter „Über <Name>“ und die Profile in
derselben Linkzeile wie die Website — nur bei aktiven Sponsoren.

### Datenmodell (Änderung am Wertobjekt `Sponsor`)

| Feld | Typ | Pflicht | Bedeutung |
|---|---|---|---|
| `description` | Zuordnung Sprache → Text | nein | Wenn gesetzt, **genau** die fünf Sprachen aus `framework.enabled_locales`, je 1–500 Zeichen, ohne Eurobetrag (AK-49, AK-51) |
| `profiles` | Zuordnung `SponsorPlatform` → URL | nein | höchstens ein Profil je Plattform; `https://`, Host gehört zur Plattform (AK-52, AK-53) |

Neues Enum **`App\Sponsor\SponsorPlatform`** (`instagram`, `facebook`, `linkedin`) mit `label()` (Eigenname, nicht
übersetzt: „Instagram“, „Facebook“, „LinkedIn“) und `hosts()` (`instagram.com`, `facebook.com`, `linkedin.com` —
erlaubt ist der Host selbst oder eine Unterdomain davon, z. B. `www.` oder `lu.linkedin.com`). Die Reihenfolge der
Fälle ist die Reihenfolge auf der Seite.

⚠ **Die Liste der Sprachen wird nicht ein weiteres Mal abgeschrieben.** `SponsorRules` bekommt sie als Argument;
`SponsorRegistryTest` liest sie aus `config/packages/translation.yaml`. Sonst entstünde eine sechste Stelle, die
beim Hinzufügen einer Sprache von Hand mitziehen müsste (siehe `CLAUDE.md`, „Eine Sprache hinzufügen“).

### Komponenten (Änderung an `sponsor/show.html.twig`)

```
Sponsorenseite
├── Kopf                      unverändert
├── Über <Name>               NEU · h2 + Beschreibung in der Seitensprache, Zeilenumbruch → Absatz; fehlt ohne Beschreibung (AK-50)
├── Zitat                     unverändert
├── Verwendung                unverändert
├── Was <Name> nicht bekommt  unverändert
├── Links                     GEÄNDERT · Website + je Profil ein Link „Instagram“ usw.; nur aktiv (AK-54); fehlt ohne beides (EC-14)
└── Fußleiste                 unverändert
```

- Profillink: sichtbarer Text = Plattformname, `aria-label` = „<Plattform> von <Name>“ (Katalogschlüssel mit
  Platzhaltern), `rel="sponsored noopener"`, kein `target`, **kein Symbol einer Plattform** (ein Logo von deren
  Server verstieße gegen AK-55; ein eingebettetes Markensymbol wäre zusätzlich eine Marke ohne Nutzungsrecht).
- Beschreibung: Twig-Ausgabe mit Escaping, danach `nl2br` (EC-13). Kein `|raw`.

### Zugriff, Missbrauch, externe Dienste

Unverändert. Die Profile sind Links, keine Einbettung — kein Dienst bekommt beim Seitenaufruf Daten (AK-55). Was
die Plattform nach einem Klick erfasst, liegt außerhalb von Endlech.lu und steht so in `docs/datenschutz.md`.

### Technische Entscheidungen (Nachtrag)

| # | Entscheidung | Alternative | Warum so |
|---|---|---|---|
| 14 | **Beschreibung je Sprache im Eintrag** | Katalog `sponsor.<slug>.description` in fünf Dateien | AK-26 („keine Übersetzungsdatei anfassen“) bleibt wahr, und `CatalogueCompletenessTest` müsste sonst Schlüssel kennen, die mit jedem Vertrag entstehen und verschwinden |
| 15 | **Sprachen aus `translation.yaml` gelesen**, nicht abgeschrieben | Konstante in `SponsorRules` | eine sechste Handliste beim Hinzufügen einer Sprache vermieden |
| 16 | **Plattformen als Enum mit erlaubten Hosts** | freie Liste beliebiger Links | AK-53: Ein „Instagram“-Link auf einen fremden Server wäre ein getarnter Werbelink |
| 17 | **Textlinks ohne Plattformsymbol** | Markensymbole (SVG) | kein Ressourcenabruf, keine Markennutzung ohne Freigabe; der Plattformname ist für alle lesbar |

### Abdeckung (Nachtrag)

| AK | Erfüllt durch | Anmerkung |
|---|---|---|
| AK-49 | Feld `description`, Abschnitt „Über <Name>“ in `show.html.twig`, Auswahl nach `app.request.locale` | |
| AK-50 | Abschnitt nur bei gesetztem Feld | |
| AK-51 | `SponsorRules`: fünf Sprachen genau, 1–500 Zeichen, kein `€`/`EUR` | Sprachen aus `translation.yaml` |
| AK-52 | Feld `profiles`, `SponsorPlatform::label()`, Linkzeile mit `aria-label`, `rel` | |
| AK-53 | `SponsorRules`: `https://` und `SponsorPlatform::hosts()` | |
| AK-54 | Linkzeile nur im Zustand „aktiv“; Beschreibung unabhängig vom Zustand | |
| AK-55 | Textlinks ohne Symbol, kein Messauslöser; Prüflauf aus T19 um Profillinks erweitert | |
| EC-13 | Escaping + `nl2br` | |
| EC-14 | Linkzeile erscheint, sobald Website **oder** ein Profil vorliegt | |
