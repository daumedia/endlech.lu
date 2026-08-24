# Fehlbestand — projektweite Muster

Stand: 2026-08-23 · Quelle: die Abschnitte *Fehlbestand* und die ⚠-Kriterien aller
26 rückerfassten Features.

> **Das ist nicht `befunde.md`.** Jene Liste schreibt `sdd-qa` aus geprüften
> Testberichten fort. Diese hier sammelt, was beim **Lesen** des Codes aufgefallen ist:
> Sie ist eine Suchliste für die QA, kein Prüfergebnis. Kein Eintrag ist verifiziert;
> jeder nennt seine Fundstelle, damit er widerlegbar ist.

| | |
|---|---|
| Akzeptanzkriterien insgesamt | 458 |
| davon ⚠ markiert (Verhalten fragwürdig) | 66 |
| Fehlbestand-Einträge | 154 |
| Offene Fragen an den Betreiber | 58 |

Der Ertrag einer Vollerfassung liegt nicht in den Einzelbefunden, sondern hier: Was in
acht Features gleichzeitig fehlt, fehlt in der Konvention, nicht in der Sorgfalt des
Einzelfalls.

---

## M-01 · Der Browser-Weg ist ungedrosselt, der API-Weg nicht

`config/packages/framework.yaml` kennt genau drei Limiter — `api_anonymous`,
`api_login`, `partner_waitlist`. Damit ist ausgerechnet der Weg geschützt, den eine App
nimmt, und der ungeschützt, den ein Browser nimmt.

| Endpunkt | Limit | Nachweis |
|---|---|---|
| `POST /api/v1/auth/login` | 5/min je IP | B23/AK-18 |
| `POST /{locale}/login` | ~~keins~~ → 5 je 15 Min | B02/FB-01, behoben 2026-08-24 (BF-13) |
| `POST /{locale}/register` | ~~keins~~ → 5/h | B01/FB-01, behoben 2026-08-23 (BF-02) |
| `/{locale}/verify/resend` | ~~keins~~ → 3/h | B01/FB-02, behoben 2026-08-23 (BF-02) |
| `POST /{locale}/profile/password` | ~~keins~~ → 5 je 15 Min | B04/FB-07, behoben 2026-08-24 (BF-20) |
| `POST /{locale}/profile/edit` | **keins** | B04/BF-21 — verschickt seit der BF-19-Reparatur zwei Mails je Aufruf, an eine **frei wählbare** Adresse |
| `/passkey/login/options` | **keins** | B03/FB-01 |
| `POST /{locale}/community/suggest` | **keins** | B11/FB-01 |
| `/open/dataset.csv` | **keins** | B17/FB-02 |
| alle Verwaltungs-POSTs | **keins** | B19/FB-05 |

**Schwerste Folge:** B02/FB-01. Unbegrenztes Passwortraten trifft ein
Anwendungssystem, dessen Verwaltungszugang an genau einem Konto hängt (B19/FB-01) und
das keine zweite Stufe kennt (B02/FB-03). **Behoben am 2026-08-24.**

**Kleinster wirksamer Schritt:** `login_throttling: max_attempts: 5` in der
`main`-Firewall. Eine Zeile. — *erledigt.*

**Stand 2026-08-24: vier der neun Zeilen sind zu, und trotzdem ist das Muster nicht
weg.** `POST /profile/edit` kam neu dazu — als Nebenwirkung einer Reparatur, die diesem
Weg erstmals einen Mailversand gab, ohne ihm einen Deckel mitzugeben. Genau das ist der
Punkt, an dem eine Einzelbehebung zu wenig ist:

> **Konvention, die dem Projekt fehlt:** Jeder Weg, der eine Mail auslöst oder ein
> Geheimnis prüft, bekommt einen Limiter — unabhängig davon, ob eine App oder ein
> Browser ihn geht. Wer einen solchen Weg neu anlegt oder einen bestehenden um einen
> Mailversand erweitert, legt den Limiter im selben Commit an.

Ohne diesen Satz irgendwo im Projekt wird die Liste weiter Zeilen bekommen, während oben
welche verschwinden.

---

## M-02 · Eine ungeprüfte Eingabe reicht bis in den offiziellen offenen Datensatz

Die gewichtigste Verkettung dieser Erfassung. Fünf Features, die einzeln jeweils
vertretbar aussehen:

