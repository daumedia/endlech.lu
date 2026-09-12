# 10 · Sitemap und robots.txt — Systemdesign

Status: `architected` · Stand: 2026-09-12 · Stack-Profil: `symfony-doctrine`

**Kein Code in diesem Dokument.** Es wird gelesen und freigegeben, nicht ausgeführt.

## Überblick

Ein neues **Seitenverzeichnis** hält an genau einer Stelle fest, welche Seiten Suchmaschinen
angeboten werden (21 feste Seiten und alle Restaurant-Detailseiten) und welche aus den
Ergebnissen herausbleiben (16 Wege). Aus diesem Verzeichnis entstehen drei Dinge: die
Sitemap unter `/sitemap.xml`, der canonical-Verweis im Kopf jeder angebotenen Seite und die
Kopfzeile `X-Robots-Tag: noindex` auf jeder ausgeschlossenen. Alle Adressen darin baut ein
gemeinsamer **Adressbildner** aus der festen Basisadresse `https://endlech.lu` — nie aus der
Anfrage. Die Sitemap wird höchstens 50 Minuten zwischengespeichert; die `robots.txt` ist eine
statische Datei, die der Webserver ausliefert, ohne dass PHP läuft.

Zwei Fragen, die beim Entwerfen an die Spec zurückgingen, sind am 2026-09-12 entschieden:
Folgeseiten der Restaurantliste behalten ihre Seitenzahl im canonical-Verweis (OF-01), und
der Ausschluss wird als Antwortkopfzeile geprüft (OF-04). AK-13, AK-15 und AK-16 sind
entsprechend gefasst.

## Seiten und Routen

| Route | Zweck | Zugang |
|---|---|---|
| `/sitemap.xml` | **neu** — die Sitemap, sprachfrei, zustandslos | öffentlich · Deckel 60/h je Adresse |
| `/robots.txt` | **neu** — statische Datei unter `public/`, vom Webserver direkt ausgeliefert | öffentlich · kein Deckel, kein PHP |
| die 21 festen Seiten (Tabelle in `spec.md`) | **Änderung** — erhalten den canonical-Verweis aus dem Adressbildner | unverändert |
| `/{s}/restaurants/{id}` | **Änderung** — ebenso | unverändert |
| die 16 Ausschlusswege, siehe unten | **Änderung** — erhalten `X-Robots-Tag: noindex` auf jeder Antwort, auch auf Weiterleitungen | unverändert |

Nicht neu: kein `/de/sitemap.xml` (liefert 404), keine Weiterleitung, kein Verzeichnis
`public/sitemap*` (BF-100 — `RouteDirectoryCollisionTest` wacht darüber).

**Die 16 Ausschlusswege** (AK-15), als Routennamen: `app_login`, `app_register`,
`app_password_reset_request`, `app_password_reset`, `app_verify_email`,
`app_email_change_confirm`, `app_partner_confirm`, `app_organisations_confirm`,
`app_app_waitlist_confirm`, `app_partner_revoke`, `app_organisations_revoke`,
`app_app_waitlist_revoke`, `community_vorschlagen`, `community_danke`, `app_board_new`,
`app_board_thanks`.

⚠ Zwei davon — `app_verify_email` und `app_email_change_confirm` — **rendern nie eine
Seite**, sie leiten nur weiter (im Code nachgesehen). Ein Vermerk im Quelltext ist dort nicht
möglich; die Kopfzeile schon. Das ist der Anlass für OF-04.

## Komponentenstruktur

