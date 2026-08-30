# Product Requirements Document

Artefaktpfad: `docs/`

**Endlech.lu** — die offene Plattform für barrierefreie Gastronomie in Luxemburg.

| | |
|---|---|
| **Stand** | 21. August 2026 |
| **Version** | v2026.08.09 (Beta) |
| **Lizenz** | MIT · offener Datensatz unter CC BY 4.0 |
| **Sprachen** | Luxemburgisch (Vorgabe), Deutsch, Französisch, Englisch |

Verwandte Dokumente: [Datenmodell](data-model.md) · [Design-System](design-system.md)
 · [App-Shell](app-shell.md) · [Feature-Inventar](../features/index.md)

Für die SDD-Kette: Der Artefaktpfad ist `docs/`. Das Datenmodell heißt hier aus
gewachsenen Gründen `data-model.md`, nicht `datenmodell.md`.

---

## Zur Lesart

Dieses Dokument trennt zwei Arten von Aussagen:

- **Ohne Kennzeichnung:** belegt aus Code, Templates, Übersetzungen, README oder
  CHANGELOG. Zitate sind wörtlich und stammen aus dem, was heute öffentlich auf
  der Seite steht.
- **▸ Vorschlag** / **▸ Hypothese:** meine Ableitung. Nicht beschlossen, nicht
  veröffentlicht, als Diskussionsgrundlage gedacht.

Diese Trennung ist nicht dekorativ. Auf `/partner` und `/organisationen` laufen
Wartelisten für Angebote, deren Preise ausdrücklich noch nicht feststehen — ein
PRD, das erfundene Zahlen wie beschlossene aussehen ließe, würde genau die
Glaubwürdigkeit beschädigen, die das Produkt zu seinem Kernversprechen gemacht
hat.

---

## Inhalt

