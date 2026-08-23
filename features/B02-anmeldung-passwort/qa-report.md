# B02 · Anmeldung mit Passwort — Testbericht

Stand: 2026-08-24 · Geprüft gegen `spec.md` vom 2026-08-23 (Rückerfassung)
Umgebung: lokal, `symfony server` auf `:8000`, MySQL 8.0 in Docker, Fixture-Stand

## Fazit

**Production-ready: nein**

Die Anmeldung selbst ist solide gebaut: Enumerationsschutz greift, die Session-ID wechselt,
CSRF ist Pflicht, die Rollenschranke sitzt serverseitig, Eingaben werden sauber abgewiesen
und escaped. 14 von 17 Kriterien bestanden.

**Der eine Fund wiegt trotzdem schwer.** Zwanzig Fehlversuche gegen `admin@endlech.lu`
wurden alle angenommen, danach griff das richtige Passwort sofort — das Konto war nie
gesperrt. Dieselben Zugangsdaten gegen `/api/v1/auth/login` werden ab dem sechsten Versuch
mit 429 abgewiesen. Geschützt ist also der Weg, den eine App nimmt; ungeschützt der, den
ein Browser nimmt. Und dahinter steht ein Verwaltungszugang ohne zweite Stufe.

**Ein Fund geht gegen die Spezifikation, nicht gegen den Code.** EC-04 behauptete, ein
`REMEMBERME`-Cookie überlebe eine Passwortänderung. Es überlebt sie nicht — und eine
fremde **Sitzung** auch nicht. Das Verhalten ist besser als rekonstruiert; die
Rekonstruktion war falsch, und sie ist es an zwei weiteren Stellen in B04.

Nächster Schritt: `/sdd-build B02` mit BUG-10.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 17 von 17 |
| davon bestanden | 14 |
| davon durchgefallen | 3 (AK-11, AK-12, AK-13) |
| **nicht prüfbar** | 0 |
| Edge Cases belegt | 4 von 4 (EC-04 widerlegt die Spec) |
| Tests neu geschrieben | 4 (1 übersprungen bis zur Reparatur) |
| Tests grün | 321, 1 übersprungen |

