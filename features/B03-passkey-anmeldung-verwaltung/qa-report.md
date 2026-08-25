# B03 · Passkey-Anmeldung & -Verwaltung — Testbericht

Stand: 2026-08-24 · Geprüft gegen `spec.md` vom 2026-08-23 (Rückerfassung)
Umgebung: lokal, `symfony server` auf `:8000`, **Brave headless mit virtuellem
WebAuthn-Authenticator** über das Chrome DevTools Protocol

## Fazit

**Production-ready: ja**

Das am sorgfältigsten gebaute Feature des Projekts — und das einzige, dessen Kern die
Rückerfassung für unprüfbar hielt. Mit einem virtuellen Authenticator über CDP ließ sich
der ganze Ablauf im echten Browser durchspielen: Passkey anlegen, abmelden, **ohne
E-Mail-Eingabe wieder anmelden**. Das war der Nachweis, auf den es ankam.

16 von 20 Kriterien bestanden, eines durchgefallen (kein Rate Limit auf den
Challenge-Endpunkten), drei nicht prüfbar. Die Besitzprüfung sitzt: Ein Admin bekommt auf
einen fremden Passkey 403 — beim Löschen **und** beim Umbenennen.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 17 von 20 ausgeführt |
| davon bestanden | 16 |
| davon durchgefallen | 1 (AK-15) |
| **nicht prüfbar** | 3 (AK-04, AK-14, AK-20) |
| Edge Cases belegt | 3 von 5 |
| Tests | 323 grün (bestehende Suite unverändert) |

## Akzeptanzkriterien im Einzelnen

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | Brave mit WebAuthn: Knopf „🔑 Mit Passkey anmelden" **sichtbar** |
| AK-02 | ✅ bestanden | `delete window.PublicKeyCredential` per `addInitScript` → Knopf **nicht** sichtbar, Passwortformular sichtbar |
| AK-03 | ✅ bestanden | **Der Kernnachweis:** Nach dem Abmelden auf `/de/login` nur den Passkey-Knopf gedrückt — danach `/de/profile` erreichbar, Feld zeigt `user@endlech.lu`. Keine E-Mail eingegeben |
| AK-04 | ⚠️ nicht prüfbar | Abbruch der Geräteabfrage. Der virtuelle Authenticator läuft mit `automaticPresenceSimulation: true`; ein Abbruch ließe sich nur durch Umkonfiguration mitten im Ablauf erzeugen |
| AK-05 | ✅ bestanden | `_assertion=voelliger-unsinn` → `302 → /de/login`, Meldung „Die Anmeldung mit Passkey hat nicht geklappt…" — **keine** technischen Interna sichtbar |
| AK-06 | ✅ bestanden | DB nach der Passkey-Anmeldung: `created_at 15:05:55`, `last_used_at 15:06:01` — fortgeschrieben |
| AK-07 | ✅ bestanden | Im Profil angelegt: Liste 0 → 1, Eintrag „🔑 **Mac** Angelegt am 24.08.2026 · noch nicht benutzt" — Name aus dem User-Agent geraten |
| AK-08 | ✅ bestanden | Umbenennen → `302 → /de/profile`, DB: `Mein Laptop` |
| AK-09 | ✅ bestanden | Name aus drei Leerzeichen → Name bleibt `Mein Laptop` |
| AK-10 | ✅ bestanden | Löschen → `302`, `webauthn_credential` danach leer |
| AK-11 | ✅ bestanden | Als **Admin** auf den Passkey von `user@endlech.lu`: Löschen **403**, Umbenennen **403**, Datensatz unverändert vorhanden |
| AK-12 | ✅ bestanden | `WebauthnUserEntityRepository` implementiert ausschließlich `PublicKeyCredentialUserEntityRepositoryInterface` — **kein** `CanRegisterUserEntity`/`CanGenerateUserEntity`. `/passkey/register/options` anonym → 302 |
| AK-13 | ✅ bestanden | Im Markup: `method=post action=/de/profile/passkeys/5/loeschen` und `…/umbenennen` — gewöhnliche Formulare |
| AK-14 ⚠ | ⚠️ nicht prüfbar | Stiller Abbruch bei unbekanntem `userHandle`. Ließe sich nur herstellen, indem man den Handle zwischen Challenge und Antwort aus der Datenbank entfernt — ein Rennen, das sich von außen nicht zuverlässig auslösen lässt |
| AK-15 ⚠ | ❌ durchgefallen | 10 Anfragen an `POST /passkey/login/options` → **10× 200**, kein Limit → **BUG-14** |
| AK-16 | ✅ bestanden | Spalten: `public_key_credential_id`, `credential_public_key`, `aaguid`, `counter`, `user_handle`, `name`, `created_at`, `last_used_at` u. a. — **kein privater Schlüssel** |
| AK-17 | ✅ bestanden | `information_schema.REFERENTIAL_CONSTRAINTS` → `DELETE_RULE = CASCADE` |
| AK-18 | ✅ bestanden | `webauthn_handle` **32 Zeichen** (16 Zufallsbytes als Hex), nicht die fortlaufende ID |
| AK-19 | ✅ bestanden | `webauthn.yaml`: zweimal `user_verification: required` (Anmelde- und Anlageprofil) |
| AK-20 | ⚠️ nicht prüfbar | Klon-Schutz über den Signaturzähler. Bräuchte einen zweiten Authenticator mit zurückgesetztem Zähler; CDP bietet dafür keinen direkten Weg. Indirekt gestützt durch AK-06 (der Zähler wandert mit) |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ⚠️ nicht prüfbar | Doppelanlage desselben Passkeys — der Browser bietet den Vorgang wegen `hide_existing_credentials: false` gar nicht erst an, was sich von außen nicht von einem Fehler unterscheiden lässt |
| EC-02 | ✅ belegt | Letzten Passkey gelöscht → danach Anmeldung mit Passwort weiterhin möglich |
| EC-03 | ✅ belegt | über B02/EC-01: `_assertion` gefüllt → Passkey-Weg greift, Passwort wird nicht geprüft |
| EC-04 | ⚠️ nicht prüfbar | Doppelte base64-Kodierung — ein hypothetischer Fehlerfall, der im Bestand nicht auftritt |
| EC-05 | ✅ belegt | Der ganze Durchlauf lief über `http://localhost:8000`; ohne den `when@dev`-Block mit `allowed_origins` hätte die serverseitige Prüfung HTTPS verlangt |

