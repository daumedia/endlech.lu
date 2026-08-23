# B03 · Passkey-Anmeldung & -Verwaltung — Spezifikation

Status: `rekonstruiert` · Stand: 2026-08-23 · **Rückerfassung aus dem Bestand**

## Zweck

Ein Nutzer meldet sich mit Face ID, Touch ID oder Geräte-PIN an, ohne eine E-Mail-Adresse
einzutippen — der Browser zeigt die passenden Konten selbst an. Im Profil legt er
weitere Passkeys an, benennt sie um und entfernt sie.

Die Begründung steht im CHANGELOG und ist ungewöhnlich klar: *„Endlech.lu richtet sich
an Menschen mit Behinderungen – und verlangte bislang genau eine Sache, die für viele
davon die größte Hürde ist: ein Passwort abzutippen."* Das Feature ist damit
Barrierefreiheit, nicht nur Sicherheit.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| B01 | rekonstruiert | ein Passkey wird an ein bestehendes Konto gehängt |
| B02 | rekonstruiert | teilt sich Firewall, `check_path` und Formularseite |

## User Stories

- **US-01** · Als Nutzer möchte ich mich ohne Passworteingabe anmelden.
- **US-02** · Als angemeldeter Nutzer möchte ich einen Passkey für dieses Gerät anlegen.
- **US-03** · Als Nutzer möchte ich meine Passkeys benennen, damit ich Geräte
  auseinanderhalte.
- **US-04** · Als Nutzer möchte ich einen verlorenen Passkey entfernen.

## Nicht im Scope

- **Konto per Passkey anlegen** — strukturell ausgeschlossen (siehe AK-12)
- Passkeys in der REST-API → nicht vorhanden, siehe FB-05
- Conditional UI / Autofill — vom Paket unterstützt, hier nicht aktiviert
- Attestation-Prüfung — `attestation_conveyance` bleibt `none`, bewusst

## Akzeptanzkriterien

- **AK-01** · Angenommen, der Browser kennt `window.PublicKeyCredential`, wenn
  `/{locale}/login` lädt, dann erscheint der Knopf „Mit Passkey anmelden" über dem
  Passwortformular.
- **AK-02** · Angenommen, der Browser kennt `window.PublicKeyCredential` **nicht**,
  wenn die Seite lädt, dann bleibt der Passkey-Bereich verborgen und nur das
  Passwortformular ist sichtbar.
- **AK-03** · Angenommen, ein Nutzer hat einen Passkey für diese Seite, wenn er den
  Knopf drückt und die Geräteprüfung besteht, dann ist er angemeldet und landet auf der
  Startseite oder auf seinem ursprünglichen Ziel — **ohne** eine E-Mail-Adresse
  eingegeben zu haben.
- **AK-04** · Angenommen, der Nutzer bricht die Geräteabfrage ab, wenn der Dialog
  schließt, dann erscheint **keine** Fehlermeldung — der Abbruch gilt als Entscheidung,
  nicht als Fehler.
- **AK-05** · Angenommen, die Assertion ist ungültig, wenn sie abgeschickt wird, dann
  landet der Nutzer wieder auf `/{locale}/login` mit der Flash-Meldung
  `flash.passkey_login_failed` — nicht mit der technischen Meldung des Prüfers.
- **AK-06** · Angenommen, ein Nutzer meldet sich per Passkey an, wenn der Datensatz
  danach betrachtet wird, dann ist der Signaturzähler fortgeschrieben und `lastUsedAt`
  gesetzt.
- **AK-07** · Angenommen, ein angemeldeter Nutzer legt im Profil einen Passkey an, wenn
  die Geräteprüfung besteht, dann erscheint er in der Liste mit einem aus dem
  User-Agent geratenen Namen („iPhone", „Mac", „Android", sonst „Passkey").
- **AK-08** · Angenommen, ein Nutzer benennt einen eigenen Passkey um, wenn das
  Formular abgeschickt wird, dann steht der neue Name in der Liste, auf 100 Zeichen
  gekürzt.
- **AK-09** · Angenommen, der eingegebene Name besteht nur aus Leerzeichen, wenn
  abgeschickt wird, dann bleibt der alte Name stehen und es erscheint
  `flash.passkey_name_empty`.
- **AK-10** · Angenommen, ein Nutzer löscht einen eigenen Passkey, wenn er bestätigt,
  dann verschwindet er aus der Liste und aus der Datenbank.
- **AK-11** · Angenommen, ein Nutzer ruft `app_passkey_rename` oder `app_passkey_delete`
  mit der ID eines **fremden** Passkeys auf, wenn die Anfrage durchläuft, dann antwortet
  der Server mit HTTP 403 — unabhängig davon, ob das CSRF-Token stimmt.