```
POST /api/v1/restaurants            jeder angemeldete Nutzer, keine Moderation   B23/AK-21
  └─ findPaginated() ohne isVerified-Vorfilter
       ├─ /{locale}/restaurants     erscheint in der öffentlichen Liste          B05/AK-15
       ├─ /{locale}/                erscheint in den Top-6 der Startseite        B12/AK-07
       ├─ /{locale}/open            hebt die ausgewiesene Abdeckung              B16/AK-17
       └─ /open/dataset.csv         landet im Datensatz unter CC BY 4.0          B17/AK-13
```

Der Web-Weg für dieselbe Absicht (B11 → B21) verlangt dagegen eine Admin-Genehmigung.
Die API umgeht diese Stufe vollständig — bei 100 Anfragen pro Minute je IP.

**Zu klären ist eine Frage, nicht fünf** (B23/OF-01): Soll `POST /api/v1/restaurants`
eine `RestaurantSuggestion` anlegen statt eines `Restaurant`? Dann fällt die ganze
Kette.

Für ein Produkt, dessen erstes Prinzip „Bewertungen sind nicht käuflich" lautet, ist
die Gleichstellung geprüfter und ungeprüfter Angaben zudem eine Aussage — unabhängig
vom Missbrauchsrisiko.

---

## M-03 · Betroffenenrechte sind nicht bedienbar

Kein einzelner Fehler, sondern eine durchgehende Lücke — und die einzige Gruppe mit
rechtlicher Frist.

| Recht | Lage | Nachweis |
|---|---|---|
| Löschung (Art. 17) | kein Weg, ein Konto zu löschen — weder im Profil noch in der Verwaltung | B01/FB-04, B04/FB-01, B19/FB-01 |
| Auskunft (Art. 15) | kein Datenexport | B01/FB-06, B04/FB-02 |
| Widerruf (Art. 7 Abs. 3) | keine Abmeldung von den Wartelisten, kein Abmeldelink in den Mails, keine Löschfunktion in der Verwaltung | B14/FB-01, B15/FB-01, B22/FB-01 |
| Speicherbegrenzung (Art. 5) | keine Löschfristen; abgelehnte Vorschläge und nie bestätigte Anmeldungen bleiben unbefristet | B14/FB-02, B15/FB-02, B21/FB-05 |
| Rechenschaft (Art. 5 Abs. 2) | kein `docs/datenschutz.md` — kein PII-Inventar, kein Löschkonzept, keine Auftragsverarbeiter-Liste | B13/FB-01 |

**Bemerkenswert:** Die technischen Voraussetzungen für das Löschen sind vollständig
vorhanden — `webauthn_credential` kaskadiert, `restaurant.submitted_by` und
`restaurant_suggestion.suggested_by` stehen auf `SET NULL`, `AvatarUploadService::delete()`
räumt die Datei ab. Es fehlt **nur der Auslöser**.

`findPendingOlderThan()` in `PartnerWaitlistEntryRepository` ist offenkundig als
Aufräumroutine gedacht — und wird **nirgends im Produktivcode aufgerufen**, nur in einem
Test (B14/FB-02).

---

## M-04 · Es gibt keine Aufzeichnung, wer was getan hat

`Restaurant.verifiedBy` ist die **einzige** Spur im gesamten Projekt.

| Bereich | Was nicht nachvollziehbar ist | Nachweis |
|---|---|---|
| Finanzen | wer einen Posten geändert oder gelöscht hat, und was vorher dastand | B18/FB-02 |
| Restaurants | wer Felder geändert oder einen Datensatz gelöscht hat | B20/FB-04 |
| Vorschläge | wer genehmigt oder abgelehnt hat | B21/FB-04 |
| Wartelisten | wer einen Status gesetzt hat | B22/FB-04 |
| Konten | wer `ROLE_ADMIN` vergeben hat (geht nur per SQL) | B19/FB-01 |

Bei einem Alleinbetrieb tragbar. Bei den **Finanzdaten** wiegt es schwerer als
anderswo, weil diese Zahlen öffentlich als Beleg dienen (B16, B17).

---

## M-05 · Die Anwendung vertraut dem `Host`-Header

`config/packages/framework.yaml` setzt weder `trusted_hosts` noch `trusted_proxies`.
Drei Stellen bauen absolute URLs aus dem Request-Host und verschicken sie per Mail:

