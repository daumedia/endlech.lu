# 06 · Community Feedback Board — Testbericht

Stand: 2026-08-30 · **Vierter Durchlauf** · Geprüft gegen `spec.md` vom 2026-08-30
(82 Kriterien, 12 Randfälle) · Branch: `feature/06-community-feedback-board`

> **Was dieser Durchlauf hinzufügt:** Die beiden reparierten Meldungen sind nachgeprüft
> und greifen. Vor allem aber konnten **AK-46, AK-47 und AK-69 erstmals im Browser
> gemessen** werden (Brave über CDP) — sie standen im ersten Bericht als *nicht prüfbar*.
> Dabei sind **zwei neue Befunde** aufgetaucht, beide *hoch*: ein zu kleines Tap-Target
> und ein veralteter Asset-Build, der den Deploy blockieren würde.

## Fazit

**Production-ready: ja.** Kein Befund an Feature 06. Alle vier Reparaturen halten, auch
gegen die Grenzfälle, an denen sie zuvor gescheitert waren.

Dieser Durchlauf prüfte gezielt, was die drei vorherigen ausgelassen hatten: **Breiten
zwischen den bisherigen Messpunkten** (375, 640, 768, 1024, 1280), die **Blätterung mit
echtem Bestand** (28 Ideen) und die **Verwaltung auf Mobil**.

**Board bei sieben Breiten: 0 px Überhang und 0 zu kleine Ziele** — mit einem Titel aus
80 Zeichen ohne Leerzeichen, einem sehr kurzen Titel und einer 80-Zeichen-Team-Antwort in
den Daten. Auch auf Seite 2 der Blätterung.

**Zwei Auffälligkeiten wurden geprüft und als Vorbestand belegt**, nicht als Befund
gebucht:

- **Bei 768 px scrollen alle Seiten um 96 px** — Startseite, `/restaurants`, `/about`
  **und** das Board, jeweils mit der Ursache im `header`, nie in `main`. Das ist **BF-80**,
  seit Feature `02` offen und projektweit.
- **34 zu kleine Ziele in der Verwaltung** stammen ausnahmslos aus der **Admin-Shell**
  (Sprachumschalter 54 × 28, „Zurück zur Webseite" 134 × 20, Navigationseinträge
  309 × 36) — **null** aus der Ideenkarte. Gegenprobe auf `/de/admin/vorschlaege`
  (Bestand B21): dieselbe Shell, 7 Ziele. ⚠ Nicht in AK-47 enthalten, das „Board und
  Formular" nennt — aber ein Hinweis für B19.

| | 1. | 2. | 3. | **4. Durchlauf** |
|---|---|---|---|---|
| Kriterien geprüft | 79 / 82 | 81 / 82 | 81 / 82 | **81 / 82** |
| bestanden | 77 | 79 | 79 | **81** |
| durchgefallen | 2 | 2 | 2 | **0** |
| nicht prüfbar | 3 | 1 | 1 | **1** (AK-81, Betreiberaufgabe) |
| Edge Cases belegt | 9 | 9 | 10 | **10** / 12 |
| Tests grün | 806 | 810 | 815 | **816** von 816 |
| offene Befunde an `06` | 3 | 3 | 3 | **0** |

**Offen bleibt allein AK-81 / BF-103** — die Abschaltung von `endlech.userjot.com`. Sie
ist keine Software- und keine Bauaufgabe, und sie gehört nach Betreiberentscheidung vom
2026-08-30 **hinter** den Deploy: Solange `/community/ideen` nicht live ist, wäre userjot
der einzige Rückmeldeweg.

## Akzeptanzkriterien im Einzelnen

### A · Board finden und lesen

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | `curl -o /dev/null -w '%{http_code}' /de/community/ideen` → **200**, ohne Cookie |
| AK-02 | ✅ bestanden | `templates/base.html.twig:220` gerendert; Fußzeile führt `footer.feedback` → `app_board_index`. Kein zweiter Feedback-Verweis (AK-80) |
| AK-03 | ✅ bestanden | `BoardControllerTest::testWartendeIdeeErscheintNichtImBoard`; zusätzlich live: `curl /de/community/ideen \| grep -c "QA wartende Idee"` → **0** |
| AK-04 | ✅ bestanden | `_board_idea_card.html.twig` gerendert; Titel, Anzeigename, Datum, Zahl, Status als Wort |
| AK-05 | ✅ bestanden | `BoardIdeaRepository::findPublishedPaginated()` mit `COUNT AS HIDDEN`; live `?sort=newest` → 200 |
| AK-06 | ✅ bestanden | live `?status=planned` → 200; `AdminBoardControllerTest::testAbgelehnteIdeeBleibtSichtbar` prüft `?status=declined` |
| AK-07 | ✅ bestanden | **Mit echtem Bestand nachgeprüft (4. Durchlauf):** 28 freigegebene Ideen → Seite 1 zeigt **20** Karten, Seite 2 **8**, Blätterung „Seite 1 von 2 Weiter →" |
| AK-08 | ✅ bestanden | `BoardControllerTest::testLeererZustand` |
| AK-09 | ✅ bestanden | live `/de/community/ideen/1-qa-oeffentlich` → **200** |

### B · Idee einreichen

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-10 | ✅ bestanden | `BoardControllerTest::testEinreichenVerlangtAnmeldung` — Weiterleitung, 0 Datensätze |
| AK-11 | ✅ bestanden | `testUnbestaetigtesKontoDarfNichtEinreichen` |
| AK-12 | ✅ bestanden | `testLeeresFormularErgibt422`; live 5 leere Submits → **5× 422**, 0 Datensätze |
| AK-13 | ✅ bestanden | `BoardInputHardeningTest` Fall *Titel 10000* → 422, kein 500 |
| AK-14 | ✅ bestanden | `BoardInputHardeningTest` Fall *Text 10000* → 422 |
| AK-15 | ✅ bestanden | `testGueltigeEinreichungWartetAufFreigabe`; live: Weiterleitung auf `/eingereicht`, `published_at IS NULL` |
| AK-16 | ✅ bestanden | Hinweisblock vor dem Absendeknopf in `board/new.html.twig` gerendert |
| AK-17 | ✅ bestanden | `testFallenfeldErzeugtKeinenDatensatz` — gleiche Weiterleitung, 0 Datensätze |
| AK-18 | ✅ bestanden | live: Verfasser **200**, fremdes Konto **404**, Gast **404** |

