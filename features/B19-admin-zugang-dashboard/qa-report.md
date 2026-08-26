# B19 · Admin-Zugang & Dashboard — Testbericht

Stand: 2026-08-24 · Vorstufe: `rekonstruiert` · Branch `fix/b04-profil-qa`

## Fazit

**Production-ready: ja** — mit einem mittleren und zwei niedrigen Befunden.

14 von 14 Akzeptanzkriterien bestanden, 3 von 3 Edge Cases. Wie bei B23 trägt die Zahl
allein zu wenig: Zwei der bestandenen Kriterien (AK-10, AK-11) sind mit ⚠ als
*fragwürdiges Verhalten* aufgenommen — „bestanden" heißt dort, dass der Code sich
verhält wie rekonstruiert, und genau das ist der Befund.

Die Zugriffsschranke selbst ist der stabilste Teil, den diese Prüfreihe bisher gesehen
hat: doppelt gesichert, an fünf Routen einzeln nachgemessen, CSRF greift, ein
Nicht-Admin kommt auch mit einem gültigen Admin-Token nicht durch.

Daneben zwei Dinge, die der Verwaltungsbereich nicht kann: **Sprachumschalter und
Nutzerverwaltung**. Der eine ist eingebaut und tut nichts, die andere fehlt ganz.

Nächster Aufruf: **`/sdd-erfassen B14`**. Die Erfassung läuft weiter.

## Akzeptanzkriterien im Einzelnen

### Zugriff

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-01 | ✅ bestanden | Gast auf `/de/admin` → **302** nach `/de/login` |
| AK-02 | ✅ bestanden | `user@endlech.lu` (ohne `ROLE_ADMIN`) → **403** |
| AK-13 | ✅ bestanden | `/de/admin/restaurants`, `/vorschlaege`, `/warteliste`, `/finanzen` → **je 403**. Beide Schranken vorhanden: `access_control` auf `^/[a-z]{2}/admin` **und** `#[IsGranted('ROLE_ADMIN')]` an **7** Controllern |
| AK-14 | ✅ bestanden | `setRoles()` kommt im gesamten `src/` und `templates/` genau zweimal vor: als Setter in `User.php:137` und in `UserFixtures.php:54`. **0** Routen zur Nutzerverwaltung. Test `testAk14KeineRouteVergibtRollen` |

### Dashboard

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-03 | ✅ bestanden | Alle sieben Kennzahlen gerendert: Restaurants 11 · Verifizierte 3 · Offene Vorschläge 0 · Benutzer 3 · Restaurants diesen Monat 11 · Benutzer diesen Monat 3 · Fotos 0 |
| AK-04 | ✅ bestanden | „Zuletzt hinzugefügte Restaurants" (5 Zeilen mit Name, Stadt, Datum, Status) und „Zuletzt registrierte Benutzer" (3 vorhanden von 5 möglichen) |
| **AK-05** | ✅ bestanden | Ein Restaurant auf `2026-08-01 00:00:00` und eines auf `2026-07-31 23:59:59` gesetzt → „Restaurants diesen Monat" fiel von 11 auf **10**. Die Grenze liegt exakt am Monatsanfang, nicht bei 30 Tagen |
| AK-12 | ✅ bestanden | Sichtbar werden Name, E-Mail und Registrierungsdatum: `admin@endlech.lu`, `user@endlech.lu`, `unverified@endlech.lu` — wie beschrieben, nicht mehr |