```
Suchmaschinen-Auszeichnung                        neuer Bereich, eigener Namensraum
├── Seitenverzeichnis                             die EINZIGE Stelle, die klassifiziert
│   ├── feste Seiten                              21 Routen samt Parametern; die drei Vergleiche
│   │                                             aus dem Vergleichsverzeichnis (03), die drei
│   │                                             Organisationsseiten aus dem Organisationstyp —
│   │                                             nicht abgeschrieben
│   ├── Detailseiten                              Restaurantnummern aus einer schlanken Abfrage
│   └── Ausschlussliste                           die 16 Routennamen oben
├── Adressbildner                                 absolute Adresse und die vier Sprachfassungen
│                                                 samt Vorgabe lb, immer auf https://endlech.lu
├── Sitemap-Endpunkt                              sprachfrei, zustandslos; gibt die gespeicherte
│   │                                             Fassung aus oder erzeugt sie vollständig neu
│   └── Sitemap-Vorlage                           urlset mit je vier Sprachverweisen, sonst nichts
├── Kopfzeilen-Abonnent                           setzt X-Robots-Tag: noindex für die Ausschlussliste
└── Seitenkopf der App-Hülle                      Änderung an der bestehenden Basisvorlage
    ├── canonical-Verweis                         nur für Seiten aus dem Verzeichnis
    └── Sprachverweise                            bestehend, auf den Adressbildner umgestellt

public/robots.txt                                 statisch, siehe Entscheidung 1
```

**Bestehendes, das wiederverwendet und nicht neu gebaut wird:** der Routen-Deckel-Abonnent
(bekommt einen vierten Zweig), die Kennzeichnung gegen Symfonys automatische Cache-Kopfzeile
(wie beim offenen Datensatz), das Vergleichsverzeichnis, der Organisationstyp.

**Wegfallend:** die fünf canonical-Blöcke, die heute einzelne Vorlagen selbst füllen
(Vergleichsübersicht, Vergleichsseite, Presse, Roadmap, Changelog). Sie wären eine zweite
Quelle und bauten ihre Adresse aus der Anfrage — auf `www` also falsch.

### Zustände der Sitemap

| Zustand | Was ausgeliefert wird |
|---|---|
| gefüllt | HTTP 200, (21 + n) × 4 Einträge |
| leerer Bestand | HTTP 200, die 84 Einträge der festen Seiten (AK-10) |
| Fehler ohne gespeicherte Fassung | HTTP 5xx aus der gewöhnlichen Fehlerbehandlung; **nichts wird gespeichert** (AK-11) |
| Fehler mit gespeicherter Fassung | die gespeicherte, vollständige Fassung (EC-01) |
| gedeckelt | HTTP 429 mit Wartezeit — **auch** wenn eine gespeicherte Fassung vorliegt (EC-04) |
| ladend | trifft nicht zu — kein Client wartet auf Teilergebnisse |

## Datenmodell

**Keine neue Tabelle, keine neue Spalte, keine Migration.**

| Änderung | Art | Bedeutung |
|---|---|---|
| Restaurant-Repository | **neue Leseabfrage** | alle Restaurantnummern, aufsteigend sortiert — nur die Nummer, ohne Beziehungen. ⚠ Nicht `findAllForExport()` wiederverwenden: Das lädt jedes Restaurant vollständig samt Beziehungen, um am Ende nur die Nummer zu brauchen. Die Abfrage ist ohne natives SQL formuliert, also MariaDB-tauglich (Stack-Profil § 6) |
| Zwischenspeicher `cache.sitemap` | **neuer Pool** | Dateisystem, Lebensdauer 50 Minuten, hält die fertig erzeugte Sitemap als Ganzes. Im Test ein Array-Adapter (Muster aller Pools in `cache.yaml`) |
| Parameter `app.canonical_base_url` | **neu** | fester Wert `https://endlech.lu`, bewusst **ohne** Umgebungsvariable — siehe Entscheidung 2 |
| Deckel `sitemap` | **neu** | siehe Missbrauchsschutz |

Löschregel: trifft nicht zu — es entsteht nichts, was gelöscht werden müsste. Ein
gelöschtes Restaurant fällt beim nächsten Erzeugen heraus.

## Zugriffsregeln

