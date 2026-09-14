# 11 · Nutzung messen, ohne zu verfolgen — Spezifikation

Status: `planned` · Stand: 2026-09-13

## Zweck

Der Betreiber sieht erstmals, welche Seiten gelesen werden, woher Besucher kommen und an welcher
Stelle sie in der Restaurantsuche und in den Wartelisten aufhören, ohne Cookies, ohne Personendaten
und ohne Einwilligungsbanner. Damit bekommt der Growth-Loop seinen zweiten Teil (Loop 2), und die
öffentliche Zusage auf `/roadmap` („Infrage kommt nur ein Weg ohne Cookies und ohne Personendaten —
sonst bleibt es ungebaut") wird eingelöst und prüfbar.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| Umami-Instanz auf dem zweiten VPS | **fehlt** — Betreiber | Die Messung braucht einen Empfänger. Umami ab Version 3, sonst passt die Trichter-Auswertung des Growth-Loops nicht |
| 10 · Sitemap und robots.txt | deployed | Liefert die Search-Console-Seite des Growth-Loops; Loop 2 wertet dieselben Seiten aus |
| 07 · Roadmap und Changelog | deployed | Der Eintrag `usage_analytics` verlässt die Roadmap; die Auslieferung steht auf `/changelog` |
| B13 · Statische Inhaltsseiten (`/legal`) | deployed | Der Datenschutzabschnitt beschreibt die Messung und trägt den Widerspruchsschalter |
| B26 · Cookie-Banner | deployed | Bleibt unverändert; sein Text muss danach weiterhin stimmen |
| B05, B06, B14, B15, 08, 06, 05, B16 | deployed | Die Seiten, auf denen die Ereignisse der Trichter ausgelöst werden |

## User Stories

- **US-01** · Als Betreiber möchte ich sehen, wie viele Besucher Restaurantliste, Detailseiten und
  Wartelisten erreichen und woher sie kommen, damit ich Arbeit dorthin lenke, wo sie Besucher bringt.
- **US-02** · Als Betreiber möchte ich sehen, an welchem Schritt der Restaurantsuche und der
  Wartelisten Besucher aufhören, damit ich den Schritt verbessere, der sie verliert.
- **US-03** · Als Besucher möchte ich nicht verfolgt werden und der Messung widersprechen können,
  damit eine Plattform zur Barrierefreiheit nicht weiß, wer sie besucht.
- **US-04** · Als Growth-Loop möchte ich die Zahlen lesend abfragen, damit ich die Wirkung meiner
  eigenen Änderungen nachmessen kann.

## Nicht im Scope

- **Reichweite als öffentliche Kennzahl auf `/open`.** Das PRD rät von Besucherzahlen als Kennzahl ab;
  wer das ändern will, schreibt ein eigenes Feature.
- **Messung in Verwaltung (`/admin`) und Profil (`/profile`)** sowie auf jeder Seite, deren Adresse ein
  Token trägt (Bestätigungs-, Abmelde-, Passwort- und Adresswechsel-Links).
- **Bestätigungsquote des Double-Opt-In.** Die Bestätigungsseiten tragen ein Token und werden nicht
  gemessen. Die Quote liegt ohnehin im Bestand (`WaitlistStatus`, PRD „Vertriebstrichter").
- **Einwilligungsbanner für die Messung.** Entschieden: keine Einwilligung (Decision Log #6).
- **Nutzerbezogene Auswertung.** Kein Konto, keine Nutzernummer, keine E-Mail-Adresse erreicht die
  Messung — auch nicht bei angemeldeten Besuchern.
- **Aufsetzen und Absichern des zweiten VPS über die Umami-Einrichtung hinaus** (Betriebssystem,
  Firewall, Sicherung) — Betrieb, `/sdd-betrieb`.
- **Growth-Loop-Läufe selbst.** Dieses Feature liefert die Zahlen und den lesenden Zugang; die Läufe
  gehören dem Growth-Loop.

## Begriffe

- **Zählaufruf** — jede Übertragung vom Browser an die Messung: ein Seitenaufruf oder ein Ereignis.
- **Ereignis** — eine benannte Handlung zwischen zwei Seitenaufrufen, z. B. „Filter angewandt".
- **Messung** — die Umami-Instanz auf dem zweiten VPS samt der Weiterleitung über endlech.lu.

## Akzeptanzkriterien

Jedes Kriterium ist ohne Codekenntnis prüfbar. Wer es nicht ohne Editor nachstellen kann,
formuliert es um.

### Seitenaufrufe und Herkunft

- **AK-01** · Angenommen ein Besucher ohne „Do Not Track" und ohne „Global Privacy Control" öffnet
  `https://endlech.lu/de/restaurants`, wenn der Betreiber danach Umami öffnet, dann ist dieser
  Seitenaufruf **binnen 5 Minuten** mit dem Pfad `/de/restaurants` sichtbar.
- **AK-02** · Angenommen ein Besucher kommt über einen Link von einer anderen Website (z. B. einer
  Google-Suche), wenn der Betreiber die Herkunft in Umami ansieht, dann steht dort **nur die Domain**
  der verweisenden Seite (`google.com`), nie ihr Pfad oder ihre Abfrage.
- **AK-03** · Angenommen ein Besucher öffnet `/de/restaurants?city=Esch&wheelchair=1&page=2`, wenn der
  Betreiber den Seitenaufruf in Umami ansieht, dann steht dort der Pfad `/de/restaurants` **ohne
  Abfrageparameter**; der Ort „Esch" erscheint nirgends in Umami.
- **AK-04** · Angenommen Besucher aus Luxemburg und Frankreich öffnen eine Seite, wenn der Betreiber
  den Ort in Umami ansieht, dann ist **nur das Land** erfasst — keine Region und keine Stadt.
- **AK-05** · Angenommen ein Besucher öffnet dieselbe Seite nacheinander auf Deutsch und Französisch,
  wenn der Betreiber in Umami auswertet, dann lassen sich beide Aufrufe **je Sprache getrennt**
  (`/de/…`, `/fr/…`) **und zusammen** (dieselbe Seite über alle Sprachen) zählen.

### Wo gemessen wird und wo nicht

- **AK-06** · Angenommen ein angemeldeter Admin öffnet Seiten unter `/{Sprache}/admin` und
  `/{Sprache}/profile`, wenn man den Netzwerk-Tab des Browsers ansieht, dann geht von diesen Seiten
  **kein Zählaufruf** aus, und in Umami erscheint kein Pfad mit `/admin` oder `/profile`.
- **AK-07** · Angenommen ein Besucher öffnet einen Link mit Token — Bestätigung einer
  Wartelisten-Anmeldung (`/partner`, `/organisationen`, `/app`), Abmeldung, E-Mail-Bestätigung,
  Adresswechsel oder Passwort-Zurücksetzen —, wenn man Netzwerk-Tab und Umami ansieht, dann gibt es
  **keinen Zählaufruf** dieser Seite, und **kein Token** steht irgendwo in Umami.
- **AK-08** · Angenommen ein Besucher öffnet Anmeldung, Registrierung, Vorschlagsformular oder
  Ideen-Formular, wenn der Betreiber Umami öffnet, dann sind diese Seitenaufrufe sichtbar
  (Entscheidung: gemessen wird überall außer Verwaltung, Profil und Token-Seiten).
- **AK-09** · Angenommen die Anwendung läuft lokal, im Testbetrieb oder wird über `www.endlech.lu`
  aufgerufen, wenn Seiten geöffnet werden, dann erscheint **kein** Aufruf in der Umami-Website von
  endlech.lu.
- **AK-10** · Angenommen eine Seite wird mit der Browserkennung eines Suchmaschinen-Crawlers (z. B.
  Googlebot) abgerufen, wenn der Betreiber Umami öffnet, dann ist dieser Aufruf **nicht** gezählt.

### Suchtrichter

- **AK-11** · Angenommen ein Besucher öffnet die Restaurantliste, wendet einen Filter an, öffnet eine
  Detailseite und klickt dort auf die Website des Restaurants, wenn der Betreiber den Suchtrichter in
  Umami auswertet, dann zählt er **je Schritt einen Durchlauf**: Liste geöffnet → Filter angewandt →
  Detailseite geöffnet → Kontaktweg genutzt.
- **AK-12** · Angenommen ein Besucher nutzt auf einer Detailseite einen der Kontaktwege — Website,
  Telefon, E-Mail, Instagram, Facebook, TikTok oder einen Bestellweg —, wenn der Betreiber das Ereignis
  „Kontaktweg genutzt" auswertet, dann ist erkennbar, **welche Art** von Kontaktweg es war, aber
  **nicht** die Telefonnummer, E-Mail-Adresse oder Zieladresse.
- **AK-13** · Angenommen ein Besucher wendet Filter an, wenn der Betreiber das Ereignis „Filter
  angewandt" ansieht, dann ist erkennbar, **welche Filter** gewählt wurden (z. B. „rollstuhlgerecht"),
  aber **kein frei eingegebener Text** (Ort).
- **AK-14** · Angenommen Besucher gehen den Suchtrichter auf Deutsch und auf Französisch durch, wenn
  der Betreiber den Trichter auswertet, dann zählt er beide **sprachübergreifend** zusammen.

### Wartelisten-Trichter

- **AK-15** · Angenommen ein Besucher öffnet `/de/app` und trägt sich erfolgreich ein, wenn der
  Betreiber den Wartelisten-Trichter auswertet, dann zählt er „Seite geöffnet" und „erfolgreich
  abgesendet" für die App-Warteliste; dasselbe gilt getrennt für `/partner` und `/organisationen`
  (samt ihrer Zielgruppenseiten).
- **AK-16** · Angenommen ein Besucher sendet ein Wartelisten-Formular mit einem Fehler ab (z. B. leere
  E-Mail-Adresse), wenn der Betreiber den Trichter auswertet, dann zählt dieser Versuch **nicht** als
  „erfolgreich abgesendet".
- **AK-17** · Angenommen ein Besucher trägt sich in eine Warteliste ein, wenn man die Zählaufrufe im
  Netzwerk-Tab und in Umami ansieht, dann enthalten sie **keine** E-Mail-Adresse, keinen Namen, keine
  Telefonnummer und keinen Formulartext.

### Engagement

- **AK-18** · Angenommen ein Besucher öffnet eine Idee im Ideen-Board und gibt ihr seine Zustimmung,
  wenn der Betreiber Umami öffnet, dann sind der Aufruf der Idee und das Ereignis „Zustimmung gegeben"
  sichtbar, **ohne** Kennung des Kontos.
- **AK-19** · Angenommen ein Besucher lädt auf `/presse` das Presse-Kit oder auf `/open` den Datensatz
  (CSV oder JSON), wenn der Betreiber Umami öffnet, dann ist je ein Ereignis „Presse-Kit geladen" bzw.
  „Datensatz geladen" mit dem Format sichtbar.

### Datenschutz und Missbrauchsschutz

- **AK-20** · Angenommen ein Besucher öffnet beliebige Seiten, wenn man danach die Cookies des Browsers
  für endlech.lu ansieht, dann hat die Messung **kein Cookie** gesetzt, und der Bannertext „Wir nutzen
  nur technisch notwendige Cookies" stimmt weiterhin.
- **AK-21** · Angenommen ein angemeldeter Besucher ist unterwegs, wenn man seine Zählaufrufe im
  Netzwerk-Tab und in Umami ansieht, dann enthalten sie **keine** Nutzernummer, keine E-Mail-Adresse
  und keinen Namen; ein angemeldeter Besuch ist in Umami nicht von einem Gastbesuch zu unterscheiden.
- **AK-22** · Angenommen der Browser sendet „Do Not Track" **oder** „Global Privacy Control", wenn der
  Besucher Seiten öffnet und Filter anwendet, dann geht **kein einziger** Zählaufruf aus.
- **AK-23** · Angenommen ein Besucher schaltet im Datenschutzabschnitt von `/legal` die Messung aus,
  wenn er danach Seiten öffnet, dann geht **kein** Zählaufruf aus; der Schalter zeigt „aus", auch nach
  einem Neuladen und in einem neuen Tab desselben Browsers; schaltet er sie wieder an, wird wieder
  gezählt. Das Ausschalten setzt **kein Cookie**.
- **AK-24** · Angenommen man sieht Quelltext und Netzwerk-Tab einer beliebigen Seite an, wenn Skript
  und Zählaufrufe geladen werden, dann richten sie sich **ausschließlich an `endlech.lu`**; weder
  Rechnername noch Adresse des zweiten VPS erscheinen, und die Konsole meldet **keinen** Verstoß gegen
  die Sicherheitsrichtlinie (CSP).
- **AK-25** · Angenommen jemand ruft über endlech.lu die Anmeldung oder die Schnittstelle von Umami
  auf (etwa den Pfad der Weiterleitung mit angehängtem `/login` oder `/api/websites`), wenn die
  Antwort kommt, dann ist sie **404** — über endlech.lu sind nur das Messskript und der Zählaufruf
  erreichbar.
- **AK-26** · Angenommen jemand kennt die Adresse des zweiten VPS, wenn er ihn ohne SSH-Tunnel über
  HTTP oder HTTPS aufruft, dann erscheint **keine** Umami-Oberfläche und **keine** Umami-Schnittstelle.
- **AK-27** · Angenommen von einer Adresse gehen innerhalb einer Stunde **300** Zählaufrufe ein, wenn
  der **301.** folgt, dann wird er nicht an Umami weitergeleitet und nicht gezählt; die Seite, auf der
  er ausgelöst wurde, funktioniert unverändert und zeigt **keine** Fehlermeldung. Nach Ablauf der
  Stunde wird wieder gezählt.
- **AK-28** · Angenommen der Anmeldeversuch in Umami erfolgt mit der Voreinstellung `admin` / `umami`,
  wenn er abgeschickt wird, dann **scheitert** er.
- **AK-29** · Angenommen der Growth-Loop meldet sich mit seinem eigenen Umami-Benutzer an, wenn er
  versucht, eine Einstellung, eine Website oder einen Benutzer zu ändern, dann wird das **abgewiesen**;
  lesen darf er die Website von endlech.lu.
- **AK-30** · Angenommen Zählaufrufe laufen über endlech.lu, wenn man danach das Protokoll der
  Anwendung ansieht, dann steht dort **keine** IP-Adresse eines Besuchers und kein Inhalt eines
  Zählaufrufs.
- **AK-31** · Angenommen ein Besucher öffnet `/legal` in einer der vier Sprachen, wenn er den
  Datenschutzabschnitt liest, dann nennt dieser: den Dienst (Umami, selbst betrieben), den Anbieter
  und Standort des Servers, **was** erfasst wird (Pfad ohne Abfrage, Herkunftsdomain, Land,
  Browser- und Gerätetyp, Sprache, die Ereignisse der Trichter) und **offen die Sitzungskennung**, mit
  der Umami zusammengehörige Aufrufe verbindet; **was nicht** (Cookies, gespeicherte IP-Adresse, Konto,
  Formularinhalte); die **unbegrenzte** Aufbewahrung mit Begründung; „Do Not Track"/„Global Privacy
  Control" und den Schalter. Der Text behauptet **nichts, was sich nicht nachstellen lässt** — also
  weder „anonym" noch „keine Kennung". Jede Angabe stimmt mit dem überein, was im Netzwerk-Tab und in
  Umami tatsächlich zu sehen ist (OF-01).

### Öffentliche Seiten und Unterlagen

- **AK-32** · Angenommen das Feature ist ausgeliefert, wenn man `/roadmap` öffnet, dann steht
  „Nutzung messen, ohne zu verfolgen" **nicht** mehr unter „Angedacht"; auf `/changelog` steht die
  Auslieferung in allen vier Sprachen mit einem Verweis auf `/legal`, in derselben vorsichtigen
  Formulierung wie AK-31.
- **AK-33** · Angenommen man liest `docs/prd.md`, wenn man die Abschnitte „Erfolgskennzahlen" und
  „Bewusst nicht gebaut" ansieht, dann steht dort nicht mehr „Es gibt kein Web-Analytics", sondern die
  cookielose Messung mit ihrer Bedingung; Besucherzahlen bleiben **keine** Zielkennzahl.

### Abnahme

- **AK-34** · Angenommen das Feature ist ausgeliefert, wenn der Betreiber endlech.lu ohne „Do Not
  Track" öffnet, dann sieht er seinen eigenen Aufruf binnen 5 Minuten in Umami; mit ausgeschaltetem
  Schalter aus AK-23 erscheint ein weiterer Aufruf **nicht**.
- **AK-35** · Angenommen der Umami-Zugang des Growth-Loops ist hinterlegt, wenn der Betreiber die
  Prüfung des Umami-Zugangs des Growth-Loops laufen lässt, dann listet sie die Website von endlech.lu
  mit ihrer Kennung, und `growth/config.json` trägt diese Kennung und die Schrittfolge beider Trichter.
- **AK-36** · Angenommen ein Besucher schaut sich zwei Restaurants an, wenn der Betreiber in Umami
  ansieht, **welche Restaurants** geöffnet wurden, dann ist erkennbar, **welches** Restaurant es war
  (über den Pfad der Detailseite) — und nicht, **wer** es geöffnet hat.
- **AK-37** · Angenommen Umami ist angehalten, blockiert oder der Deckel aus AK-27 ist erreicht, wenn
  ein Besucher die Startseite, die Restaurantliste und eine Detailseite öffnet, dann erscheint der
  erste Inhalt jeweils **höchstens 100 ms später** als bei laufender Messung (Mittel aus fünf Aufrufen);
  Filter, Wartelisten-Formulare und Kontaktwege funktionieren unverändert, und Besucher sehen **keine**
  Fehlermeldung (OF-04).
- **AK-38** · Angenommen der Growth-Loop hat einen eigenen SSH-Schlüssel für den Tunnel, wenn jemand mit
  diesem Schlüssel eine Anmeldung mit Befehlszeile oder eine Weiterleitung zu einem anderen Ziel als
  Umami versucht, dann wird beides **abgewiesen**; nur die Weiterleitung zu Umami gelingt (OF-03).
- **AK-39** · Angenommen kein Tunnel ist offen, wenn der Growth-Loop Loop 2 startet, dann öffnet er den
  Tunnel selbst, liest die Zahlen und schließt ihn danach wieder; ist der zweite VPS nicht erreichbar,
  meldet Loop 2 ab, und Loop 1 läuft trotzdem durch (OF-03).

## Edge Cases

- **EC-01** · Die Messung ist nicht erreichbar (VPS aus, Werbeblocker aktiv, Deckel erreicht) →
  Seiten, Filter, Formulare und Kontaktwege funktionieren unverändert; kein Seitenaufbau wartet auf die
  Messung; Besucher sehen keine Fehlermeldung. Seit OF-04 als **AK-37** mit Zahl geprüft.
- **EC-02** · JavaScript ist abgeschaltet → es wird nicht gemessen; der Schalter in `/legal` erscheint
  nicht oder zeigt, dass ohne JavaScript ohnehin nicht gemessen wird.
- **EC-03** · Ein Besucher wechselt die Sprache auf einer Seite → zwei Seitenaufrufe, je einer pro
  Sprache (AK-05); der Suchtrichter läuft über den Wechsel hinweg weiter (AK-14).
- **EC-04** · Ein Besucher öffnet eine Detailseite eines nicht mehr vorhandenen Restaurants → Seite
  antwortet mit 404; ob der Aufruf gezählt wird, ist unerheblich, er darf aber keinen Fehler auslösen.
- **EC-05** · Viele Besucher hinter derselben öffentlichen Adresse (Schule, Firma, Mobilfunk) → der
  Deckel aus AK-27 zählt je Adresse; bei 300 Zählaufrufen je Stunde reicht das für rund 100 Seiten.
  Darüber gehen Zahlen verloren, die Seite funktioniert weiter.
- **EC-06** · Die installierte App (PWA) ist offline → kein Zählaufruf, kein Fehler; online zählen
  Aufrufe aus der App wie Browseraufrufe.
- **EC-07** · Ein Besucher hat den Schalter ausgeschaltet und leert den Browserspeicher → danach wird
  wieder gezählt (der Schalter kann sich ohne Cookie und ohne Konto nichts dauerhafter merken). Das
  steht so in `/legal`.

## Datenschutz — Durchgang durch den Katalog

**1 · Personenbezogene Daten.** Die Messung verarbeitet beim Zählaufruf die **IP-Adresse** des
Besuchers, um das Land zu bestimmen; gespeichert werden laut Entscheidung nur das Land (AK-04), keine
IP (AK-30, AK-31). Der Browser überträgt Pfad, Herkunftsdomain, Sprache, Browser- und Gerätetyp.
Kein Konto, keine E-Mail, kein Formularinhalt (AK-17, AK-21). ⚠ **Besondere Kategorien:** Der Besuch
einer Plattform für barrierefreie Gastronomie kann auf eine Behinderung hindeuten — auf eine
**Person** bezogen wäre das eine Gesundheitsangabe. Deshalb gehört nichts in die Messung, das einen
Besuch einer Person zuordnet; genau das prüfen AK-17, AK-21 und AK-24. Logs: AK-30. Aufbewahrung:
**unbegrenzt**, begründet mit dem langfristigen Verlauf (Decision Log #12). Die **Sitzungskennung**
von Umami wird in `/legal` offen genannt, statt „anonym" zu behaupten (OF-01, AK-31).

**2 · Weitergabe an externe Dienste.** Kein Fremddienst: Umami ist selbst betrieben auf einem zweiten
VPS bei **Hostinger**, im selben Konto wie die Anwendung — der bestehende Auftragsverarbeitungsvertrag
gilt. Serverstandort **Deutschland** (OF-02, beim Systemdesign nachgemessen). Kein Training, keine Weitergabe. Die Zählaufrufe laufen über
endlech.lu (AK-24), damit der VPS verborgen bleibt.

**3 · Zugriff.** Umami-Oberfläche nur per SSH-Tunnel (AK-26), der Tunnelschlüssel des Growth-Loops darf nur dorthin
weiterleiten (AK-38); Betreiberkonto ohne Voreinstellung
(AK-28); Growth-Loop mit eigenem, nur lesendem Benutzer (AK-29). Über endlech.lu nur Skript und
Zählaufruf (AK-25). Fremde ID: trifft nicht zu, weil die Messung keine Datensätze einzelner Nutzer
anbietet.

**4 · Missbrauch und Kosten.** Deckel 300 Zählaufrufe je Stunde je Adresse (AK-27) gegen verfälschte
Zahlen und Last auf dem Anwendungsserver. Keine Kosten je Aufruf. Kein Upload.

**5 · Löschen und Auskunft.** Kontolöschung: trifft nicht zu, weil die Messung keinen Bezug zu einem
Konto hat (AK-21) — es gibt nichts zu löschen. Auskunft: Ein Besuch lässt sich keiner Person zuordnen;
eine Auskunft kann deshalb keine Messdaten enthalten. Widerspruch: AK-22, AK-23.

**6 · Geheimnisse.** Umami-Betreiberkonto, Growth-Loop-Benutzer, Datenbankpasswort der Instanz und
SSH-Schlüssel für den Tunnel — keines davon im Repository, der Loop-Zugang unter `~/.config/umami/`.
Die Kennung der Website ist öffentlich und kein Geheimnis.

## Offene Fragen

- ~~**OF-01**~~ · **Entschieden am 2026-09-13: vorsichtig formulieren.** `/legal` und `/changelog` sagen
  nur, was sich nachstellen lässt — keine Cookies, keine gespeicherte IP-Adresse, kein Konto, keine
  Formularinhalte —, und nennen die Sitzungskennung von Umami offen (AK-31, AK-32). Ursprüngliche
  Frage: Gilt die Sitzungskennung als Personenbezug, und tragen damit „ohne Personendaten" auf
  `/roadmap` und die Begründung der unbegrenzten Aufbewahrung? ⚠ **Bleibt vor dem Deploy zu tun:**
  `/sdd-betrieb` prüft die Tragfähigkeit der Zusage auf `/roadmap` und der unbegrenzten Aufbewahrung.
  Keine Rechtsauskunft in dieser Spec. — Betreiber, vor `/sdd-deploy 11`.
- ~~**OF-02**~~ · **Entschieden am 2026-09-13: Deutschland.** Wird beim Systemdesign nachgemessen wie am
  2026-09-05 (Reverse DNS, ASN, Laufzeit) — Rechnername und Adresse kommen in keine öffentliche Unterlage.
- ~~**OF-03**~~ · **Entschieden am 2026-09-13: Der Growth-Loop baut den Tunnel selbst** — mit eigenem
  SSH-Schlüssel, der nur die Weiterleitung zu Umami erlaubt und keine Befehlszeile öffnet (AK-38); ohne
  erreichbaren VPS meldet Loop 2 ab (AK-39).
- ~~**OF-04**~~ · **Entschieden am 2026-09-13: ja, als Kriterium** — AK-37 mit höchstens 100 ms
  Verzögerung beim ersten Inhalt, geprüft bei angehaltenem Umami.
- **OF-05** · **„Erfolgreich eingetragen" zählt auch Dubletten und Honeypot-Treffer** (beim Bau am
  2026-09-13 aufgefallen). Die Erfolgsmeldung der Wartelisten erscheint absichtlich auch bei einer
  bereits eingetragenen Adresse und beim Honeypot-Treffer — sonst ließe sich die Warteliste von außen
  abfragen (Anti-Enumeration, Feature 08, B14, B15). Die Messung sieht dieselbe Meldung und zählt
  deshalb auch diese Fälle. AK-15 („trägt sich erfolgreich ein") ist damit nur näherungsweise
  erfüllt. Unterscheiden ließe es sich nur serverseitig, was genau die Eigenschaft aufgäbe. Vorschlag:
  hinnehmen und in AK-15 benennen. — Betreiber.
- **OF-06** · **Rechtsgrundlage im Text von `/legal`** (beim Bau am 2026-09-13 gesetzt). Der Absatz nennt
  nach dem Muster der Abschnitte zu Hostinger und Sentry „berechtigtes Interesse, Art. 6 Abs. 1 lit. f
  DSGVO". Das ist eine rechtliche Einordnung, die weder Spec noch Bau treffen dürfen. Gehört mit OF-01
  vor dem Deploy in `/sdd-betrieb`. — Betreiber.
- **OF-07** · **Die Trichtertabelle in `design.md` zählt in Umami null** (beim Beheben von BF-150 am
  2026-09-13). Abschnitt „Trichter" schreibt die Pfadschritte als `/*/restaurants`, `/*/restaurants/*`,
  `/*/app`, `/*/partner`, `/*/organisationen*` und begründet das mit „Umami übersetzt `*` in jedem
  Schritt … auch mitten im Pfad". Umami 3.3.1 ersetzt den Stern aber nur am Anfang oder Ende
  (`getFunnel.ts`); gemessen zählt die mittige Form 0, die Form mit führendem Stern 1. `growth/config.json`
  ist berichtigt, den Entwurf darf `sdd-build` nicht ändern. Vorschlag: Tabelle auf `*/restaurants`,
  `*/restaurants/*`, `*/app`, `*/partner`, `*/organisationen*` umstellen und den Begründungssatz ersetzen.
  Wer Umamis Trichter-Bericht von Hand anlegt (T05), nimmt die berichtigte Form. — Betreiber.

## Decision Log

| # | Frage | Entscheidung | Begründung |
|---|---|---|---|
| 1 | Womit messen? | Umami, selbst betrieben | Cookielos, selbst hostbar, vom Growth-Loop direkt lesbar; Vorarbeit in `docs/datenschutz.md` (BE-02) |
| 2 | Wo läuft Umami? | Zweiter VPS, neben Uptime Kuma | Getrennt von der Anwendung; entschieden 2026-09-13 |
| 3 | Adresse des Kuma-VPS wird durch das Skript öffentlich — was tun? | **Über endlech.lu leiten**: Skript und Zählaufrufe unter einem Pfad von endlech.lu | Die Abwägung aus BE-01 bleibt gültig (wer weiß, wo die Überwachung steht, kann sie lahmlegen); Nebeneffekt: eigene Pfade blockieren Werbeblocker seltener |
| 4 | Was messen? | Seitenaufrufe und Herkunft, Suchtrichter, Wartelisten-Trichter, Engagement | Alle vier gewählt |
| 5 | Wo messen? | Überall außer Verwaltung, Profil und Token-Seiten | Anmeldung und Formulare gehören zum Trichter; Token-Adressen dürfen nie in eine Messung |
| 6 | Einwilligung? | Keine; Banner unverändert, Absatz in `/legal` | Cookielos — der Bannertext bleibt wahr. Tragfähigkeit: OF-01 |
| 7 | Widerspruch? | „Do Not Track" und „Global Privacy Control" plus Schalter in `/legal` | Widerspruch ohne Browsereinstellung möglich; passt zur öffentlichen Zusage |
| 8 | Deckel? | 300 Zählaufrufe je Stunde je Adresse | Ein bis drei Aufrufe je Seite, reicht auch hinter geteilter Adresse |
| 9 | Datenumfang? | Pfad ohne Abfrage, Herkunft nur als Domain, Filter nur als Filtername | Kein Freitext (Ort), keine fremden Adresspfade |
| 10 | Ortsgenauigkeit? | Nur Land | Genug für „Luxemburg, Grenzgänger, Rest" |
| 11 | Wo ist die Umami-Oberfläche erreichbar? | Nur per SSH-Tunnel | Jede eigene Adresse steht in öffentlichen Zertifikatslogs; kein Anmeldeformular im Netz |
| 12 | Aufbewahrung? | **Unbegrenzt**, Begründung „langfristiger Verlauf" | Vergleich der Reichweite über Jahre; die Begründung in `/legal` behauptet keine Anonymität (OF-01) |
| 13 | Wer sieht die Zahlen? | Betreiber und Growth-Loop (nur lesend); `/open` unverändert | Das PRD rät von Besucherzahlen als öffentlicher Kennzahl ab |
| 14 | Hosting-Vertrag? | Gleiches Hostinger-Konto — bestehender Auftragsverarbeitungsvertrag | Standort Deutschland (OF-02) |
| 15 | Randfälle als Kriterium? | Nur echte Besuche auf endlech.lu; Sprachwechsel und Seitenwege; nach OF-04 zusätzlich Ausfall/Blockade | AK-09, AK-10, AK-05, AK-14, AK-37 |
| 16 | Abnahme? | Eigener Besuch in Umami sichtbar; Growth-Loop verbindet | AK-34, AK-35 |
| 17 | Changelog? | Öffentlich, vier Sprachen, Verweis auf `/legal` | Transparenz passt zur öffentlichen Zusage auf `/roadmap` |
| 18 | „Erfolgreich abgesendet" im Wartelisten-Trichter | Nur Absendungen, die zur Erfolgsseite führen; fehlerhafte zählen nicht | Der Trichter zeigt, wie viele sich tatsächlich eintragen; bestätigt am 2026-09-13 (AK-16) |
| 19 | Aussagen über die Sitzungskennung (OF-01) | Vorsichtig formulieren, Kennung offen nennen | Nur Nachstellbares behaupten; Tragfähigkeit der Roadmap-Zusage prüft `/sdd-betrieb` vor dem Deploy |
| 20 | Standort des zweiten VPS (OF-02) | Deutschland | Angabe des Betreibers, beim Systemdesign nachgemessen |
| 21 | Tunnel für den wöchentlichen Loop (OF-03) | Der Loop baut ihn selbst, mit eingeschränktem Schlüssel | Eine unbeaufsichtigte Routine liefert sonst nur Loop 1 |
| 22 | Ausfall der Messung als Kriterium (OF-04) | Ja, AK-37, höchstens 100 ms | Ohne Kriterium prüft niemand, dass ein toter VPS die Seite nicht verlangsamt |