| Stelle | Was verschickt wird | Nachweis |
|---|---|---|
| `RegistrationController` | Bestätigungs-URL mit Token | B01/FB-09 |
| `WaitlistConfirmationService::register()` | Bestätigungs-URL, beide Wartelisten | B14/FB-04, B15/FB-04 |
| `PasskeyAuthenticator::authenticate()` | `WebauthnBadge($request->getHost(), …)` | B03/FB-03 |

Beim Passkey fängt die konfigurierte `rp_id` den Fall ab. Bei den Mails nicht: Wer eine
Registrierung mit manipuliertem `Host`-Header abschickt, kann eine Bestätigungsmail an
das Opfer schicken, deren Link auf seinen Server zeigt.

Ob der Angriff durchgeht, hängt zusätzlich vom Webserver ab — im Anwendungscode ist
nichts dagegen vorgesehen. **Eine Konfigurationszeile deckt alle drei Stellen.**

---

## M-06 · Zwei Upload-Wege, zwei verschiedene Sorgfaltsmaßstäbe

Beide schreiben in den Web-Root, beide werden direkt vom Webserver ausgeliefert.

| Weg | Typprüfung | Größe | Nachweis |
|---|---|---|---|
| Avatar (B04) | `File`-Constraint, drei MIME-Typen, echter Inhalt über `fileinfo` | 2 MB | B04/AK-15 |
| Restaurantfotos (B09) | **keine** — der Upload läuft an Symfonys Formularsystem vorbei | **keine** | B09/AK-12, FB-01, FB-02 |

Nachgeprüft (nicht vermutet): Über B09 gespeicherte `.html`- und `.svg`-Dateien behalten
ihre Endung und werden im Ursprung der Seite ausgeliefert — also gespeichertes Skript
mit Zugriff auf die Sitzung jedes Besuchers, der die Datei öffnet. **Kein RCE:**
`text/x-php` hat keine zugeordnete Endung, `guessExtension()` liefert dafür `null`.

Reichweite: setzt `ROLE_ADMIN` voraus — was in Verbindung mit M-01 (kein
Passwort-Ratelimit) und M-04 (keine Aufzeichnung) an Gewicht gewinnt.

Dazu, unabhängig vom Typ: Bilder werden nie neu kodiert, Metadaten wie GPS-Koordinaten
bleiben in der öffentlich abrufbaren Datei (B04/FB-06, B09/FB-04). Und beim Löschen
eines Restaurants bleiben die Dateien liegen (B20/AK-13).

---

## M-07 · Code, der aussieht, als liefe er — und nicht läuft

| Was | Warum es nicht läuft | Nachweis |
|---|---|---|
| „Bestätigungsmail erneut senden" | `/verify/{token}` ist vor `/verify/resend` deklariert und fängt die Anfrage ab. Belegt über `router:match /de/verify/resend` → `app_verify_email`. Der Link in `notice.html.twig:32` führt ins Leere | B01/AK-15 |
| `findPendingOlderThan()` | nirgends im Produktivcode aufgerufen | B14/FB-02 |
| `src/Schedule.php` (Monats-Snapshot) | Symfony Scheduler braucht `messenger:consume`; Produktion läuft mit `sync://` ohne Worker. Der reale Auslöser ist ein Cron | B18/AK-17 |
| E-Mail-Bestätigung als Zugangsvoraussetzung | kein `user_checker` — ein unbestätigtes Konto erreicht alles außer dem Vorschlags-Wizard | B01/AK-13, B02/AK-13 |
| Admin-Sprachumschalter | schreibt `_locale` in die Sitzung; es gibt keinen Leser dafür | B19/AK-10 |
| Ablehnungsnotiz `adminNote` | wird erfasst und dem Einreicher nie gezeigt | B21/AK-12 |
| Cookie-Ablehnung | niemand liest `cookie_consent` aus. Folgenlos, weil die Seite keine Fremdressourcen lädt — der Banner fragt nach einer Einwilligung, die nicht erforderlich ist | B26/AK-08 |

---

## M-08 · Auf dem Telefon fehlt die halbe Navigation

Für eine ausdrücklich als iPhone-App gebaute Anwendung (B25) der zentrale Befund.

| Funktion | Lage auf Mobil | Nachweis |
|---|---|---|
| Abmelden | **kein Weg** — der einzige `app_logout`-Link im Projekt trägt `hidden md:inline-block`, es gibt kein Burger-Menü, die Profilseite hat keinen Knopf | `docs/app-shell.md` #1, B25/AK-17 |
| Sprache wechseln | **kein Weg** — der Umschalter steht nur in `hidden md:block` | `docs/app-shell.md` #2, B24/AK-13 |
| „Restaurant vorschlagen" | kein Weg | `docs/app-shell.md` #3 |
| „Mitmachen" (Partner, Gemeinden, Unternehmen, Vereine) | nur über die Fußzeile | `docs/app-shell.md` #3 |

