# 03 · Vergleichsseiten — Spezifikation

Status: `planned` · Stand: 2026-08-28

## Zweck

Wer in Luxemburg nach einem barrierefreien Restaurant sucht, landet bei Google Maps und
erfährt dort nie, dass es eine Plattform gibt, die Türbreiten in Zentimetern führt. Nach
diesem Feature findet er über eine Suchmaschine oder die Fußzeile eine Seite, die beide
Angebote nebeneinanderstellt — mit Quellen, mit Prüfdatum und einschließlich der Punkte,
in denen Endlech.lu heute unterlegen ist.

Vier Vergleiche entstehen: **Google Maps**, **Wheelmap**, **TripAdvisor und
Bewertungsportale**, **Jaccede**. Dazu eine Übersichtsseite und ein eigener Bereich in
der Fußzeile.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| B05 · Restaurantsuche | `approved` | Ziel des Handlungsaufrufs am Ende jeder Vergleichsseite (AK-09) |
| B13 · Statische Inhaltsseiten | `approved` | liefert das Muster für eine reine Inhaltsseite ohne Formular |
| B16 · Transparenzseite `/open` | `approved` | Quelle der eigenen Zahlen in der Tabelle (AK-14 bis AK-16) |
| B24 · Mehrsprachigkeit | `approved` | vier Sprachfassungen, `hreflang`, Katalogvollständigkeit (AK-25) |
| 02 · Barrierefreiheit der Plattform | `approved` | gilt für jede neue Seite; AK-54 dort lässt den Prüflauf fehlschlagen (AK-20 bis AK-24) |

## User Stories

- **US-01** · Als Gast mit eingeschränkter Mobilität möchte ich verstehen, was Endlech.lu
  mir gegenüber Google Maps zusätzlich beantwortet, damit ich entscheiden kann, ob sich
  ein zweiter Anlaufpunkt für mich lohnt.
- **US-02** · Als Gast möchte ich erfahren, wofür die andere Plattform besser ist, damit
  ich nicht zwischen zwei Werkzeugen wählen muss, die sich ergänzen.
- **US-03** · Als jemand, der über eine Suchmaschine nach „Endlech.lu vs Google Maps"
  sucht, möchte ich eine Seite finden, die genau diese Frage beantwortet, statt auf der
  Startseite zu landen.
- **US-04** · Als Vertreter einer Gemeinde möchte ich nachvollziehen können, woher die
  Aussagen über andere Anbieter stammen, damit ich sie in einer Vorlage für den Gemeinderat
  zitieren kann.
- **US-05** · Als Betreiber von Endlech.lu möchte ich, dass die Vergleichsseiten meine
  eigenen Schwächen benennen, damit sie zu den öffentlich zugesagten Produktprinzipien
  passen und nicht angreifbar sind.

## Nicht im Scope

- **`sitemap.xml` und `robots.txt`** — die Plattform hat beides heute nicht. Ein eigenes
  Feature; hier mitzuspezifizieren hieße, zwei Dinge in einer Spec zu beschreiben.
- **`meta description` und Open Graph für alle übrigen Seiten** — dieses Feature schafft
  nur den Platz dafür und füllt ihn für die eigenen fünf Seiten. Die übrigen rund fünfzehn
  Seiten bleiben unverändert.