- **AK-12** · Angenommen, jemand versucht, über den Passkey-Weg ein **neues** Konto
  anzulegen, wenn die Anfrage durchläuft, dann lehnt das Bundle ab, weil
  `WebauthnUserEntityRepository` die Schnittstellen `CanRegisterUserEntity` und
  `CanGenerateUserEntity` nicht implementiert.
- **AK-13** · Angenommen, Umbenennen oder Löschen wird ohne JavaScript bedient, wenn
  das Formular abgeschickt wird, dann funktioniert beides — nur das **Anlegen** braucht
  zwingend ein Skript.

### Fragwürdiges Verhalten — als Kriterium aufgenommen, zur Klärung vorgelegt

- **AK-14** ⚠ · Angenommen, ein Passkey wird angelegt, dessen `userHandle` zu keinem
  Konto passt, wenn `saveCredentialRecord()` läuft, dann kehrt die Methode **stillschweigend**
  zurück — kein Log, keine Exception, keine Meldung an den Nutzer.
  *(So verhält sich der Code heute: `WebauthnCredentialRepository::saveCredentialRecord()`,
  `if (!$user instanceof User) { return; }`. Die Begründung im Kommentar ist
  nachvollziehbar — ein Passkey ohne Besitzer wäre weder sichtbar noch löschbar. Der
  Nutzer sieht aber die Erfolgsmeldung des Browsers und glaubt, es sei ein Passkey
  hinterlegt. Beim nächsten Anmeldeversuch scheitert er.)*

- **AK-15** ⚠ · Angenommen, jemand ruft `/passkey/login/options` beliebig oft auf, wenn
  die Anfragen durchlaufen, dann erzeugt der Server jedes Mal eine Challenge, ohne zu
  zählen.
  *(So verhält sich der Code heute: Die Bundle-Controller sind über `access_control`
  als `PUBLIC_ACCESS` freigegeben und von keinem Rate-Limiter erfasst. Die Endpunkte
  sind zudem sprachfrei, greifen also nicht in `ApiRateLimitSubscriber`, der nur
  `^/api/v1` behandelt.)*

### Datenschutz und Missbrauchsschutz

- **AK-16** · Angenommen, ein Passkey wird angelegt, wenn geprüft wird, welche
  personenbezogenen Daten entstehen, dann sind es: öffentlicher Schlüssel, Credential-ID,
  AAGUID, Signaturzähler, ein aus dem User-Agent geratener Gerätename, Anlage- und
  Nutzungszeitpunkt. **Kein privater Schlüssel** — der verlässt das Gerät nie.
- **AK-17** · Angenommen, ein Konto wird gelöscht, wenn die Datenbank betrachtet wird,
  dann verschwinden seine Passkeys mit (`ON DELETE CASCADE` auf `webauthn_credential.user_id`
  plus `orphanRemoval` im Mapping).
- **AK-18** · Angenommen, der WebAuthn-Handle wird betrachtet, wenn er mit der
  Datenbank-ID verglichen wird, dann sind sie verschieden: 16 Zufallsbytes in Hex, nicht
  die fortlaufende Zahl — die Nutzerzahl ist aus einem fremdverwahrten Wert nicht
  ablesbar.
- **AK-19** · Angenommen, eine Anmeldung per Passkey läuft, wenn die Geräteprüfung
  betrachtet wird, dann ist `user_verification: required` — Besitz allein genügt nicht,
  es braucht Biometrie oder PIN.
- **AK-20** · Angenommen, ein Angreifer klont einen Authenticator, wenn beide sich
  anmelden, dann fällt der zurückgebliebene Signaturzähler auf — deshalb schreibt
  `saveCredentialRecord()` bei **jeder** Anmeldung fort, nicht nur beim Anlegen.

## Edge Cases

- **EC-01** · Derselbe Passkey wird ein zweites Mal angelegt → verhindert durch
  `hide_existing_credentials: false`; der Browser kennt die vorhandenen Schlüssel und
  bietet den Vorgang nicht an.
- **EC-02** · Nutzer löscht seinen letzten Passkey → zulässig, das Passwort bleibt der
  Weg hinein. Ein Konto ohne beides kann es nicht geben, weil ein Passwort bei der
  Registrierung Pflicht ist.
- **EC-03** · Anmeldung mit gefülltem `_assertion` **und** `_username`/`_password` →
  `PasskeyAuthenticator` hat Priorität 0, `form_login` −30; der Passkey gewinnt.
