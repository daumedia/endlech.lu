# B02 · Anmeldung mit Passwort — Spezifikation

Status: `approved` · Stand: 2026-08-23 · **Rückerfassung aus dem Bestand**

> QA vom 2026-08-24: 14 von 17 Kriterien, BF-13 *hoch*. **EC-04 ist widerlegt** —
> siehe `qa-report.md`. Die dortige Annahme über Passwortänderungen war falsch.
>
> Beschreibt, **was der Code heute tut**. ⚠ markiert fragwürdiges Verhalten, das so im
> Bestand steht. [Fehlbestand](#fehlbestand) ist kein Kriterium, sondern Suchliste.

## Zweck

Ein Nutzer meldet sich mit E-Mail und Passwort an und bleibt auf Wunsch eine Woche
angemeldet. Die Sitzung ist der Zugang zu Profil, Passkey-Verwaltung, Vorschlags-Wizard
und — bei entsprechender Rolle — zur Verwaltung.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| B01 | rekonstruiert | ohne Konto keine Anmeldung |

## User Stories

- **US-01** · Als registrierter Nutzer möchte ich mich mit E-Mail und Passwort
  anmelden, damit ich meine geschützten Bereiche erreiche.
- **US-02** · Als Nutzer möchte ich angemeldet bleiben, damit ich nicht bei jedem
  Besuch neu tippen muss.
- **US-03** · Als Nutzer möchte ich mich abmelden können.

## Nicht im Scope

- Anmeldung per Passkey → B03 (teilt sich `check_path` und Formularseite mit B02)
- Anmeldung an der REST-API → B23 (eigene stateless Firewall, JWT)
- Passwort ändern → B04
- Passwort zurücksetzen → existiert nicht, siehe B01/FB-05

## Akzeptanzkriterien

- **AK-01** · Angenommen, ein Gast ruft `/{locale}/login` auf, wenn die Seite lädt,
  dann erscheinen zwei getrennte Formulare: oben der Passkey-Knopf (nur bei
  WebAuthn-fähigem Browser sichtbar), darunter E-Mail und Passwort.
- **AK-02** · Angenommen, ein **angemeldeter** Nutzer ruft `/{locale}/login` auf, wenn
  die Seite lädt, dann wird er auf die Startseite weitergeleitet.
- **AK-03** · Angenommen, E-Mail und Passwort sind korrekt, wenn abgeschickt wird, dann
  ist der Nutzer angemeldet und landet auf der Startseite — oder auf der Seite, die er
  vor der Umleitung aufrufen wollte.
- **AK-04** · Angenommen, das Passwort ist falsch, wenn abgeschickt wird, dann bleibt
  der Nutzer auf `/{locale}/login`, sieht eine Fehlermeldung aus der
  `security`-Domäne und die E-Mail-Adresse steht wieder im Feld.
- **AK-05** · Angenommen, die E-Mail-Adresse existiert **nicht**, wenn abgeschickt
  wird, dann erscheint dieselbe Meldung wie bei falschem Passwort — es ist nicht
  unterscheidbar, ob das Konto existiert.
- **AK-06** · Angenommen, „Angemeldet bleiben" ist angehakt, wenn die Anmeldung
  gelingt, dann wird ein `REMEMBERME`-Cookie mit 7 Tagen Laufzeit gesetzt.
- **AK-07** · Angenommen, das CSRF-Feld `_csrf_token` fehlt oder ist falsch, wenn
  abgeschickt wird, dann scheitert die Anmeldung, auch bei korrekten Zugangsdaten.
- **AK-08** · Angenommen, ein Nutzer ruft `/{locale}/logout` auf, wenn die Anfrage
  durchläuft, dann ist die Sitzung beendet und er landet auf der Startseite.
- **AK-09** · Angenommen, ein Gast ruft eine geschützte Seite auf (`/profile`,
  `/admin`, `/community/suggest`), wenn die Anfrage durchläuft, dann wird er auf
  `/{locale}/login` geleitet und nach erfolgreicher Anmeldung an sein ursprüngliches
  Ziel weitergereicht.
- **AK-10** · Angenommen, ein angemeldeter Nutzer ohne `ROLE_ADMIN` ruft `/{locale}/admin`
  auf, wenn die Anfrage durchläuft, dann antwortet der Server mit HTTP 403.

### Fragwürdiges Verhalten — als Kriterium aufgenommen, zur Klärung vorgelegt

- **AK-11** ⚠ · Angenommen, jemand probiert dieselbe E-Mail-Adresse mit beliebig vielen
  Passwörtern durch, wenn die Versuche nacheinander abgeschickt werden, dann greift
  **keine** Sperre — weder nach fünf noch nach fünfhundert Versuchen.
  *(So verhält sich der Code heute: `config/packages/security.yaml` konfiguriert kein
  `login_throttling`, obwohl Symfony es mitbringt; die drei Rate-Limiter in
  `config/packages/framework.yaml` decken nur API und Partner-Warteliste ab. Der
  Sicherheitskatalog nennt fünf Versuche in fünfzehn Minuten als Mindeststandard.)*

- **AK-12** ⚠ · Angenommen, ein Nutzer ist angemeldet, wenn eine fremde Seite ihn dazu
  bringt, `/{locale}/logout` zu laden (etwa über ein `<img>`-Tag), dann wird er
  abgemeldet.
  *(So verhält sich der Code heute: Der `logout`-Schlüssel der Firewall setzt kein
  `enable_csrf`, und der Abmeldelink in `base.html.twig:122` ist ein gewöhnliches
  `<a href>` — also GET. Folge: Logout-CSRF. Schadenshöhe gering, aber es ist die
  Voraussetzung für Login-CSRF-Ketten.)*

- **AK-13** ⚠ · Angenommen, ein Konto ist unbestätigt, wenn E-Mail und Passwort korrekt
  sind, dann gelingt die Anmeldung vollständig.
  *(Identisch mit B01/AK-13, hier wiederholt, weil es an dieser Firewall entschieden
  wird: kein `user_checker`.)*

### Datenschutz und Missbrauchsschutz

- **AK-14** · Angenommen, eine Anmeldung schlägt fehl, wenn die Logs gelesen werden,
  dann steht dort kein Klartextpasswort.
- **AK-15** · Angenommen, eine Anmeldung gelingt, wenn die Sitzungs-Cookies betrachtet
  werden, dann tragen sie `httponly` und — unter HTTPS — `secure`.
- **AK-16** · Angenommen, ein Nutzer meldet sich an, wenn die Session-ID vor und nach
  der Anmeldung verglichen wird, dann hat sie sich geändert (Schutz gegen
  Session-Fixation; Symfony-Vorgabe `migrate`).
- **AK-17** · Angenommen, die Anmeldung berührt personenbezogene Daten, wenn geprüft
  wird welche, dann sind es E-Mail-Adresse und Passwort-Hash — beide bereits aus B01
  vorhanden, es entstehen keine neuen.

## Edge Cases

- **EC-01** · Beide Authenticator an derselben Firewall: Ist `_assertion` gefüllt,
  greift `PasskeyAuthenticator` (Priorität 0), sonst `form_login` (−30). Ein
  Formular mit **beidem** würde als Passkey behandelt.
- **EC-02** · Gast ruft `/logout` auf → Symfony beendet eine nicht vorhandene Sitzung
  und leitet auf die Startseite.
- **EC-03** · Anmeldung mit einer E-Mail in abweichender Groß-/Kleinschreibung → hängt
  an der Kollation der Spalte `user.email`; bei der üblichen
  `utf8mb4_unicode_ci`-Kollation gelingt sie.
- **EC-04** · `remember_me`-Cookie überlebt eine Passwortänderung (siehe B04/FB).

## Fehlbestand

- **FB-01 · Kein `login_throttling`.** Siehe AK-11. *Folge:* unbegrenztes
  Passwortraten; in Verbindung mit B01/FB-08 (nur Mindestlänge 8, keine
  Leak-Listen-Prüfung) ist das der wahrscheinlichste Weg zu einem fremden Konto.
- **FB-02 · Kein CSRF-Schutz beim Abmelden.** Siehe AK-12.
- **FB-03 · Keine Zwei-Faktor-Authentisierung.** Der Passkey (B03) ist ein Ersatz für
  das Passwort, keine zweite Stufe. Für `ROLE_ADMIN` gibt es keine zusätzliche Hürde.
- **FB-04 · Keine Sitzungsübersicht, kein „überall abmelden".** Weder Anzeige aktiver
  Sitzungen noch Möglichkeit, fremde zu beenden.
- **FB-05 · Keine Benachrichtigung bei Anmeldung von einem neuen Gerät.**
- **FB-06 · Kein `trusted_hosts`.** Betrifft die ganze Anwendung, siehe B01/FB-09.

## Offene Fragen

- **OF-01** · `login_throttling` ist eine Konfigurationszeile und behebt FB-01
  vollständig. Warum fehlt sie? — Betreiber
- **OF-02** · Soll `ROLE_ADMIN` eine zweite Stufe bekommen (FB-03)? Der Bestand hat
  genau ein Admin-Konto; das Risiko konzentriert sich dort. — Betreiber

## Decision Log

| # | Frage | Entscheidung im Bestand | Begründung, soweit erkennbar |
|---|---|---|---|
| 1 | Ein oder zwei Formulare auf der Login-Seite | zwei | dokumentiert in `_passkey_login.html.twig`: der `AuthenticationController` des npm-Pakets ruft `form.checkValidity()`, das an den `required`-Feldern des Passwortformulars scheitern würde |
| 2 | Reihenfolge im Markup | Passkey zuerst | Tab-Reihenfolge muss der sichtbaren folgen |
| 3 | `entry_point` | `form_login` | Pflicht, sobald eine Firewall zwei Authenticator führt — sonst bricht der Container-Build |
| 4 | Fehlermeldung | generisch aus der `security`-Domäne | verhindert User-Enumeration (AK-05) — anders als das Registrierformular, siehe B01/AK-14 |
| 5 | `remember_me`-Laufzeit | 7 Tage | Grund nicht erkennbar |
