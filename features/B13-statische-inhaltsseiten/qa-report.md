# B13 · Statische Inhaltsseiten — Testbericht

Stand: 2026-08-24 · Vorstufe: `rekonstruiert` · Branch `fix/b04-profil-qa`

## Fazit

**Production-ready: ja** — zwei Befunde, beide inhaltlich, keiner technisch.

12 von 12 Kriterien bestanden. Die Seiten selbst sind unauffällig: drei Controller ohne
Datenbankzugriff, alle vier Sprachen liefern übersetzte Überschriften (`Über Endlech` /
`About Endlech` / `À propos d'Endlech` / `Iwwer Endlech`), und der Cookie-Banner-Link
zeigt auf `/de/legal#datenschutz`, wo ein `<section id="datenschutz" class="mb-8
scroll-mt-24">` wartet.

**Der Befund, der zählt, ließ sich messen — anders als die Spec annahm.** Sie schreibt zu
AK-07, ob die Datenschutzerklärung die tatsächliche Verarbeitung abbildet, *„lässt sich
aus dem Repository nicht feststellen"*. Man kann es feststellen: Die Erklärung nennt
**einen** von drei Empfängern.

| Dienst | Was er empfängt | in der Erklärung |
|---|---|---|
| **Sentry** | technische Fehlerdaten (nur `prod`) | ✅ genannt, mit Rechtsgrundlage |
| **Brevo** | **jede E-Mail-Adresse** (Registrierung, Wartelisten, Bestätigungen) | ❌ **nicht genannt** |
| **HAFAS / Verkéiersverbond** | Restaurantkoordinaten bei jedem Detailseitenaufruf | ❌ **nicht genannt** |

Nächster Aufruf: **`/sdd-erfassen B16`**. Die Erfassung läuft weiter.

## Akzeptanzkriterien im Einzelnen

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-01** | ✅ bestanden | `/de/about` → 200; Überschriften: `Michael Ferreira`, `Die Geschichte von Endlech.lu`, **`Planung beginnt`, `Aktive Entwicklung`, `Unfreiwillige Pause`, `Neustart`, `Erste Live-Version`** (das ist die Zeitleiste), `Unsere Mission` |
| AK-02 | ✅ bestanden | `/de/criteria` → 200; sechs Kriterien: Barrierefreier Eingang, Behindertenparkplatz, Barrierefreie Toilette, Rollstuhlgerechtes Inneres, Hilfsbereites Personal, Gut lesbare Speisekarte |
| AK-03 | ✅ bestanden | `/de/legal` → 200; „Impressum" **und** „Datenschutz" auf einer Seite, mit `§ 5 TMG / Art. 11 Loi sur le commerce électronique` |
| **AK-04** | ✅ bestanden | `<section id="datenschutz" class="mb-8 scroll-mt-24">`; der Banner verlinkt auf `/de/legal#datenschutz` |
| AK-05 | ✅ bestanden | alle drei Seiten ohne Anmeldung → 200 |
| **AK-06** | ✅ bestanden | `de: Über Endlech · en: About Endlech · fr: À propos d'Endlech · lb: Iwwer Endlech` |
| AK-09 | ✅ bestanden | `info@endlech.lu` als einzige Adresse — Pflichtangabe |
| AK-10 | ✅ bestanden | `grep` nach `Repository|EntityManager` in allen drei Controllern: **0 Treffer** |

### Fragwürdiges Verhalten — bestätigt

| AK | Ergebnis | Nachweis |
|---|---|---|
| **AK-07** ⚠ | ✅ bestätigt, **und messbar** | siehe Tabelle oben → BF-65 |
| **AK-08** ⚠ | ✅ bestätigt | Auf `/de/criteria` kommen die Wörter „Punktzahl", „Score", „10 Punkte", „gleichgewichtet" und „acht Merkmale" **nicht vor** → BF-66 |

## Edge Cases

