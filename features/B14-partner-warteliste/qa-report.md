# B14 · Partner-Warteliste — Testbericht

Stand: 2026-08-24 · Vorstufe: `rekonstruiert` · Branch `fix/b04-profil-qa`

## Fazit

**Production-ready: ja** — zwei mittlere und zwei niedrige Befunde, keiner davon
technisch.

23 von 23 Kriterien bestanden, 5 von 5 Edge Cases. Das ist das sauberste Feature dieser
Prüfreihe: Honeypot, Rate Limit, Turbo-Stream, Double-Opt-In, Mailfehlerbehandlung und
die Trennung zwischen „bereits bestätigt" und „unbekannter Token" verhalten sich alle
exakt wie beschrieben, an den Grenzwerten nachgemessen. Auch die Mailfehlerbehandlung
stimmt: Bei gestopptem Transport blieb der Eintrag gespeichert und der Nutzer bekam eine
verständliche Meldung.

**Was fehlt, ist nicht der Code, sondern der Rechtsrahmen.** Die Einwilligung wird
sauber erfasst (`consentAt`, Zeitpunkt, Sprache, Herkunft) — aber sie lässt sich nicht
widerrufen, und die Daten haben keine Löschfrist. Art. 7 Abs. 3 DSGVO verlangt, dass der
Widerruf so einfach ist wie die Erteilung; Art. 5 Abs. 1 lit. e verlangt eine
Speicherbegrenzung. Beides fehlt, und beides ist eine Zeile Code weniger als eine
Entscheidung.

Bemerkenswert: `PartnerWaitlistEntryRepository::findPendingOlderThan()` existiert und ist
offensichtlich für die Aufräumroutine gedacht — sie wird **nirgends im Produktivcode
aufgerufen**, nur in einem Test. Toter Code, der eine Aufräumlogik vortäuscht.

Nächster Aufruf: **`/sdd-erfassen B15`**. Die Erfassung läuft weiter.

## Eine Korrektur an der Rekonstruktion

**FB-06 der Spec ist falsch.** Sie behauptet: *„Der Freitext `message` wird nicht
begrenzt geprüft."*

Gemessen: 20.000 Zeichen im Feld `message` → **HTTP 422**. Im Code steht
`src/Form/PartnerWaitlistType.php:77`: `new Length(max: 2000, maxMessage: 'partner_waitlist.message_max')`.
Alle fünf Textfelder tragen eine Längengrenze (180 / 120 / 180 / 40 / 120 / 2000).

Die Spec ist in `spec.md` berichtigt, der alte Wortlaut bleibt durchgestrichen stehen.

## Akzeptanzkriterien im Einzelnen

### Anmeldung

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | `/de/partner` → **200**, Formular mit neun Feldern gerendert |
| AK-02 | ✅ bestanden | DB nach dem Absenden: `status=pending`, `locale=de`, `source=qa-kampagne` (aus `?utm_source=`), `consent_at=2026-08-24 16:16:22`, Token vorhanden |
| AK-03 | ✅ bestanden | Mail an `anna@qa.example`, Betreff „Bestätigen Sie Ihre Anmeldung zur Warteliste", Link `http://localhost:8000/de/partner/confirmation/3dc4faa8…` (64 Hex, absolut) |
| **AK-04** | ✅ bestanden | Mailpit gestoppt → HTTP 302, Einträge **10 → 11**, `QA Mailfehler status=pending` in der DB, Meldung: *„Ihre Anmeldung ist gespeichert, aber die Bestätigungsmail konnte nicht versendet werden."* |
| **AK-05** | ✅ bestanden | mit `Accept: text/vnd.turbo-stream.html` → **200**, `Content-Type: text/vnd.turbo-stream.html`, Rumpf beginnt mit `<turbo-stream action="replace" target="partner-waitlist-form">` |
| AK-06 | ✅ bestanden | ohne Turbo-Accept → **302** nach `/de/partner` |
| **AK-07** | ✅ bestanden | ungültiges Formular **mit** Turbo-Accept → **422** und `Content-Type: text/html` — nicht turbo-stream. EC-03 damit belegt |