## Sicherheitsprüfung

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Zugriff auf fremde ID (IDOR) | bestanden | Admin auf fremden Passkey: 403 bei Löschen **und** Umbenennen; Datensatz unverändert |
| Zugriffsregeln serverseitig | bestanden | `/passkey/register/options` anonym → 302; `denyUnlessOwnedByCurrentUser()` vor der CSRF-Prüfung |
| Rate Limit greift | **BUG-14** | 10× `POST /passkey/login/options` → 10× 200 |
| PII in Logs | bestanden | keine Passwörter; der Passkey-Weg überträgt keine |
| PII an externe Dienste | bestanden | keine — der private Schlüssel verlässt das Gerät nie, der Server sieht nur den öffentlichen Teil |
| Geheimnisse im Repository | bestanden | `WEBAUTHN_RP_ID=localhost` in `.env` ist kein Geheimnis; siehe aber FB-06 |
| Eingaben | bestanden | `_assertion=voelliger-unsinn` → verständliche Meldung, keine Interna |
| Löschen | bestanden | Passkey gelöscht, Tabelle leer; Handle beim Konto zurückgesetzt |

## Fehler

### BUG-14 · Challenge-Endpunkte ohne Rate Limit — mittel

**Betrifft:** AK-15, FB-01
**Reproduktion:** `POST /passkey/login/options` zehnmal in Folge
**Erwartet:** Drosselung wie bei den übrigen öffentlichen Endpunkten
**Tatsächlich:** 10× `200`, jedes Mal eine frische Challenge
**Ort:** `config/routes/webauthn.yaml` bzw. `access_control` — die Bundle-Controller sind
`PUBLIC_ACCESS` und von keinem Limiter erfasst. `ApiRateLimitSubscriber` greift nur auf
`^/api/v1`, und die Passkey-Routen sind sprachfrei, fallen also auch nicht unter die
Web-Limiter.
**Einordnung:** Kein Weg zu einem fremden Konto — eine Challenge allein nützt niemandem.
Es ist eine Ressourcenfrage: Jede Anfrage erzeugt Zufallsdaten und schreibt sie in den
Sitzungsspeicher.
**Vorschlag:** Den `login_throttling`-Gedanken auf `^/passkey/` ausdehnen, etwa über
einen eigenen Limiter im `ApiRateLimitSubscriber` oder einen zweiten Subscriber.

## Hinweise ohne Fehlerstatus

- **FB-06 · `WEBAUTHN_RP_ID` auf Produktion nicht einsehbar.** Lokal steht `localhost`.
  Ein falscher Wert macht das Feature unbenutzbar und fällt erst einem Nutzer auf. Vor der
  nächsten Auslieferung nachsehen — die Frage steht seit der Rückerfassung offen.
- **Drei Kriterien sind nicht prüfbar**, und das ist kein Makel der Umsetzung: AK-04
  (Abbruch), AK-14 (stiller Abbruch bei unbekanntem Handle) und AK-20 (Klon-Schutz)
  verlangen Zustände, die sich von außen nicht zuverlässig herstellen lassen. AK-14 wäre
  am ehesten durch einen Unit-Test auf `saveCredentialRecord()` zu erreichen.
- **Der Weg über CDP hat sich gelohnt.** Die Rückerfassung hatte notiert, die Assertion
  sei „mit PHPUnit nicht testbar" — das stimmt, aber es heißt nicht unprüfbar. Der
  vollständige Ablauf ist jetzt belegt.
- **Testdaten entfernt:** Browser-Tests laufen ohne die DAMA-Transaktionsisolation der
  PHPUnit-Suite. Nach dem Lauf: `webauthn_credential` leer, `user.webauthn_handle`
  zurückgesetzt.

## Nächster Schritt

`/sdd-qa B04`. BUG-14 ist *mittel* und blockiert nicht; er steht als BF-18 in
`features/befunde.md` und gehört zum Rate-Limit-Muster, das sich durch das ganze Projekt
zieht (M-01).
