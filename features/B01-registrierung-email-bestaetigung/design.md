# B01 · Registrierung & E-Mail-Bestätigung — Systemdesign

Status: `review` · Stand: 2026-08-23 · Stack-Profil: `symfony-doctrine`

**Kein Code in diesem Dokument.** Beschreibend, nicht vorschreibend: So ist es gebaut.

## Überblick

Ein klassischer Symfony-Formular-Ablauf ohne Bundle: `RegistrationController` nimmt
`RegistrationType` entgegen, hasht das Passwort über den `UserPasswordHasher`, lässt
die Entity selbst einen Bestätigungstoken erzeugen und schickt eine `TemplatedEmail`.
Gespeichert wird **vor** dem Versand, damit ein Transportfehler die Anmeldung nicht
verschluckt.

Die Bestätigung ist ein zweiter Controller: `EmailVerificationController` schlägt den
Token im `UserRepository` nach, prüft den Ablaufzeitpunkt, setzt `isVerified` und
leert Token und Frist. Ein dritter Weg — erneutes Senden — ist implementiert, aber
durch die Routenreihenfolge nicht erreichbar.

`symfonycasts/verify-email-bundle` wird **nicht** benutzt; die Signaturlogik ist von
Hand nachgebaut, allerdings als schlichter Zufallstoken in der Datenbank statt als
signierte URL.

## Seiten und Routen

| Route | Pfad | Zweck | Zugang |
|---|---|---|---|
| `app_register` | `/{_locale}/register` | Formular und Verarbeitung | öffentlich (`PUBLIC_ACCESS`), leitet Angemeldete weg |
| `app_verify_notice` | `/{_locale}/verify` | Hinweisseite „Schau in dein Postfach" | öffentlich |
| `app_verify_email` | `/{_locale}/verify/{token}` | Token einlösen | öffentlich |
| `app_verify_resend` | `/{_locale}/verify/resend` | Mail erneut senden | `IS_AUTHENTICATED_FULLY` — **nie erreichbar**, siehe unten |

⚠ **Routenreihenfolge:** Symfony wertet Routen in Deklarationsreihenfolge aus.
`/verify/{token}` steht in der Controller-Klasse vor `/verify/resend` und hat keine
Anforderung an `{token}`, fängt also jeden Ein-Segment-Pfad unter `/verify/` ab.
Behebbar durch Umsortieren der Methoden oder ein `requirements`-Muster auf `{token}`.

Alle drei Web-Routen sind über `access_control` mit `^/[a-z]{2}/verify` bzw.
`^/[a-z]{2}/register` als `PUBLIC_ACCESS` freigegeben — die Regel deckt auch
`resend` ab, dessen `#[IsGranted]` erst danach greift.

## Komponentenstruktur

```
registration/register.html.twig                Formularseite
└── RegistrationType                           Name · E-Mail · plainPassword (Repeated)
    └── User (data_class)                      plainPassword ist mapped:false

email_verification/notice.html.twig            Hinweisseite
└── Link auf app_verify_resend                 führt ins Leere (Routen-Kollision)

email/verification.html.twig                   Mailinhalt
└── extends email/base.html.twig               gemeinsames Branding aller Mails
```

Beteiligte Klassen:

| Klasse | Rolle |
|---|---|
| `App\Controller\RegistrationController` | Formular, Hashing, Token, Versand |
| `App\Controller\EmailVerificationController` | Hinweis, Einlösen, erneuter Versand |
| `App\Form\RegistrationType` | Felder und Validierung |
| `App\Entity\User` | Token-Erzeugung und Ablaufprüfung liegen **in der Entity** |
| `App\Repository\UserRepository::findByVerificationToken()` | Nachschlagen |

## Datenmodell

### Tabelle `user` (in Backticks, weil reserviertes Wort)