## Akzeptanzkriterien im Einzelnen

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | `GET /de/login` → 200; im Markup `_username`, `_password`, `_remember_me`, `_csrf_token` **und** `_assertion`; Passkey-Bereich trägt `class="hidden"` (Feature-Detection) |
| AK-02 | ✅ bestanden | angemeldet → `302 → /de/` |
| AK-03 | ✅ bestanden | `user@endlech.lu` + `user123` → `302 → /de/`; `/de/profile` zeigt die Adresse im Formular |
| AK-04 | ✅ bestanden | falsches Passwort → `302 → /de/login`, Meldung „Fehlerhafte Zugangsdaten.", `_username` bleibt mit `'user@endlech.lu'` gefüllt |
| AK-05 | ✅ bestanden | unbekannte Adresse → **wortgleiche** Meldung. Automatisiert in `testAk05MeldungVerraetNichtObDasKontoExistiert` |
| AK-06 | ✅ bestanden | `REMEMBERME`-Cookie gesetzt, Laufzeit **7,00 Tage**, `HttpOnly` |
| AK-07 | ✅ bestanden | Anmeldung ohne `_csrf_token` → `302 → /de/login`, `/de/profile` danach 302 (nicht angemeldet) |
| AK-08 | ✅ bestanden | `/de/logout` → `302 → /de/`, danach `/de/profile` → 302 |
| AK-09 | ✅ bestanden | `/de/profile`, `/de/admin`, `/de/community/suggest` als Gast → je `302 → /de/login`; nach der Anmeldung Weiterleitung auf **`/de/profile`** (Zielpfad gemerkt) |
| AK-10 | ✅ bestanden | `ROLE_USER` auf `/de/admin` → **403**; `ROLE_ADMIN` → 200. Test `testAk10RolleUserErreichtDenAdminbereichNicht` |
| AK-11 | ❌ durchgefallen | **20 Fehlversuche, 20× angenommen**, danach reguläre Anmeldung erfolgreich (`/de/admin` → 200). Gegenprobe API: `401 401 401 401 401 429 429 429`. `login_throttling` nicht konfiguriert → **BUG-10** |
| AK-12 | ❌ durchgefallen | Abmeldung per GET mit `Referer`/`Origin` einer fremden Domain → `302`, danach `/de/profile` → 302. `logout.enable_csrf` nicht gesetzt, der Link ist ein `<a href>` → **BUG-11** |
| AK-13 | ❌ durchgefallen | `unverified@endlech.lu` meldet sich an, `/de/profile` → 200, nur `/community/suggest` leitet auf `/de/verify`. Entspricht **BF-03**, seit 2026-08-23 als Betreiberentscheidung akzeptiert |
| AK-14 | ✅ bestanden | `user123`, `admin123`, `unverified123`, `falsch1`, `falsch20` → je **0 Treffer** in `var/log/dev.log`. Die zwei `_password`-Treffer sind der Routenname `app_profile_password` |
| AK-15 | ✅ bestanden | `PHPSESSID` mit `HttpOnly`; `Secure=FALSE` korrekt bei lokalem HTTP (`cookie_secure: auto`) |
| AK-16 | ✅ bestanden | Session-ID vor `b7874f2b…`, nach `9bb1fdb2…` — gewechselt |
| AK-17 | ✅ bestanden | gelesen werden `email`, `password` (Hash), `roles`; `is_verified` **nicht** (siehe AK-13). Es entstehen keine neuen Daten |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ belegt | Formular mit `_assertion` **und** korrektem `_username`/`_password` → der Passkey-Weg greift und scheitert („Die Anmeldung mit Passkey hat nicht geklappt"), das Passwort wird **nicht** geprüft |
| EC-02 | ✅ belegt | Gast auf `/de/logout` → `302 → /de/` |
| EC-03 | ✅ belegt | `USER@ENDLECH.LU` → Anmeldung gelingt, `/de/profile` → 200 |
| EC-04 | ❌ **Spec widerlegt** | siehe unten |

### EC-04 · Die Rekonstruktion war falsch

Die Spec sagt: *„`remember_me`-Cookie überlebt eine Passwortänderung (siehe B04/FB)."*

Gemessen:

| Nach der Passwortänderung in Sitzung A | Ergebnis |
|---|---|
| Sitzung A (hat geändert) | `/de/profile` → **200** — bleibt angemeldet |
| Sitzung B (anderer Browser) | `/de/profile` → **302** — abgemeldet |
| nur `REMEMBERME` aus Sitzung B | wird **nicht** mehr als angemeldet erkannt |

Symfony entwertet fremde Sitzungen und `remember_me`-Cookies von sich aus: Der
Sicherheitskontext vergleicht bei jedem Request den serialisierten Nutzer aus der Sitzung
mit dem frisch geladenen, und die `remember_me`-Signatur schließt den Passwort-Hash ein.

**Aus dem Projektcode war das nicht zu sehen** — `ProfileController::changePassword()` ruft
tatsächlich nichts dergleichen auf. Genau daraus hatte die Rückerfassung geschlossen, es
geschehe nichts. Das ist der Fall, vor dem `sdd-erfassen` warnt: Eine Rekonstruktion kann
selbst falsch sein.

**Betroffen sind drei Stellen:**

| Stelle | Behauptung | Wirklichkeit |
|---|---|---|
| B02/EC-04 | „Cookie überlebt die Änderung" | es wird entwertet |
| B04/AK-13 ⚠ | „alle anderen Sitzungen und alle `REMEMBERME`-Cookies bleiben gültig" | beide werden entwertet |
| B04/FB-04 | „Keine Sitzungsinvalidierung bei Passwortänderung" | Symfony erledigt sie |

Der neue Test `testEc04PasswortaenderungEntwertetFremdeSitzungen` hält das fest — die
Eigenschaft ist im Projektcode unsichtbar und könnte bei einem Symfony-Update
stillschweigend wegfallen. Gegengeprüft: Ohne die Passwortänderung schlägt der Test fehl,
er misst also tatsächlich.

## Sicherheitsprüfung

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Zugriff auf fremde ID (IDOR) | bestanden | B02 adressiert kein Objekt über eine ID |
| Zugriffsregeln serverseitig | bestanden | `ROLE_USER` → `/de/admin` 403; Gast → 302 auf drei geschützten Pfaden |
| Rate Limit greift | **BUG-10** | 20 Fehlversuche, 20× angenommen; API zum Vergleich ab dem sechsten 429 |
| PII in Logs | bestanden | fünf Passwörter geprüft, je 0 Treffer |
| PII an externe Dienste | bestanden | B02 verschickt nichts |
| Geheimnisse im Repository | siehe Hinweis | `.env.local` nicht getrackt; ⚠ `APP_SECRET` lokal identisch mit dem committeten dev-Wert |
| Eingaben | bestanden | `' OR '1'='1`, `admin@endlech.lu'--`, XSS, 10.000 Zeichen, Null-Byte → alle abgewiesen; Ausgabe als `&lt;script&gt;`; Tabelle intakt (3 Konten) |
| Sitzungsverhalten | bestanden | Session-ID wechselt; fremde Sitzung überlebt keine Passwortänderung |

## Fehler

### BUG-10 · Anmeldung ohne Sperre nach Fehlversuchen — hoch

**Betrifft:** AK-11, FB-01
**Reproduktion:**
1. 20× `/de/login` mit `admin@endlech.lu` und falschem Passwort absenden
2. danach einmal mit `admin123`
**Erwartet:** Sperre nach wenigen Versuchen (Katalog: fünf in fünfzehn Minuten)
**Tatsächlich:** 20× `302`, keine Sperre; der 21. Versuch mit korrektem Passwort führt
direkt in den Verwaltungsbereich (`/de/admin` → 200)
**Ort:** `config/packages/security.yaml` — die Firewall `main` konfiguriert kein
`login_throttling`, obwohl Symfony es mitbringt
**Warum es hier schwerer wiegt:** Der Verwaltungszugang hängt an genau einem Konto
(B19/FB-01, es gibt keine Rollenverwaltung), es gibt keine zweite Stufe (FB-03) und keine
Benachrichtigung bei fremder Anmeldung (FB-05). Die Passwortregel ist eine Mindestlänge
von acht Zeichen ohne Leak-Listen-Prüfung (B01/FB-08).
**Vorschlag:** `login_throttling: max_attempts: 5` in der Firewall `main`, dazu ein
großzügiger `when@test`-Wert, sonst färbt es die Suite rot.

### BUG-11 · Abmelden ohne CSRF-Schutz — niedrig

**Betrifft:** AK-12, FB-02
**Reproduktion:** Als angemeldeter Nutzer `/de/logout` mit
`Referer: https://boese.example/falle.html` aufrufen
**Erwartet:** Abweisung oder wenigstens ein Token
**Tatsächlich:** `302 → /de/`, danach `/de/profile` → 302 — abgemeldet
**Ort:** `config/packages/security.yaml`, `logout:` ohne `enable_csrf`; der Abmeldelink in
`templates/base.html.twig:122` ist ein `<a href>`, also GET
**Einordnung:** Der Schaden ist gering — ein Angreifer meldet jemanden ab, mehr nicht. Es
bleibt eine fehlende Härtung, kein Datenabfluss.
**Vorschlag:** `logout.enable_csrf: true` und den Link zu einem POST-Formular machen.

### BUG-12 · „Angemeldet bleiben" wirkt für den einzigen geschützten Bereich nicht — mittel

**Betrifft:** kein AK (Hinweis aus der Prüfung von AK-06; im `design.md` als
„besonders zu prüfen" vermerkt)
**Reproduktion:**
1. Mit „Angemeldet bleiben" anmelden
2. Das Sitzungs-Cookie löschen, nur `REMEMBERME` behalten
3. `/de/` und dann `/de/profile` aufrufen
**Erwartet:** Wer „angemeldet bleiben" wählt, bleibt angemeldet
**Tatsächlich:** `/de/` → 200 und die **Kopfzeile zeigt „Abmelden"** — der Nutzer gilt als
angemeldet. `/de/profile` → `302 → /de/login`
**Ort:** `config/packages/security.yaml`, `access_control` `^/[a-z]{2}/profile` =
`IS_AUTHENTICATED_FULLY`. Das schließt `remember_me`-Sitzungen aus.
**Warum das ein Befund ist:** Sicherheitstechnisch ist die strenge Wahl richtig. Der
Widerspruch liegt in der Oberfläche: Sie behauptet einen Zustand, den der Klick auf
„Profil" widerlegt. Entweder die Kopfzeile unterscheidet die beiden Zustände, oder
`/profile` akzeptiert `IS_AUTHENTICATED_REMEMBERED` — beides ist besser als der jetzige
Widerspruch.

## Neue Tests

| Datei | Fälle | Deckt ab |
|---|---|---|
| `tests/Functional/Controller/SecurityControllerTest.php` (ergänzt) | 4 (1 übersprungen) | AK-05, AK-10, AK-11 (übersprungen bis BUG-10), EC-04 |

Der EC-04-Test wurde **gegengeprüft**: Ohne die Passwortänderung schlägt er fehl.

Vollständige Suite: **321 Tests, 1104 Assertions, 1 übersprungen, 0 Fehler.**

## Hinweise ohne Fehlerstatus

- **`APP_SECRET` lokal identisch mit dem committeten dev-Wert** (`.env.dev` und `.env.local`
  tragen beide `dfe5df93…`). Er signiert die `remember_me`-Cookies: Wer ihn kennt, kann sie
  fälschen. Ob auf Produktion ein eigener Wert gesetzt ist, war von hier nicht einsehbar —
  bereits im B01-Bericht als offene Frage vermerkt.
- **Kein automatisierter Test für BUG-10.** Ein Test dafür setzt voraus, dass
  `login_throttling` existiert; er liegt als übersprungener Test bereit.
- **`doctrine:schema:validate` bleibt rot** — Altlasten-Rauschen, unverändert.

## Nächster Schritt

`/sdd-build B02` mit dem Auftrag, **BUG-10** zu beheben (Grad *hoch*, blockiert), danach
erneut `/sdd-qa B02`. BUG-11 und BUG-12 sind *niedrig* bzw. *mittel* und blockieren nicht,
gehören aber in denselben Lauf — alle drei sitzen in derselben Konfigurationsdatei.

Die Korrektur der falschen Rekonstruktion in **B04/AK-13 und B04/FB-04** gehört in den
QA-Durchlauf von B04; hier ist sie nur festgehalten.