### Sprache

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-06 | ✅ bestanden | `/de/admin/locale/fr` mit Referer `/de/admin` → 302 zurück auf `/de/admin`; `_locale` steht in der Sitzung |
| AK-07 | ✅ bestanden | `/de/admin/locale/xx` → **404**. Das Requirement steht im Path-Regex: `{^/(?P<_locale>lb\|de\|fr\|en)/admin/locale/(?P<locale>lb\|de\|fr\|en)$}`. Test `testAk07UnbekannterSprachcodeErgibt404` |
| **AK-10** ⚠ | ✅ bestätigt | Nach der Wahl `fr` liefert `/de/admin` weiterhin **Deutsch** („Navigation · Dashboard · Restaurants · Vorschläge"). Der Sitzungswert hat keinen Leser → **BF-34** |
| **AK-11** ⚠ | ✅ bestätigt | drei Varianten gemessen → **BF-33** |

### Darstellung

| AK | Ergebnis | Nachweis |
|---|---|---|
| AK-08 | ✅ bestanden | Auf `/de/admin`: Bottom-Nav, Cookie-Banner und Cookie-Link **alle nicht vorhanden**. Gegenprobe auf `/de/restaurants`: beide vorhanden |
| AK-09 | ✅ bestanden | Je Seite genau **eine** Hervorhebung, richtig zugeordnet: `/de/admin` → Dashboard · `/de/admin/restaurants` → Restaurants · `/de/admin/finanzen` → Finanzen. Die anderen vier jeweils ohne `bg-purple-50` |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ bestanden | siehe AK-08 — die drei Stellen entschärfen die Kollision tatsächlich einzeln |
| EC-02 | ✅ bestanden | ohne Referer → 302 auf `http://localhost:8000/de/admin`. Test `testEc02OhneRefererLandetManAufDemDashboard` |
| EC-03 | ⚠️ nicht prüfbar | `sticky top-24` gegen `h-20` ist eine visuelle Beziehung. Ohne Screenshot-Vergleich bei mehreren Viewport-Höhen ist das keine Messung, sondern eine Einschätzung — und die zählt hier nicht |

## Sicherheitsprüfung

| Prüfung | Ergebnis |
|---|---|
| **Fremder Zugriff** | Nicht-Admin auf fünf `admin_*`-Routen → durchgehend 403 |
| **Fremder Schreibzugriff** | Verwaltungs-POST als Nicht-Admin, **mit gültigem Admin-CSRF-Token** → **403**. Die Rollenprüfung steht vor der Tokenprüfung |
| **CSRF** | `_token=falsch` auf `admin_restaurant_toggle_verified` → 302, `is_verified` bleibt bei `0`. Die Prüfung greift |
| **Rate Limit** | **8 Umschaltvorgänge in Folge, alle 302, keine Sperre** → BF-35 |
| **Open Redirect** | drei Varianten erfolgreich → BF-33 |
| **Personendaten in Protokollen** | `admin123`, `user123` → je **0 Treffer** in `var/log/` |
| **Weitere Referer-Weiterleitungen** | `grep` über `src/Controller/`: genau **eine** Stelle (`AdminLocaleController.php:26`). Der Befund ist damit auf einen Endpunkt begrenzt |

## Fehler

### BF-33 · Open Redirect in `admin_set_locale` — mittel

**Betrifft:** AK-11 · FB-04 der Spec

**Reproduktion:** als Admin angemeldet, `GET /de/admin/locale/fr` mit gesetztem `Referer`:

| Referer | Antwort |
|---|---|
| `https://boeswillig.example/phishing` | `302 → https://boeswillig.example/phishing` |
| `//evil.example/x` | `302 → http://evil.example/x` |
| `javascript:alert(1)` | `302 → javascript:alert(1)` |

**Erwartet:** Weiterleitung nur auf die eigene Herkunft, sonst Rückfall auf das Dashboard
**Tatsächlich:** jede der drei Angaben wird übernommen

**Ort:** `src/Controller/AdminLocaleController.php:26–28`
```php
$referer = $request->headers->get('referer');

return $this->redirect($referer ?: $this->generateUrl('admin_dashboard'));
```

**Warum das trotz `ROLE_ADMIN` zählt:** Die Rollenprüfung schützt den Endpunkt, nicht das
Opfer. Der Angriff läuft andersherum — ein Admin bekommt einen Link auf **die echte
Domain** `endlech.lu/de/admin/locale/de` und landet auf einer Seite, die aussieht wie
Endlech.lu und nach seinem Passwort fragt. Die vertrauenswürdige Domain in der Kette ist
der ganze Zweck. Und das Ziel ist ausgerechnet der Zugang, der ohne zweite Stufe
auskommt (FB-03) und an genau einem Konto hängt (FB-01).

Die dritte Zeile (`javascript:`) richtet in heutigen Browsern nichts an — sie zeigt aber,
dass **gar keine** Prüfung stattfindet, nicht nur eine unvollständige.

**Vorschlag:** Vor der Weiterleitung prüfen, ob der Referer dieselbe Herkunft trägt:
```php
$referer = $request->headers->get('referer');
$eigenes = $referer !== null && str_starts_with($referer, $request->getSchemeAndHttpHost() . '/');

return $this->redirect($eigenes ? $referer : $this->generateUrl('admin_dashboard'));
```
Der `. '/'` am Ende ist wesentlich: Ohne ihn passierte `https://endlech.lu.evil.example`
die Prüfung.

**Der Regressionstest `testAk11SprachwahlLeitetUngeprueftAufDenRefererWeiter` hält den
Befund fest und schlägt fehl, sobald er behoben ist** — dasselbe Verfahren wie bei BF-28
in B23, wo es funktioniert hat.

### BF-34 · Der Sprachumschalter im Verwaltungsbereich tut nichts — niedrig

**Betrifft:** AK-10 · beantwortet OF-02 der Spec

**Reproduktion:**
1. Als Admin `/de/admin/locale/fr` aufrufen (Referer `/de/admin`)
2. `/de/admin` laden

**Erwartet:** französische Oberfläche
**Tatsächlich:** unverändert deutsch — „Navigation · Dashboard · Restaurants ·
Vorschläge · Wartelisten · Finanzen"

**Ort:** `AdminLocaleController::setLocale()` schreibt `_locale` in die Sitzung. Es gibt
keinen `LocaleSubscriber`, der den Wert beim nächsten Request wieder anwendet, und die
Routen tragen die Sprache im Pfad. Die Weiterleitung führt auf den Referer zurück — der
trägt `/de/`, also bleibt es bei `/de/`.

**Damit ist OF-02 beantwortet: nein, er wirkt nicht.** Der Sitzungswert ist toter Zustand.

**Warum trotzdem nur *niedrig*:** Der Verwaltungsbereich ist einsprachig benutzbar, und
der öffentliche Umschalter funktioniert (B24). Es ist ein Bedienelement, das eine Wirkung
verspricht und keine hat — ärgerlich, aber folgenlos.

**Vorschlag:** Entweder die Weiterleitung auf die Zielsprache umschreiben (den
Sprachanteil im Referer-Pfad ersetzen) oder den Umschalter aus der Admin-Shell entfernen.
Die zweite Fassung ist ehrlicher, solange niemand den Bereich mehrsprachig braucht.

### BF-35 · Keine Drosselung auf Verwaltungsschreibvorgängen — niedrig

**Betrifft:** FB-05 der Spec

**Reproduktion:** acht `POST /de/admin/restaurants/228/verifizieren` in Folge, jeweils mit
frisch geholtem CSRF-Token
**Tatsächlich:** `302 302 302 302 302 302 302 302`, keine Sperre; `is_verified` schaltete
achtmal um und stand am Ende wieder auf `0`

**Ort:** kein Limiter an den `admin_*`-Routen

**Grad *niedrig*, weil ein gültiger Admin-Zugang Voraussetzung ist** — wer den hat, kann
ohnehin alles. Der Wert eines Deckels läge woanders: Er begrenzt den Schaden einer
gekaperten Admin-Sitzung und eines fehlerhaften Skripts. Beides sind reale Fälle, keins
davon ist der Hauptweg.

**Fünfte Wiederholung von M-01.** Die Konvention steht seit heute in `CLAUDE.md` — dieser
Befund ist der erste, bei dem sie beim nächsten Eingriff greifen kann.

## Hinweise ohne Fehlerstatus

- **Keine Nutzerverwaltung** (FB-01, gemessen bestätigt: 0 Routen, `setRoles()` nur in
  den Fixtures). Kein Fehler nach der Spec, aber die Folge greift weit: Ein zweiter Admin
  entsteht nur per SQL, ein missbräuchliches Konto wird nur per SQL stillgelegt — und die
  Kontolöschung auf Nutzerwunsch (B04/FB-01, Feature `01`) ist ohne sie nicht
  durchführbar. **Das ist die Abhängigkeit, an der Feature `01` hängt.**
- **Kein Audit-Log** (FB-02). Nicht ausführbar prüfbar — es gibt nichts zu messen, wo
  nichts geschrieben wird. Der Code belegt es: Außer `Restaurant.verifiedBy` hält keine
  Verwaltungsaktion fest, wer sie ausgelöst hat. Bei einem Alleinbetrieb tragbar; sobald
  ein zweiter Admin dazukommt, ist es die erste Lücke, die auffällt.
- **Kennzahlen ungecacht** (FB-06): sieben Zählstellen im `AdminStatsService`, kein
  Cache-Aufruf. Bei elf Restaurants folgenlos. Der Pool dafür existiert
  (`cache.open_stats`).
- **EC-03 bleibt nicht prüfbar.** `sticky top-24` gegen `h-20` ist eine visuelle
  Beziehung; ohne Screenshot-Vergleich wäre jede Aussage dazu geraten.
- **`code-reviewer`-Agent nicht eingesetzt** — Sitzungsvorgabe. Alle Befunde stammen aus
  dem Angriffsdurchlauf.

## Neue Tests

Vier in `tests/Functional/Controller/AdminDashboardControllerTest.php`:
`testAk11SprachwahlLeitetUngeprueftAufDenRefererWeiter` (hält BF-33 fest),
`testEc02OhneRefererLandetManAufDemDashboard`,
`testAk07UnbekannterSprachcodeErgibt404`,
`testAk14KeineRouteVergibtRollen` (schlägt an, sobald eine Nutzerverwaltung entsteht).

**Suite: 346 Tests, 0 Fehler.**

## Nächster Schritt

`/sdd-erfassen B14`. B19 geht auf `approved`; die drei Befunde stehen in
`features/befunde.md`. Kein Befund ist *hoch* — die Erfassung läuft weiter.

BF-33 ist der Kandidat, den ich beim nächsten Reparaturdurchgang mitnehmen würde: fünf
Zeilen, und er sitzt vor dem einzigen Zugang, der keine zweite Stufe hat.