1. [Vision und Mission](#vision-und-mission)
2. [Das Problem](#das-problem)
3. [Zielgruppen](#zielgruppen)
4. [Produktprinzipien](#produktprinzipien)
5. [Funktionsumfang heute](#funktionsumfang-heute)
6. [Erfolgskennzahlen](#erfolgskennzahlen)
7. [Geschäftsmodell](#geschäftsmodell)
8. [Roadmap](#roadmap)
9. [Risiken und offene Fragen](#risiken-und-offene-fragen)
10. [Nicht im Umfang](#nicht-im-umfang)

---

## Vision und Mission

> **„Barrierefreiheit soll kein Zufall sein.** Mit Endlech.lu wollen wir Menschen
> mit eingeschränkter Mobilität helfen, Restaurants in Luxemburg zu finden, die
> wirklich zugänglich sind — geprüft von der Community, für die Community."
>
> — `about.mission_text`

Der Name trägt das Versprechen: *endlech* ist Luxemburgisch für „endlich".

**Das Produkt kommt nicht aus einer Marktlücke, sondern aus einem Alltag.**

> „Ich bin Michael, 30 Jahre alt und seit Geburt mit SMA2 (Spinale
> Muskelatrophie Typ 2) beeinträchtigt. Als leidenschaftlicher Programmierer und
> Fotograf weiß ich aus eigener Erfahrung, wie wichtig echte Barrierefreiheit im
> Alltag ist — und genau das treibt mich bei Endlech.lu an."
>
> — `about.founder_bio`

**Entstehung**

| Zeitraum | Schritt |
|---|---|
| 2018 | erste Planung |
| 2020 | aktive Entwicklung |
| 2022 | unfreiwillige Pause |
| Ende 2025 | Neustart |
| März 2026 | erste Live-Version mit echten Daten |
| August 2026 | Transparenzseite, Wartelisten, Passkey-Anmeldung |

**Fernziel.** Aus der Fußzeile: *„Die Plattform für barrierefreie Gastronomie in
Luxemburg. Gemeinsam für mehr Inklusion."* Der Anspruch ist Vollständigkeit für
ein kleines Land — nicht Wachstum über dessen Grenzen hinaus.

---

## Das Problem

Die schärfste Formulierung steht auf der Partnerseite und richtet sich an
Restaurantbetreiber:

> **„Ihr Gast steht vor der Tür — und dreht wieder um."**
>
> „Eine Stufe am Eingang, eine Tür, die zu schmal ist, ein WC im Untergeschoss:
> Für viele Menschen in Luxemburg endet der Restaurantbesuch, bevor er beginnt."

Drei Ebenen:

**Für Gäste** ist Barrierefreiheit heute nicht planbar. Weder Google Maps noch
die Websites der Häuser beantworten die Fragen, auf die es ankommt: Wie breit ist
die Tür? Ist das WC im Erdgeschoss? Passt ein Rollstuhl zwischen die Tische? Wer
darauf angewiesen ist, ruft vorher an — bei jedem einzelnen Lokal.

**Für Betriebe** ist der Aufwand unklar und der Nutzen unsichtbar. Wer umbaut,
kann es niemandem zeigen; wer nicht umbaut, merkt nicht, wen er verliert.

**Für Gemeinden** fehlt die Datengrundlage. Es gibt keine Erhebung, wie
barrierefrei die Gastronomie eines Ortes ist — und damit keinen Ausgangswert, an
dem sich Fortschritt messen ließe.

### Der zeitliche Hebel

Die Partnerseite nennt den rechtlichen Rahmen:

> „Das luxemburgische Gesetz vom 7. Januar 2022 über die Zugänglichkeit von
> Orten, die dem Publikum offenstehen, gilt auch für bestehende Restaurants. Für
> sie läuft die Frist zur Anpassung bis zum **1. Januar 2032**."

Dazu die Förderung: **50 % der Kosten, gedeckelt auf 24 000 € HTVA.**

Damit hat jedes Restaurant in Luxemburg in den kommenden Jahren einen konkreten
Anlass, sich mit dem Thema zu befassen — und einen Grund, es öffentlich zu
zeigen.

---

## Zielgruppen

### 1. Gäste mit eingeschränkter Mobilität

**Bedarf:** vor dem Losfahren wissen, ob es geht.

**Heutiger Weg:** `/restaurants` mit vierzehn kombinierbaren Filtern (Rollstuhl,
WC, Assistenzhund, Beleuchtung, Wickeltisch, Behindertenparkplatz, aktuell
geöffnet, Stadt, Küche, Sprachen, vegan/vegetarisch/halal, nur verifizierte) →
Detailseite mit Öffnungszeiten, Kontakt, Fotos, Türbreite, Tischabstand und
Haltestellen in der Nähe.

**Offener Punkt:** Kein Konto nötig, aber auch keine Möglichkeit, Favoriten zu
speichern oder eine eigene Erfahrung zu hinterlassen.

### 2. Restaurantbetreiber

**Bedarf:** wissen, was fehlt; zeigen können, was da ist.

**Heutiger Weg:** Der Eintrag entsteht ohne Zutun des Hauses — durch das Team
oder über einen Community-Vorschlag. Wer mehr will, trägt sich auf `/partner` in
die Warteliste ein (Double-Opt-In, kein Konto, keine Zahlung).

**Angekündigte Leistungen:** Beratungsbericht, Inclusion-Box, Sichtbarkeit.

### 3. Gemeinden

**Bedarf:** eine Bestandsaufnahme der eigenen Gastronomie.

> „Wir erfassen die Gastronomie in Ihrer Gemeinde vor Ort – Lokal für Lokal, nach
> denselben Kriterien wie überall auf Endlech.lu. Am Ende wissen Sie, wo Ihre
> Gemeinde steht, und Ihre Einwohner sehen es auch."

**Ergebnisse:** öffentliche Karte, schriftlicher Bericht, Präsentation im
Gemeinderat. **Dauer:** 8–16 Wochen. **Versprechen:** *„Sie bezahlen die
Erhebung, nicht das Ergebnis."*

**Heutiger Weg:** `/organisationen/gemeinden` mit eigener Landing-Page und
Warteliste.

### 4. Unternehmen

**Bedarf:** gesellschaftliches Engagement mit sichtbarem Bezug.

> „Sie können Barrierefreiheit in Luxemburg mitfinanzieren, ohne dafür etwas zu
> bekommen, das andere nicht bekommen. Was Sie erhalten, ist die Nennung als
> Unterstützer – keine bessere Bewertung, keine bevorzugte Platzierung, keinen
> Einfluss auf Inhalte."

**Formate:** Inclusion-Boxen finanzieren, Mitarbeitende als Testpersonen, eine
Gemeinde-Erhebung mitfinanzieren, Übersetzungen und Workshops.

**Ausschlüsse, öffentlich benannt:** Gastronomieketten und Betriebe, die selbst
auf Endlech.lu bewertet werden; Umbaufirmen; Lieferanten.

### 5. Vereine und Organisationen

**Bedarf:** mitentscheiden, was überhaupt gemessen wird.

> „Wir messen Barrierefreiheit – aber wer legt fest, was gemessen wird und wie
> schwer es wiegt? Diese Entscheidung wollen wir nicht allein treffen. Dafür gibt
> es den Beirat."

**Hier fließt in keine Richtung Geld.** Der Beirat entscheidet über Kriterien,
Gewichtung im Score und Methodenänderungen; zwei Sitzungen im Jahr; die
Mitglieder werden öffentlich benannt.

---

## Produktprinzipien

Diese fünf Sätze stehen so oder sinngemäß öffentlich auf der Seite. Sie sind
damit keine internen Leitlinien, sondern eingegangene Verpflichtungen.

### 1. Bewertungen sind nicht käuflich

> „Wir sind eine Plattform für Barrierefreiheit. Wären unsere Bewertungen
> käuflich, wären sie wertlos — für Ihre Gäste und für Sie."

Konkret: *„Eintrag, Barrierefreiheits-Daten und Score sind und bleiben kostenlos
und öffentlich — für jedes Restaurant in Luxemburg, ob Partner oder nicht."* Und:
*„Die Mitgliedschaft hat keinerlei Einfluss auf Ihre Bewertung, Ihre Platzierung
in Suchergebnissen oder Ihre Sichtbarkeit in Filtern."*

**Technische Entsprechung:** Es gibt kein Feld, über das sich Sichtbarkeit
erkaufen ließe. `PartnerWaitlistEntry` hat keinerlei Wirkung auf
`RestaurantRepository::findPaginated()`.

### 2. Lücken werden gezeigt, nicht versteckt

> „Alle zwölf Kantone stehen in der Liste, auch die ohne einen einzigen Eintrag.
> Die weißen Flecken sind die ehrlichere Hälfte der Aussage."

**Technische Entsprechung:** `CantonResolver` **rät nicht**. Ein Ort, der nicht
sicher zugeordnet werden kann, erscheint als „unzugeordnet", nicht als
Vermutung.

### 3. Nicht gemessen heißt nicht erfüllt

`App\Open\AccessibilityScore` vergibt 0–10 Punkte aus acht gleichgewichteten
Merkmalen. Was nicht erfasst ist, zählt als nicht erfüllt. Der Wert misst also
**dokumentierte** Barrierefreiheit — nicht die tatsächliche.

### 4. Derselbe Maßstab gilt für uns

> „Wir verlangen von Restaurants, offenzulegen, wie barrierefrei sie sind.
> Denselben Maßstab legen wir an uns selbst an: Wie viele Lokale sind erfasst,
> wie viele haben wir persönlich geprüft, was kostet der Betrieb und woher kommt
> das Geld."

### 5. Zahlen erst, wenn sie belastbar sind

> „Wir zeigen Einnahmen erst, wenn ein vollständiges Quartal vorliegt."

**Technische Entsprechung:** Die Sperre ist strukturell. Die Beträge stehen
während der Sperrfrist gar nicht erst im Ergebnis-Array — lägen sie darin und
wären nur im Template verborgen, wären sie über `/open.json` abrufbar.

---

## Funktionsumfang heute

Alle Web-Routen liegen unter `/{_locale}` (`lb`|`de`|`fr`|`en`). Die REST-API und
die Daten-Endpunkte sind bewusst sprachfrei.

### Restaurantsuche — öffentlich, kein Konto nötig

| Route | Funktion |
|---|---|
| `/` | Startseite: Hero, „So funktioniert's", die sechs bestbewerteten Häuser |
| `/restaurants` | Liste mit vierzehn Filtern, drei Sortierungen, Seitenblättern (6 pro Seite) |
| `/restaurants/{id}` | Detailseite: Barrierefreiheit, Maße, Öffnungszeiten, Kontakt, Fotogalerie, Bestellwege, Haltestellen in der Nähe |
| `/criteria` | Kriterienkatalog — wonach bewertet wird |
| `/about` | Über Endlech: Mission, Person, Zeitleiste |
| `/legal` | Impressum und Datenschutz |

Die Haltestellen kommen über `PublicTransportService` aus der HAFAS-Schnittstelle
(24 Stunden gecacht); ohne API-Schlüssel bleibt der Block still leer.

### Community

| Route | Zugriff | Funktion |
|---|---|---|
| `/community/suggest` | `ROLE_USER` | fünfstufiger Wizard, zwölf Pflichtfragen mit „Ja / Nein / Weiß nicht" |
| `/community/thanks` | öffentlich | Bestätigung |

### Konto und Anmeldung

| Route | Funktion |
|---|---|
| `/register`, `/verify/{token}` | Registrierung mit E-Mail-Bestätigung (Token 24 Stunden gültig) |
| `/login` | Passwort **oder Passkey** — der Passkey-Knopf verlangt keine E-Mail-Eingabe |
| `/profile` | Name, E-Mail, Avatar, Passwort, Passkeys, eigene Einreichungen |

**Passkeys** (WebAuthn) stehen gleichrangig neben dem Passwort. Die Begründung
aus dem CHANGELOG: *„Endlech.lu richtet sich an Menschen mit Behinderungen – und
verlangte bislang genau eine Sache, die für viele davon die größte Hürde ist: ein
Passwort abzutippen."* Ein Konto lässt sich per Passkey **nicht** anlegen, nur
absichern.

### Vertrieb

| Route | Funktion |
|---|---|
| `/partner` | Landing-Page + Warteliste für Restaurants |
| `/organisationen` | Übersicht der drei Organisationstypen + Warteliste |
| `/organisationen/gemeinden`, `…/unternehmen`, `…/vereine` | eigene Seite je Zielgruppe, Formulartyp vorgewählt |

Beide Wartelisten teilen sich `WaitlistConfirmationService`: Token → Speichern →
Bestätigungsmail. Scheitert der Versand, ist die Anmeldung trotzdem gesichert.
Schutz gegen Missbrauch: Honeypot-Feld ohne Validierungsfehler (ein Fehler
verriete dem Bot die Falle) und ein Rate-Limit von 5 Versuchen je IP und Stunde.

Die Organisationsformulare funktionieren **ohne JavaScript**: Ohne Skript sind
alle drei Feldgruppen sichtbar und beschriftet.

### Transparenz

| Route | Funktion |
|---|---|
| `/open` | Kennzahlen zu Plattform, Wirkung und Finanzen, mit Verlauf und Druckansicht |
| `/open.json` | dieselben Kennzahlen maschinenlesbar |
| `/open/dataset.csv`, `/open/dataset.json` | vollständiger Datensatz unter CC BY 4.0 |

Der Datensatz enthält **keine** E-Mail-Adressen und Telefonnummern.

### Verwaltung — `ROLE_ADMIN`

| Bereich | Umfang |
|---|---|
| `/admin` | Kennzahlen-Übersicht |
| `/admin/restaurants` | vollständiges CRUD, Verifizieren, Fotos hochladen und sortieren |
| `/admin/vorschlaege` | Vorschläge prüfen, genehmigen (erzeugt das Restaurant) oder ablehnen |
| `/admin/warteliste` | beide Wartelisten kombiniert, Status pflegen, Restaurant zuordnen |
| `/admin/finanzen` | Finanzposten pflegen, Snapshot von Hand auslösen |

### Schnittstellen und App

**REST-API `/api/v1`** — sprachfrei, JWT, als Backend einer nativen iOS-App
gebaut: Anmeldung und Registrierung, Restaurantliste mit denselben Filtern,
Detail, Bilder, eigenes Profil, eigene Einreichungen. Dokumentiert unter
`/api/docs` (Swagger UI).

**PWA** — über Safaris „Zum Home-Bildschirm" installierbar: Vollbild, App-Icon,
Service Worker mit Offline-Fallback, mobile Bottom-Navigation.

---

## Erfolgskennzahlen

### Was heute tatsächlich gemessen wird

Alle Werte stammen aus `App\Open\OpenStatsService` und stehen live auf `/open`
sowie unter `/open.json`. Monatlich friert `app:metrics:snapshot` sie in einem
`MetricSnapshot` ein.

**Plattform** — Abdeckung und Prüftiefe

| Feld | Bedeutung |
|---|---|
| `restaurants` | erfasste Häuser |
| `verified`, `verifiedShare` | davon vom Team vor Ort geprüft, absolut und als Anteil |
| `communesCovered` / `totalCommunes` / `communeCoverage` | Gemeinden mit mindestens einem Eintrag, von 100 |
| `cantonsCovered` / `totalCantons` | Kantone, von 12 |
| `unassigned` | Einträge, deren Ort sich nicht zuordnen ließ |
| `averageScore`, `maxScore`, `scoreDistribution` | Punktzahl 0–10 und ihre Verteilung |
| `byCanton`, `byCommune` | dieselben Werte je Gebietseinheit |

**Wirkung** — was in den erfassten Häusern tatsächlich vorhanden ist

`stepFreeEntrances`, `accessibleRestrooms`, `assistanceDogsWelcome`,
`brightLighting`, `changingTables`, `disabledParking`, `wideDoors`,
`wheelchairTableSpacing`, dazu `documentedDoorWidths` und
`documentedTableSpacing` (wie viele überhaupt ausgemessen wurden) sowie
`inclusionBoxesDelivered`.

> Die Trennung zwischen `wideDoors` und `documentedDoorWidths` ist der
> Kernindikator für die Datenqualität: Sie zeigt, wie viel der Aussage auf
> Messung beruht und wie viel auf Abwesenheit von Messung.

**Finanzen** — Ausgaben je Kategorie mit Anteil, Gesamtausgaben,
Gesamteinnahmen (nach Quartalssperre), Saldo, Stand der letzten Pflege.

**Verlauf** — `MetricSnapshot` hält je Monat 13 Zahlen plus einen vollständigen
JSON-Abzug fest. Ohne Snapshot zeigt `/open` **keine** Veränderungen — eine
Veränderung gegen einen unbekannten Ausgangswert wäre erfunden.

**Fehler** — Sentry, nur in `prod`, EU-Region, `send_default_pii: false`.
**Es gibt kein Web-Analytics** (kein Google Analytics, kein Matomo, kein
Plausible). Reichweite wird derzeit nicht gemessen.

### ▸ Vorschlag: Zielkorridore

Belastbare Ziele fehlen — es gibt Kennzahlen, aber keine Schwellen, an denen
sich Erfolg oder Stillstand ablesen ließe. Vier Vorschläge, jeweils mit
Begründung:

| Kennzahl | Vorschlag | Warum diese Größe |
|---|---|---|
| **Gemeindeabdeckung** | 25 der 100 Gemeinden binnen zwölf Monaten | Ein Viertel des Landes ist die Schwelle, ab der eine Karte nicht mehr nach Einzelfällen aussieht — und das Argument gegenüber der 26. Gemeinde trägt. |
| **Verifikationsquote** | mindestens 30 % vom Team vor Ort geprüft | Darunter wird „community-geprüft" zur Behauptung. Die Quote ist wichtiger als die absolute Zahl der Einträge. |
| **Messquote** | 60 % der Einträge mit dokumentierter Türbreite | `AccessibilityScore` bestraft fehlende Messungen. Ohne steigende Messquote sinkt der Durchschnittswert, je mehr Häuser dazukommen — ein Wachstum, das wie Verschlechterung aussieht. |
| **Vertriebstrichter** | Bestätigungsquote der Wartelisten ≥ 60 % | Wer den Double-Opt-In nicht abschließt, war nie interessiert. Unter 60 % stimmt etwas an Ansprache oder Zustellung nicht. |

Bewusst **nicht** vorgeschlagen: Besucherzahlen und Verweildauer. Für eine
Plattform, deren Nutzen darin besteht, dass jemand *nicht* umsonst hinfährt, wäre
Verweildauer das falsche Signal.

---

## Geschäftsmodell

### Belegt

Drei Erlöspfade, alle noch **ohne Preis und ohne Zahlungsabwicklung im Code**:

| Pfad | Zielgruppe | Art | Stand |
|---|---|---|---|
| **Partnerprogramm** | Restaurants | wiederkehrend | Warteliste; *„Preise und Paketumfang stehen noch nicht fest. Wer auf der Warteliste steht, erfährt sie als Erstes"* |
| **Erhebungen** | Gemeinden | Projektauftrag | Warteliste; Umfang beschrieben (Karte, Bericht, Gemeinderat), Dauer 8–16 Wochen |
| **Sponsoring** | Unternehmen | Zuwendung | Warteliste; Formate benannt, Ausschlüsse öffentlich |

**Vereine sind ausdrücklich kein Vertriebskanal.** *„Hier fließt in keine
Richtung Geld."*

**Kostenseite**, laufend erfasst in `FinanceEntry` und veröffentlicht: Hosting,
E-Mail-Versand, Apple-Developer-Programm, Domain, Material für Inclusion-Boxen.
Die Kategorie `APPLE_DEVELOPER` belegt, dass die iOS-App bereits Geld kostet,
bevor sie existiert.

**Was strukturell ausgeschlossen ist:** bezahlte Platzierung, bezahlte Bewertung,
Werbung. Nicht durch eine Richtlinie, sondern dadurch, dass es kein Feld gibt,
das so etwas ausdrücken könnte.

### ▸ Hypothese: der Trichter hinter `WaitlistStatus`

Die sechs Status lesen sich als Vertriebsstufen. Was je Stufe zu beobachten
wäre:

| Stufe | Frage | Kennzahl |
|---|---|---|
| `pending` → `confirmed` | Kommt die Mail an, ist das Interesse echt? | Bestätigungsquote |
| `confirmed` → `contacted` | Melden wir uns schnell genug? | Tage bis zur ersten Antwort |
| `contacted` → `qualified` | Passt die Anfrage zum Angebot? | Qualifizierungsquote |
| `qualified` → `converted` | Trägt der Preis? | Abschlussquote, Dauer |
| → `declined` | Warum nicht? | Ablehnungsgründe (heute nicht erfasst) |

**Fehlendes Feld:** Es gibt keinen strukturierten Ablehnungsgrund — nur
`message` als Freitext. Wer wissen will, ob am Preis, am Zeitpunkt oder am
Zuschnitt gescheitert wird, müsste das ergänzen.

### ▸ Hypothese: die Reihenfolge der Erlöspfade

Der Gemeindepfad sollte zuerst tragfähig werden, obwohl das Partnerprogramm
zuerst gebaut wurde. Begründung: Eine Gemeinde-Erhebung bringt in einem Auftrag
zwanzig bis vierzig neue Einträge und damit genau das, was der Plattform heute
fehlt — Abdeckung. Das Partnerprogramm skaliert dagegen mit der Zahl der Häuser,
die den Eintrag schon kennen. Der zweite Pfad wird umso leichter, je weiter der
erste getragen hat.

---

## Roadmap

### Belegt offen

| Vorhaben | Beleg |
|---|---|
| **Kartenansicht** | `CHANGELOG.md`: „**Map:** Kartenansicht der Locations. *(geplant)*" — die Koordinaten liegen bereits an jedem Restaurant |
| **Favoriten** | README, unerledigt: „User Profiles: Save favorites" |
| **Bewertungen und Kommentare** | README, unerledigt: „Reviews: Comment system for accessibility feedback" |
| **Native iOS-App** | `/api/v1` ist ausdrücklich als deren Backend gebaut; Kostenposten `APPLE_DEVELOPER` läuft bereits |
| **Chat-Widget** | Stand bis 2026-08-30 nur auf dem externen Board `endlech.userjot.com` (Status „Planned") und in keinem Projektartefakt. Mit Feature `06` hierher überführt; das externe Board wird abgeschaltet |
| **KI-Filter** | Ebenda, Status „Planned". Was genau gefiltert werden soll, ist nicht festgehalten — der Punkt ist ein Merkposten, keine Anforderung |
| **Android-App · Google-Login · Apple-Login** | Ebenda, Status „Pending". Vollständigkeitshalber notiert; keiner der drei ist bisher begründet oder priorisiert |

> **Herkunft der letzten vier Zeilen:** Bis August 2026 lief unter
> `endlech.userjot.com` ein extern gehostetes Ideen-Board, verlinkt aus der
> Fußzeile. Es enthielt sieben Einträge, alle vom Betreiber selbst, alle mit
> null Stimmen — kein Community-Bestand. Beim Bau von Feature `06` wurden die
> dort noch nicht anderweitig erfassten Vorhaben hierher übernommen und das
> externe Board abgeschaltet. „Presskit" und „iOS app" standen bereits.

### Bewusst zurückgestellt

Aus `CLAUDE.md`, jeweils mit Begründung dokumentiert: Conditional UI (Passkey-
Autofill), Passkey-Registrierung neuer Konten, Passkeys in der REST-API,
Attestation-Prüfung, Apple-Splash-Screens, Pull-to-Refresh, Push-Notifications,
vollständiger Mobile-Audit aller Seiten.

### ▸ Vorschlag: Reihenfolge

**Zuerst — Bewertungen.** Nicht wegen des Funktionsumfangs, sondern weil hier
eine Lücke zwischen Versprechen und Produkt klafft (siehe
[Risiken](#risiken-und-offene-fragen)). Die Startseite wirbt mit „Bewerten" und
„echten Bewertungen von echten Besuchern"; beides gibt es nicht. Von allen
offenen Punkten ist das der einzige, der ein bestehendes Versprechen einlöst
statt ein neues zu geben.

**Danach — Karte.** Der sichtbarste Zugewinn für Gäste, technisch vorbereitet
(Koordinaten vorhanden), und zugleich das Ergebnis, das eine Gemeinde-Erhebung
vorzeigbar macht.

**Danach — Favoriten.** Klein, in sich abgeschlossen, gibt dem Konto einen
Nutzen über das Einreichen hinaus.

**Zuletzt — iOS-App.** Die PWA deckt den mobilen Fall bereits ab. Eine native App
kostet laufend Geld und Pflegeaufwand; sie lohnt sich, wenn es genug Inhalt gibt,
den man unterwegs braucht — nicht vorher.

---

## Risiken und offene Fragen

### 1. Ein Kernversprechen ist nicht eingelöst

Die Startseite wirbt mit „Bewerten" und *„Echte Bewertungen von echten Besuchern.
Keine Werbung, keine bezahlten Einträge."* Die Über-Seite spricht von der
Plattform, die *„mit jeder Bewertung"* wächst.

**Tatsächlich gibt es keine Nutzer-Bewertungen.** `Restaurant::$rating` ist ein
einzelnes Zahlenfeld, das im Admin-Formular gepflegt wird; eine Review-Entity
existiert nicht. Auch die Kriterienseite formuliert vorsichtiger: *„Die
Bewertungen auf Endlech.lu basieren auf Erfahrungen unserer Community."*

Zwei Wege: die Funktion bauen, oder die Texte an das anpassen, was das Produkt
tut. Nichts zu tun ist der schlechteste von beiden — gerade bei einer Plattform,
deren wichtigstes Kapital Glaubwürdigkeit ist.

### 2. Der Score bestraft Wachstum

`AccessibilityScore` zählt nicht Gemessenes als nicht erfüllt. Jedes neue,
unvollständig erfasste Haus senkt damit den Durchschnitt — auch dann, wenn es
tatsächlich gut zugänglich ist. Je erfolgreicher die Erfassung, desto
schlechter sieht die Kennzahl aus.

Die Regel selbst ist richtig (siehe
[Produktprinzipien](#produktprinzipien)). Was fehlt, ist die zweite Zahl daneben:
die Messquote. Sie steht bereits im Datensatz (`documentedDoorWidths`,
`documentedTableSpacing`), aber nicht als eigene Kennzahl im Hero.

### 3. Alleinbetrieb

Entwicklung, Redaktion, Vor-Ort-Prüfung, Vertrieb und Buchhaltung liegen bei
einer Person. Der Deploy erfolgt per Merge nach `production`, ohne Testgate.
Fällt diese Person aus, steht alles — und die Wartelisten laufen weiter.

### 4. Die Wartelisten versprechen etwas, das es noch nicht gibt

Beide Seiten sammeln Kontakte für Angebote ohne Preis. Das ist offen kommuniziert
und rechtlich sauber (Double-Opt-In, Einwilligungszeitpunkt gespeichert). Das
Risiko liegt in der Zeit: Wer sich im August einträgt und im Februar noch nichts
gehört hat, ist als Interessent verloren.

**Offene Frage:** Wie lange darf eine Anmeldung unbeantwortet bleiben, bevor eine
Zwischennachricht fällig wird?

### 5. Zwei Datenbanken

Lokal und in der CI läuft MySQL 8.0, auf Production MariaDB 10.5. Jeder Deploy
führt Migrationen aus. MySQL-eigene Syntax fällt erst auf Production auf — und
dort mitten im Deploy.

### 6. Abhängigkeit von fremden Schnittstellen

Die Haltestellen kommen aus der HAFAS-Schnittstelle, der E-Mail-Versand über
Brevo. Beide sind mit sanftem Ausfall abgesichert (leerer Schlüssel = Funktion
still aus), aber beide sind nicht ersetzbar ohne Aufwand.

### 7. Die README ist an mehreren Stellen überholt

Sie führt „Multilingual", „Crowdsourcing" und „Authentication" als offene Punkte,
obwohl alle drei live sind. Wer das Projekt zum ersten Mal ansieht, unterschätzt
es dadurch.

---

## Nicht im Umfang

Bewusste Grenzen — nicht vergessen, sondern entschieden:

| Nicht enthalten | Begründung |
|---|---|
| **Zahlungsabwicklung** | Kein Preis steht fest; ohne Preis kein Bezahlvorgang |
| **Tischreservierung** | Andere machen das besser; es hätte keinen Bezug zur Barrierefreiheit |
| **Lieferung und Bestellung** | Nur Verlinkung über `OrderingOption` — Endlech.lu ist kein Marktplatz |
| **Selbstauskunft der Betriebe** | Betreiber können ihre eigenen Werte nicht setzen. Das wäre der kürzeste Weg, das Prinzip „Bewertungen sind nicht käuflich" praktisch auszuhebeln |
| **Bewertungen außerhalb der Gastronomie** | Geschäfte, Ämter, Arztpraxen wären dieselbe Mechanik — aber eine andere Erhebung, andere Kriterien und ein anderes Versprechen |
| **Ausdehnung über Luxemburg hinaus** | *„100 % Luxemburg. Speziell für die Luxemburger Gastronomie. Lokal, relevant, aktuell."* Die Gemeindeliste, die Kantonszuordnung und der Rechtsrahmen sind auf das Land zugeschnitten |
| **Dark Mode** | Nie begonnen; keine halbfertige Umsetzung im Bestand |
| **Web-Analytics** | Keine Besucherverfolgung. Passt zur Datensparsamkeit, kostet aber jede Aussage über Reichweite |