| EC | Ergebnis | Nachweis |
|---|---|---|
| EC-01 | ✅ bestanden | Die drei Controller enthalten keinen Repository- oder EntityManager-Zugriff |
| EC-02 | ✅ bestanden | Pfade englisch (`/about`, `/criteria`, `/legal`), Routennamen deutsch (`app_kriterien`, `app_impressum`) |

## Fehler

### BF-65 · Die Datenschutzerklärung nennt einen von drei Empfängern — mittel

**Betrifft:** AK-07

**Nachweis.** Der Datenschutzabschnitt im Volltext (gekürzt):

> „Die Nutzung unserer Webseite ist in der Regel ohne Angabe personenbezogener Daten
> möglich. […] **Fehleranalyse (Sentry)** — Zur Erkennung und Behebung technischer
> Fehler nutzen wir Sentry (Functional Software, Inc.). […] Rechtsgrundlage ist unser
> berechtigtes Interesse […] Art. 6 Abs. 1 lit. f DSGVO."

Der Sentry-Abschnitt ist gut: Empfänger, Serverstandort (Frankfurt), Datenarten, und die
Zusage, dass IP-Adressen nicht übertragen werden — das deckt sich mit
`send_default_pii: false` in `sentry.yaml`.

**Was fehlt** (jeweils gegen die gemessene Wirklichkeit des Projekts):

| Begriff im Text gesucht | gefunden |
|---|---|
| Brevo / Sendinblue | **—** |
| HAFAS / Mobilitéit / Verkéiersverbond | **—** |
| Auftragsverarbeitung | — |
| Empfänger | — |
| Löschfrist / Aufbewahrung | — |
| Auskunft | — |
| Widerruf | — |

**Brevo ist die wesentliche Lücke.** Jede Registrierung (B01), jede Wartelisten-Anmeldung
(B14, B15), jede Bestätigungsmail und jede interne Meldung läuft über
`brevo+api://…@default` — der Dienst empfängt damit **jede E-Mail-Adresse, die das
Projekt speichert**, samt Namen. Art. 13 Abs. 1 lit. e DSGVO verlangt die Nennung der
Empfänger.

**Der einleitende Satz stimmt zudem nicht mehr:** *„Die Nutzung unserer Webseite ist in
der Regel ohne Angabe personenbezogener Daten möglich."* Das Lesen ja — aber die
Plattform führt Konten, speichert Wartelisten mit Einwilligungszeitpunkt (`consentAt`)
und erfasst Einreicher. Der Satz ist ein Textbaustein aus einer Zeit, in der die Seite
nur eine Liste war.

**Warum das *mittel* ist und nicht *hoch*:** Es fließen keine Daten irgendwohin, wo sie
nicht hingehören — die Verarbeitung selbst ist an allen geprüften Stellen sauber (B01,
B14, B15, B17). Was fehlt, ist die **Auskunft darüber**. Der Schaden ist eine
Informationspflichtverletzung, kein Datenabfluss.

**Vorschlag:** Zwei Abschnitte nach dem Muster des Sentry-Blocks — er zeigt, dass das
Projekt es kann:
- *„E-Mail-Versand (Brevo)"* mit Empfänger, Zweck, Datenarten, Rechtsgrundlage
- *„Nahverkehrsdaten (Verkéiersverbond)"* mit dem Hinweis, dass **keine Besucherdaten**
  übermittelt werden (das ist in B10/AK-14 gemessen und ein gutes Argument)

Dazu die Betroffenenrechte — die sind ohnehin fällig, siehe unten.

**Zusammenhang mit drei offenen Befunden:** BF-04 (keine Betroffenenrechte, Feature `01`),
BF-37 (kein Widerruf für Wartelisten), B14/FB-05 (keine Auskunftsfunktion). Eine
Datenschutzerklärung, die Rechte nennt, die es nicht gibt, wäre schlechter als eine, die
schweigt. **Die Reihenfolge ist also: erst Feature `01`, dann der Text.**