### Missbrauchsschutz

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-08** | ✅ bestanden | Honeypot gefüllt → 302, Einträge **1 → 1**, **0 Mails**. Die Antwortrümpfe von Honeypot- und Erfolgsfall sind **byteweise identisch** (md5 `dca1be0d…` beide) |
| **AK-09** | ✅ bestanden | `302 302 302 302 302 429` — fünf durch, der sechste abgewiesen; Meldung *„Sie haben in kurzer Zeit mehrere Anmeldungen abgeschickt…"* |
| **AK-10** | ✅ bestanden | **10 reine GETs** auf `/de/partner`, danach gingen fünf Submits durch. Der Seitenaufruf verbraucht nichts. EC-04 damit belegt |
| AK-18 | ✅ bestanden | `PartnerWaitlistType`: kein `Blank`-Constraint am Feld `website`, nur `'mapped' => false` |
| **AK-19** | ✅ bestanden | `<div aria-hidden="true" class="absolute w-px h-px -left-[9999px] overflow-hidden">` mit `<input type="text" … tabindex="-1">`. **Kein** `type="hidden"` — genau wie beschrieben |

### Bestätigung

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-11 | ✅ bestanden | Link eingelöst → 200, `status=confirmed`, `confirmed_at` gesetzt, interne Meldung an `info@endlech.lu` |
| AK-12 | ✅ bestanden | zweiter Aufruf desselben Links → Seite enthält „bereits"; Token steht noch in der DB (EC-02) |
| AK-13 | ✅ bestanden | 64 unbekannte Hex-Zeichen → **404**, keine Exception |
| AK-14 | ✅ bestanden | `abc` → 404 · 64 **Groß**buchstaben → 404. Das Requirement `[a-f0-9]{64}` greift vor dem Controller |
| **AK-15** | ✅ bestanden | Bestätigung über `/fr/partner/confirmation/…` → interne Mail trotzdem **auf Deutsch** („Neue bestätigte Partner-Anmeldung"), `Reply-To: anna@qa.example`. Tests `testAk15InterneMeldungBleibtDeutschBeiFranzoesischerBestaetigung` und `…TraegtDenInteressentenAlsReplyTo` |
| **AK-16** | ✅ bestanden | Mailpit gestoppt, dann bestätigt → **200**, `status=confirmed`. Der Nutzer merkt vom gescheiterten Versand nichts |

### Datenschutz

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-17 | ✅ bestanden | Spalten: `id, restaurant_id, restaurant_name, contact_name, email, phone, locality, message, status, confirmation_token, confirmed_at, consent_at, locale, source, created_at, updated_at` — **keine IP-Adresse** |
| AK-20 | ✅ bestanden | `consent_at` wird beim Anlegen gesetzt (siehe AK-02) |

### Fragwürdiges Verhalten — bestätigt

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-21** ⚠ | ✅ bestätigt | `DESCRIBE partner_waitlist_entry` → **0** Spalten mit „expires". Kein Ablauf → BF-36 |
| **AK-22** ⚠ | ✅ bestätigt | `debug:router` → **0** passende Routen; **0** Abmeldelinks in den Mailvorlagen → BF-37 |
| **AK-23** ⚠ | ✅ bestätigt | Nach fünf Partner-Submits liefert das **Organisations**formular **429** mit der Meldung aus `flash.partner_rate_limited`. Beide Controller: `#[Autowire(service: 'limiter.partner_waitlist')]` → BF-38 |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ bestanden | `#[ORM\HasLifecycleCallbacks]` (Zeile 13), `#[ORM\PreUpdate]` (Zeile 89), Initialisierung im Konstruktor mit Kommentar |
| EC-02 | ✅ bestanden | nach der Bestätigung: `token=steht noch` — nur so ist AK-12 von AK-13 unterscheidbar |
| EC-03 | ✅ bestanden | siehe AK-05 und AK-07 — Erfolgsfall turbo-stream, Fehlerfall `text/html` |
| EC-04 | ✅ bestanden | siehe AK-10 |
| EC-05 | ✅ bestanden | `when@test`-Override auf 10000 vorhanden; die Suite läuft grün trotz elf Submit-Tests |

## Sicherheitsprüfung

| Prüfung | Ergebnis |
|---|---|
| **Rate Limit überrannt** | greift exakt an der Grenze (5/6), GET verbraucht nichts |
| **Honeypot** | gefüllt → nichts gespeichert, nichts versandt, Antwort byteweise identisch |
| **Token raten** | 64 Hex aus `random_bytes(32)`; unbekannt → 404, falsches Format → Route greift nicht |
| **XSS über den Freitext** | `<script>alert(1)</script>` als Restaurantname, `<img src=x onerror=alert(1)>` als Nachricht → in **beiden** Verwaltungsansichten (`/de/admin/warteliste` und die Detailseite) **maskiert**, nicht ausführbar |
| **Längengrenzen** | 20.000 Zeichen → 422 (siehe die Spec-Korrektur oben) |
| **Personenbezogene Daten** | keine IP-Adresse, kein User-Agent |
| **Mailfehler** | Eintrag bleibt erhalten, Meldung verständlich, Bestätigung funktioniert trotzdem |

## Fehler

### BF-36 · Der Bestätigungstoken läuft nie ab — niedrig

**Betrifft:** AK-21 · FB-03 der Spec

**Nachweis:** `DESCRIBE partner_waitlist_entry` → keine Spalte mit „expires".
`PartnerWaitlistEntry::generateConfirmationToken()` setzt keinen Ablaufzeitpunkt —
anders als `User::generateVerificationToken()`, das 24 Stunden vergibt.

**Folge:** Ein Token, der einmal in einem fremden Postfach, in einem weitergeleiteten
Mailverlauf oder in einem Server-Log landet, bleibt dauerhaft einlösbar. Der Schaden ist
gering — eingelöst wird eine Wartelisten-Bestätigung, keine Kontoübernahme — aber es ist
ein Muster, das im selben Projekt an anderer Stelle bereits richtig gelöst ist.

**Vorschlag:** Dieselben 24 Stunden wie bei `User`, mit derselben Mechanik. Wichtig
dabei: **Der Token darf trotzdem stehen bleiben** — sonst fällt AK-12 („bereits
bestätigt") mit AK-13 („Link ungültig") zusammen. Die Frist ist ein zusätzliches Feld,
kein Ersatz für den Token.

### BF-37 · Die Einwilligung lässt sich nicht widerrufen — mittel

**Betrifft:** AK-22 · FB-01 der Spec

**Nachweis:**
- `debug:router` → **0** Routen, die auf Abmelden, Austragen oder Widerruf passen
- **0** Abmeldelinks in den Mailvorlagen unter `templates/email/`
- Auch die Verwaltung (B22) kennt keine Löschfunktion

**Warum das über einen Komfortmangel hinausgeht:** Die Anmeldung erfasst `consentAt`
sorgfältig — Zeitpunkt, Sprache, Herkunftsquelle — und macht damit sichtbar, dass das
Verarbeiten auf einer **Einwilligung** beruht. Art. 7 Abs. 3 DSGVO verlangt, dass der
Widerruf so einfach ist wie die Erteilung. Erteilt wird er mit einem Klick auf einen
Link in einer Mail; widerrufen lässt er sich gar nicht.

**Verstärkt durch die fehlende Löschfrist** (FB-02): Nicht bestätigte Anmeldungen
bleiben unbefristet gespeichert. `PartnerWaitlistEntryRepository::findPendingOlderThan()`
ist genau dafür geschrieben — und wird **nur im Test** aufgerufen:
```
src/Repository/PartnerWaitlistEntryRepository.php:31   public function findPendingOlderThan(...)
tests/Integration/Repository/PartnerWaitlistEntryRepositoryTest.php:29   $found = $this->repository->findPendingOlderThan(...)
```
Das ist die unangenehmere Hälfte: Der tote Code *sieht aus*, als gäbe es eine
Aufräumroutine. Wer die Datenhaltung prüft und die Methode findet, hakt den Punkt ab.

**Vorschlag:** Ein signierter Abmeldelink in jeder Mail — er deckt zugleich FB-05
(Auskunft) teilweise ab, weil er einen Einstiegspunkt ohne Konto schafft. Dazu ein
Konsolenbefehl auf `findPendingOlderThan()`, an denselben Cron gehängt wie
`app:metrics:snapshot`. Das Muster steht im Projekt bereits (`src/Command/`,
`src/Schedule.php`).

**Verwandt mit BF-04** (Betroffenenrechte, Feature `01`), aber nicht identisch: Dort geht
es um Konten, hier um Wartelisten-Daten ohne Konto. Ein Widerrufsweg über einen
signierten Link erreicht Menschen, die nie ein Konto hatten — Feature `01` erreicht sie
nicht.

### BF-38 · Beide Wartelisten teilen sich ein Kontingent — niedrig

**Betrifft:** AK-23 · OF-03 der Spec

**Reproduktion:**
1. Fünfmal `POST /de/partner` (Kontingent erschöpft)
2. Einmal `POST /de/organisationen` mit vollständigen Pflichtfeldern

**Erwartet:** eigenes Kontingent je Warteliste
**Tatsächlich:** **HTTP 429**, Meldung aus `flash.partner_rate_limited`

**Ort:** `PartnerController.php:33` und `OrganisationController.php:85` — beide
`#[Autowire(service: 'limiter.partner_waitlist')]`

**Folge:** Hinter einer geteilten IP — einer Gemeindeverwaltung, einem Coworking-Raum —
blockieren sich Interessenten gegenseitig, obwohl sie nichts miteinander zu tun haben.
Und ein Gemeindesekretär, der sich auf der Organisationsliste einträgt, verbraucht
Kontingent, das für Restaurants gedacht war.

Der Meldungstext selbst ist unauffällig („Sie haben in kurzer Zeit mehrere Anmeldungen
abgeschickt") — der Schlüsselname `flash.partner_rate_limited` ist irreführender als das,
was der Nutzer liest.

**Vorschlag:** Ein zweiter Limiter `organisation_waitlist` mit denselben Werten. Zwei
Zeilen in `framework.yaml`, eine im Controller, plus der `when@test`-Override.

## Hinweise ohne Fehlerstatus

- **FB-04 (kein `trusted_hosts`, Bestätigungslink aus dem Request-Host)** ist derselbe
  Befund wie B23/BF-29 und B01/FB-09 — dort bereits erfasst und als **Serveraufgabe**
  gekennzeichnet. Kein eigener Eintrag, sonst steht dieselbe Sache dreimal im Register.
- **FB-05 (keine Auskunftsfunktion)** — verwandt mit BF-37 und Feature `01`. Ein
  signierter Link deckt beides zusammen ab; getrennt gebaut wird es doppelt so teuer.
- **`code-reviewer`-Agent nicht eingesetzt** — Sitzungsvorgabe.

## Neue Tests

Drei in `tests/Functional/Controller/PartnerControllerTest.php`:
`testAk15InterneMeldungBleibtDeutschBeiFranzoesischerBestaetigung`,
`testAk15InterneMeldungTraegtDenInteressentenAlsReplyTo`,
`testAk23BeideWartelistenTeilenSichDenLimiter` (hält BF-38 fest; er fällt, sobald die
Limiter getrennt werden).

Die vorhandene Abdeckung war bereits die beste im Projekt — elf Tests, die AK-01 bis
AK-14 weitgehend abdecken. Ergänzt habe ich nur, was fehlte.

**Suite: 349 Tests, 0 Fehler.**

## Nächster Schritt

`/sdd-erfassen B15`. B14 geht auf `approved`; die vier Befunde stehen in
`features/befunde.md`.

BF-37 ist der, den ich als nächstes bauen würde — nicht weil er technisch drängt,
sondern weil er zusammen mit BF-04 und FB-05 aus B14 dasselbe Loch beschreibt: **Es gibt
keinen Weg, gespeicherte Daten wieder loszuwerden.** Drei Features, ein Bauvorgang.
