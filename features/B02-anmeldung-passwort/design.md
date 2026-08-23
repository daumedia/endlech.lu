# B02 · Anmeldung mit Passwort — Systemdesign

Status: `approved` · Stand: 2026-08-23 · Stack-Profil: `symfony-doctrine`

## Überblick

Symfony-`form_login` in seiner schlichtesten Form: Der `SecurityController` rendert nur
die Seite und reicht `AuthenticationUtils` durch; die eigentliche Prüfung erledigt die
Firewall. Bemerkenswert ist die Firewall selbst — sie führt **zwei** Authenticator
gleichzeitig, weil der Passkey-Weg (B03) denselben `check_path` benutzt.

Welcher greift, entscheidet allein das Feld `_assertion` im abgeschickten Formular.
Deshalb steht auf der Login-Seite kein Umschalter und kein zweiter Endpunkt.

## Seiten und Routen

| Route | Pfad | Zweck | Zugang |
|---|---|---|---|
| `app_login` | `/{_locale}/login` | Formularseite **und** `check_path` beider Wege | `PUBLIC_ACCESS` |
| `app_logout` | `/{_locale}/logout` | Abmelden | Controller-Rumpf leer, Firewall fängt ab |

Die Methode `SecurityController::logout()` wird nie ausgeführt — sie existiert nur,
damit die Route im Router steht.

## Komponentenstruktur

```
security/login.html.twig
├── Fehlerkasten                      error.messageKey aus der security-Domäne
├── partials/_passkey_login.html.twig  → B03, eigenes <form>, steht ZUERST
└── <form method="post">              kein action → derselbe Pfad
    ├── _username    (email, required, autofocus, autocomplete="email")
    ├── _password    (password, required, autocomplete="current-password")
    ├── _remember_me (checkbox)
    └── _csrf_token  (csrf_token('authenticate'))
```

⚠ Weil das Passkey-Formular zuerst im Markup steht, greift `filter('form')` in Tests
das falsche. `AbstractWebTestCase::formWithField()` löst das; wer darauf verzichtet,
bekommt „Unreachable field `_username`".

## Firewall-Konfiguration

`config/packages/security.yaml`, Firewall `main`:

| Schlüssel | Wert | Bedeutung |
|---|---|---|
| `lazy` | `true` | Nutzer wird erst geladen, wenn jemand danach fragt |
| `provider` | `app_user_provider` | Entity `User`, Kennung `email` |
| `custom_authenticator` | `App\Security\PasskeyAuthenticator` | B03 |
| `entry_point` | `form_login` | Pflicht bei zwei Authenticatoren |
| `form_login.login_path` / `check_path` | beide `app_login` | eine Route für Anzeige und Prüfung |
| `form_login.default_target_path` | `app_home` | |
| `form_login.enable_csrf` | `true` | Token-ID `authenticate` |
| `remember_me.lifetime` | `604800` (7 Tage) | Signatur über `%kernel.secret%` |
| `logout.path` / `target` | `app_logout` / `app_home` | **ohne** `enable_csrf` |

**Nicht gesetzt:** `login_throttling`, `user_checker`, `switch_user`.

## Datenmodell

B02 legt nichts an. Gelesen werden `user.email`, `user.password`, `user.roles`.
`user.is_verified` wird **nicht** gelesen — das ist FB-03 aus B01.

Die Sitzung liegt im Symfony-Standard-Handler (Dateien unter `var/sessions/`, sofern
nicht anders konfiguriert). Das `REMEMBERME`-Cookie ist signaturbasiert
(`remember_me`-Standard, kein Token in der Datenbank) — es lässt sich deshalb serverseitig
nicht einzeln zurückziehen, was FB-04 begründet.

## Zugriffsregeln

| Wer | Darf | Erzwungen durch |
|---|---|---|
| Gast | Login-Seite sehen, abschicken | `access_control` `^/[a-z]{2}/login` = `PUBLIC_ACCESS` |
| Angemeldeter | `/profile` und Unterseiten | `^/[a-z]{2}/profile` = `IS_AUTHENTICATED_FULLY` **und** `#[IsGranted]` an der Controller-Klasse |
| `ROLE_ADMIN` | `/admin` und alles darunter | `^/[a-z]{2}/admin` = `ROLE_ADMIN` |
| Gast auf geschützter Seite | wird umgeleitet | `entry_point: form_login` merkt sich das Ziel über `TargetPathTrait` |

Die Regeln greifen über den **Pfad**, nicht über Voter. Das Muster `^/[a-z]{2}/…`
deckt die Sprachkomponente ab; die API-Regeln stehen davor, weil `access_control`
in Reihenfolge ausgewertet wird und die API-Pfade keinen Sprachpräfix haben.