| Wer | Darf lesen | Darf schreiben | Erzwungen durch |
|---|---|---|---|
| jeder, auch anonym | Sitemap, robots.txt | nichts | öffentliche Route ohne Anmeldung; die Sitemap-Route ist **zustandslos** markiert — ein Zugriff auf die Sitzung würde dort einen Fehler werfen statt still eine anzulegen |
| — | Was **nicht** in der Sitemap stehen darf | — | **Konstruktion:** Die Sitemap besteht ausschließlich aus Verzeichniseinträgen und Restaurantnummern. Es gibt keinen Weg, eine andere Adresse hineinzubekommen. Dazu der Abdeckungs-Prüflauf (Entscheidung 3) |

⚠ **Die Sperren in der robots.txt sind keine Zugriffsregel.** Verwaltung und Profil
schützen weiterhin die bestehenden `access_control`-Regeln und die Anmeldung; wer die
robots.txt ignoriert, landet dort auf der Anmeldeseite wie bisher.

## Missbrauchsschutz

| Endpunkt | Limit | Verhalten bei Überschreitung | Wo konfiguriert |
|---|---|---|---|
| `/sitemap.xml` | 60 je Stunde je Adresse, gleitendes Fenster | HTTP 429 mit `Retry-After` in Sekunden | Deckel `sitemap` in `config/packages/framework.yaml`, **mit** `when@test`-Override auf 10000; angewandt im bestehenden Routen-Deckel-Abonnent, vierter Zweig |
| `/robots.txt` | keines | — | trifft nicht zu: statische Datei, erreicht PHP nie |

**Eigener Deckel, nicht der des Datensatzes** — auch wenn die Zahl dieselbe ist. Teilten sich
beide ein Kontingent, sperrte ein Nutzer, der den Datensatz 60-mal holt, sich die Sitemap mit
(dasselbe Muster wie BF-38). `LimiterCoverageTest` verlangt Verdrahtung und Override.

## Externe Dienste

| Dienst | Wofür | Was geht hin | Was wird vorher entfernt |
|---|---|---|---|
| — | — | **Nichts.** Die Anwendung ruft keinen Dienst auf. Google holt sich Sitemap, robots.txt und öffentliche Seiten selbst | trifft nicht zu |
| Google Search Console | Nachweis AK-24, AK-25 | wird vom **Betreiber** in der Oberfläche bedient, nicht von der Anwendung | trifft nicht zu |

## Technische Entscheidungen

