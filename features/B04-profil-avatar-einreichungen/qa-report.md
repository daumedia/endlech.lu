# B04 · Profil, Avatar & Einreichungen — Testbericht

Stand: 2026-08-24 · Geprüft gegen `spec.md` vom 2026-08-23 (Rückerfassung)

## Fazit

**Production-ready: nein**

Das Profil ist in seinen Kernfunktionen sauber: Der Avatar-Upload prüft den tatsächlichen
MIME-Typ (eine als PNG deklarierte HTML-Datei wird mit 422 abgewiesen, ebenso 3 MB), die
alte Datei wird beim Neu-Upload gelöscht, die Einreichungsliste zeigt exakt die eigenen
Restaurants, und der Passwortwechsel verlangt das aktuelle Passwort.

**Der blockierende Fund ist die E-Mail-Änderung.** Sie geht ohne jede erneute Bestätigung
durch, und `is_verified` bleibt dabei auf `1`. Wer eine Sitzung kapert, schreibt die
Adresse auf seine eigene um und ist dauerhaft drin — der rechtmäßige Inhaber kann sich
nicht zurückholen, weil es kein Passwort-Zurücksetzen gibt (Feature `01`).

**Eine Korrektur an der Rekonstruktion:** AK-13 und FB-04 behaupteten, eine
Passwortänderung lasse fremde Sitzungen unberührt. Gemessen ist das Gegenteil — Symfony
entwertet sie. Die Spec war falsch, das Verhalten ist besser als beschrieben.

Nächster Schritt: `/sdd-build B04` mit BUG-15.

| | Anzahl |
|---|---|
| Akzeptanzkriterien geprüft | 18 von 18 |
| davon bestanden | 15 |
| davon durchgefallen | 3 (AK-12, AK-13, AK-14) |
| **nicht prüfbar** | 0 |
| Tests | 323 grün (bestehende Suite) |

## Akzeptanzkriterien im Einzelnen

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | Gast auf `/de/profile` → 302 |
| AK-02 | ✅ bestanden | angemeldet → 200; Formulare `/de/profile/edit`, `/de/profile/password`, dazu die Passkey-Verwaltung und der Einreichungsabschnitt |
| AK-03 | ✅ bestanden | Name geändert → `302 → /de/profile`, DB zeigt `Neuer Name` |
| AK-04 | ✅ bestanden | 8×8-PNG hochgeladen → 302; DB `6a8c5eedd103c4.90154113.png`, Datei liegt unter `public/uploads/avatars/` |
| AK-05 | ✅ bestanden | HTML-Datei mit `type=image/png` deklariert → **422**; 3-MB-Datei → **422**; Avatar unverändert |
| AK-06 | ✅ bestanden | zweiter Upload → neuer Dateiname, **alte Datei vom Dateisystem entfernt** |
| AK-07 | ✅ bestanden | Löschen mit Token → DB `NULL`, Datei weg |
| AK-08 | ✅ bestanden | Löschen mit falschem Token → Avatar bleibt |
| AK-09 | ✅ bestanden | falsches aktuelles Passwort → „Das aktuelle Passwort ist nicht korrekt." |
| AK-10 | ✅ bestanden | Wechsel gelingt; neues Passwort meldet an, altes wird abgewiesen |
| AK-11 | ✅ bestanden | Profil verlinkt die IDs `156, 157, 160` — exakt die drei, die laut Datenbank `submitted_by_id` dieses Nutzers tragen |
| AK-12 | ❌ durchgefallen | Adresse auf `ganz-neue@qa.example` geändert → 302; DB: neue Adresse, `is_verified = 1`, kein Token → **BUG-15** |
| AK-13 | ❌ durchgefallen | **Die Spec ist falsch.** Zwei Sitzungen, in einer das Passwort geändert: s1 → 200, **s2 → 302**. Fremde Sitzungen werden entwertet → siehe Korrektur unten |
| AK-14 | ❌ durchgefallen | 8 Versuche mit falschem aktuellem Passwort → **alle angenommen**, kein Limit → **BUG-16** |
| AK-15 | ✅ bestanden | siehe AK-05 — `File`-Constraint prüft über `fileinfo`, nicht über die Endung |
| AK-16 | ✅ bestanden | `GET /uploads/avatars/<datei>` → **200** ohne Anmeldung (bewusste Eigenschaft, kein Fehler) |
| AK-17 | ✅ bestanden | `ProfileController` ruft dreimal `findBySubmitter($this->getUser())` — keine ID aus der Anfrage, damit strukturell kein IDOR |
| AK-18 | ✅ bestanden | `user123`, `GanzNeuesPW1`, `raten1` → je 0 Treffer im Log |

## Korrektur an der Rekonstruktion

Die Spec behauptete an zwei Stellen, eine Passwortänderung sei folgenlos für bestehende
Sitzungen:

> **AK-13** ⚠ · „…dann bleiben **alle anderen Sitzungen und alle `REMEMBERME`-Cookies
> gültig**."
> **FB-04** · „Keine Sitzungsinvalidierung bei Passwortänderung."