⚠ `IS_AUTHENTICATED_FULLY` schließt `remember_me`-Sitzungen **aus**. Wer über das
Cookie wiederkommt, ist `IS_AUTHENTICATED_REMEMBERED` und wird beim Aufruf von
`/profile` erneut zur Anmeldung geschickt. Das ist sicherheitstechnisch die strengere
Wahl und wahrscheinlich beabsichtigt — im Alltag bedeutet es, dass „Angemeldet
bleiben" für die geschützten Bereiche wirkungslos ist.

## Missbrauchsschutz

| Endpunkt | Limit | Verhalten bei Überschreitung | Wo konfiguriert |
|---|---|---|---|
| `app_login` (Passwort) | **keins** | — | — (FB-01) |
| `app_login` (Passkey) | **keins** | — | siehe B03 |
| `app_logout` | **kein CSRF** | — | (FB-02) |

Vorhanden: CSRF auf dem Passwortformular (`authenticate`), generische Fehlermeldung
gegen Enumeration, Session-Migration gegen Fixation (Symfony-Vorgabe).

Zum Vergleich: Dieselbe Anmeldung über `/api/v1/auth/login` **ist** limitiert
(`api_login`, 5 pro Minute je IP, `ApiRateLimitSubscriber`). Der ungeschützte Weg ist
also ausgerechnet der, den ein Browser nimmt.

## Externe Dienste

Keine. Sentry (nur `prod`) sieht Exceptions; `AuthenticationException` wird von
Symfony abgefangen und erreicht Sentry nicht als Fehler.

## Erkennbare Entscheidungen

| # | Entscheidung | Alternative | Warum so, soweit erkennbar |
|---|---|---|---|
| 1 | Zwei Authenticator an einer Firewall | zweite Firewall für Passkeys | ein `check_path`, eine Weiterleitung, ein „Angemeldet bleiben" — ausführlich begründet in `PasskeyAuthenticator` |
| 2 | `enable_csrf: true` | Verzicht | Standard-Härtung; das Passkey-Formular verzichtet bewusst darauf, begründet im Partial |
| 3 | Generische Fehlermeldung | „E-Mail unbekannt" | Enumerationsschutz — inkonsistent mit dem Registrierformular (B01/AK-14) |
| 4 | `IS_AUTHENTICATED_FULLY` für `/profile` | `IS_AUTHENTICATED_REMEMBERED` | strengere Wahl; entwertet aber „Angemeldet bleiben" für den einzigen geschützten Nutzerbereich |
| 5 | Kein `login_throttling` | eine Konfigurationszeile | **Grund nicht erkennbar** — die API hat ein Limit, der Browser-Weg nicht |

## Abdeckung der Akzeptanzkriterien

| AK | Erfüllt durch | Anmerkung |
|---|---|---|
| AK-01 | `security/login.html.twig`, `partials/_passkey_login.html.twig` | Passkey-Knopf über Feature-Detection in `passkey_ui_controller.ts` |
| AK-02 | `if ($this->getUser())` in `SecurityController::login()` | |
| AK-03 | `form_login`, `default_target_path`, `TargetPathTrait` | |
| AK-04 | `AuthenticationUtils::getLastAuthenticationError()` / `getLastUsername()` | |
| AK-05 | Symfony-Standardverhalten `BadCredentialsException` | |
| AK-06 | `remember_me` mit `lifetime: 604800` | |
| AK-07 | `form_login.enable_csrf`, `csrf_token('authenticate')` | |
| AK-08 | `logout.path` / `target` | |
| AK-09 | `entry_point: form_login`, `access_control` | |
| AK-10 | `access_control` `^/[a-z]{2}/admin` = `ROLE_ADMIN` | |
| AK-11 ⚠ | **Abwesenheit** von `login_throttling` | Lücke, siehe FB-01 |
| AK-12 ⚠ | **Abwesenheit** von `logout.enable_csrf` | Lücke, siehe FB-02 |
| AK-13 ⚠ | **Abwesenheit** eines `user_checker` | identisch mit B01/AK-13 |
| AK-14 | Monolog-Konfiguration; kein Logging im Controller | |
| AK-15 | `framework.session` (Symfony-Vorgaben `cookie_httponly`, `cookie_secure: auto`) | |
| AK-16 | Symfony-Vorgabe `session_fixation_strategy: migrate` | |
| AK-17 | Feldbestand aus B01 | |

## Für `sdd-qa` besonders zu prüfen

1. **AK-11** — Brute-Force-Versuch in Schleife; zählen, ab wann gesperrt wird (Erwartung
   nach Bestand: nie).
2. **AK-12** — Logout über eine fremde Herkunft auslösen.
3. **Entscheidung 4** — praktisch prüfen, ob „Angemeldet bleiben" für `/profile`
   tatsächlich wirkungslos ist. Wenn ja, ist es ein Bedienfehler mit Sicherheitsnutzen
   und gehört benannt.