Nur die für B01 belegten Felder; die vollständige Referenz steht in
[`docs/data-model.md`](../../docs/data-model.md#user).

| Feld | Typ | Pflicht | Bedeutung |
|---|---|---|---|
| `id` | INT AUTO_INCREMENT | ja | Primärschlüssel |
| `name` | VARCHAR(100) | ja | Anzeigename, 2–100 Zeichen |
| `email` | VARCHAR(180) **UNIQUE** | ja | zugleich `getUserIdentifier()` |
| `roles` | JSON | ja | leer in der DB; `getRoles()` hängt `ROLE_USER` zur Laufzeit an |
| `password` | VARCHAR(255) | ja | Hash, Algorithmus `auto` |
| `is_verified` | TINYINT(1) | ja | Vorgabe `false` |
| `verification_token` | VARCHAR(64) | nein | 64 Hex-Zeichen; `null` nach Einlösen |
| `verification_token_expires_at` | DATETIME | nein | Anlage + 24 h; `null` = abgelaufen |
| `created_at` | DATETIME | ja | im Konstruktor gesetzt |

**Beziehungen:** keine für B01. (`avatarFilename` → B04, `webauthnHandle` und
`passkeys` → B03.)

**Indizes:** `UNIQUE(email)` erzwingt EC-01 auf Datenbankebene.
⚠ **Auf `verification_token` liegt kein Index**, obwohl `findByVerificationToken()`
genau danach sucht. Bei der aktuellen Kontozahl ohne Wirkung, aber es ist der einzige
Suchpfad des Features.

## Zugriffsregeln

| Wer | Darf | Erzwungen durch |
|---|---|---|
| Gast | registrieren, Token einlösen | `access_control` `^/[a-z]{2}/register`, `^/[a-z]{2}/verify` = `PUBLIC_ACCESS` |
| Angemeldeter | wird von `/register` weggeleitet | `if ($this->getUser())` in der Controller-Methode, **nicht** in der Firewall |
| Angemeldeter | erneut senden für **das eigene** Konto | `#[IsGranted('IS_AUTHENTICATED_FULLY')]` plus `$this->getUser()` — keine ID aus der Anfrage, damit kein IDOR möglich |
| beliebig | Token einlösen | wer den Token hat, verifiziert das zugehörige Konto. Das ist der Zweck; der Token ist das Geheimnis |

**Kein Voter, kein `user_checker`.** Der Zugang zu geschützten Seiten hängt allein an
`IS_AUTHENTICATED_FULLY`, nicht an `is_verified` — siehe AK-13 und FB-03.

## Missbrauchsschutz

| Endpunkt | Limit | Verhalten bei Überschreitung | Wo konfiguriert |
|---|---|---|---|
| `app_register` | **keins** | — | — (FB-01) |
| `app_verify_resend` | **keins** | — | — (FB-02) |
| `app_verify_email` | **keins** | — | Token-Erraten ist bei 256 Bit Entropie kein praktischer Weg |

Vorhanden ist nur, was das Formular selbst mitbringt: CSRF über den stateless
`submit`-Token (Same-Origin-Referer), Längenbegrenzung des Passworts gegen teure
Hash-Berechnung (EC-04), und der `UNIQUE`-Index.

## Externe Dienste

| Dienst | Wofür | Was geht hin | Was wird vorher entfernt |
|---|---|---|---|
| Brevo (Mail-API, Produktion) | Bestätigungsmail | Empfängeradresse, Anzeigename, Bestätigungs-URL **mit Token** | nichts — der Token muss mit |
| Mailpit (nur Entwicklung) | dasselbe, lokal | dito | — |

Der Bestätigungstoken verlässt damit das System über einen Auftragsverarbeiter. Das ist
bei jedem Double-Opt-In so und der Grund für die kurze Frist. Ob ein AV-Vertrag mit
Brevo vorliegt, geht aus dem Repository nicht hervor — gehört nach
`docs/datenschutz.md`, das noch nicht existiert.

Sentry (nur `prod`) sieht Exceptions dieses Features. `send_default_pii: false` und
`zend.exception_ignore_args = On` verhindern, dass Funktionsargumente — also das
Klartextpasswort aus dem Hasher-Aufruf — in einen Stacktrace geraten.

## Erkennbare Entscheidungen

| # | Entscheidung | Alternative | Warum so, soweit erkennbar |
|---|---|---|---|
| 1 | Token-Logik in der Entity statt in einem Service | `VerifyEmailBundle`, eigener Service | hält den Controller schlank; der Preis ist, dass die Frist nicht konfigurierbar ist |
| 2 | Zufallstoken in der DB statt signierter URL | `symfonycasts/verify-email-bundle` | keine Abhängigkeit; der Preis ist eine zusätzliche Spalte und eine Abfrage ohne Index |
| 3 | Erst speichern, dann senden | umgekehrt, oder Transaktion um beides | ein Transportfehler darf die Anmeldung nicht verschlucken — dieselbe Reihenfolge wie in `WaitlistConfirmationService` (B14/B15) |
| 4 | Keine automatische Anmeldung nach der Registrierung | `userAuthenticator->authenticateUser()` | Grund nicht erkennbar |
| 5 | `plainPassword` unmapped | Feld auf der Entity | das Klartextpasswort hängt nie an einem persistenten Objekt |
| 6 | Mails asynchron über Messenger | synchron | in `.env` so vorgesehen — ⚠ **auf Produktion aber `MESSENGER_TRANSPORT_DSN=sync://`**, dort läuft der Versand also im Request. Das macht AK-12 auf Produktion tatsächlich erreichbar |

## Abdeckung der Akzeptanzkriterien

| AK | Erfüllt durch | Anmerkung |
|---|---|---|
| AK-01 | `RegistrationController::register()`, `registration/register.html.twig` | |
| AK-02 | `if ($this->getUser())` am Methodenanfang | Controller, nicht Firewall |
| AK-03 | `RegistrationType`, `Length(min: 2)` auf `name` | |
| AK-04 | `RegistrationType`, `Length(min: 8)` auf `plainPassword` | |
| AK-05 | `RepeatedType`, `invalid_message: form.password_mismatch` | |
| AK-06 | `register()` — Hash, `generateVerificationToken()`, `persist`/`flush`, Mail, Redirect | |
| AK-07 | `UserPasswordHasherInterface`, `password_hashers: auto` | |
| AK-08 | kein Authenticator-Aufruf im Controller | Verhalten durch Abwesenheit — siehe Entscheidung 4 |
| AK-09 | `EmailVerificationController::verify()` | |
| AK-10 | `User::isVerificationTokenExpired()` | |
| AK-11 | `UserRepository::findByVerificationToken()` liefert `null` | deckt auch den eingelösten Token ab, weil er auf `null` gesetzt wurde |
| AK-12 | `try`/`catch (TransportExceptionInterface)` in `register()` | greift nur bei synchronem Versand — auf Produktion gegeben |
| AK-13 ⚠ | **Abwesenheit** eines `user_checker` in `security.yaml` | das Kriterium beschreibt eine Lücke, siehe FB-03 |
| AK-14 ⚠ | `#[UniqueEntity]` auf `App\Entity\User` | |
| AK-15 ⚠ | Routenreihenfolge in `EmailVerificationController` | nachweisbar über `router:match` |
| AK-16 | Feldbestand der Tabelle `user` | |
| AK-17 | Monolog-Konfiguration, `send_default_pii: false`, `zend.exception_ignore_args` | |
| AK-18 | `User::generateVerificationToken()` | |
| AK-19 | `setVerificationToken(null)` in `verify()` | |
| AK-20 | `TemplatedEmail` in `register()`, `config/packages/mailer.yaml` | |

Keine Zeile ohne Zuordnung. Umgekehrt bleibt **eine Methode ohne Kriterium**:
`EmailVerificationController::resend()` — sie ist über AK-15 nur in ihrer
Unerreichbarkeit beschrieben. Das ist der Hinweis auf toten Code, den diese Tabelle
sichtbar machen soll.

## Für `sdd-qa` besonders zu prüfen

1. **AK-15** reproduzieren (`router:match`, dann im Browser) — der Einzeiler-Fehler mit
   der größten Sichtbarkeit für Nutzer.
2. **AK-13** — anmelden mit einem unbestätigten Konto und durchklicken, welche Bereiche
   offenstehen.
3. **FB-01** — `/register` in Schleife aufrufen und zählen, wie viele Mails der
   Versanddienst annimmt.
4. **FB-04/FB-05** — beides sind Pflichten aus dem Katalog, keine Wünsche.