| # | Entscheidung | Alternative | Warum so |
|---|---|---|---|
| 1 | **robots.txt als statische Datei** unter `public/` | eigene Route mit Controller | Google stellt bei einer 5xx-Antwort auf die robots.txt **zwölf Stunden das Crawlen ein** (*developers.google.com/search/docs/crawling-indexing/robots/robots_txt, Stand 2026-08-31*). Eine Datei, die der Webserver ohne PHP ausliefert, kann an einer Datenbankstörung oder einem kaputten Deploy nicht scheitern. Sie braucht keinen Deckel und ist im Prüflauf direkt lesbar |
| 2 | **Feste Basisadresse `https://endlech.lu` als Parameter** — für Sitemap, canonical und Sprachverweise | (a) aus der Anfrage, wie heute bei den Sprachverweisen · (b) aus `DEFAULT_URI` | (a) scheitert zweifach: `www.endlech.lu` liefert **HTTP 200** (gemessen, OF-03), und eine zwischengespeicherte Sitemap hielte eine Stunde lang fest, über welchen Host der erste Abrufer kam. (b) steht in `.env` auf `http://localhost` und müsste in Coolify gesetzt sein — eine vergessene Variable kündigte Google dann `localhost` an, lautlos (dieselbe Lehre wie `TRUSTED_PROXIES`). Die Hauptadresse ist eine Produkteigenschaft, keine Umgebungseinstellung |
| 3 | **Ein Seitenverzeichnis als einzige Quelle** für Sitemap, canonical und Ausschluss, dazu ein **Abdeckungs-Prüflauf**, der jede öffentliche GET-Route mit Sprachpräfix einer von drei Klassen zuordnen muss: *angeboten*, *ausgeschlossen*, *bewusst keins von beidem* (z. B. einzelne Board-Ideen) | canonical je Vorlage, wie heute bei fünf Vorlagen; Ausschlussvermerk je Vorlage | Zwei Quellen laufen auseinander, und `spec.md` verlangt, dass eine neue Seite ohne Nachziehen **rot** wird. Der Prüflauf hat dasselbe Muster wie `LimiterCoverageTest` und `RouteDirectoryCollisionTest`: Er prüft die Ursache (eine unklassifizierte Route), nicht das Symptom |
| 4 | **Ausschluss als Kopfzeile `X-Robots-Tag: noindex`**, gesetzt von einem Antwort-Abonnenten anhand der Ausschlussliste | `<meta name="robots">` in 16 Vorlagen | Wirkt laut Google für jede Antwort, auch Weiterleitungen und Nicht-HTML (*…/block-indexing, Stand 2025-12-10*) — und zwei der 16 Wege sind reine Weiterleitungen. Eine Stelle statt sechzehn. **Entschieden am 2026-09-12 (OF-04).** |
| 5 | **Zwischenspeicher 50 Minuten auf dem Server, `max-age` 10 Minuten in der Antwort** | beide 60 Minuten | Beide Speicher addieren sich: Eine Fassung, die kurz vor Ablauf ausgeliefert wird, bliebe sonst bei einem Zwischenhändler bis zu zwei Stunden alt. 50 + 10 hält AK-08/AK-09 („binnen 60 Minuten") auch dann ein |
| 6 | **Kein Verwerfen bei Änderungen**, nur Ablauf | ein Listener wie `RoadmapCacheListener` | Die Spec verlangt 60 Minuten, nicht sofort. Der Listener wäre Code für eine Zusage, die niemand gemacht hat — und Doctrine meldet Löschungen über Fremdschlüssel-Kaskaden ohnehin nicht (dort am eigenen Leib erfahren) |
| 7 | **Vollständig erzeugen, dann speichern** — keine Teilausgabe, kein Auffangen von Datenbankfehlern | Fehler auffangen und die festen Seiten ausliefern | Eine verkürzte Sitemap sagt Google „die Restaurantseiten sind weg" (AK-11). Ein Fehler, der ungefangen zur 5xx wird, sagt nur „später nochmal". ⚠ Wer beim Bauen einen `try`/`catch` um die Abfrage legt, bricht genau dieses Kriterium — der Prüflauf dazu lässt die Abfrage absichtlich scheitern |
| 8 | **Sitemap aus einer Twig-Vorlage** in eine Zeichenkette gerendert | XML-Schreiber im Code | Projektkonvention: Ausgaben entstehen in Vorlagen. Die Adressen enthalten keine Sonderzeichen (keine Parameter, Nummern statt Namen); die HTML-Maskierung der Vorlage erzeugt ohnehin gültige XML-Entitäten |
| 9 | **Eigener sprachfreier Controller-Bereich** mit eigenem Block in `config/routes.yaml` und Eintrag in der `exclude`-Liste | in `Controller/Open/` mitschwimmen | Dasselbe Muster wie `Open/`, `Health/`, `Marketing/`, `Api/V1/`: je Zweck ein Block. `Open/` ist offene Daten; eine Sitemap dort wäre der erste Fremdkörper |
| 10 | **Schema-Prüfung im Prüflauf mit mitgelieferten Schemata**: `sitemap.xsd` von sitemaps.org plus ein kleines Schema für das Sprachverweis-Element | Online-Validator; Prüfung nur gegen `sitemap.xsd` | `sitemap.xsd` erlaubt fremde Elemente nur mit **strenger** Prüfung (`processContents="strict"`, *sitemaps.org/schemas/sitemap/0.9/sitemap.xsd, gelesen 2026-09-12*) — ohne ein Schema für die Sprachverweise fiele jede korrekte Sitemap durch. Ein Online-Validator läuft in der CI nicht. Die Schemadatei steht unter CC BY-SA; sie liegt mit Quellenangabe bei den Prüfläufen |
| 11 | **canonical = Route und ihre Pfadparameter; von den Abfrageparametern bleibt nur `page`, und nur als ganze Zahl ab 2** | alle Abfrageparameter verwerfen, auch die Seitenzahl | Google: *„Don't use the first page of a paginated sequence as the canonical page."* **Entschieden am 2026-09-12 (OF-01).** Nur für die Restaurantliste relevant — keine andere Verzeichnisseite kennt einen Seitenparameter. `page=1` und ungültige Angaben entfallen, sonst gäbe es für Seite 1 zwei maßgebliche Adressen. ⚠ **Dieselbe Regel gilt für die Sprachverweise im Seitenkopf:** Nennte Seite 2 sich selbst maßgeblich, verwiese in den vier Sprachen aber auf Seite 1, widersprächen sich beide Angaben. Der Adressbildner behält die Seitenzahl deshalb für canonical **und** Sprachverweise |
| 12 | **Sprachverweise in Sitemap und Seitenkopf zugleich** | nur eine der beiden Stellen | Google nennt beides zulässig und rät aus Pflegegründen zu einer (*…/localized-versions, gelesen 2026-09-12*). Die Seitenkopf-Verweise gibt es aber seit Langem, und AK-14 verlangt Übereinstimmung. Aus **einem** Adressbildner gespeist kostet die zweite Stelle keine Pflege |

### Beim Bauen zu beachten

- ⚠ **Fünf bestehende Prüfläufe prüfen canonical- oder Sprachverweise**
  (`ComparisonControllerTest`, `PressControllerTest`, `RoadmapControllerTest`,
  `RoadmapFreshnessTest`, `QueryParameterReflexionTest`). Mit Entscheidung 2 lauten diese
  Adressen auch lokal auf `https://endlech.lu` statt auf den Testhost. Das ist gewollt; die
  Prüfläufe ziehen mit.
- ⚠ **Jede Sitemap-Adresse wird im Prüflauf tatsächlich aufgerufen** (AK-04). Mit den
  Fixtures sind das (21 + 11) × 4 = 128 Abrufe gegen den Testhost — Pfad der Adresse, nicht
  ihr Host.
- ⚠ **`public/robots.txt` enthält keine Platzhalter** (`*`, `$`). Google versteht sie, aber
  ohne sie ist die Regelauswertung eine reine Präfixprüfung — der Prüflauf kann sie dann exakt
  nachbilden, statt einen Parser nachzubauen. Die Sperren stehen je Sprache ausgeschrieben.

## Abdeckung der Akzeptanzkriterien

Abgegangen aus `spec.md`, Kriterium für Kriterium.

| AK | Erfüllt durch | Anmerkung |
|---|---|---|
| AK-01 | Sitemap-Endpunkt, Sitemap-Vorlage | Nachweis per Schema-Prüfung mit beiden Schemata (Entscheidung 10) |
| AK-02 | Seitenverzeichnis (21 feste Seiten) + Leseabfrage der Restaurantnummern | Anzahl im Prüflauf gegen die Fixtures: 128 |
| AK-03 | Adressbildner mit fester Basisadresse; nur Pfadparameter | Entscheidung 2 und 11 |
| AK-04 | Seitenverzeichnis enthält nur Routen, die 200 liefern; Startseite mit abschließendem Schrägstrich, wie die Route | Prüflauf ruft jede Adresse auf |
| AK-05 | Adressbildner: vier Sprachfassungen samt Vorgabe `lb` je Eintrag, der Eintrag selbst eingeschlossen | Google verlangt, dass jede Fassung sich selbst mit aufführt |
| AK-06 | Konstruktion: Sitemap nur aus Verzeichnis und Restaurantnummern; Abdeckungs-Prüflauf | Entscheidung 3 |
| AK-07 | Sitemap-Vorlage enthält weder Datum noch Priorität noch Häufigkeit | Google wertet Priorität und Häufigkeit nicht aus (*…/build-sitemap, Stand 2026-07-08*) |
| AK-08 | Zwischenspeicher 50 min + `max-age` 10 min; Abfrage beim nächsten Erzeugen | Entscheidung 5; gilt auch für eine Freigabe über „Vorschläge prüfen" (B21), weil sie ein Restaurant anlegt |
| AK-09 | ebenso | |
| AK-10 | Seitenverzeichnis unabhängig vom Bestand; leere Abfrage ergibt null Detailseiten | Zustandstabelle |
| AK-11 | Vollständig erzeugen, dann speichern; kein Auffangen | Entscheidung 7; Prüflauf mit absichtlich scheiternder Abfrage |
| AK-12 | Seitenkopf: canonical-Verweis aus dem Adressbildner für jede Verzeichnisseite | ersetzt die fünf vorlageneigenen Blöcke |
| AK-13 | Adressbildner verwirft Sortier- und Filterparameter, behält `page` ab 2 | Entscheidung 11; die vier Beispielzeilen der Spec als Datenfälle im Prüflauf |
| AK-14 | Seitenkopf-Sprachverweise und Sitemap aus demselben Adressbildner | Entscheidung 12 |
| AK-15 | Kopfzeilen-Abonnent mit Ausschlussliste | Entscheidung 4; der Prüflauf ruft jeden der 16 Wege auf, die zwei Weiterleitungen eingeschlossen |
| AK-16 | Ausschlussliste und Verzeichnis sind disjunkt; Abdeckungs-Prüflauf prüft beides | zusätzlich: keine Verzeichnisseite trägt ein `noindex` im Quelltext |
| AK-17 | `public/robots.txt` mit Sitemap-Zeile | statisch, Entscheidung 1 |
| AK-18 | `public/robots.txt`: Sperren für `/api/` sowie je Sprache `/{s}/admin`, `/{s}/profile`, `/{s}/api/` | ohne Platzhalter |
| AK-19 | keine Sperre berührt Verzeichnisseiten, `/open.json`, `/open/dataset.*` | Prüflauf wendet die Präfixregeln auf jede Sitemap-Adresse an |
| AK-20 | keine Sperre berührt die 16 Ausschlusswege | sonst läse Google den Ausschlussvermerk nie (*…/block-indexing*) |
| AK-21 | `public/robots.txt` enthält keine Sperre der gesamten Seite | Prüflauf |
| AK-22 | Konstruktion wie AK-06; zusätzlich Prüflauf auf `@` und Token-Muster | |
| AK-23 | Deckel `sitemap` im Routen-Deckel-Abonnent | Missbrauchsschutz; `LimiterCoverageTest` |
| AK-24 | **Plattform:** Betreiber reicht die Sitemap in der Search Console ein | Konfigurationsaufgabe nach dem Deploy, Screenshot in `qa-report.md` |
| AK-25 | **Plattform:** robots.txt-Bericht in der Search Console | ebenso |

Keine Zeile ist leer, keine trägt einen Vorbehalt.

## Gelesene Quellen

| Quelle | Stand | Was daraus folgt |
|---|---|---|
| developers.google.com/search/docs/specialty/international/localized-versions | gelesen 2026-09-12 | Sprachverweise je Eintrag, sich selbst eingeschlossen, vollständige Adressen, `x-default` |
| developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap | 2026-07-08 | 50.000 Adressen / 50 MB, UTF-8, absolute Adressen; `lastmod` nur bei belegbarer Genauigkeit, `priority`/`changefreq` ignoriert |
| developers.google.com/search/docs/crawling-indexing/block-indexing | 2025-12-10 | `X-Robots-Tag` für jede Antwort; **kein `noindex` hinter einer robots.txt-Sperre** |
| developers.google.com/search/docs/crawling-indexing/robots/robots_txt | 2026-08-31 | Präfix, Groß-/Kleinschreibung, längste Regel gewinnt; 5xx stoppt das Crawlen zwölf Stunden |
| developers.google.com/search/docs/specialty/ecommerce/pagination-and-incremental-page-loading | 2025-12-10 | Folgeseiten nicht auf Seite 1 kanonisieren → OF-01 |
| sitemaps.org/schemas/sitemap/0.9/sitemap.xsd | Schema von 2008 | fremde Elemente streng geprüft → Entscheidung 10 |
