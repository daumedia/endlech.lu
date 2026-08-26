# B22 · Wartelisten-Verwaltung — Testbericht

Stand: 2026-08-24 · Vorstufe: `rekonstruiert` · Branch `fix/b04-profil-qa`

## Fazit

**Production-ready: ja** — ein niedriger Befund, sonst nichts.

26 von 26 Kriterien bestanden, 4 von 4 Edge Cases. Das Feature hat die dichteste
Schreibweg-Absicherung im ganzen Projekt: **jeder** der vier schreibenden Endpunkte
trägt ein eigenes CSRF-Token mit der ID des Eintrags im Schlüssel. Nachgemessen: Das
Token von Eintrag 14 wirkt gegen Eintrag 15 **nicht** — dort steht „Ungültiges
CSRF-Token", der Status bleibt. Ein einzelnes abgegriffenes Token nützt damit für genau
eine Zeile.

Die sieben Filterkombinationen habe ich mit vier über vier Tage verteilten Einträgen
geprüft, abwechselnd aus beiden Quellen. Die Durchmischung ist der Beleg, auf den es
ankommt:

```
ungefiltert:   O2 Neuest → P2 Neu → O1 Mitte → P1 Alt
?sort=asc:     P1 Alt → O1 Mitte → P2 Neu → O2 Neuest
```

Die Quellen wechseln sich ab — das Zusammenführen sortiert also erneut, statt zwei
sortierte Hälften aneinanderzuhängen.

Was fehlt, ist ausschließlich Skalierung: keine Seitenaufteilung, keine Obergrenze, und
die Restaurant-Auswahlliste lädt den kompletten Kernbestand. Bei elf Restaurants und
einer Handvoll Anmeldungen folgenlos — es wächst aber mit dem, was das Projekt erreichen
will.

Nächster Aufruf: **`/sdd-erfassen B17`**. Die Erfassung läuft weiter.

## Akzeptanzkriterien im Einzelnen

### Liste und Filter

Testdaten: `P1 Alt` (01.08.), `O1 Mitte` (02.08.), `P2 Neu` (03.08.), `O2 Neuest`
(04.08.) — abwechselnd Partner und Organisation.

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | Eine Tabelle, alle vier Einträge, absteigend |
| **AK-02** | ✅ bestanden | `O2 Neuest → P2 Neu → O1 Mitte → P1 Alt` — die Quellen wechseln sich ab. Test `testAk02BeideQuellenSindNachDatumDurchmischt` |
| AK-03 | ✅ bestanden | `?source=partner` → `P2 Neu → P1 Alt` · `?source=organisation` → `O2 Neuest → O1 Mitte` |
| AK-04 | ✅ bestanden | `?type=commune` → nur `O1 Mitte` (die einzige Gemeinde) |
| AK-05 | ✅ bestanden | `?status=confirmed` → `O2 Neuest → P2 Neu` — beide Quellen, nur bestätigte |
| AK-06 | ✅ bestanden | `?sort=asc` → `P1 Alt → O1 Mitte → P2 Neu → O2 Neuest`; `?sort=unsinn` → wieder absteigend |
| AK-07 | ✅ bestanden | `?status=erfunden&type=erfunden` → volle Liste, keine Exception. Test `testAk07UnbekannteFilterwerteZeigenDieVolleListe` |
| **EC-01** | ✅ bestanden | `?source=partner&type=commune` → nur `O1 Mitte` — der Typ überschreibt die widersprüchliche Quellenangabe |