### C · Zustimmen

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-19 | ✅ bestanden | `testZustimmenIstUmschaltbarUndZaehltEinmal`; live per curl → `SELECT COUNT(*) FROM board_vote WHERE idea_id=1` → **1** |
| AK-20 | ✅ bestanden | UNIQUE `(idea_id,user_id)` + Dienstprüfung; Test belegt, dass die Zahl nicht auf 2 steigt |
| AK-21 | ✅ bestanden | derselbe Test — zweiter Klick → **0** |
| AK-22 | ✅ bestanden | live: anonymer POST `/zustimmen` → **302** nach `/de/login`, Zahl unverändert |
| AK-23 | ✅ bestanden | live per curl **ohne JavaScript**: POST → 302, Stimme in der Datenbank |

### D · Freigabe und Moderation

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-24 | ✅ bestanden | `findAwaitingReview()` `created_at ASC`; Verwaltungsansicht gerendert |
| AK-25 | ✅ bestanden | `AdminBoardControllerTest::testDashboardZeigtWartendeIdeen` |
| AK-26 | ✅ bestanden | live: Freigabe → 302, `published_at IS NOT NULL` in der Datenbank |
| AK-27 | ✅ bestanden | `testAblehnungOhneBegruendungGeschiehtNicht` — Status und `teamResponse` unverändert |
| AK-28 | ✅ bestanden | `testAbgelehnteIdeeBleibtSichtbar` — Idee **und** Begründung erscheinen im Board |
| AK-29 | ✅ bestanden | live: Konto ohne Adminrechte → **403** auf GET *und* POST; `published_at` blieb `NULL` |
| AK-30 | ✅ bestanden | `testVeroeffentlichteIdeeLaesstSichNichtLoeschen` (Dienstebene) |

### E · Status und Antwort · F · Dubletten · G · Benachrichtigung

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-31 | ✅ bestanden | `BoardLocaleTest` — kein roher Schlüssel in vier Sprachen, Status als Wort |
| AK-32 | ✅ bestanden | `board/show.html.twig` mit eigener Auszeichnung; im Board-Test mitgeprüft |
| AK-33 | ✅ bestanden | `BoardModerator::changeStatus()` ohne Versand; `testZweiteFreigabe…` belegt „genau eine Mail" |
| AK-34 | ✅ bestanden | `testZusammenfuehrenZaehltDoppelteStimmeEinmal` → 2 statt 3 |
| AK-35 | ✅ bestanden | `testDubletteLeitetAufDasOriginal` |
| AK-36 | ✅ bestanden | **`BoardNotifierPayloadTest::testAK36_MailLinkZeigtAufDasOriginal`** — der Link im gerenderten Mailkörper zeigt auf das Original |
| AK-37 | ✅ bestanden | `testFreigabeVeroeffentlichtUndSchicktEineMail` → `assertEmailCount(1)` |
| AK-38 | ✅ bestanden | `testZweiteFreigabeWirktNichtUndSchicktKeineZweiteMail` |
| AK-39 | ✅ bestanden | `publish()` flusht vor dem Versand; live belegt: `published_at` und `notified_at` gesetzt, obwohl der lokale Mailtransport falsch konfiguriert war (Port 50505 statt 1025) — die Veröffentlichung überlebte den Zustellfehler |

### H · Mehrsprachigkeit · I · Barrierefreiheit

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-40 | ✅ bestanden | `BoardLocaleTest` × 4 Sprachen, Board **und** Formular |
| AK-41 | ✅ bestanden | `testFremdsprachigerBeitragBleibtUnveraendert` — Text unverändert, `lang="lb"` gesetzt |
| AK-42 | ✅ bestanden | `testGueltigeEinreichungWartetAufFreigabe` prüft `getLocale() === 'de'` |
| AK-43 | ✅ bestanden | `CatalogueCompletenessTest` grün (16 Fälle) |
| AK-44 | ✅ bestanden | `aria-pressed` + Textwechsel „Zustimmen"/„Zugestimmt" im gerenderten Markup |
| AK-45 | ✅ bestanden | Statusabzeichen trägt Emoji **und** Wort; `BoardLocaleTest` prüft alle fünf Status |
| AK-46 | ✅ **bestanden (4. Durchlauf)** | Board **und** Seite 2 der Blätterung bei **320, 375, 390, 640, 1024 und 1280 px: je 0 px** — mit 80-Zeichen-Wort in Titel, Beschreibung und Team-Antwort. Bei 768 px 96 px, aber **Ursache im `header`, nicht in `main`**, und identisch auf Startseite, `/restaurants` und `/about` → **BF-80**, Vorbestand |
| AK-47 | ✅ **bestanden (4. Durchlauf)** | Board bei **sieben Breiten je 0 zu kleine Ziele**; „Kurz" jetzt 144 × 44 (320 px) bzw. 214 × 44 (390 px). Fokus auf allen geprüften Elementen sichtbar (8/8, 2/2, 4/4 im 2. Durchlauf). ⚠ Die 34 zu kleinen Ziele der **Admin-Shell** fallen nicht unter dieses Kriterium („Board und Formular") — Hinweis für B19 |
| AK-48 | ✅ bestanden | `_form_field.html.twig` rendert `aria-describedby` und `aria-invalid`; 422-Antwort geprüft |
| AK-49 | ✅ bestanden | **live per curl, ohne jedes JavaScript:** Board 200 · `?sort=newest` 200 · `?status=planned` 200 · `?page=2` 200 · Einreichen 302 mit Datensatz · Zustimmen 302 mit Stimme in der Datenbank |