- **EC-04** · `findOneByCredentialId()` bekommt die **rohe** Kennung; eine
  base64-Kodierung von Hand käme doppelt an, weil Doctrine anhand des Feld-Mappings
  selbst kodiert. Der Login schlüge dann mit „The credential ID is invalid" fehl.
- **EC-05** · Lokale Entwicklung über `http://localhost` → nur möglich, weil
  `when@dev` `allowed_origins` füllt; die serverseitige Prüfung verlangt sonst HTTPS,
  auch wenn Browser `localhost` als sicher behandeln.

## Fehlbestand

- **FB-01 · Kein Rate Limit auf den Challenge-Endpunkten.** Siehe AK-15.
- **FB-02 · Kein Hinweis auf den stillen Abbruch beim Anlegen.** Siehe AK-14 — die
  Methode sollte mindestens protokollieren.
- **FB-03 · Kein `trusted_hosts`, während `WebauthnBadge` den Host aus dem Request
  nimmt.** `PasskeyAuthenticator::authenticate()` übergibt `$request->getHost()`.
  Die Prüfung fällt zwar auf die konfigurierte `rp_id` zurück (`WEBAUTHN_RP_ID`), aber
  der Anwendungscode vertraut einem Header, den der Client setzt. Siehe B01/FB-09.
- **FB-04 · Keine Benachrichtigung, wenn ein Passkey angelegt oder entfernt wird.**
  Bei der Passwortänderung fehlt sie ebenso (B04/FB) — hier wiegt sie schwerer, weil
  ein hinzugefügter Passkey einem Angreifer dauerhaften Zugang verschafft.
- **FB-05 · Keine Passkey-Unterstützung in `/api/v1`.** Die iOS-App kann nur mit
  Passwort anmelden — ausgerechnet auf dem Gerät, auf dem Face ID selbstverständlich
  ist. Ausdrücklich als „bewusst nicht enthalten" in `CLAUDE.md` vermerkt.
- **FB-06 · Kein `WEBAUTHN_RP_ID` in `.env.local` dokumentiert.** `.env` trägt
  `localhost`; ob auf Produktion `endlech.lu` gesetzt ist, geht aus dem Repository nicht
  hervor. Ein falscher Wert macht jede Anmeldung unmöglich — und fällt erst nach dem
  Deploy auf.

## Offene Fragen

- **OF-01** · Soll das Anlegen bei unbekanntem Handle scheitern statt still zu enden
  (AK-14)? — Betreiber
- **OF-02** · Soll Conditional UI aktiviert werden? Das Paket kann es; es würde die
  Passkey-Anmeldung noch einen Schritt kürzer machen. — Betreiber
- **OF-03** · Ist `WEBAUTHN_RP_ID` auf Produktion gesetzt und korrekt? — Betreiber,
  vor der nächsten Auslieferung

## Decision Log

| # | Frage | Entscheidung im Bestand | Begründung |
|---|---|---|---|
| 1 | Firewall-Schlüssel `webauthn:` oder eigener Authenticator | eigener | der Bundle-Schlüssel ist für 6.0 abgekündigt und verlangt `Content-Type: application/json`; über das Formular läuft alles durch dieselbe Mechanik wie das Passwort |
| 2 | Kontoerstellung per Passkey | strukturell ausgeschlossen | Schnittstellen nicht implementiert — verlässlicher als eine Konfigurationszeile |
| 3 | `webauthnHandle` statt Datenbank-ID | eigener Zufallswert | der Wert liegt dauerhaft auf fremdem Gerät; eine fortlaufende Zahl gäbe die Nutzerzahl preis |
| 4 | 16 statt 32 Zufallsbytes | 16 | `PublicKeyCredentialUserEntity` erzwingt `strlen($id) <= 64`; 32 Bytes wären als Hex genau 64 und lägen auf der Grenze |
| 5 | `allowed_origins` leer auf Produktion | leer | gefüllt gilt nur exakter Origin-Abgleich inkl. Port, Einträge ohne Schema werden still auf `https://…:443` normalisiert |
| 6 | Besitzprüfung vor CSRF-Prüfung | so | wer nicht Eigentümer ist, hat dort unabhängig vom Token nichts verloren; 403 statt Weiterleitung |
| 7 | Gerätename aus dem User-Agent, nicht übersetzt | Produktnamen | der Wert wird einmal festgeschrieben; ein übersetzter trüge für immer die Sprache jenes Moments |
| 8 | Attestation | `none` | ein Attestation-Zwang sperrte Authenticator aus, ohne dass jemand die Herstellerdaten auswertet |