### Detailansicht und Schreibwege

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-08 / AK-20 | ✅ bestanden | Detailseite zeigt E-Mail, Restaurantname, Ort, Einwilligung, Sprache und Quelle |
| **AK-09 / AK-10** | ✅ bestanden | Statuswechsel `pending → contacted`: Meldung *„Status auf »Kontaktiert« geändert."*; DB vorher `confirmedAt=NULL`, nachher `2026-08-24 16:30:17` — der Zeitstempel wird automatisch gesetzt |
| AK-11 | ✅ bestanden | `_token=falsch` → *„Ungültiges CSRF-Token."*, Status bleibt `contacted` |
| AK-12 | ✅ bestanden | `status=erfunden` → *„Unbekannter Status."*, Status bleibt `contacted` |
| AK-13 | ✅ bestanden | Zuordnung → *„Mit »Pizzeria Bella Vista« verknüpft."*, `restaurant_id=237` |
| AK-14 | ✅ bestanden | `restaurant=0` → *„Verknüpfung entfernt."*, `restaurant_id=NULL` |
| AK-15 | ✅ bestanden | `restaurant=999999` → *„Restaurant nicht gefunden."*, `restaurant_id` bleibt `NULL` |
| AK-22 | ✅ bestanden | `/partner/999999` → **404** · `/partner/abc` → **404** (Requirement `\d+`) |

### Zugriff

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-16** | ✅ bestanden | Nicht-Admin: `/warteliste` **403**, `/warteliste/partner/14` **403**, `/warteliste/organisation/2` **403**. Schreibweg mit **gültigem Admin-Token** als Nicht-Admin → **403**. Gast → 302 nach `/de/login` |
| **AK-21** | ✅ bestanden | Zwei Formulare je Detailseite mit unterschiedlichen Token (`status` und `restaurant`). Das Token von Eintrag 14 gegen Eintrag 15: *„Ungültiges CSRF-Token"*, Status `confirmed → confirmed`. Test `testAk21TokenEinesFremdenEintragsWirktNicht` |