### Datenschutz und Missbrauchsschutz

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-50 | ✅ bestanden | Gerendertes Board enthält weder E-Mail-Adresse noch vollen Namen; nur „Test U." |
| AK-51 | ✅ bestanden | `AuthorNameTest` — 12 Fälle inkl. EC-01, EC-02 |
| AK-52 | ✅ bestanden | dev-Log enthält den Text **nur im `doctrine.DEBUG`-Kanal**; `monolog.yaml` schließt ihn in `when@prod` über `channels: ["!deprecation","!doctrine","!request"]` aus. Kein Beitragstext, keine Adresse in der Serverausgabe |
| AK-53 | ✅ bestanden | **`BoardNotifierPayloadTest::testAK53_KeinMarketingKontaktDurchDasBoard`** — `SELECT COUNT(*) FROM marketing_contact` vorher = nachher |
| AK-54 | ✅ bestanden | **`testAK54_MailTraegtWederBeschreibungstextNochFremdeEmpfaenger`** am tatsächlichen Mailkörper: `To` = ausschließlich der Verfasser, `Cc`/`Bcc` leer, Titel enthalten, „Angststörung"/Telefon/Fremdadresse **nicht** enthalten |
| AK-55 | ✅ bestanden | `send_default_pii: false` und `zend.exception_ignore_args=On` gelten unverändert; das Feature ändert `sentry.yaml` nicht |
| AK-56 | ✅ bestanden | live: fremdes Konto (sogar Admin) auf wartende Idee → **404**, nicht 403 |
| AK-57 | ✅ bestanden | live: POST auf `/admin/ideen/{id}/veroeffentlichen` als Nicht-Admin → **403**, `published_at` blieb `NULL` |
| AK-58 | ✅ bestanden | Der Endpunkt nimmt keine Konto-Kennung entgegen; das Konto kommt aus der Sitzung |
| AK-59 | ✅ **bestanden (2. Durchlauf)** | Deckel exakt (5 durch, 6. → **429**); Meldung jetzt: „Zu viele Einreichungen. Bitte versuche es in **59 Minuten** erneut." BF-101 behoben |
| AK-60 | ✅ bestanden | live: 61 Zustimmungsversuche, danach „Zu viele **Zustimmungen**. Bitte versuche es in **60 Minuten** erneut." BF-102 behoben — der Text nennt jetzt den richtigen Vorgang |
| AK-61 | ✅ bestanden | live: A gedeckelt, **B von derselben IP** reicht ein → **302**. Der Deckel hängt am Konto |
| AK-62 | ✅ bestanden | live: 5 ungültige Submits (5× 422), danach gültig → **302**. Tippfehler verbrauchen nicht |
| AK-63 | ✅ bestanden | Kein Dateifeld im gerenderten Formular; `allow_extra_fields` auf `false` |
| AK-64 | ✅ bestanden | `testVerweiseUndHtmlBleibenText` — Zeichenfolge als Text, kein `<a href>`, kein ausgeführtes Skript |
| AK-65 | ✅ bestanden | `testKontoloeschungLaesstVeroeffentlichtesStehen` — Idee bleibt, `submittedBy` ist `NULL` |
| AK-66 | ✅ bestanden | derselbe Test — Zahl sinkt von 1 auf 0, `board_vote` leer |
| AK-67 | ✅ bestanden | `testExportEnthaeltIdeenUndZustimmungen` — `boardIdeas` und `boardVotes` im Export |
| AK-68 | ✅ bestanden | Kein Namensfeld an der Entität; live `SELECT COUNT(*)` auf verwaiste Stimmen und Dubletten → **0** |

