# 12 · Onboarding für neue Nutzer — Systemdesign

Status: `architected` · Stand: 2026-10-22 · Stack-Profil: `symfony-doctrine`

**Kein Code in diesem Dokument.** Es wird gelesen und freigegeben, nicht ausgeführt.

## Überblick

Die Einführung besteht aus vier gewöhnlichen, serverseitig gerenderten Seiten unter
`/{locale}/einfuehrung/…`. Ob jemand nach der Anmeldung dort landet statt auf der
Startseite, entscheidet **eine** Stelle: ein Zuhörer auf die erfolgreiche Anmeldung, der
nur dann umlenkt, wenn die Anmeldung sonst auf der Startseite enden würde — eine zuvor
gemerkte Zielseite (B11) bleibt damit unangetastet. Am Konto stehen zwei neue Angaben:
ein **Merker**, der beim Bestätigen der Adresse gesetzt wird („Einführung vorgesehen"),
und das **Datum**, an dem sie zum ersten Mal angezeigt wurde. Der Newsletter-Schritt und
der neue Schalter im Profil benutzen denselben Dienst; er schreibt die Einwilligung ans
Konto und ins Auftragsbuch von Feature 04, das der bestehende Fünf-Minuten-Lauf nach
Brevo trägt. Neu ist dort nur ein **Rückweg**: Abbestellen sperrt den Kontakt in Brevo.
Die Willkommens-Mail selbst schreibt die Anwendung nicht — sie ist eine Automation in
Brevo, die auf neue Kontakte mit Herkunft „Konto" reagiert.

## Seiten und Routen

| Route | Zweck | Zugang |
|---|---|---|
| `GET /{locale}/einfuehrung/{schritt}` (`app_onboarding`) | einer der vier Bildschirme: `willkommen` (Vorgabe, auch ohne Segment), `eintraege`, `profilbild`, `newsletter` | angemeldet (auch „angemeldet bleiben"), **bestätigt** |
| `POST /{locale}/einfuehrung/profilbild` (`app_onboarding_avatar`) | Bild hochladen, danach zurück auf denselben Bildschirm | angemeldet, bestätigt |
| `POST /{locale}/einfuehrung/newsletter` (`app_onboarding_newsletter`) | Häkchen auswerten, danach Restaurantsuche | angemeldet, bestätigt |
| `POST /{locale}/profile/newsletter` (`app_profile_newsletter`) | Schalter im Profil, beide Richtungen, danach zurück ins Profil | angemeldet (auch unbestätigt, AK-38) |
| `GET /{locale}/verify/{token}` (`app_verify_email`, **Änderung**) | wie bisher; zusätzlich: Merker setzen, und ist dasselbe Konto bereits angemeldet, Weiterleitung auf `willkommen` statt auf die Anmeldeseite (AK-05) | öffentlich |
| Ziel nach der Anmeldung (**Änderung**, keine eigene Route) | Startseite → `app_onboarding` (`willkommen`), wenn das Konto fällig ist | — |

Umleitungen im Ablauf:

| Lage | Antwort |
|---|---|
| Gast ruft `einfuehrung/…` auf | Anmeldeseite; die Adresse wird als Ziel gemerkt, nach der Anmeldung landet man dort (AK-10) |
| angemeldet, unbestätigt | Weiterleitung auf `/{locale}/verify` mit Hinweis (AK-11) |
| `newsletter`, Konto hat schon eingewilligt | Weiterleitung auf die Restaurantsuche (AK-13: der Bildschirm existiert für dieses Konto nicht) |
| unbekannter Schritt | 404 (durch die Routenbedingung) |

Nach dem letzten Bildschirm geht es in die **bestehende** Restaurantsuche. Eine
Erfolgsseite gibt es nicht.

## Komponentenstruktur

Wiederverwendet und nur benannt: das Seitengerüst `base.html.twig` (Kopfzeile, untere
Navigationsleiste, Sprachumschalter), `partials/_form_field.html.twig`,
`partials/_avatar.html.twig`, `partials/_verified_badge.html.twig`, der Stimulus-Auslöser
`usage-event`.

```
Einführung (ein Template je Bildschirm, gemeinsamer Rahmen)
├── Schrittkopf                Überschrift „Schritt N von M · <Titel>", beim Laden fokussiert
├── Inhalt des Bildschirms
│   ├── Willkommen             Gruß mit Namen; Karten „Restaurant vorschlagen" und „App-Warteliste"
│   ├── Einträge lesen         Abzeichen „Verifiziert", „Nicht verfügbar", Maße — mit den Begriffen der Detailseite
│   ├── Profilbild             vorhandenes Bild, Upload-Formular mit Feld­fehler, Hinweis, wo das Bild erscheint
│   └── Newsletter             Häkchen (leer), Erklärung, Absendeknopf „Restaurants suchen"
└── Fußleiste                  „Zurück" (ab 2), „Weiter" bzw. „Restaurants suchen", „Einführung beenden"

Profilseite (bestehend, ergänzt)
├── Abschnitt „Newsletter"     Stand mit Datum, ein Knopf in eigenem Formular; Hinweis bei unbestätigter Adresse
└── Link „Einführung ansehen"  für bestätigte Konten, führt auf `willkommen`

Verwaltungsübersicht (bestehend, ergänzt)
└── Kachel „Einführung"        zwei Zahlen: vorgesehen · davon angezeigt
```

Die vier Zustände je Bildschirm:

| Bildschirm | leer | ladend | Fehler | gefüllt |
|---|---|---|---|---|
| Willkommen | — (kein Datenbezug) | gewöhnlicher Seitenwechsel | — | Gruß, zwei Karten |
| Einträge lesen | — | — | — | statische Erklärung |
| Profilbild | kein Bild: Platzhalter mit Initiale (`_avatar`) | Absendeknopf während des Hochladens gesperrt, Seite bleibt bedienbar | 422, Meldung am Feld, nichts gespeichert (AK-26, AK-27) | Bild sichtbar, Erfolgsmeldung |
| Newsletter | Häkchen leer | — | Deckel erreicht: Meldung, Stand unverändert (AK-59) | — (nach dem Absenden geht es in die Suche) |
| Profil · Newsletter | „nicht abonniert" | — | Deckel erreicht: Meldung oben im Profil | „abonniert seit <Datum>"; bei unbestätigter Adresse der Zusatz aus AK-38 |
| Kachel Verwaltung | 0 · 0 | — | — | zwei Zahlen |

**Ohne JavaScript bedienbar** ist jeder Bildschirm von selbst (gewöhnliche Links und
Formulare). Nur die Messung braucht das Skript — ohne es wird nicht gezählt, sonst
ändert sich nichts.

## Datenmodell

### Tabelle `user` — **Änderung**, zwei neue Spalten

| Feld | Typ | Pflicht | Bedeutung |
|---|---|---|---|
| `onboarding_due` | boolean, Vorgabe `false` | ja | **Einführung vorgesehen.** Gesetzt beim Bestätigen der Adresse über `app_verify_email`. Bestandskonten tragen `false` und bleiben dabei (AK-06); wer am Tag der Auslieferung unbestätigt ist, bekommt den Merker bei seiner Bestätigung (AK-07) |
| `onboarding_shown_at` | datetime (unveränderlich), leer erlaubt | nein | **Zum ersten Mal angezeigt am.** Gesetzt, sobald ein Bildschirm der Einführung erstmals ausgeliefert wird; danach nie überschrieben |

Fällig ist ein Konto genau dann, wenn `is_verified` **und** `onboarding_due` **und**
`onboarding_shown_at` leer ist.

Keine Beziehungen, keine Indizes (die Zählung in der Verwaltung läuft über eine
Tabelle mit wenigen hundert Zeilen). **Beim Löschen des Kontos** verschwinden beide
Angaben mit der Zeile — es gibt nichts nachzuräumen.

⚠ **Abweichung von AK-53** („genau ein Datum"): Der Merker ist eine zweite Angabe. Ohne
ihn ließen sich Bestandskonten von neuen nur über einen Stichtag oder ein erfundenes
Datum unterscheiden (Entscheidung 2). Als **OF-05** an die Spec zurückgegeben, AK-53 dort
mit Vermerk neu gefasst.

Migration: zwei `ADD COLUMN`, kein Datenumbau.

### Tabelle `marketing_contact` (Feature 04) — **Änderung**, kein neues Feld

Der Zustand `sync_state` bekommt einen fünften Wert **`blacklist_pending`** („Sperre an
Brevo übertragen"). Die Spalte ist ein `VARCHAR(20)`; der Wert hat 17 Zeichen, eine
Migration ist nicht nötig. Die Verwaltungsansicht von Feature 04 braucht dafür eine
Beschriftung.

Zwei Verhaltensänderungen im Auftragsbuch:

- **Sprache des Kontakts** kommt für Konten aus der Anfrage, in der eingewilligt oder
  bestätigt wurde (`lb`, `de`, `fr`, `en`, `pt`) — bisher stand dort fest `de`. Wird ein
  Kontakt ohne Anfrage neu abgeleitet (BF-84, Wechsel der Quelle), bleibt die schon
  gespeicherte Sprache stehen.
- **Abbestellen über die Anwendung** setzt `revoked_at` (wie die Abmeldung über Brevo)
  und, wenn der Kontakt schon einmal übertragen wurde (`synced_at` gesetzt), den Zustand
  `blacklist_pending`. Ein nie übertragener Kontakt bleibt einfach gesperrt — bei Brevo
  gibt es ihn nicht.
  ⚠ **Überholt durch BF-167 und BF-181 (2026-10-23):** Ob es den Kontakt bei Brevo gibt,
  lässt sich an `synced_at`, `attempts` und dem Zustand nicht sicher ablesen (verlorene
  Antwort, Adresswechsel, neue Einwilligung). Abbestellen stellt deshalb für **jede**
  bestehende Zeile den Sperrauftrag, außer über einem Löschauftrag. Der Auftrag legt bei
  Brevo nichts an (`PUT` auf die `ext_id`, 404 = Erfolg) — AK-46 bleibt gewahrt.

## Zugriffsregeln

| Wer | Darf lesen | Darf schreiben | Erzwungen durch |
|---|---|---|---|
| Gast | nichts der Einführung | nichts | `access_control`: neue Zeile `^/[a-z]{2}/einfuehrung` → `IS_AUTHENTICATED_REMEMBERED`, **vor** keinem Catch-all (es gibt keinen) |
| angemeldet, unbestätigt | nichts der Einführung | eigene Einwilligung im Profil | Prüfung im Controller der Einführung (Weiterleitung, AK-11); Profil wie bisher über `^/[a-z]{2}/profile` |
| angemeldet, bestätigt | eigene Bildschirme | eigenes Profilbild, eigene Einwilligung | Controller arbeiten ausschließlich mit dem angemeldeten Konto; **keine** Kontokennung in Adresse oder Formular (AK-57) |
| Admin | Summen in der Übersicht | — | `^/[a-z]{2}/admin` → `ROLE_ADMIN` (B19) |
| fremde Website | — | nichts | CSRF: Formulare der Einführung über Symfony-Formulare (zustandsloses Token wie im Projekt), der Profilschalter über ein eigenes Token `profile-newsletter` (wie die Passkey-Formulare) — AK-58 |

⚠ Die Profilregel steht auf `IS_AUTHENTICATED_REMEMBERED` (BF-15); die neue Zeile
folgt ihr bewusst. Nichts in der Einführung ist so empfindlich, dass es eine frische
Anmeldung verlangte — Bild und Einwilligung sind jederzeit umkehrbar.

## Missbrauchsschutz

| Endpunkt | Limit | Verhalten bei Überschreitung | Wo konfiguriert |
|---|---|---|---|
| `app_profile_newsletter` **und** `app_onboarding_newsletter` (gemeinsames Kontingent) | **10 Wechsel je Stunde, gezählt am Konto** (gleitendes Fenster) | Stand unverändert, Meldung „Zu viele Änderungen, bitte in N Minuten erneut", nichts ins Auftragsbuch | neuer Limiter `newsletter_toggle` in `config/packages/framework.yaml`, `when@test` 10000; Abfrage über `ActionLimiter`, **verbraucht erst, wenn sich der Stand tatsächlich ändert** (AK-60, BF-11) |
| `app_onboarding_avatar` | kein eigener Deckel | — | Begründung in der Spec (keine Mail, kein Geheimnis, kein Bestandsladen); Größe 2 MB und Typprüfung am Inhalt wie im Profil |
| Bildschirme der Einführung (GET) | kein Deckel | — | reine Lesezugriffe aufs eigene Konto |

`LimiterCoverageTest` muss den neuen Limiter als verdrahtet sehen; er wird es, weil beide
Controller ihn injizieren.

## Externe Dienste

| Dienst | Wofür | Was geht hin | Was wird vorher entfernt bzw. nie mitgeschickt |
|---|---|---|---|
| **Brevo** (bestehend, Feature 04) | Kontakt anlegen, fortschreiben, sperren; Willkommens-Mail per Automation | Adresse, `ext_id`, die fünf Attribute aus `04/AK-07` (`CONTACT_NAME`, `ORGANISATION` leer, `LOCALE`, `ORIGIN=ACCOUNT`, `FUNNEL_STATUS` leer), Listenzuordnung, beim Abbestellen bzw. erneuten Einwilligen `emailBlacklisted` | Merker und Datum der Einführung, Profilbild, **auf welchem Weg** eingewilligt wurde |
| **Umami** (bestehend, Feature 11) | drei Ereignisse der Einführung | Ereignisname, bei „beendet" die Bildschirmnummer `1`–`4` | Konto, Name, Adresse, Häkchen, ob ein Bild hochgeladen wurde. Der Normalisierer lässt nur die drei Namen und die Ziffern 1–4 durch |

**Brevo-Automation „Willkommen · Konto"** (Plattformeinstellung, eine Aufgabe für den
Betreiber):

| Baustein | Einstellung | wofür |
|---|---|---|
| Einstieg | Kontakt wird der Liste aus `BREVO_LIST_ID` hinzugefügt | entsteht beim ersten Abgleich eines neuen Kontakts |
| Bedingung | `ORIGIN` ist `ACCOUNT` | AK-43: Wartelisten-Kontakte bekommen sie nicht |
| Wiederholung | „Kontakte können den Ablauf nur einmal durchlaufen" | AK-44, zweite Sicherung neben der Sperre statt Löschung |
| Verzweigung | nach `LOCALE` in fünf Mails | AK-40 |
| Inhalt | AK-47, AK-48; Abmeldelink von Brevo | AK-41 |
| Bestand | beim Einschalten **keine** vorhandenen Kontakte einbeziehen | AK-45 |

Abbestellen und Sperre in Brevo: Ein gesperrter Kontakt bekommt von Brevo weder
Kampagne noch Automationsmail — damit trägt die Sperre AK-36 und AK-46.

## Technische Entscheidungen

| # | Entscheidung | Alternative | Warum so |
|---|---|---|---|
| 1 | **Weiche nach der Anmeldung als Zuhörer auf die erfolgreiche Anmeldung**, der nur eine Weiterleitung auf die Startseite durch eine auf `willkommen` ersetzt | eigener Erfolgs-Handler für `form_login`, zweiter im Passkey-Authenticator | Eine Stelle für beide Anmeldewege. Symfony bestimmt das Ziel weiter selbst — eine gemerkte Zielseite (B11, Gast auf `einfuehrung`) ist danach schon eingelöst und kein Sonderfall mehr. „Angemeldet bleiben" liefert keine Weiterleitung und bleibt unberührt (EC-06). Verglichen wird der **Pfad**, weil `form_login` eine absolute, der Passkey-Weg eine relative Adresse liefert |
| 2 | **Merker „vorgesehen" am Konto**, gesetzt beim Bestätigen | Stichtag im Code und Vergleich mit `created_at`; Datenumbau, der Bestandskonten ein Datum einträgt | Ein Stichtag verfehlt AK-07 (vor der Auslieferung angelegt, danach bestätigt). Ein eingetragenes Datum behauptete, jemand habe etwas gesehen, das er nie sah — und stünde so im Datenexport. Der Merker kostet ein Feld mehr als AK-53 (→ OF-05) |
| 3 | **„Gesehen" beim Ausliefern eines Bildschirms** | beim Klick auf „Einführung beenden" | AK-02 verlangt „einmal angezeigt"; wer den Tab schließt, klickt nichts |
| 4 | **Abbestellen sperrt den Kontakt in Brevo, statt ihn zu löschen** | Löschauftrag wie bei der Kontolöschung | Ein gelöschter und neu angelegter Kontakt wäre für Brevo ein neuer — die Willkommens-Mail käme ein zweites Mal (AK-44). Die Sperre hält zudem Brevos eigene Abmeldeerinnerung, wie Feature 04 es für die Abmeldung über den Link schon entschieden hat (dort Entscheidung 7) |
| 5 | **Abbestellen wirkt für die Adresse, nicht nur fürs Konto** — die Einwilligung fällt an allen Quellen (Konto, Wartelisten) | nur die Konto-Einwilligung zurücknehmen | Es gibt **einen** Brevo-Kontakt je Adresse; eine Sperre trifft ihn ganz. Dieselbe Wirkung wie der Abmeldelink (`blockByEmail`). Der Text im Profil sagt es |
| 6 | **Erneutes Einwilligen hebt eine Sperre in Brevo auf** — `emailBlacklisted: false` wird nur mitgeschickt, wenn die Zeile eine Sperre trägt **und** die Einwilligung jünger ist | das Feld bei jedem Abgleich mitsenden; gar nicht | Ohne das stünde im Profil „abonniert", während Brevo nichts zustellt (gilt heute schon nach einer Abmeldung über den Link, `04/AK-45`). Bei jedem Abgleich mitgesendet hübe es eine Abmeldung auf, deren Webhook noch nicht angekommen ist — gegen den erklärten Willen |
| 7 | **Sprache des Kontakts aus der Anfrage** | Sprache am Konto speichern | Die Spec schließt eine Kontosprache aus. Eingewilligt bzw. bestätigt wird immer in einer Anfrage mit Sprache; das reicht für die eine Mail |
| 8 | **Willkommens-Mail als Brevo-Automation** mit Einstieg „Liste" und Bedingung `ORIGIN` | Mail aus der Anwendung mit eigenem Abmeldeweg | Spec-Entscheidung 9. Folge für OF-03: Wer schon als Wartelisten-Kontakt in der Liste steht, wird nicht neu hinzugefügt und bekommt **keine** Willkommens-Mail — die Frage bleibt beim Betreiber, dieses Verhalten ist die Vorgabe, bis er sie beantwortet |
| 9 | **„Einträge lesen" rendert dieselben Katalogschlüssel wie die Detailseite** (`verified.*`, `accessibility.not_available`, `accessibility.not_measured`, `accessibility.below_minimum`) | eigene Texte für die Einführung | AK-23 ist dann strukturell erfüllt: ändert jemand den Begriff auf der Detailseite, ändert er ihn hier mit |
| 10 | **Gemeinsamer Dienst für Einwilligen/Abbestellen** (Profil und Einführung) | Logik je Controller | Zwei Wege, ein Verhalten (AK-35 = AK-33); ein gemeinsames Kontingent ist nur so zählbar |
| 11 | **Neuer Sync-Zustand statt neuem Feld** | Spalte „in Brevo gesperrt" | Der Lauf arbeitet ohnehin nach Zustand; ein Auftrag „Sperre übertragen" passt in dieselbe Mechanik wie „Löschung übertragen", inklusive Wiederholung bei Fehlschlag (`04/AK-19`) |

## Abdeckung der Akzeptanzkriterien

Ausgeführt gegen `spec.md`, Kriterium für Kriterium.

| AK | Erfüllt durch | Anmerkung |
|---|---|---|
| AK-01 | Zuhörer auf die Anmeldung (Entscheidung 1) + Fälligkeitsregel (Datenmodell) | |
| AK-02 | `onboarding_shown_at` beim Ausliefern gesetzt (Entscheidung 3); Fälligkeitsregel verlangt es leer | |
| AK-03 | Entscheidung 1: gemerkte Zielseite ist keine Weiterleitung auf die Startseite → keine Umlenkung, Datum bleibt leer | |
| AK-04 | Fälligkeitsregel verlangt `is_verified`; Merker entsteht erst beim Bestätigen | |
| AK-05 | Änderung an `app_verify_email`: angemeldet als dasselbe Konto → `willkommen` | |
| AK-06 | `onboarding_due` Vorgabe `false`, kein Datenumbau | |
| AK-07 | Merker wird beim Bestätigen gesetzt, unabhängig vom Anlagedatum | |
| AK-08 | Konten aus der App bestätigen über dieselbe Route `app_verify_email` | im Test mit einem über `/api/v1/auth/register` angelegten Konto |
| AK-09 | Link „Einführung ansehen" im Profil → `willkommen`; der Bildschirm ist für jedes bestätigte Konto erreichbar | |
| AK-10 | `access_control` `^/[a-z]{2}/einfuehrung` | |
| AK-11 | Prüfung im Controller der Einführung → `/verify` | |
| AK-12 | Reihenfolge der Schritte im Controller, Fußleiste | |
| AK-13 | Newsletter-Schritt fällt bei bestehender Einwilligung weg (Zählung M, Weiterleitung) | |
| AK-14 | Schrittkopf „Schritt N von M" | |
| AK-15 | „Weiter" ist ein Link, kein Absenden | |
| AK-16 | „Zurück" in der Fußleiste ab Bildschirm 2 | |
| AK-17 | „Einführung beenden" auf jedem Bildschirm; letzter Bildschirm „Restaurants suchen" | auf `newsletter` ist es der Absendeknopf, auf `profilbild` (wenn letzter) ein Link |
| AK-18 | Seitengerüst `base.html.twig` mit Kopfzeile und unterer Leiste | Einführung liegt nicht unter `admin_*` |
| AK-19 | Textumfang der Bildschirme; Nachweis durch Messung mit einer Person | **Abnahmekriterium**, in `qa-report.md` mit Stoppuhr |
| AK-20 | Bildschirm Willkommen, zwei Karten | Ziele `community_vorschlagen`, `app_app_waitlist` |
| AK-21 | Texte der Einführung; Prüflauf über alle fünf Kataloge auf die Wortstämme von „Bewertung", „Kommentar", „Favorit" | Muster von `testKeineSeiteBietetNochEinenBeiratAn`; die Mail prüft der Betreiber in Brevo |
| AK-22 | Bildschirm Einträge lesen, Punkte a–c; d entfällt bis OF-01 | |
| AK-23 | Entscheidung 9 | |
| AK-24 | eigener Satz auf dem Bildschirm, sinngleich mit `criteria.score_unmeasured` | |
| AK-25 | Upload-Formular der Einführung, `AvatarUploadService` | |
| AK-26 | dieselben Einschränkungen und Meldungsschlüssel wie `ProfileType` (2 MB, JPG/PNG/WebP), 422 | |
| AK-27 | Typprüfung am Dateiinhalt (Symfony prüft den erkannten MIME-Typ, nicht die Endung) | |
| AK-28 | Hinweistext auf dem Bildschirm Profilbild | |
| AK-29 | `_avatar` zeigt das vorhandene Bild; Hochladen ersetzt (Verhalten des Dienstes) | |
| AK-30 | Formularfeld ohne Vorbelegung | |
| AK-31 | Erklärtext auf dem Bildschirm | |
| AK-32 | gemeinsamer Dienst: ohne Häkchen kein Aufruf | |
| AK-33 | gemeinsamer Dienst → Einwilligung am Konto → Auftragsbuch (Sprache aus der Anfrage, Entscheidung 7) → Fünf-Minuten-Lauf | Zusage `04/AK-10` |
| AK-34 | Profil-Abschnitt „Newsletter" | |
| AK-35 | gemeinsamer Dienst (Entscheidung 10) | |
| AK-36 | Abbestellen → `revoked_at` + `blacklist_pending` → Sperre beim nächsten Lauf (Entscheidungen 4, 11) | ≤ 5 Min Takt + Lauf |
| AK-37 | bestehender Brevo-Webhook (`blockByEmail`) leert die Einwilligung am Konto; das Profil liest sie | Webhook muss in Brevo eingerichtet sein (Feature 04) |
| AK-38 | Dienst speichert die Einwilligung; Auftragsbuch nimmt sie erst bei bestätigter Adresse; Hinweistext im Profil; beim Bestätigen trägt der bestehende Aufruf in `app_verify_email` nach | |
| AK-39 | keine Abhängigkeit anderer Wege von der Einwilligung (unverändert) | |
| AK-40 | Brevo-Automation (Einstieg, Verzweigung nach `LOCALE`) + Sprache aus der Anfrage | **Plattformeinstellung**, Nachweis Bildschirmfoto + Testempfang |
| AK-41 | Abmeldelink von Brevo → Webhook → `blockByEmail` | Plattformeinstellung + bestehender Webhook |
| AK-42 | ohne Einwilligung kein Eintrag im Auftragsbuch, also kein Kontakt | |
| AK-43 | Bedingung `ORIGIN = ACCOUNT` in der Automation | Plattformeinstellung |
| AK-44 | Sperre statt Löschung (Entscheidung 4) + „nur einmal" in der Automation | Plattformeinstellung + Anwendung |
| AK-45 | Automation ohne Bestandseinbezug | Plattformeinstellung |
| AK-46 | Abbestellen vor dem ersten Abgleich: Kontakt entsteht nie; danach: Sperre in Brevo | |
| AK-47 | Inhalt der Mails in Brevo | Plattformeinstellung, Prüfung durch Lesen |
| AK-48 | Inhalt der Mails; Attributliste der Anwendung unverändert | Plattformeinstellung |
| AK-49 | zwei Zählungen in `AdminStatsService`, Kachel in `admin/dashboard.html.twig` | |
| AK-50 | drei Ereignisse in `UsageEventCatalogue`, Auslöser im Template (beim Erscheinen von Willkommen; Klick auf „beenden"; Absenden bzw. Klick auf „Restaurants suchen") | |
| AK-51 | Normalisierer lässt je Ereignis nur die festen Schlüssel durch; Werte fest im Template | |
| AK-52 | Profilpfade sind von der Messung ausgenommen (`CollectPayloadNormalizer`, `before_send.ts`); der Schalter löst kein Ereignis aus | |
| AK-53 | zwei Spalten am Konto | ⚠ Abweichung, **OF-05**; AK-53 in der Spec mit Vermerk neu gefasst |
| AK-54 | Bildschirme enthalten nur Upload und Häkchen | |
| AK-55 | neue Wege protokollieren nichts Personenbezogenes; Upload-Dienst speichert unter erzeugtem Namen | im Bau gegen das Protokoll prüfen |
| AK-56 | Attributliste des Mappers unverändert; neue Angaben bleiben lokal | |
| AK-57 | Controller arbeiten nur mit dem angemeldeten Konto, keine Kennung im Formular | |
| AK-58 | CSRF-Schutz beider Formulararten (Zugriffsregeln) | |
| AK-59 | Limiter `newsletter_toggle`, Schlüssel = Konto | |
| AK-60 | Verbrauch nur bei tatsächlichem Wechsel | |
| AK-61 | Spalten an der Kontozeile; Brevo-Löschung und Bild über die bestehende Kaskade (Feature 01) | |
| AK-62 | `AccountDataExporter` um Merker, Datum und Newsletter-Stand ergänzen | |
| AK-63 | Schlüssel bleibt serverseitig (unverändert) | |
| AK-64 | fünf Kataloge, `CatalogueCompletenessTest` | |
| AK-65 | Sprachumschalter erzeugt dieselbe Route mit demselben Schritt | im Bau prüfen, dass der Parameter `schritt` mitgeht |
| AK-66 | gewöhnliche Links und Formulare, sichtbarer Fokus (`focus:outline-2`) | |
| AK-67 | Überschrift mit Schritt im Seitentitel und als fokussierte Überschrift; Fehler über `_form_field` | |
| AK-68 | `min-h-[44px]` an allen Knöpfen und Links der Fußleiste | |

Keine Zeile ist leer. Zehn Kriterien (AK-40, AK-41 teilweise, AK-43 bis AK-45, AK-47,
AK-48, AK-19) hängen ganz oder teilweise an einer Plattformeinstellung oder einer
Messung mit einer Person und brauchen in `tasks.md` eine eigene Aufgabe mit Nachweis.