### Fragwürdiges Verhalten — bestätigt

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-17** ⚠ | ✅ bestätigt | **0** Blätter-Links in der Liste; `findFiltered()` ohne `setMaxResults` → BF-40 |
| **AK-18** ⚠ | ✅ bestätigt | Auswahlliste: **12** Optionen bei **11** Restaurants in der Datenbank (11 + „keine Zuordnung"); `AdminWaitlistController.php:96`: `findBy([], ['name' => 'ASC'])` → BF-40 |
| **AK-19** ⚠ | ✅ bestätigt | **0** Spalten für einen Bearbeiter. Kein eigener Befund — das ist B19/FB-02 (kein Audit-Log), dort bereits erfasst |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ bestanden | siehe oben |
| EC-02 | ✅ bestanden | `partnerRow()` / `organisationRow()` (Zeilen 209/225); **0** `instanceof` im Template |
| EC-03 | ✅ bestanden | Sechs Werte in `WaitlistStatus`, `QUALIFIED` steht zwischen `CONTACTED` und `CONVERTED` |
| **EC-04** | ✅ bestanden | `applyStatus(WaitlistEntryInterface $entry, …)` — derselbe Weg für einen Organisationseintrag durchgespielt: `status=qualified`, Meldung *„Status auf »Qualifiziert« geändert."*, `confirmedAt` gesetzt |

## Sicherheitsprüfung

| Prüfung | Ergebnis |
|---|---|
| **Rollenschranke** | Nicht-Admin 403 auf allen drei Leserouten und dem Schreibweg; Gast 302 |
| **CSRF je Endpunkt** | vier eigene Token, ID im Schlüssel gebunden — gegen einen fremden Eintrag wirkungslos |
| **ID-Verwechslung zwischen den Tabellen** | `/warteliste/partner/2` (Organisations-ID) → **404** · `/warteliste/organisation/14` (Partner-ID) → **404**. Beide Routen lösen nur in ihrer eigenen Tabelle auf |
| **Unbekannte Eingaben** | unbekannter Status, unbekannter Typ, nicht existierende Restaurant-ID → jeweils Meldung, keine Änderung, keine Exception |
| **XSS über den Freitext** | in B14 geprüft: in beiden Verwaltungsansichten maskiert |
| **Testsuite** | 355 Tests, 0 Fehler |

## Fehler

### BF-40 · Die Verwaltungsliste skaliert nicht — niedrig

**Betrifft:** AK-17 und AK-18

**Nachweis:**
- `/de/admin/warteliste`: **0** Blätter-Links; `PartnerWaitlistEntryRepository::findFiltered()`
  hat kein `setMaxResults`. Beide Listen werden vollständig geladen, im Speicher
  zusammengeführt und mit `usort()` sortiert.
- Detailseite einer Partneranmeldung: **12** Optionen in der Auswahlliste bei **11**
  Restaurants — `AdminWaitlistController.php:96` lädt `findBy([], ['name' => 'ASC'])`.

**Warum das trotzdem nur *niedrig* ist:** Der Bereich ist für einen einzigen Admin
gebaut, und die Zahlen sind zweistellig. Der Aufwand wächst linear, nicht quadratisch,
und es gibt keinen Nutzer, der ihn auslösen könnte — die Seite ist rollengeschützt.

**Warum es trotzdem im Register steht:** Beide Werte wachsen mit dem, was das Projekt
erreichen will. Die Restaurantliste ist der **Kernbestand** der Anwendung; wenn die
Plattform funktioniert, hat sie irgendwann vierstellig viele Einträge, und dann lädt jede
geöffnete Wartelisten-Anmeldung sie alle. Die öffentliche Suche (B05) und die
Restaurantverwaltung (B20) blättern beide — das Muster ist im Projekt vorhanden und
wurde hier nur nicht angewandt.

**Vorschlag:** Für die Liste dasselbe `Paginator`-Muster wie in `RestaurantRepository::findPaginated()`.
Für die Auswahlliste genügt eine Suchfunktion statt einer vollständigen Liste — oder,
billiger, ein `setMaxResults` mit einem Hinweis, wenn abgeschnitten wurde. **Ein
stillschweigend abgeschnittener Bestand wäre schlimmer als der heutige Zustand**: Der
Admin würde ein Restaurant nicht finden und annehmen, es existiere nicht.

## Hinweise ohne Fehlerstatus

- **AK-19 (kein Bearbeiter am Statuswechsel)** bekommt keine eigene Nummer. Das ist
  B19/FB-02 — kein Audit-Log im gesamten Verwaltungsbereich, dort bereits als Hinweis
  erfasst. Hier fällt es nur besonders auf, weil ein Statuswechsel eine
  Geschäftsentscheidung ist: Wer eine Anmeldung auf `declined` setzt, trifft eine
  Absage, und die ist später niemandem zuzuordnen.
- **Es gibt weiterhin keinen Löschweg** für Wartelisten-Einträge — die Verwaltung kennt
  nur Statuswechsel. Das ist Teil von BF-37 (B14) und wird dort mitgezählt.
- **`code-reviewer`-Agent nicht eingesetzt** — Sitzungsvorgabe.

## Neue Tests

Drei in `tests/Functional/Controller/AdminWaitlistControllerTest.php`:
`testAk02BeideQuellenSindNachDatumDurchmischt`,
`testAk21TokenEinesFremdenEintragsWirktNicht`,
`testAk07UnbekannteFilterwerteZeigenDieVolleListe`.

Der erste ist der, den ich am wichtigsten halte: Er legt vier Einträge abwechselnd aus
beiden Quellen an, datiert sie über vier Tage zurück und prüft die Reihenfolge. Der
Fehler, den er abfängt — zwei sortierte Hälften aneinandergehängt statt einmal über
alles sortiert — sieht bei zwei Testeinträgen richtig aus und fällt erst im Betrieb auf.

Die vorhandenen acht Tests deckten AK-01, 03, 04, 09, 11, 13, 14 und 16 bereits ab.

**Suite: 355 Tests, 0 Fehler.**

## Nächster Schritt

`/sdd-erfassen B17`. B22 geht auf `approved`; BF-40 steht in `features/befunde.md`.