Gemessen (zweimal, in B02/EC-04 und hier):

| | vor der Änderung | danach |
|---|---|---|
| Sitzung, die geändert hat | 200 | **200** |
| andere Sitzung desselben Kontos | 200 | **302** |
| `REMEMBERME` der anderen Sitzung | gilt | wird **nicht** mehr akzeptiert |

Symfony entwertet beides selbst: Der Sicherheitskontext vergleicht bei jedem Request den
serialisierten Nutzer aus der Sitzung mit dem frisch geladenen, und die
`remember_me`-Signatur schließt den Passwort-Hash ein.

**Warum die Rekonstruktion daneben lag:** `ProfileController::changePassword()` ruft
tatsächlich weder eine Session-Invalidierung noch einen Wechsel des Geheimnisses auf. Aus
dem Projektcode allein ist der Schluss also naheliegend — er ist trotzdem falsch, weil
das Framework die Arbeit übernimmt. Genau davor warnt `sdd-erfassen`: Eine Rekonstruktion
kann selbst falsch sein.

`spec.md` ist entsprechend berichtigt; der Regressionstest
`testEc04PasswortaenderungEntwertetFremdeSitzungen` (B02) hält das Verhalten fest.

## Sicherheitsprüfung

| Prüfung | Ergebnis | Beleg |
|---|---|---|
| Zugriff auf fremde ID (IDOR) | bestanden | keine ID aus der Anfrage; Einreichungsliste exakt die eigenen drei |
| Zugriffsregeln serverseitig | bestanden | Gast → 302; `#[IsGranted]` an der Klasse plus `access_control` |
| Rate Limit greift | **BUG-16** | 8 Rateversuche auf das aktuelle Passwort, alle angenommen |
| PII in Logs | bestanden | drei Passwörter, je 0 Treffer |
| PII an externe Dienste | bestanden | B04 verschickt nichts — ⚠ auch nicht bei einer E-Mail-Änderung, siehe BUG-15 |
| Geheimnisse im Repository | bestanden | keine neuen |
| Eingaben | bestanden | Datei-Uploads: falscher Typ und Übergröße je 422 |
| Löschen | **offen** | kein Kontolöschweg → Feature `01` |

## Fehler

### BUG-15 · E-Mail-Änderung ohne erneute Bestätigung — hoch

**Betrifft:** AK-12, FB-03
**Reproduktion:**
1. Als `user@endlech.lu` anmelden
2. Im Profil die Adresse auf `ganz-neue@qa.example` ändern
3. Datenbank ansehen
**Erwartet:** Bestätigungsmail an die neue Adresse, `is_verified` auf `false`, bis sie
bestätigt ist
**Tatsächlich:** `302`, Adresse sofort geändert, **`is_verified` bleibt `1`**, kein Token
**Ort:** `src/Form/ProfileType.php` führt `email` als reguläres Feld;
`ProfileController::edit()` ruft nur `flush()`
**Warum *hoch*:** Der Bestätigungsstatus gilt danach für eine Adresse, die nie bestätigt
wurde. Wer eine Sitzung kapert, schreibt die Adresse um und ist dauerhaft drin — und der
rechtmäßige Inhaber kann sich nicht zurückholen, weil es kein Passwort-Zurücksetzen gibt
(BF-04, Feature `01`). Es gibt auch keine Benachrichtigung an die alte Adresse (FB-05).
**Vorschlag:** Änderung erst nach Bestätigung wirksam werden lassen — die Mechanik dafür
steht in B01 bereits (Token, Frist, Mailvorlage). Alternativ mindestens eine
Benachrichtigung an die alte Adresse.

### BUG-16 · Passwortänderung ohne Rate Limit — niedrig

**Betrifft:** AK-14, FB-07
**Reproduktion:** Achtmal `/de/profile/password` mit falschem `currentPassword` absenden
**Erwartet:** Sperre nach wenigen Versuchen
**Tatsächlich:** 8× `302`, alle angenommen
**Ort:** `src/Controller/ProfileController.php::changePassword()` — kein Limiter
**Einordnung:** Setzt eine bereits gekaperte Sitzung voraus, ist also nachrangig
gegenüber BUG-15. Gehört zum Rate-Limit-Muster M-01.

## Hinweise ohne Fehlerstatus

- **Avatare behalten ihre Metadaten** (FB-06 der Spec). Die Datei wird unverändert
  verschoben; ein Handyfoto brächte seine GPS-Koordinaten in ein öffentlich abrufbares
  Verzeichnis. Nicht als Fehler gewertet, weil kein Kriterium es fordert — für eine
  Plattform mit Kontaktdaten aber bedenkenswert.
- **Der Kontrast zu B09 ist auffällig:** Hier greift ein `File`-Constraint mit MIME-Prüfung,
  dort läuft der Upload an Symfonys Formularsystem vorbei und prüft nichts. Zwei
  Upload-Wege im selben Projekt, zwei Sorgfaltsmaßstäbe (M-06).

## Nächster Schritt

`/sdd-build B04` mit BUG-15 (*hoch*, blockiert) und BUG-16 (*niedrig*, im selben Lauf).