Die Bottom-Navigation hat vier Felder — Start, Restaurants, Über uns, Profil — und
ersetzt die ausgeblendete Kopfnavigation damit nur teilweise.

---

## M-09 · Vorgänge, die sich nicht wiederholen lassen dürfen, tun es

| Vorgang | Was bei der Wiederholung passiert | Nachweis |
|---|---|---|
| Vorschlag genehmigen | ein **zweites** Restaurant entsteht — es gibt keine Statusprüfung | B21/AK-10 |
| Snapshot-Knopf im Admin | der Vormonat wird mit **heutigen** Zahlen überschrieben (`capture(null, force: true)`) | B18/AK-16 |

Der zweite Fall verletzt genau die Eigenschaft, für die `MetricSnapshot` existiert:
„Ein aus den heutigen Daten zurückgerechneter Verlauf änderte sich rückwirkend […] als
Beleg gegenüber einem Ministerium wertlos."

---

## M-10 · Dieselbe Regel steht zweimal im Projekt

| Regel | Fassung A | Fassung B | Nachweis |
|---|---|---|---|
| Öffnungszeiten inkl. Nachtschicht | `OpeningHoursService` (PHP) | `findPaginated()`, `open`-Zweig (SQL) | B07 |
| Eingabevalidierung | FormTypes mit Constraints | `mb_strlen`/`filter_var` in `Api\V1` | B23/FB-09 |
| Verhalten bei bekannter E-Mail | Web verrät sie (`UniqueEntity`) | API verschweigt sie ausdrücklich | B01/AK-14 vs. B23/AK-04 |

Die dritte Zeile ist mehr als Doppelung: Der Anti-Enumeration-Schutz der API (mit
Timing-Ausgleich und Hinweis-Mail sorgfältig gebaut) ist wirkungslos, solange dieselbe
Auskunft über das Registrierformular frei abrufbar ist.

---

## Was zuerst

Nach Verhältnis von Wirkung zu Aufwand, nicht nach Schwere:

| # | Maßnahme | Aufwand | Behebt |
|---|---|---|---|
| 1 | `login_throttling` in der `main`-Firewall | eine Zeile | M-01, schwerster Einzelposten |
| 2 | `trusted_hosts` setzen | eine Zeile | M-05 vollständig |
| 3 | Routenreihenfolge in `EmailVerificationController` tauschen | zwei Zeilen — **zusammen mit einem Limit**, sonst öffnet es einen Mailversandweg | M-07 |
| 4 | Statusprüfung in `AdminSuggestionController::approve()` | drei Zeilen | M-09 |
| 5 | Restaurant-Upload durch ein Formular mit `File`-Constraint führen | ein FormType | M-06 |
| 6 | Entscheidung zu `POST /api/v1/restaurants` | eine Entscheidung, dann Umbau | M-02, fünf Features auf einmal |
| 7 | Kontolöschung, Wartelisten-Widerruf, `docs/datenschutz.md` | echtes Vorhaben | M-03 — das einzige mit rechtlicher Frist |

Die Punkte 1 bis 4 sind zusammen unter zehn Zeilen Code.

---

## Grenzen dieser Übersicht

- **Nichts davon ist geprüft.** Die Einträge stammen aus dem Lesen des Codes; einige
  wurden praktisch nachgestellt (`router:match`, `guessExtension()`, `fileinfo`), die
  meisten nicht. Jeder Eintrag nennt seine Fundstelle, damit er widerlegbar ist.
- **Fehlende Einträge sind kein Freispruch.** Geprüft wurde gegen
  `~/.claude/sdd/sicherheit.md`; was dort nicht steht, wurde auch hier nicht gesucht.
- **Der Bestand ist an vielen Stellen sorgfältiger als der Durchschnitt.** Passkeys
  (B03), die Wartelisten-Mechanik (B14/B15) und die Diagrammregeln der Transparenzseite
  (B16) sind ungewöhnlich gut begründet — im Quelltext, nicht nur in der
  Dokumentation. Die Muster oben betreffen Konventionen, die fehlen, nicht Nachlässigkeit
  im Einzelfall.