### BF-66 · Die Kriterienseite erklärt die Punktzahl nicht — niedrig

**Betrifft:** AK-08

**Nachweis:** Auf `/de/criteria` stehen sechs Kriterien mit Beschreibung. Die Wörter
„Punktzahl", „Score", „10 Punkte", „gleichgewichtet" und „acht Merkmale" kommen
**nicht** vor.

Gleichzeitig veröffentlicht `/open` eine Durchschnittspunktzahl (aktuell 5,09 von 10) und
der CC-BY-Datensatz eine Spalte `accessibilityScore` je Restaurant. `AccessibilityScore`
wertet **acht** Merkmale gleichgewichtet — die Seite listet **sechs** Kriterien, und
welche der acht das sind, steht nirgends.

**Warum das mehr ist als eine Textlücke:** Die Punktzahl zählt nicht Erfasstes als nicht
erfüllt (B16/AK-18, in B11/BF-49 mit Zahlen belegt). Ein Restaurant, über das nichts
bekannt ist, bekommt 0 von 10. Wer diese Zahl sieht — auf `/open`, im Datensatz, in einem
Fördergespräch — muss wissen, was sie misst. Die Seite, die genau dafür da ist, sagt es
nicht.

**Vorschlag:** Ein Abschnitt „Wie die Punktzahl entsteht" mit den acht Merkmalen, der
Gleichgewichtung und dem entscheidenden Satz: *nicht erfasst zählt wie nicht erfüllt*.
Das ist auch ohne die Reparatur von BF-49 richtig und macht die Zahl ehrlich.

## Sicherheitsprüfung

| Prüfung | Ergebnis |
|---|---|
| **Anmeldung** | keine der drei Seiten verlangt eine |
| **Datenbank / Fremddienste** | die Controller berühren beides nicht |
| **Personenbezogene Daten** | nur die Impressums-Pflichtangabe `info@endlech.lu` |
| **Anker und Sprunglink** | `scroll-mt-24` vorhanden, Banner-Link korrekt |
| **Übersetzungen** | vier Sprachen, alle mit eigener Überschrift |
| **Vollständigkeit der Datenschutzerklärung** | **lückenhaft** → BF-65 |
| **Testsuite** | 365 Tests, 0 Fehler |

## Hinweise ohne Fehlerstatus

- **Der Sentry-Abschnitt ist vorbildlich** und deckt sich mit der Konfiguration
  (`send_default_pii: false`, EU-Region Frankfurt). Er ist das Muster, nach dem die
  beiden fehlenden Abschnitte gebaut gehören.
- **`docs/datenschutz.md` existiert weiterhin nicht.** Die Spec nennt es als das
  Dokument, gegen das der Text zu prüfen wäre. Diese Prüfung hat den Vergleich
  stattdessen gegen den Code geführt — das geht auch, ist aber bei jeder Änderung neu zu
  machen.
- **`code-reviewer`-Agent nicht eingesetzt** — Sitzungsvorgabe.

## Neue Tests

Keine. Beide Befunde sind Textfragen; ein Test darauf würde Formulierungen festschreiben,
und das ist die falsche Ebene. Ein sinnvoller Test wäre der umgekehrte: „Wenn
`MAILER_DSN` auf Brevo zeigt, muss der Begriff in der Datenschutzerklärung vorkommen" —
das ist reizvoll, aber zu fragil für den Nutzen.

**Suite: 365 Tests, 0 Fehler.**

## Nächster Schritt

`/sdd-erfassen B16`. B13 geht auf `approved`; BF-65 und BF-66 stehen in
`features/befunde.md`.

BF-65 gehört **nach** Feature `01` gebaut, nicht davor: Eine Erklärung, die
Betroffenenrechte nennt, die es nicht gibt, wäre schlechter als der heutige Zustand.