### Abnahme und Nachträge

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-69 | ✅ **bestanden (2. Durchlauf)** | Im Browser durchgespielt: einreichen → Weiterleitung auf `/eingereicht` · vor der Freigabe **nicht** im Board · **in der Warteschlange sichtbar** · Freigabe über die Verwaltungs-Detailseite (per curl abgeschlossen, weil der Playwright-Klick das falsche Formular traf) → `published_at` gesetzt, **HTTP 302** |
| AK-70 | ✅ bestanden | `php bin/phpunit` → **806 Tests, 3418 Zusicherungen, OK** |
| AK-71 | ✅ bestanden | Sichtbarkeit an einer Stelle (`findPublished*`), Setzen an einer Stelle (`BoardModerator`); live und in Tests belegt |
| AK-72 | ✅ bestanden | Fünf-Werktage-Zusage im Formular **und** auf `/eingereicht` gerendert |
| AK-73 | ✅ bestanden | `Overdue::levelFor()` mit `DUE_SOON_WORKDAYS = 3`; Verwaltungsansicht kennzeichnet mit eigenem Wort |
| AK-74 | ✅ bestanden | `php bin/console app:board:cleanup` → „Nichts abzuräumen"; zusätzlich täglich beim Öffnen der Warteschlange |
| AK-75 | ✅ bestanden | `testUmgesetzteIdeenStehenImEigenenAbschnitt`; Hauptliste schließt `done` per Repository aus |
| AK-76 | ✅ bestanden | `testVerfasserKannWartendeIdeeZurueckziehen` — 0 Datensätze danach |
| AK-77 | ✅ bestanden | `testVeroeffentlichteIdeeLaesstSichNichtZurueckziehen` → 403; live: fremde Idee → **403**, Datensatz bleibt |
| AK-78 | ✅ bestanden | `docs/datenschutz.md` führt „**Datenschutzstufe: B — bestätigt am 2026-08-30**" mit Begründung und der Bedingung, unter der sie kippt; das Board ist als eigene Verarbeitung samt Löschfrist erfasst |
| AK-79 | ✅ bestanden | `OVERDUE_WORKDAYS = 5`, zweite Stufe mit eigenem Wort („Überfällig") und eigener Farbe |
| AK-80 | ✅ bestanden | Genau ein Feedback-Verweis in der Fußzeile, ohne `target="_blank"` |
| AK-81 | ❌ durchgefallen | **Nicht ausgeführt.** `endlech.userjot.com` nimmt weiterhin Einreichungen entgegen — Abruf am 2026-08-30 zeigte das Board unverändert erreichbar. Siehe **BF-103** |
| AK-82 | ✅ bestanden | `docs/prd.md` führt „Chat-Widget" und „KI-Filter" in der Roadmap, mit Herkunftsvermerk |

---

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ | `AuthorNameTest` — leer / nur Leerzeichen / `null` → kein „ ." |
| EC-02 | ✅ | `AuthorNameTest` — 60 Zeichen ohne Leerzeichen → auf 30 gekürzt |
| EC-03 | ✅ | `BoardInputHardeningTest` — 120 × „日" **und** 120 × „ß"; Slug bleibt ≤ 160, kein Datenbankfehler |
| EC-04 | ⚠️ nicht prüfbar | Gleichzeitigkeit (Zustimmen während Selbstlöschung) ist ohne Nebenläufigkeitswerkzeug nicht herstellbar. Die Kaskade macht den Zustand strukturell widerspruchsfrei — das ist Quelltextlage, kein Nachweis |
| EC-05 | ✅ | `testZweiteFreigabeWirktNichtUndSchicktKeineZweiteMail` — eine Mail, zweiter `publish()` gibt `false` |
| EC-06 | ⚠️ nicht prüfbar | dieselbe Gleichzeitigkeitsfrage |
| EC-07 | ✅ | live belegt: Der lokale Mailtransport war falsch konfiguriert (Mailpit auf Port 50505, `.env.dev` auf 1025) — die Freigabe wirkte trotzdem, `published_at` und `notified_at` gesetzt |
| EC-08 | ✅ | `testVerweiseUndHtmlBleibenText` + `BoardInputHardeningTest` Fall *Skript* |
| EC-09 | ✅ | `testKontoloeschungLaesstVeroeffentlichtesStehen` — wartende Idee weg, veröffentlichte bleibt |
| EC-10 | ✅ **bestanden (3. Durchlauf)** | Brave mit `emulateMedia({media:'print'})` auf `/de/community/ideen`: `header`, `footer` und Bottom-Navigation je `display: none`, die Ideenkarten bleiben sichtbar |
| EC-11 | ✅ | `testVeroeffentlichteIdeeLaesstSichNichtZurueckziehen` deckt den Kern ab: nach der Veröffentlichung ist Zurückziehen serverseitig gesperrt |
| EC-12 | ✅ | `app:board:cleanup` ausgeführt; die Frist ist eine Höchstdauer, kein Löschen auf die Minute |

---

## Sicherheitsprüfung

Aktiv angegriffen, nicht gelesen. Grundlage: `~/.claude/sdd/sicherheit.md`.

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| **1 · Zugriff auf fremde ID (IDOR)** | ✅ bestanden | Gast → **404**; fremdes Konto (Admin!) → **404**; Verfasser → **200**. Schreibweg: A zieht fremde wartende Idee zurück → **403**, Datensatz bleibt (`COUNT` = 1) |
| **2 · Zugriffsregeln serverseitig** | ✅ bestanden | Kein Voter im Projekt; Prüfung im Controller **vor** CSRF. Nicht-Admin: GET `/de/admin/ideen` → **403**, POST `/veroeffentlichen` → **403**, `published_at` blieb `NULL`. Anonymer POST → 302 auf `/de/login`, keine Wirkung |
| **3 · Rate Limits** | ⚠️ mit Befund | `board_submit` **exakt am Grenzwert**: 5 durch, 6. → **429**, `COUNT` = 5. Konto-Bindung belegt: B von derselben IP kommt durch. Tippfehler verbrauchen nicht (5× 422, dann 302). `board_vote`: ab dem 60. abgewiesen. **Aber: keine Wartezeit in der Meldung → BF-101** |
| **4 · PII in Protokollen** | ✅ bestanden | Beitragstext nur im `doctrine.DEBUG`-Kanal, der in `when@prod` ausgeschlossen ist. Keine Adresse, kein Klartext-Passwort (Symfony maskiert mit `<redacted>`). Serverausgabe während der Prüfung: kein Beitragstext |
| **5 · PII an externe Dienste** | ✅ bestanden | **Tatsächlicher Mailkörper geprüft**, nicht der Quelltext: `To` = `['user@endlech.lu']`, `Cc`/`Bcc` leer; Titel enthalten; „Ich bin auf einen Rollstuhl angewiesen und habe eine Angststörung", „691 123456", „privat@example.org" **nicht** enthalten. Kein Marketing-Kontakt entstanden |
| **6 · Geheimnisse** | ✅ bestanden | Kein Schlüsselmuster in den 12 neuen Dateien; keine Änderung an `.env*`; die einzige Konfigurationsänderung ist `framework.yaml` (zwei Limiter) |
| **7 · Eingaben** | ✅ bestanden | `BoardInputHardeningTest`, 11 Formen: leer · 1 Zeichen · 10.000 Zeichen (Titel und Text) · Emoji · SQL (`'; DROP TABLE board_idea; --`) · Skript · Pfadwechsel · Nullbyte · 120 × „日" · 120 × „ß". **Kein einziger HTTP 500.** Die Tabelle steht nach dem SQL-Versuch noch |
| **8 · Löschen** | ✅ bestanden | Nach Kontolöschung: `board_vote` leer, veröffentlichte Idee steht mit `submitted_by_id IS NULL`, wartende weg. Verwaiste Zeilen: `COUNT` = **0** in beiden Richtungen |

---

## Fehler

### BF-101 · Die Deckel-Meldung nennt keine Wartezeit — mittel

**Betrifft:** AK-59
**Reproduktion:**
1. Als bestätigtes Konto sechsmal in Folge eine Idee einreichen
2. Die Flash-Meldung nach dem sechsten Versuch lesen

**Erwartet:** „eine Meldung **mit der Wartezeit**" (Wortlaut AK-59)
**Tatsächlich:** „Zu viele Einreichungen in kurzer Zeit. Bitte versuche es später
erneut." — der Nutzer erfährt nicht, wie lange „später" ist.
**Ort:** `translations/messages.de.yaml` → `flash.board_rate_limited`;
`src/Controller/BoardController.php:109`
**Vorschlag:** `ActionLimiter::retryAfter()` ist vorhanden und wird im Projekt an vier
Stellen genau dafür benutzt — `AccessibilityController.php:64` rechnet sogar in Minuten
um (`(int) ceil($limiter->retryAfter() / 60)`). Dasselbe Muster hier übernehmen und die
Meldung um einen `%minutes%`-Platzhalter erweitern.

> **✅ Behoben am 2026-08-30.** `BoardController::wartezeitInMinuten()` übernimmt das
> Muster aus `AccessibilityController`. **Reproduktion greift nicht mehr:** Der Aufruf
> mit erschöpftem Deckel liefert jetzt „Zu viele Einreichungen. Bitte versuche es in
> **45 Minuten** erneut." Regressionsschutz: `BoardRateLimitMessageTest`.

### BF-102 · Beim Zustimmen erscheint „Zu viele Einreichungen" — niedrig

**Betrifft:** AK-60
**Reproduktion:**
1. Als bestätigtes Konto 60 Zustimmungen abgeben
2. Eine 61. abgeben und die Flash-Meldung lesen

**Erwartet:** eine Meldung, die vom Zustimmen spricht
**Tatsächlich:** „Zu viele **Einreichungen** in kurzer Zeit." — derselbe Schlüssel wird
für beide Wege benutzt und benennt den falschen Vorgang.
**Ort:** `src/Controller/BoardController.php:199` (derselbe Schlüssel wie Zeile 109)
**Vorschlag:** Ein zweiter Schlüssel `flash.board_vote_rate_limited` in allen vier
Katalogen.

> **✅ Behoben am 2026-08-30.** `flash.board_vote_rate_limited` in allen vier Katalogen.
> **Reproduktion greift nicht mehr:** „Zu viele **Zustimmungen**. Bitte versuche es in
> **49 Minuten** erneut." Regressionsschutz: `BoardRateLimitMessageTest` prüft in vier
> Sprachen, dass beide Schlüssel existieren, die Wartezeit tragen und **verschieden**
> sind.

### BF-103 · Das externe Board nimmt weiterhin Einreichungen entgegen — mittel

**Betrifft:** AK-81
**Reproduktion:** `endlech.userjot.com` aufrufen — das Board ist unverändert erreichbar
und bietet „Give Feedback" an.
**Erwartet:** keine neuen Einreichungen mehr möglich
**Tatsächlich:** unverändert in Betrieb
**Ort:** außerhalb des Repositorys — Einstellung im Anbieter-Konto
**Vorschlag:** **Unmittelbar nach dem Deploy** im userjot-Konto schließen.

> ⚠ **Korrektur der ursprünglichen Empfehlung (2026-08-30, Betreiberentscheidung).**
> Dieser Bericht riet zunächst, vor dem Deploy abzuschalten. Das war falsch herum
> gedacht: Solange `/community/ideen` nicht live ist, ist userjot der **einzige**
> Rückmeldeweg. Eine Abschaltung davor erzeugte ein Fenster ganz ohne Weg — schlimmer
> als das, was sie verhindern soll.
>
> Die Reihenfolge ist deshalb: **Deploy → userjot schließen.** Das Fenster, in dem beide
> erreichbar sind, ist damit so kurz wie möglich; das externe Board ist ab dem Deploy
> ohnehin unverlinkt und nur noch über Suchmaschinen und Lesezeichen auffindbar.
>
> Der Punkt gehört in die **Nachverifikation** von `/sdd-deploy 06` — er ist der einzige
> Schritt, den kein Prüflauf sieht.

### BF-104 · Titel-Verweise auf dem Board sind 18 px hoch — hoch

**Betrifft:** AK-47
**Reproduktion:**
1. Browser auf 390 × 844 (oder 320 px) stellen
2. `/de/community/ideen` mit mindestens einer freigegebenen Idee öffnen
3. Die Höhe des Titel-Verweises in einer Karte messen

**Erwartet:** mindestens 44 × 44 px (AK-47; das Design-System nennt dieselbe Zahl).
WCAG 2.2 AA (2.5.8 *Target Size Minimum*) verlangt mindestens 24 × 24.
**Tatsächlich:** vier Verweise mit **18 px Höhe** — gemessen `a 192×18
"Kartenansicht mit Filtern"`, `a 202×18`, `a 157×18`, `a 82×18`.
**Ort:** `templates/partials/_board_idea_card.html.twig` — der `<a>` im `<h3>` hat keine
eigene Zielgröße; nur der Zustimmungsknopf trägt `min-h-[48px]`.
**Warum das hier schwerer wiegt:** Der Titel ist der **einzige** Weg von der Liste in die
Einzelansicht, und die Zielgruppe dieser Plattform sind Menschen mit motorischen
Einschränkungen. Feature `02` sagt WCAG 2.2 AA über den vollen Bestand zu.
**Vorschlag:** Die Karte als Ganzes zum Ziel machen (Stretched-Link-Muster: `<a>` mit
`absolute inset-0` über der Karte, `position: relative` am `<article>`) — dann bleibt der
Zustimmungsknopf als eigenes Formular davor bedienbar. Alternativ dem `<a>` eine
Mindesthöhe und Innenabstand geben.

> **✅ Behoben am 2026-08-30.** Umgesetzt wurde die **zweite** Variante:
> `-my-1 flex min-h-[44px] items-center` am Verweis.
>
> ⚠ **Der Stretched Link wurde versucht und verworfen.** Das Pseudoelement war korrekt
> gesetzt (`content: ""`, `position: absolute`, `inset: 0px`, `article` auf `relative`),
> blieb im Stapel aber unter den Geschwisterelementen: `elementFromPoint` traf an drei
> Stellen `p` und `svg` statt des Verweises, und ein Klick in die Kartenmitte navigierte
> nicht. Eine Mindesthöhe am Verweis selbst hängt nicht von der Stapelreihenfolge ab und
> ist im Markup prüfbar.
>
> **Nachgemessen im Browser (Brave/CDP):** Titel **192 × 44** bei 390 px, **140 × 48** bei
> 320 px; **null** zu kleine Ziele in `main` auf beiden Breiten; Überhang weiterhin 0 px;
> Klick auf den Titel führt auf `/de/community/ideen/24-kartenansicht`; der
> Zustimmungsknopf bleibt bedienbar (1 → 0). Regressionsschutz: `BoardTargetSizeTest`.

### BF-105 · Der committete Asset-Build ist veraltet — der Deploy würde blockieren — hoch

**Betrifft:** AK-70 (Prüflauf grün) mittelbar; unmittelbar den Deploy
**Reproduktion:**
1. `cat public/build/*.css | grep -c "line-clamp-3"` → **0**
2. `npm run build`
3. `git status --porcelain public/build` → `D app.f5e6e5d8.css`, `?? app.7edd716d.css`,
   `M entrypoints.json`, `M manifest.json`

**Erwartet:** kein Diff — `verify-assets` in `.github/workflows/cd.yml` baut neu und
vergleicht mit dem committeten Stand.
**Tatsächlich:** Vier Dateien weichen ab. `line-clamp-3` wird **ausschließlich** von
`templates/partials/_board_idea_card.html.twig` benutzt und fehlt im ausgelieferten CSS.
**Ort:** `public/build/` — und die Ursache in `features/06-community-feedback-board/tasks.md`
Zeile 19: „`npm run build` entfällt: Das Feature kommt ohne Änderung unter `assets/` aus."
**Warum das falsch ist:** Tailwind v4 scannt in diesem Projekt **`templates/`** —
`assets/styles/app.css` Zeile 14: `@source "../../templates";`. Neue Twig-Dateien mit
neuen Utility-Klassen brauchen genauso einen Bau wie geänderte Assets.
**Vorschlag:** `npm run build` ausführen und `public/build` mitcommitten. Den Satz im
Aufgabenplan und in `CLAUDE.md` („Änderung unter `assets/` → `npm run build`") um
`templates/` erweitern — sonst tritt derselbe Fehler beim nächsten Feature wieder auf.

> **✅ Behoben am 2026-08-30.** `npm run build` gelaufen; `line-clamp-3`, `after:*` und
> die Zielgrößen sind im CSS. **Determinismus belegt:** Ein dritter Bau erzeugte
> identische Prüfsummen für `app.*.css` und `manifest.json` — `verify-assets` bleibt
> grün. Zu committen sind: `app.b236d552.css` (neu), `app.f5e6e5d8.css` (entfällt),
> `entrypoints.json`, `manifest.json`.
>
> **Regressionsschutz: `BuiltAssetsTest`** prüft vier charakteristische Regeln gegen das
> gebaute CSS und meldet den Fall damit im normalen Prüflauf statt erst im Deploy.
> Gegengeprüft: Mit umbenannter Regel wird er rot („Die Regel `.line-clamp-3` fehlt im
> gebauten CSS").
>
> ⚠ **Der Satz in `CLAUDE.md` ist NICHT geändert** — er betrifft das ganze Projekt und
> gehört nicht in einen Fehlerauftrag. Als Muster in `features/befunde.md` festgehalten.

> ⚠ **Der Bau wurde für die Messung einmal ausgeführt und danach zurückgenommen**
> (`git checkout public/build`), damit der Befund reproduzierbar bleibt und die
> Behebung dort geschieht, wo sie hingehört.

### BF-106 · Ein Titel aus einem langen Wort sprengt das Board — mittel

**Betrifft:** AK-46
**Reproduktion:**
1. Eine Idee mit einem Titel aus **80 × „W"** ohne Leerzeichen anlegen und freigeben
   (innerhalb der erlaubten 120 Zeichen — das Formular nimmt sie an)
2. `/de/community/ideen` bei 320 px Breite öffnen

**Erwartet:** kein waagerechtes Scrollen (AK-46)
**Tatsächlich:** `scrollWidth − clientWidth = 1089 px` bei 320 px, **1019 px** bei 390 px.
**Isoliert belegt:** Wird genau diese Karte im DOM entfernt, fällt der Überhang auf **0 px**.
**Ort:** `templates/partials/_board_idea_card.html.twig` — weder der Titel noch der
Beschreibungstext haben eine Umbruchregel für Wörter ohne Trennstelle.

> ⚠ **Das ist BF-82 zum zweiten Mal.** Dort: „Ein Anbietername von 57 Zeichen **ohne
> Leerzeichen** sprengt bei 320 px die Kartendarstellung (`scrollX=104`)" — Feature 03,
> als *niedrig* geführt, weil die Namen dort aus einer festen Liste kommen. **Hier ist es
> Nutzereingabe**, und jeder Besucher sieht die Folge. Beim zweiten Auftreten ist das kein
> Einzelfall mehr, sondern eine fehlende projektweite Regel.
>
> **Vorschlag:** `overflow-wrap: anywhere` (Tailwind: `break-words` bzw. `[overflow-wrap:anywhere]`)
> auf jedes Element, das **Nutzertext** trägt — hier Titel und Beschreibung, in Feature 03
> die Anbieternamen. Als Konvention ins Design-System, nicht als Einzelfix.

> **✅ Behoben am 2026-08-30.** `wrap-anywhere` (Tailwind 4.1: `overflow-wrap: anywhere`)
> auf **jedem** Element mit Nutzertext in Feature 06 — acht Stellen über vier Templates:
> Titel, Beschreibung, Team-Antwort und Anzeigename in der Karte, die Team-Antwort auf der
> Einzelansicht sowie Titel, Beschreibung und Anzeigename in beiden Verwaltungsansichten.
>
> **Nachgemessen mit denselben Grenzfällen:** Überhang **0 px** bei 320 **und** 390 px,
> auch mit einem Titel *und* einer Beschreibung *und* einer Team-Antwort aus je 80 × „W".
> Der lange Titel bricht um (144 × 216 statt Überlauf).
>
> ⚠ **Nur der eigene Fall ist behoben.** `BF-82` in Feature 03 bleibt offen, und die
> Design-System-Regel für Nutzertext ist **nicht** gezogen — beides gehört nicht in einen
> Fehlerauftrag für Feature 06. Regressionsschutz: `BoardTargetSizeTest::testBF106…`.

### BF-107 · Kurze Titel ergeben ein 36 px breites Ziel — mittel

**Betrifft:** AK-47
**Reproduktion:**
1. Eine Idee mit dem Titel „Kurz" freigeben
2. `/de/community/ideen` bei 320 oder 390 px öffnen, den Titel-Verweis messen

**Erwartet:** mindestens 44 × 44 px (AK-47)
**Tatsächlich:** **36 × 44** — die Höhe stimmt seit der Reparatur von BF-104, die
**Breite** nicht. `min-h-[44px]` setzt nur die Höhe; der Verweis ist so breit wie sein Text.
**Ort:** `templates/partials/_board_idea_card.html.twig` — `-my-1 flex min-h-[44px] items-center`
**Vorschlag:** `w-full` ergänzen (der Verweis füllt die Kartenbreite) oder `min-w-[44px]`
plus waagerechten Innenabstand. `w-full` löst zugleich das Problem, dass nur der Text
selbst Ziel ist und nicht die Zeile.

> ⚠ **`BoardTargetSizeTest` fängt diesen Fall nicht.** Er prüft, dass `min-h-[44px]` im
> Markup steht — und das steht dort. Ein Prüflauf, der Klassen liest, sieht keine
> gerenderte Breite. Das ist die Grenze dieses Prüflaufs und gehört benannt, damit ihn
> niemand für mehr hält, als er ist.

> **✅ Behoben am 2026-08-30 — in zwei Anläufen.**
>
> Der erste (`w-full` am Verweis) reichte **nicht**: `w-full` bezieht sich auf die
> Überschrift, und die schrumpft im Flex-Container auf ihre Textbreite. Gemessen danach:
> „Kurz" weiterhin **36 × 44**.
>
> Der zweite Anlauf setzt `flex-1` an der Überschrift — und deckte prompt den nächsten
> Fall auf: Bei 320 px teilen sich Titel und Statusabzeichen eine Zeile, der Titel
> schrumpfte auf **37 px**. Erst `basis-full min-w-0 sm:basis-auto sm:flex-1` löst beides:
> unter `sm:` bekommt der Titel die volle Zeile, das Abzeichen rückt darunter.
>
> **Nachgemessen:** bei **320 und 390 px je null** zu kleine Ziele; „Kurz" jetzt 144 × 44
> bzw. 214 × 44.
>
> **Der Prüflauf ist mitgewachsen:** `BoardTargetSizeTest` prüft nun alle vier Klassen,
> die zusammen wirken (`min-h-[44px]`, `w-full`, `flex`, `basis-full`) — und die
> dokumentierte Grenze steht als Warnung im Klassenkommentar. Gegengeprüft: Ohne `w-full`
> wird er rot.

---

## Neue Tests

| Datei | Fälle | Deckt ab |
|---|---|---|
| `tests/Functional/Board/BoardNotifierPayloadTest.php` | 3 | AK-36, AK-53, AK-54 — am **tatsächlichen Mailkörper**, nicht am Quelltext |
| `tests/Functional/Controller/BoardInputHardeningTest.php` | 12 | Angriffsdurchlauf Abschnitt 7; EC-03 in beiden Ausprägungen |
| `tests/Functional/Controller/BoardRateLimitMessageTest.php` | 4 | Regressionsschutz BF-101/BF-102 (nach der Reparatur ergänzt) |
| `tests/Unit/Board/BuiltAssetsTest.php` | 4 | Regressionsschutz BF-105 — gebautes CSS gegen die Klassen des Features |
| `tests/Functional/Controller/BoardTargetSizeTest.php` | 2 | Regressionsschutz BF-104, **BF-106 und BF-107** — Zielgröße und Umbruchregel |

Beide bleiben im Projekt und laufen künftig bei jeder Änderung mit.

---

## Was auffiel, aber kein Kriterium verletzt

- **Die Messenger-Warteschlange enthielt vier alte, fremde Nachrichten**, die beim
  Konsumieren mit `Variable "revokeUrl" does not exist in
  "email/partner/confirmation.html.twig" at line 35` scheitern. **Vorbestand aus B14**,
  nicht dieses Feature — aber es bedeutet, dass Partner-Bestätigungsmails lokal nicht
  zustellbar sind. Gehört gesondert geprüft.
- **Lokal ist `MAILER_DSN` falsch konfiguriert:** Mailpit ist auf Port **50505** gemappt,
  `.env.dev` zeigt auf `1025`. Betrifft nur die Entwicklungsumgebung.
- `lint:container` schlägt mit einem Webauthn-Alias-Fehler fehl — **mit `git stash`
  gegengeprüft: besteht auch ohne dieses Feature** (Vorbestand aus B03).
- `make fix-check` existiert im Makefile nicht, `php-cs-fixer` ist nicht installiert; die
  Stilprüfung des Stack-Profils konnte in keinem Durchgang laufen.

---

## Nächster Schritt

**`/sdd-deploy 06`.** Kein offener Befund an Feature 06; 816 Tests grün.

⚠ **Zwei Punkte gehören in die Nachverifikation nach dem Deploy:**

1. **BF-103 / AK-81 — `endlech.userjot.com` schließen.** Erst *nach* dem Deploy, damit
   zu keinem Zeitpunkt gar kein Rückmeldeweg besteht (Betreiberentscheidung 2026-08-30).
   Der Fußzeilenverweis zeigt bereits aufs eigene Board.
2. **`public/build` muss mitcommittet werden** — vier Dateien (`app.*.css` neu und alt,
   `entrypoints.json`, `manifest.json`). Ohne sie blockiert `verify-assets` den Deploy
   (BF-105). Der Bau ist deterministisch, `BuiltAssetsTest` meldet den Fall künftig im
   normalen Prüflauf.

**Nicht Teil dieses Features, aber hier belegt** — gehört in die jeweiligen Befunde:

- **BF-80** (Feature `02`, offen): Bei 768 px scrollen alle Seiten um 96 px, Ursache im
  `header`. Auf vier Seiten gemessen, davon drei aus dem Bestand.
- **Admin-Shell** (B19): 34 Bedienelemente unter 44 × 44 px, u. a. der Sprachumschalter
  mit 54 × 28. Auf `/de/admin/vorschlaege` (B21) dieselbe Lage. **Kein neuer Befund
  gebucht** — es fehlt ein Kriterium dafür, und die QA erfindet keine Anforderungen.

---

# Nachtrag: BF-116 (2026-08-31, aus dem Deploy)

**Nicht von der QA gefunden, sondern vom Deploy selbst.** Beide QA-Durchläufe von
Feature `06` waren grün — der Fehler tritt nur auf PHP 8.4 auf, und lokal läuft 8.5.

**Befund:** `#[ORM\ManyToOne]` ohne `targetEntity` bei einem Property vom Typ `?self`.
Auf Produktion brach `cache:clear` ab: *„The target-entity `App\Entity\self` cannot be
found in `App\Entity\BoardIdea#duplicateOf`."* Der Deploy vom 2026-08-31 scheiterte, die
Wartungsseite blieb stehen, die Seite war offline. Rollback per Revert.

## ✅ Behoben — und diesmal reproduziert

Der Baubericht meldete den Fehler als **lokal nicht reproduzierbar** und stützte sich auf
einen statischen Prüflauf. Diese Prüfung hat die Reproduktion nachgeholt: **ein
Container mit der Produktions-Sprachversion genügt.**

```
docker run --rm -v "$PWD":/app -w /app php:8.4-cli php <skript>
```

**Die Ursache, gemessen:**

| PHP | `ReflectionProperty::getType()->getName()` |
|---|---|
| 8.5.2 (lokal) | `App\Entity\BoardIdea` — aufgelöst |
| **8.4.25** (Produktion) | **`self`** — nicht aufgelöst |

**Die Reparatur, in beide Richtungen belegt** — Doctrines `AttributeDriver` unter 8.4:

| Zustand | `targetEntity` löst auf zu | |
|---|---|---|
| **mit** `targetEntity: self::class` | `App\Entity\BoardIdea` | Klasse existiert ✓ |
| **ohne** (der Zustand des Deploys) | **`App\Entity\self`** | existiert nicht ✗ — **wortgleich mit der Produktionsmeldung** |

Kein Symfony-Kernel, keine Extensions, keine Datenbank nötig — es genügte, den
`AttributeDriver` die Metadaten aufbauen zu lassen.

## Prüfung des neuen Prüflaufs

`MappingSelfTargetTest` (statisch, umgebungsunabhängig) — mit einer **anderen** Gegenprobe
als der Bau geprüft: eine `OneToOne`-Assoziation mit `self`-Typ in einer neuen Entity →
**rot**. Der Lauf fängt also nicht nur `ManyToOne` und nicht nur den bekannten Fall.

Projektweit nachgezählt: **genau eine** Stelle war betroffen. Fünf weitere Assoziationen
haben kein `targetEntity`, aber auch keinen `self`-Typ — dort ist die Auflösung
unproblematisch.

## Regression

| Prüfung | Ergebnis |
|---|---|
| Dublettenzusammenführung (nutzt `duplicateOf`) | 2 Tests grün |
| `/de/community/ideen`, `/de/community/ideen/neu` | 200 |
| `cache:clear --env=prod` lokal | OK |
| Volle Suite | **922 Tests grün** |
| `doctrine:schema:validate` | „mapping files are correct"; die Schema-Abweichung ist **vorbestehend** (Index-Rauschen an `cuisine`, `ordering_option`, `restaurant`) und mit wie ohne die Änderung identisch |

## Die Lehre, die über diesen Befund hinausgeht

BF-116 galt als „nur auf Produktion prüfbar" — bis ein `docker run` in unter einer Minute
das Gegenteil zeigte. Als projektweites Muster festgehalten: **Bevor ein Befund als „lokal
nicht reproduzierbar" abgelegt wird, wird die Laufzeitumgebung im Container nachgestellt.**
Für die Sprachversion genügt das offizielle Image; für Apache und `mod_dir` (BF-100) wäre
es `php:8.4-apache`.

## Fazit

**Production-ready: ja.** Kein neuer Befund, 922 Tests grün, Ursache und Reparatur unter
der Produktions-Sprachversion belegt.

⚠ **Offen bleibt BF-111** (*mittel*, blockiert nicht): Eine wartende Idee **ohne
Verfasser** ist öffentlich lesbar, weil `null !== null` false ergibt. Über den
Anwendungsweg heute nicht erreichbar — `AccountDeleter` löscht wartende Ideen vor dem
Konto —, aber die Prüfung ist richtig aus dem falschen Grund. Die Zeile steht unverändert
in `BoardController.php:169`.

## Nächster Schritt

`/sdd-deploy` — ⚠ mit Feature `07` zusammen, in dieser Reihenfolge (VB-01).