- **Ein Admin-Bereich zum Pflegen der Vergleiche** — die Inhalte stehen im Quelltext und
  ändern sich mit einem Commit (Decision Log #5). Es entsteht keine Entität und keine
  Migration.
- **Vergleiche mit weiteren Anbietern** — vier sind beschlossen. Ein fünfter ist ein
  Nachtrag an dieser Spec, kein neues Feature.
- **Übersetzte URL-Pfade je Sprache** — der Slug ist in allen vier Sprachen derselbe, wie
  bei `/organisationen/gemeinden`. Ein erster Fall übersetzter Pfade wäre eine Änderung an
  der App-Hülle und gehört nicht hierher.
- **Ein automatischer Warnkasten bei veralteten Angaben** — bewusst ausgeschlossen
  (Decision Log #7); die Aktualität trägt der Prozess, siehe OF-02.

## Akzeptanzkriterien

Jedes Kriterium ist ohne Codekenntnis prüfbar.

### Auffindbarkeit

- **AK-01** · Angenommen, ein Besucher ist auf einer beliebigen öffentlichen Seite, wenn er
  zur Fußzeile scrollt, dann sieht er einen eigenen Bereich mit der Überschrift
  „Vergleiche", darin vier benannte Links (je einer pro Wettbewerber) und einen Link
  „Alle Vergleiche".
- **AK-02** · Angenommen, ein Besucher öffnet `/de/vergleich`, wenn die Seite lädt, dann
  stehen dort alle vier Vergleiche, jeder mit dem Namen des Wettbewerbers und einem Satz
  dazu, für welche Frage dieser Vergleich gedacht ist.
- **AK-03** · Angenommen, ein Besucher öffnet `/de/vergleich/google-maps`, wenn die Seite
  lädt, dann antwortet die Anwendung mit HTTP 200 und zeigt die Vergleichsseite — dasselbe
  gilt für `wheelmap`, `tripadvisor` und `jaccede`.
- **AK-04** · Angenommen, jemand ruft `/de/vergleich/foobar` auf, wenn die Anfrage
  verarbeitet wird, dann antwortet die Anwendung mit **404** — nicht mit der Übersicht,
  nicht mit einer leeren Seite, nicht mit einem Serverfehler.
- **AK-05** · Angenommen, ein Besucher steht auf `/de/vergleich/wheelmap`, wenn er im
  Sprachumschalter Französisch wählt, dann steht er auf `/fr/vergleich/wheelmap` — nicht
  auf der Startseite und nicht auf einer Fehlerseite.
- **AK-06** · Angenommen, ein Besucher ist auf `/de/vergleich/google-maps`, wenn er ans
  Seitenende scrollt, dann findet er Links auf die anderen drei Vergleiche, jeder mit dem
  Namen des Wettbewerbers im Linktext.
- **AK-31** · Angenommen, jemand ruft `endlech.lu/vergleich` ohne Sprachkürzel auf, wenn
  die Anfrage verarbeitet wird, dann landet er auf der Übersichtsseite in einer der vier
  Sprachen — nicht auf einer Fehlerseite.
  *(Am 2026-08-28 aus OF-01 ergänzt: Die Entscheidung war getroffen, hatte aber kein
  Kriterium — und wäre damit nie geprüft worden.)*

### Inhalt einer Vergleichsseite

- **AK-07** · Angenommen, eine Vergleichsseite ist geöffnet, wenn man den Bereich unter der
  Überschrift liest, dann stehen dort zwei Blöcke — „Endlech.lu passt, wenn …" und
  „<Wettbewerber> passt, wenn …" — sowie ein Satz, dass sich beide nicht ausschließen.
- **AK-08** · Angenommen, eine Vergleichsseite ist geöffnet, wenn man die Merkmalstabelle
  liest, dann enthält sie vier Gruppen mit diesen Überschriften: „Barrierefreiheitsdaten",
  „Herkunft und Prüfung der Daten", „Abdeckung und Aktualität", „Offenheit und
  Geschäftsmodell".
- **AK-09** · Angenommen, eine Zeile der Merkmalstabelle wird gelesen, wenn man in eine
  ihrer beiden Wertspalten schaut, dann steht dort ein Symbol für Ja, Nein oder Teilweise
  **und** ein erklärender Halbsatz — nie ein Symbol allein.
- **AK-10** · Angenommen, eine Vergleichsseite ist geöffnet, wenn man den Abschnitt „Wann
  <Wettbewerber> die bessere Wahl ist" liest, dann nennt er mindestens **drei** konkrete
  Fälle, in denen der Wettbewerber überlegen ist.
- **AK-11** · Angenommen, eine Vergleichsseite ist geöffnet, wenn man ans Seitenende
  scrollt, dann stehen dort mindestens **vier** häufige Fragen mit Antworten und danach
  ein Link zur Restaurantsuche.

### Ehrlichkeit und Nachprüfbarkeit

- **AK-12** · Angenommen, eine Tabellenzeile trifft eine Aussage über den Wettbewerber,
  wenn man ihrer Fußnote folgt, dann findet man dort eine benannte Quelle und ein
  Prüfdatum in der Form „geprüft am 28. August 2026".
- **AK-13** · Angenommen, Endlech.lu hat weniger Lokale erfasst als der Wettbewerber, wenn
  man die Zeile zur Abdeckung liest, dann steht die eigene, kleinere Zahl dort
  ausgeschrieben — sie wird weder weggelassen noch durch einen relativierenden Zusatz
  ersetzt.
- **AK-14** · Angenommen, eine Vergleichsseite ist geöffnet, wenn man ans Seitenende
  scrollt, dann steht dort der Hinweis, dass die genannten Marken ihren jeweiligen
  Inhabern gehören und Endlech.lu in keiner Verbindung zu ihnen steht.
- **AK-32** · Angenommen, ein Unternehmen bestreitet eine Aussage über sich, wenn es die
  Vergleichsseite bis zum Ende liest, dann findet es dort einen Weg, das zu melden.
  *(Am 2026-08-28 aus OF-03 ergänzt — dort wurde entschieden, dass der Kontaktweg im
  Impressum genügt; ohne Kriterium hätte niemand geprüft, ob er auch verlinkt ist.)*
- **AK-15** · Angenommen, eine Vergleichsseite ist geöffnet, wenn man ihren Quelltext nach
  Bildern durchsucht, dann findet sich **kein** Logo und **kein** Screenshot eines
  Wettbewerbers — weder aus dem eigenen Verzeichnis noch von einem fremden Server geladen.

### Eigene Zahlen

- **AK-16** · Angenommen, `/open` weist 47 erfasste Lokale aus, wenn man die entsprechende
  Zeile auf einer Vergleichsseite liest, dann steht dort ebenfalls 47.
- **AK-17** · Angenommen, ein Restaurant wird neu angelegt, wenn eine Vergleichsseite nach
  Ablauf des Kennzahlen-Zwischenspeichers erneut geladen wird, dann zeigt sie die erhöhte
  Zahl, ohne dass jemand eine Datei geändert hat.
- **AK-18** · Angenommen, eine Vergleichsseite wird zehnmal hintereinander aufgerufen, wenn
  man die Datenbankabfragen mitzählt, dann werden die Kennzahlen **nicht** bei jedem
  Aufruf über den gesamten Bestand neu berechnet.

### Suchmaschinen

- **AK-19** · Angenommen, alle fünf neuen Adressen sind erreichbar, wenn man ihre
  Fenstertitel nebeneinanderlegt, dann trägt jede einen eigenen — keine zwei sind gleich.
  *(Verschärft AK-30 aus Feature 02 für diesen Fall.)*
- **AK-20** · Angenommen, eine Vergleichsseite ist geöffnet, wenn man ihren Kopfbereich
  liest, dann steht dort eine geschriebene Kurzbeschreibung in der Sprache der Seite.
- **AK-21** · Angenommen, `/fr/vergleich/jaccede` ist geöffnet, wenn man den Kopfbereich
  liest, dann verweist die kanonische Adresse auf genau diese Seite und die vier
  Sprachverweise auf dieselbe Vergleichsseite in den vier Sprachen.

### Barrierefreiheit

Feature `02` gilt unverändert weiter. Die folgenden Kriterien sind die Stellen, an denen
eine Vergleichsseite typischerweise dagegen verstößt.

- **AK-22** · Angenommen, die fünf neuen Adressen sind gebaut, wenn der
  Barrierefreiheits-Prüflauf des Projekts läuft, dann prüft er sie mit — sie stehen in
  seiner Routenliste.
- **AK-23** · Angenommen, ein Screenreader liest eine Zelle der Merkmalstabelle, wenn er
  sie erreicht, dann hört der Nutzer „Ja", „Nein" oder „Teilweise" als Text — das Symbol
  allein trägt die Aussage nie.
- **AK-24** · Angenommen, jemand bedient die Seite ausschließlich mit der Tastatur, wenn er
  zur Merkmalstabelle gelangt, dann kann er ihren Scrollbereich fokussieren, der Fokus ist
  sichtbar, und er kann die Tabelle waagerecht bewegen.
- **AK-25** · Angenommen, das Fenster ist 320 px breit, wenn eine Vergleichsseite geladen
  wird, dann entsteht **keine** waagerechte Scrollleiste für die Seite — nur die Tabelle
  scrollt in ihrem eigenen Bereich.
- **AK-26** · Angenommen, im Betriebssystem ist „Bewegung reduzieren" aktiv, wenn der
  Zeiger über eine Schaltfläche oder einen Link der Seite fährt, dann läuft kein Übergang.
- **AK-27** · Angenommen, eine Vergleichsseite ist geöffnet, wenn man ihre Überschriften
  von oben nach unten liest, dann gibt es genau eine erste Ebene und keine übersprungene
  Ebene.

### Mehrsprachigkeit

- **AK-28** · Angenommen, ein Übersetzungsschlüssel der Vergleichsseiten fehlt in einer der
  vier Sprachen, wenn der Prüflauf des Projekts läuft, dann schlägt er fehl.
- **AK-29** · Angenommen, eine Vergleichsseite wird auf Luxemburgisch geöffnet, wenn man
  sie von oben bis unten liest, dann steht darin kein Text, der aus einer anderen Sprache
  eingesprungen ist.

### Datenschutz und Missbrauchsschutz

Der Katalog aus `~/.claude/sdd/sicherheit.md` ist vollständig durchgegangen. Was nicht
zutrifft, steht hier mit Begründung — nicht weggelassen.

**1 · Personenbezogene Daten** — *trifft nicht zu.* Die Seiten nehmen keine Eingabe
entgegen, tragen kein Formular, setzen kein Konto voraus und speichern nichts. Es
entsteht keine Datenart, für die eine Löschfrist zu bestimmen wäre. In Protokollen landet
nichts, was nicht schon jeder andere Seitenaufruf hinterlässt.

**2 · Weitergabe an externe Dienste** — die Entscheidung gegen fremde Logos (Decision Log
#14) ist auch eine Datenschutzentscheidung:

- **AK-30** · Angenommen, ein Besucher öffnet eine Vergleichsseite, wenn man die
  ausgehenden Verbindungen des Browsers beobachtet, dann wird **kein** fremder Server
  kontaktiert — die IP-Adresse des Besuchers erreicht keinen Dritten.

**3 · Zugriff** — die Seiten sind öffentlich, ohne Anmeldung, ohne Rollen. Es gibt keinen
Datensatz, dessen Eigentümer zu prüfen wäre. Der Fall „fremde Kennung im Aufruf" ist über
AK-04 abgedeckt: Der Slug ist eine feste Aufzählung, alles andere ist 404.

**4 · Missbrauch und Kosten** — kein Rate Limit im Sinne eines Zählers: Die Seiten lösen
keine E-Mail aus, prüfen kein Geheimnis und kosten pro Aufruf nichts. Der dritte Fall der
Projektkonvention greift dagegen sehr wohl — ein Weg, der bei jedem Aufruf den gesamten
Bestand lädt, braucht einen Deckel. Er ist hier über den vorhandenen
Kennzahlen-Zwischenspeicher gelöst und wird über AK-18 nachgewiesen. Es gibt keinen
Upload.

**5 · Löschen und Auskunft** — *trifft nicht zu.* Es entstehen keine Daten, die zu einer
Person gehören; beim Löschen eines Kontos ist an diesem Feature nichts zu tun.

**6 · Geheimnisse** — *trifft nicht zu.* Das Feature braucht keinen Schlüssel und keine
Zugangsdaten.

## Edge Cases

- **EC-01** · Slug mit abweichender Schreibweise (`/vergleich/Google-Maps`,
  `/vergleich/google_maps`) → 404, nicht stillschweigend korrigiert
- **EC-02** · Kein einziges Restaurant in der Datenbank → die Abdeckungszeile zeigt „0",
  keine Division durch null, kein leeres Feld
- **EC-03** · Seite ohne JavaScript → vollständig lesbar, Tabelle und häufige Fragen
  eingeschlossen
- **EC-04** · Druck oder PDF-Export → das Farbband des Kopfbereichs bleibt lesbar, kein
  weißer Text auf weißem Grund
- **EC-05** · Sehr langer Wettbewerbername in der Spaltenüberschrift → bricht um, statt die
  Spaltenbreite zu sprengen
- **EC-06** · Der Kennzahlen-Zwischenspeicher ist leer (erster Aufruf nach dem Ausliefern)
  → die Seite lädt, die Zahlen werden einmal berechnet, kein Fehler
- **EC-07** · Ein Wettbewerber ändert seinen Namen oder stellt den Dienst ein → die Seite
  bleibt erreichbar; die Korrektur ist ein Commit, kein Notfall (siehe OF-02)

## Offene Fragen

- **OF-01** · ~~Wird `/vergleich` zusätzlich sprachfrei erreichbar, wie `/open` es über
  `app_open_redirect` ist?~~ — **entschieden am 2026-08-28 in `design.md`:** ja, als Route
  `app_comparison_redirect`
- **OF-02** · In welchem Rhythmus werden die Angaben über die Wettbewerber nachgeprüft?
  Ohne Warnkasten (Decision Log #7) trägt allein der Prozess die Aktualität. Vorschlag:
  halbjährlich, mit Termin im Kalender — entscheidet Michael. **Bleibt offen**; der
  Entwurf macht den Rhythmus nur nachprüfbar, indem jede Quelle ihr eigenes Datum trägt
- **OF-03** · ~~Reicht der bestehende Kontaktweg (Impressum, `info@endlech.lu`) als
  Korrekturweg?~~ — **entschieden am 2026-08-28 in `design.md`:** kein eigener Meldeweg;
  der Baustein *Rechtlicher Fuß* verweist auf den Kontaktweg im Impressum
- **OF-04** · ~~Welche Bewertungsportale fasst der Vergleich zusammen, und wie lautet der
  Slug?~~ — **entschieden am 2026-08-28 in `design.md`:** Slug `tripadvisor`, auf
  TripAdvisor zugeschnitten; andere Portale werden im Text erwähnt, ohne belegpflichtige
  Aussage über sie

- **OF-05** · ~~Jaccede als vierter Vergleich~~ — **entschieden am 2026-08-28 während des Baus:
  gestrichen.** Die Recherche ergab, dass die Plattform seit dem 2. Juli 2026 nur noch als
  statischer Abzug erreichbar ist: Suche, Anmeldung und das Anlegen von Orten antworten mit
  404, beide Apps sind aus den Stores verschwunden, der `last-modified`-Kopf steht auf diesem
  Datum. Ein Vergleich mit einem eingestellten Dienst wäre eine Falschaussage über einen
  fremden Verein. **Damit nennt AK-03 einen Slug zu viel** — `jaccede` entfällt, die übrigen
  drei bleiben unverändert gültig.

  ⚠ **Nachtrag 2026-08-28 (Primärquellen-Recherche):** Zwei Belege härten die Entscheidung
  über den Abzug hinaus. **(1)** Die Domain `jaccede.com` wurde am **1. Juni 2026 neu
  registriert** und steht seither anonym auf „Domains By Proxy, LLC" (US) — Beleg
  `https://rdap.verisign.com/com/v1/domain/jaccede.com`, Ereignis `registration`
  `2026-06-01T18:37:29Z`. Der Bestand gehört damit **nicht mehr dem französischen Verein**;
  eine Vergleichsseite beschriebe einen Fremdbestand, als wäre er dessen Angebot.
  **(2)** `api.jaccede.com` löst nicht mehr auf (**NXDOMAIN**, geprüft gegen `8.8.8.8` und
  `1.1.1.1`), während das ausgelieferte JS-Bundle fest auf `https://api.jaccede.com/v4`
  zeigt. Das ist der strukturelle Beweis: Die Oberfläche kann gar keine Daten laden,
  unabhängig davon, welche Seite zufällig noch HTTP 200 liefert.

## Decision Log

| # | Frage | Entscheidung | Begründung |
|---|---|---|---|
| 1 | Welche Vergleiche entstehen | Google Maps, Wheelmap, TripAdvisor/Bewertungsportale, Jaccede | die vier Wege, auf denen jemand heute nach barrierefreier Gastronomie sucht |
| 2 | Umfang | Fußzeilenbereich, Übersichtsseite, vier Einzelseiten, Querverweise | eine Seite je Suchanfrage; ein Sammelvergleich beantwortet keine davon gezielt |
| 3 | Haltung | ehrliche Einordnung mit Belegen, Stärken der anderen ausdrücklich benannt | Produktprinzip 2 und 4; außerdem ist vergleichende Werbung nur objektiv und nachprüfbar zulässig |
| 4 | Sprachen | alle vier | alles andere bräche mit B24 und dem Sprachverweis-Konzept |
| 5 | Pflege | redaktionell im Quelltext | eine Entität samt Formular für vier Seiten, die sich zweimal im Jahr ändern, wäre Aufwand ohne Ertrag |
| 6 | Abschnitte | Kurzfazit, Merkmalstabelle, „Wann die andere Seite besser ist", häufige Fragen | Aufbau der Referenzseite; der dritte Abschnitt ist der, der die Seite glaubwürdig macht |
| 7 | Aktualität | Prüfdatum sichtbar, kein automatischer Warnkasten | bewusst gegen den Vorschlag entschieden — die Folge ist OF-02 |
| 8 | Abschluss der Seite | Restaurantsuche und Querverweise, kein Vorschlags-Aufruf | ein Aufruf pro Seite; der Vorschlags-Weg steht ohnehin in der Fußzeile |
| 9 | Pfad | `/vergleich/{slug}`, deutsch | folgt `/partner` und `/organisationen`, den beiden jüngeren Außenseiten; B13 nutzt zwar englische Pfade, ist aber der ältere Stand |
| 10 | SEO-Grundlage | Kurzbeschreibung und kanonische Adresse nur für diese Seiten | die volle Grundlage für alle Seiten wäre ein zweites Feature im Bauch des ersten |
| 11 | Ort in der Fußzeile | eigene vierte Spalte | die Linkspalte trägt bereits zehn Einträge; eine zweite Überschrift darin macht die Fußzeile schief |
| 12 | Merkmalsgruppen | Barrierefreiheitsdaten, Herkunft und Prüfung, Abdeckung und Aktualität, Offenheit und Geschäftsmodell | die dritte Gruppe ist die, in der Endlech.lu verliert — sie steht bewusst drin |
| 13 | Eigene Zahlen | live aus den Kennzahlen von `/open` | eine feste Zahl im Text veraltet still; „Derselbe Maßstab gilt für uns" verlangt dieselbe Quelle wie die Transparenzseite |
| 14 | Darstellung der Wettbewerber | nur die Wortmarke als Text | die Nennung fremder Marken ist im zulässigen Vergleich erlaubt, das Übernehmen ihrer Logos ist eine eigene Frage — und ein fremd geladenes Logo gäbe die IP des Besuchers weiter |
| 15 | Absicherung | Quelle mit Prüfdatum je Aussage, Markenhinweis am Seitenende | ohne Nachprüfbarkeit ist der Vergleich rechtlich angreifbar und inhaltlich wertlos |
| 16 | Ein eigener Korrekturweg auf der Seite | nein, nicht aufgenommen | der Kontaktweg steht im Impressum und in der Fußzeile; ob das reicht, klärt OF-03 |
| 17 | Fehlende Übersetzung | kann nicht ausgeliefert werden | der vorhandene Katalogtest blockiert; damit ist die Vollständigkeit erzwungen statt erhofft |

---

## Hinweise für `/sdd-architektur 03`

Kein Teil der Kriterien — Fundstellen aus dem Bestand, damit sie beim Entwurf nicht
neu gesucht werden müssen.

| Stelle | Was daran zu beachten ist |
|---|---|
| `templates/base.html.twig:168` | die Fußzeile ist `md:grid-cols-3`; eine vierte Spalte ändert das in `docs/app-shell.md` beschriebene Layout — die Datei mitziehen |
| `templates/base.html.twig:20–28` | die Sprachverweis-Schleife ruft `url(_current_route, _current_params …)`; eine Route mit Slug muss sich in allen vier Sprachen auflösen, sonst wirft jede Seite |
| `templates/partner/index.html.twig:86–135` | die dortige Leistungstabelle ist die fertige Vorlage: Scrollbereich mit `tabindex="0" role="region"`, `<caption class="sr-only">`, `th scope`, Symbol `aria-hidden` plus `sr-only`-Text |
| `src/Controller/OrganisationController.php:58` | Muster für einen Slug als Aufzählung im Requirement, unbekannter Wert → `createNotFoundException()` (deckt AK-04 und EC-01) |
| `tests/Functional/AccessibilityStructureTest.php:21–33` | Routenliste — die fünf neuen Adressen eintragen, sonst greift AK-22 nicht |
| `tests/Unit/Translation/CatalogueCompletenessTest.php:119` | der Scanner erfasst nur *literale* Schlüssel mit Punkt; dynamisch zusammengesetzte (`('vergleich.' ~ slug)|trans`) fallen durchs Netz und machten AK-28 wirkungslos |
| `src/Open/OpenStatsService.php:179–195` | liefert `restaurants`, `verified`, `verifiedShare`, `communesCovered`, `communeCoverage`, `averageScore` — Quelle für AK-16 bis AK-18 |
| `docs/design-system.md` | dunkles Kopfband `from-cyan-700 to-purple-800` für Geschäfts- und Datenseiten, als `<section>` wegen EC-04; jede Aktion `min-h-[48px]`, `motion-safe:transition`, `focus:outline-2` |

**Die Ja/Nein-Werte je Wettbewerber sind nicht Teil dieser Spec.** Sie sind das Ergebnis
einer Recherche, die zum Bauen gehört, und jede einzelne braucht nach AK-12 eine Quelle
mit Datum. Was hier festgelegt ist, sind die Merkmalsgruppen (AK-08) und die Pflicht zum
Beleg — nicht die Antworten.
