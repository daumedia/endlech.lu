# 11 · Nutzung messen, ohne zu verfolgen — Spezifikation

Status: `planned` · Stand: 2026-09-14 (Überarbeitung)

> **Überarbeitung vom 2026-09-14 — Umami über seine eigene Domain.** Die gebaute Fassung verlangte auf dem
> zweiten VPS einen eigenen Zähl-Eingang mit Firewall, eine Länderdatei und einen SSH-Tunnel. Der Betreiber
> hat entschieden, stattdessen die dort **bereits laufende** Umami-Instanz über ihre eigene Domain zu nutzen
> und den VPS darüber hinaus nicht zu verändern (Decision Log #23–#28). Die Weiterleitung über endlech.lu
> bleibt. Geändert: AK-04, AK-24, AK-26, AK-27, AK-31, AK-39; entfallen: AK-38; neu: AK-40 bis AK-42, EC-08,
> OF-08. `v2026.09.14.1` liegt mit der alten Fassung auf `master` und wird **nicht** ausgerollt.

## Zweck

Der Betreiber sieht erstmals, welche Seiten gelesen werden, woher Besucher kommen und an welcher
Stelle sie in der Restaurantsuche und in den Wartelisten aufhören, ohne Cookies, ohne Personendaten
und ohne Einwilligungsbanner. Damit bekommt der Growth-Loop seinen zweiten Teil (Loop 2), und die
öffentliche Zusage auf `/roadmap` („Infrage kommt nur ein Weg ohne Cookies und ohne Personendaten —
sonst bleibt es ungebaut") wird eingelöst und prüfbar.

## Abhängigkeiten

| Braucht | Status | Warum |
|---|---|---|
| Umami-Instanz auf dem zweiten VPS | **vorhanden** — Umami 3.3.1 aus dem Docker-Katalog des Hosters, Daten erhalten; die Route über ihre Domain schaltet der Betreiber wieder ein (Stand 2026-09-14: aus) | Die Messung braucht einen Empfänger. Version 3.3.1, weil `zaehler.js` und die Trichter-Auswertung des Growth-Loops darauf beruhen |
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
- **Änderungen am zweiten VPS über das Wiedereinschalten der Umami-Route hinaus** — kein eigener
  Zähl-Eingang, keine Firewallregel, keine Länderdatei, kein Tunnel (Decision Log #23). Betriebssystem und
  Sicherung des VPS: `/sdd-betrieb`.
- **Schutz der Umami-Domain gegen Zählaufrufe, die an endlech.lu vorbei direkt dort eingehen** — hingenommen
  (Decision Log #28, EC-08).
- **Growth-Loop-Läufe selbst.** Dieses Feature liefert die Zahlen und den lesenden Zugang; die Läufe
  gehören dem Growth-Loop.

## Begriffe

- **Zählaufruf** — jede Übertragung vom Browser an die Messung: ein Seitenaufruf oder ein Ereignis.
- **Ereignis** — eine benannte Handlung zwischen zwei Seitenaufrufen, z. B. „Filter angewandt".
- **Messung** — die Umami-Instanz auf dem zweiten VPS samt der Weiterleitung über endlech.lu.
- **Umami-Domain** — die Adresse, unter der die Umami-Instanz erreichbar ist (Oberfläche und Zählschnittstelle).
  Sie steht auf keiner Seite von endlech.lu und in keiner Unterlage des Repositorys.

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
- **AK-04** · Angenommen ein Besucher aus Luxemburg und einer aus Frankreich öffnen eine Seite, wenn der
  Betreiber den Ort in Umami ansieht, dann erscheint jeder mit **seinem eigenen** Land (Luxemburg bzw.
  Frankreich) — **nicht** mit dem Land des Anwendungsservers. Region und Stadt dürfen erfasst sein; sie
  stehen so in `/legal` (AK-31). *Geändert am 2026-09-14, vorher „nur das Land" (Decision Log #25).*
- **AK-05** · Angenommen ein Besucher öffnet dieselbe Seite nacheinander auf Deutsch und Französisch,
  wenn der Betreiber in Umami auswertet, dann lassen sich beide Aufrufe **je Sprache getrennt**
  (`/de/…`, `/fr/…`) **und zusammen** (dieselbe Seite über alle Sprachen) zählen.
- **AK-40** · Angenommen zwei Besucher mit demselben Browser, aber von **verschiedenen** Internetadressen
  öffnen am selben Tag dieselbe Seite, wenn der Betreiber die Besucherzahl in Umami ansieht, dann zählt er
  **zwei** Besucher, nicht einen. *Neu am 2026-09-14: Hinter der Domain sähe Umami ohne weitergereichte
  Besucheradresse nur den Anwendungsserver, und alle Besuche fielen zusammen.*

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
  *Seit 2026-09-14 benannt (OF-05, Decision Log #31):* „Erfolgreich abgesendet" zählt auch eine bereits eingetragene
  Adresse und einen Honeypot-Treffer, weil die Erfolgsmeldung dort absichtlich gleich aussieht (Anti-Enumeration).
  Der Trichter ist damit eine Näherung nach oben; genaue Zahlen stehen in der Verwaltung der Wartelisten.
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
  Rechnername noch Adresse des zweiten VPS **noch die Umami-Domain** erscheinen, und die Konsole meldet
  **keinen** Verstoß gegen die Sicherheitsrichtlinie (CSP).
- **AK-25** · Angenommen jemand ruft über endlech.lu die Anmeldung oder die Schnittstelle von Umami
  auf (etwa den Pfad der Weiterleitung mit angehängtem `/login` oder `/api/websites`), wenn die
  Antwort kommt, dann ist sie **404** — über endlech.lu sind nur das Messskript und der Zählaufruf
  erreichbar.
- **AK-26** · Angenommen jemand ruft die Umami-Domain ohne Anmeldung auf, wenn er Oberfläche oder
  Schnittstelle nach Messdaten, Websites oder Benutzern fragt, dann erhält er **keine** davon (Anmeldeseite
  bzw. Abweisung). Ruft er stattdessen die Adresse des zweiten VPS mit dem Port der Umami-Instanz auf, an
  der Domain vorbei, dann antwortet dort **nichts**. *Geändert am 2026-09-14, vorher „ohne SSH-Tunnel
  keine Oberfläche" (Decision Log #27).*
- **AK-27** · Angenommen von einer Adresse gehen **über endlech.lu** innerhalb einer Stunde **300**
  Zählaufrufe ein, wenn
  der **301.** folgt, dann wird er nicht an Umami weitergeleitet und nicht gezählt; die Seite, auf der
  er ausgelöst wurde, funktioniert unverändert und zeigt **keine** Fehlermeldung. Nach Ablauf der
  Stunde wird wieder gezählt. *Der Deckel gilt nur für diesen Weg; direkt an die Umami-Domain gesendete
  Aufrufe deckelt er nicht (EC-08).*
- **AK-28** · Angenommen der Anmeldeversuch in Umami erfolgt mit der Voreinstellung `admin` / `umami`,
  wenn er abgeschickt wird, dann **scheitert** er.
- **AK-41** · Angenommen jemand kennt Benutzername und Passwort des Betreiberkontos in Umami, wenn er
  sich über die Umami-Domain **ohne** den zweiten Faktor anmelden will, dann **scheitert** die Anmeldung.
  *Neu am 2026-09-14 (Decision Log #27).*
- **AK-29** · Angenommen der Growth-Loop meldet sich mit seinem eigenen Umami-Benutzer an, wenn er
  versucht, eine Einstellung, eine Website oder einen Benutzer zu ändern, dann wird das **abgewiesen**;
  lesen darf er die Website von endlech.lu.
- **AK-30** · Angenommen Zählaufrufe laufen über endlech.lu, wenn man danach das Protokoll der
  Anwendung ansieht, dann steht dort **keine** IP-Adresse eines Besuchers und kein Inhalt eines
  Zählaufrufs.
- **AK-42** · Angenommen man durchsucht das Repository, die Berichte des Growth-Loops unter `growth/` und
  das Protokoll der Anwendung, wenn man nach dem Passwort des Growth-Loop-Benutzers und nach der
  Umami-Domain sucht, dann findet man **keines** von beiden. *Neu am 2026-09-14.*
- **AK-31** · Angenommen ein Besucher öffnet `/legal` in einer der vier Sprachen, wenn er den
  Datenschutzabschnitt liest, dann nennt dieser: den Dienst (Umami, selbst betrieben), den Anbieter
  und Standort des Servers, **was** erfasst wird (Pfad ohne Abfrage, Herkunftsdomain, **Land, Region
  und Stadt**, Browser- und Gerätetyp, Sprache, die Ereignisse der Trichter) und **offen die Sitzungskennung**, mit
  der Umami zusammengehörige Aufrufe verbindet; **was nicht** (Cookies, gespeicherte IP-Adresse, Konto,
  Formularinhalte); die **unbegrenzte** Aufbewahrung mit Begründung; „Do Not Track"/„Global Privacy
  Control" und den Schalter. Der Text behauptet **nichts, was sich nicht nachstellen lässt** — also
  weder „anonym" noch „keine Kennung". Jede Angabe stimmt mit dem überein, was im Netzwerk-Tab und in
  Umami tatsächlich zu sehen ist (OF-01). *Geändert am 2026-09-14: Region und Stadt statt nur Land
  (Decision Log #25).*

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
  Prüfung des Umami-Zugangs des Growth-Loops laufen lässt, dann meldet sie sich **über die Umami-Domain**
  an, listet die Website von endlech.lu mit ihrer Kennung, und `growth/config.json` trägt diese Kennung und
  die Schrittfolge beider Trichter.
- **AK-36** · Angenommen ein Besucher schaut sich zwei Restaurants an, wenn der Betreiber in Umami
  ansieht, **welche Restaurants** geöffnet wurden, dann ist erkennbar, **welches** Restaurant es war
  (über den Pfad der Detailseite) — und nicht, **wer** es geöffnet hat.
- **AK-37** · Angenommen Umami ist angehalten, blockiert oder der Deckel aus AK-27 ist erreicht, wenn
  ein Besucher die Startseite, die Restaurantliste und eine Detailseite öffnet, dann erscheint der
  erste Inhalt jeweils **höchstens 100 ms später** als bei laufender Messung (Mittel aus fünf Aufrufen);
  Filter, Wartelisten-Formulare und Kontaktwege funktionieren unverändert, und Besucher sehen **keine**
  Fehlermeldung (OF-04).
- ~~**AK-38**~~ · **Entfallen am 2026-09-14** — es gibt keinen SSH-Tunnel mehr (Decision Log #26).
  Vorher: Der Tunnelschlüssel des Growth-Loops erlaubt nur die Weiterleitung zu Umami.
- **AK-39** · Angenommen die Umami-Domain ist nicht erreichbar oder die Anmeldung des Growth-Loops
  scheitert, wenn der Growth-Loop Loop 2 startet, dann meldet Loop 2 ab, **ohne** Umami-Domain oder
  Passwort in seine Ausgabe zu schreiben, und Loop 1 läuft trotzdem durch. *Geändert am 2026-09-14, vorher
  mit selbst geöffnetem Tunnel (Decision Log #26).*

## Edge Cases

- **EC-01** · Die Messung ist nicht erreichbar (VPS aus, Umami-Domain nicht erreichbar, Werbeblocker
  aktiv, Deckel erreicht) →
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
- **EC-08** · Jemand schickt Zählaufrufe **direkt an die Umami-Domain**, an endlech.lu vorbei → Umami
  zählt sie, ohne Deckel (AK-27) und ohne die Kürzung der Weiterleitung. Die Zahlen lassen sich damit
  verfälschen; Personendaten von Besuchern fallen dabei nicht an. **Hingenommen** wie bei jedem
  Analytics-Dienst (Decision Log #28). Die Domain steht auf keiner Seite von endlech.lu (AK-24, AK-42).
  Folge für den Betrieb: OF-08.

## Datenschutz — Durchgang durch den Katalog

**1 · Personenbezogene Daten.** Die Messung verarbeitet beim Zählaufruf die **IP-Adresse** des
Besuchers, um den Ort zu bestimmen; gespeichert werden **Land, Region und Stadt** (AK-04, seit 2026-09-14),
keine IP (AK-30, AK-31). ⚠ Eine Stadt grenzt einen Besuch stärker ein als ein Land — bei einer kleinen
Ortschaft zusammen mit Gerät, Sprache und aufgerufenem Restaurant unter Umständen bis auf wenige Personen.
Entschieden hat der Betreiber am 2026-09-14 mit offener Nennung in `/legal`; die Tragfähigkeit zusammen mit
der unbegrenzten Aufbewahrung gehört zu OF-01. Der Browser überträgt Pfad, Herkunftsdomain, Sprache, Browser- und Gerätetyp.
Kein Konto, keine E-Mail, kein Formularinhalt (AK-17, AK-21). ⚠ **Besondere Kategorien:** Der Besuch
einer Plattform für barrierefreie Gastronomie kann auf eine Behinderung hindeuten — auf eine
**Person** bezogen wäre das eine Gesundheitsangabe. Deshalb gehört nichts in die Messung, das einen
Besuch einer Person zuordnet; genau das prüfen AK-17, AK-21 und AK-24. Logs: AK-30. Aufbewahrung:
**unbegrenzt**, begründet mit dem langfristigen Verlauf (Decision Log #12). Die **Sitzungskennung**
von Umami wird in `/legal` offen genannt, statt „anonym" zu behaupten (OF-01, AK-31).

**2 · Weitergabe an externe Dienste.** Kein Fremddienst: Umami ist selbst betrieben auf einem zweiten
VPS bei **Hostinger**, im selben Konto wie die Anwendung — der bestehende Auftragsverarbeitungsvertrag
gilt. Serverstandort **Deutschland** (OF-02, beim Systemdesign nachgemessen). Kein Training, keine Weitergabe. Die Zählaufrufe laufen über
endlech.lu (AK-24), damit die Umami-Domain auf keiner Seite von endlech.lu steht. ⚠ **Seit 2026-09-14 ist der
Standort nicht mehr verborgen, sondern nur nicht aus endlech.lu ableitbar:** Die Umami-Domain ist im
öffentlichen DNS eingetragen, und ihr Zertifikat steht in öffentlichen Zertifikatslogs. Wer die Domain
kennt, findet den VPS. **Hersteller:** Das Umami-Dashboard lädt im Browser dessen, der es öffnet, ein
Telemetrie-Bild des Herstellers mit der Versionsnummer (in Umami 3.3.1 nachgesehen). Besucherdaten gehen
darüber nicht an den Hersteller; `/legal` bleibt damit wahr.

**3 · Zugriff.** Umami-Oberfläche über die Umami-Domain; ohne Anmeldung keine Daten (AK-26), Betreiberkonto
ohne Voreinstellung (AK-28) und mit zweitem Faktor (AK-41); Growth-Loop mit eigenem, nur lesendem Benutzer
ohne zweiten Faktor (AK-29), Passwort nur unter `~/.config/umami/` (AK-42). Einen Deckel für
Anmeldeversuche in Umami selbst gibt es nach Durchsicht von 3.3.1 nicht; der zweite Faktor macht ein
erratenes Betreiberpasswort wertlos, das Passwort des Growth-Loops muss lang und zufällig sein. Über
endlech.lu nur Skript und Zählaufruf (AK-25). Fremde ID: trifft nicht zu, weil die Messung keine
Datensätze einzelner Nutzer anbietet.

**4 · Missbrauch und Kosten.** Deckel 300 Zählaufrufe je Stunde je Adresse **über endlech.lu** (AK-27) gegen
verfälschte Zahlen und Last auf dem Anwendungsserver. Direkt an die Umami-Domain gesendete Aufrufe sind
ungedeckelt und hingenommen (EC-08) — die Last träfe dabei den zweiten VPS, auf dem auch die
Überwachung läuft (OF-08). Keine Kosten je Aufruf. Kein Upload.

**5 · Löschen und Auskunft.** Kontolöschung: trifft nicht zu, weil die Messung keinen Bezug zu einem
Konto hat (AK-21) — es gibt nichts zu löschen. Auskunft: Ein Besuch lässt sich keiner Person zuordnen;
eine Auskunft kann deshalb keine Messdaten enthalten. Widerspruch: AK-22, AK-23.

**6 · Geheimnisse.** Umami-Betreiberkonto samt zweitem Faktor, Growth-Loop-Benutzer und Datenbankpasswort
der Instanz — keines davon im Repository, der Loop-Zugang unter `~/.config/umami/` (AK-42). Einen
SSH-Schlüssel für einen Tunnel gibt es seit 2026-09-14 nicht mehr. Die Kennung der Website ist öffentlich
und kein Geheimnis. Die Umami-Domain ist kein Geheimnis im engeren Sinn (öffentliches DNS), wird aber in
keine Unterlage, keinen Bericht und kein Protokoll geschrieben (AK-24, AK-42), damit der Weg vom Projekt zum
VPS nicht dokumentiert ist.

## Offene Fragen

- ~~**OF-01**~~ · **Entschieden am 2026-09-13: vorsichtig formulieren.** `/legal` und `/changelog` sagen
  nur, was sich nachstellen lässt — keine Cookies, keine gespeicherte IP-Adresse, kein Konto, keine
  Formularinhalte —, und nennen die Sitzungskennung von Umami offen (AK-31, AK-32). Ursprüngliche
  Frage: Gilt die Sitzungskennung als Personenbezug, und tragen damit „ohne Personendaten" auf
  `/roadmap` und die Begründung der unbegrenzten Aufbewahrung? ⚠ **Bleibt vor dem Deploy zu tun:**
  `/sdd-betrieb` prüft die Tragfähigkeit der Zusage auf `/roadmap` und der unbegrenzten Aufbewahrung.
  Keine Rechtsauskunft in dieser Spec. ⚠ **Seit 2026-09-14 zusätzlich:** Region und Stadt statt nur Land,
  zusammen mit der unbegrenzten Aufbewahrung (Decision Log #25). **Entschieden am 2026-09-14 (Decision Log #29):
  so belassen** — unbegrenzte Aufbewahrung mit Land, Region und Stadt, Texte weiter vorsichtig. Betreiberentscheidung
  ohne fachliche Prüfung; keine Rechtsauskunft in dieser Spec.
- ~~**OF-02**~~ · **Entschieden am 2026-09-13: Deutschland.** Wird beim Systemdesign nachgemessen wie am
  2026-09-05 (Reverse DNS, ASN, Laufzeit) — Rechnername und Adresse kommen in keine öffentliche Unterlage.
- ~~**OF-03**~~ · **Überholt am 2026-09-14** (kein Tunnel mehr, Decision Log #26). **Entschieden am 2026-09-13: Der Growth-Loop baut den Tunnel selbst** — mit eigenem
  SSH-Schlüssel, der nur die Weiterleitung zu Umami erlaubt und keine Befehlszeile öffnet (AK-38); ohne
  erreichbaren VPS meldet Loop 2 ab (AK-39).
- ~~**OF-04**~~ · **Entschieden am 2026-09-13: ja, als Kriterium** — AK-37 mit höchstens 100 ms
  Verzögerung beim ersten Inhalt, geprüft bei angehaltenem Umami.
- ~~**OF-05**~~ · **Entschieden am 2026-09-14 (Decision Log #31): hingenommen, in AK-15 benannt.** **„Erfolgreich eingetragen" zählt auch Dubletten und Honeypot-Treffer** (beim Bau am
  2026-09-13 aufgefallen). Die Erfolgsmeldung der Wartelisten erscheint absichtlich auch bei einer
  bereits eingetragenen Adresse und beim Honeypot-Treffer — sonst ließe sich die Warteliste von außen
  abfragen (Anti-Enumeration, Feature 08, B14, B15). Die Messung sieht dieselbe Meldung und zählt
  deshalb auch diese Fälle. AK-15 („trägt sich erfolgreich ein") ist damit nur näherungsweise
  erfüllt. Unterscheiden ließe es sich nur serverseitig, was genau die Eigenschaft aufgäbe. Vorschlag:
  hinnehmen und in AK-15 benennen. — Betreiber.
- ~~**OF-06**~~ · **Entschieden am 2026-09-14 (Decision Log #30): so stehen lassen** — Betreiberentscheidung, keine geprüfte Rechtslage. **Rechtsgrundlage im Text von `/legal`** (beim Bau am 2026-09-13 gesetzt). Der Absatz nennt
  nach dem Muster der Abschnitte zu Hostinger und Sentry „berechtigtes Interesse, Art. 6 Abs. 1 lit. f
  DSGVO". Das ist eine rechtliche Einordnung, die weder Spec noch Bau treffen dürfen. Gehört mit OF-01
  vor dem Deploy in `/sdd-betrieb`. — Betreiber.
- ~~**OF-07**~~ · **Im Entwurf umgesetzt am 2026-09-14** (Tabelle „Trichter" auf führenden Stern umgestellt, Begründungssatz ersetzt). **Die Trichtertabelle in `design.md` zählt in Umami null** (beim Beheben von BF-150 am
  2026-09-13). Abschnitt „Trichter" schreibt die Pfadschritte als `/*/restaurants`, `/*/restaurants/*`,
  `/*/app`, `/*/partner`, `/*/organisationen*` und begründet das mit „Umami übersetzt `*` in jedem
  Schritt … auch mitten im Pfad". Umami 3.3.1 ersetzt den Stern aber nur am Anfang oder Ende
  (`getFunnel.ts`); gemessen zählt die mittige Form 0, die Form mit führendem Stern 1. `growth/config.json`
  ist berichtigt, den Entwurf darf `sdd-build` nicht ändern. Vorschlag: Tabelle auf `*/restaurants`,
  `*/restaurants/*`, `*/app`, `*/partner`, `*/organisationen*` umstellen und den Begründungssatz ersetzen.
  Wer Umamis Trichter-Bericht von Hand anlegt (T05), nimmt die berichtigte Form. — Betreiber.
- ~~**OF-08**~~ · **Entschieden am 2026-09-14 (Decision Log #32): hingenommen** — Betreiber mit zweitem Faktor, `growth-loop` mit langem Zufallspasswort und nur lesend; keine Begrenzung am Proxy. Gemessen in QA Nachprüfung 3: 30 Fehlversuche in 3 s ohne Sperre. **Direkte Zählaufrufe an die Umami-Domain belasten den VPS der Überwachung** (bei der
  Überarbeitung am 2026-09-14). Entschieden ist, sie hinzunehmen (EC-08). Offen ist die Betriebsfolge: Eine
  Flut solcher Aufrufe träfe denselben VPS, auf dem Uptime Kuma die Anwendung überwacht, und könnte die
  Überwachung verlangsamen oder falsche Alarme auslösen. Dazu kommt, dass Umami Anmeldeversuche nicht
  deckelt. Ob dafür eine Begrenzung am Proxy des VPS nötig wird, gehört zu `/sdd-betrieb`, nicht zu diesem
  Feature. — Betreiber.

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
| 10 | Ortsgenauigkeit? | ~~Nur Land~~ — **ersetzt durch #25** | Genug für „Luxemburg, Grenzgänger, Rest" |
| 11 | Wo ist die Umami-Oberfläche erreichbar? | ~~Nur per SSH-Tunnel~~ — **ersetzt durch #27** | Jede eigene Adresse steht in öffentlichen Zertifikatslogs; kein Anmeldeformular im Netz |
| 12 | Aufbewahrung? | **Unbegrenzt**, Begründung „langfristiger Verlauf" | Vergleich der Reichweite über Jahre; die Begründung in `/legal` behauptet keine Anonymität (OF-01) |
| 13 | Wer sieht die Zahlen? | Betreiber und Growth-Loop (nur lesend); `/open` unverändert | Das PRD rät von Besucherzahlen als öffentlicher Kennzahl ab |
| 14 | Hosting-Vertrag? | Gleiches Hostinger-Konto — bestehender Auftragsverarbeitungsvertrag | Standort Deutschland (OF-02) |
| 15 | Randfälle als Kriterium? | Nur echte Besuche auf endlech.lu; Sprachwechsel und Seitenwege; nach OF-04 zusätzlich Ausfall/Blockade | AK-09, AK-10, AK-05, AK-14, AK-37 |
| 16 | Abnahme? | Eigener Besuch in Umami sichtbar; Growth-Loop verbindet | AK-34, AK-35 |
| 17 | Changelog? | Öffentlich, vier Sprachen, Verweis auf `/legal` | Transparenz passt zur öffentlichen Zusage auf `/roadmap` |
| 18 | „Erfolgreich abgesendet" im Wartelisten-Trichter | Nur Absendungen, die zur Erfolgsseite führen; fehlerhafte zählen nicht | Der Trichter zeigt, wie viele sich tatsächlich eintragen; bestätigt am 2026-09-13 (AK-16) |
| 19 | Aussagen über die Sitzungskennung (OF-01) | Vorsichtig formulieren, Kennung offen nennen | Nur Nachstellbares behaupten; Tragfähigkeit der Roadmap-Zusage prüft `/sdd-betrieb` vor dem Deploy |
| 20 | Standort des zweiten VPS (OF-02) | Deutschland | Angabe des Betreibers, beim Systemdesign nachgemessen |
| 21 | Tunnel für den wöchentlichen Loop (OF-03) | ~~Der Loop baut ihn selbst, mit eingeschränktem Schlüssel~~ — **ersetzt durch #26** | Eine unbeaufsichtigte Routine liefert sonst nur Loop 1 |
| 22 | Ausfall der Messung als Kriterium (OF-04) | Ja, AK-37, höchstens 100 ms | Ohne Kriterium prüft niemand, dass ein toter VPS die Seite nicht verlangsamt |
| 23 | Überarbeitung: Weg zu Umami | **Vorhandene Umami-Instanz über ihre eigene Domain**; der zweite VPS wird darüber hinaus nicht verändert | Betreiber am 2026-09-14: „normale Statistiken wie bei Google Analytics, ohne den zweiten VPS zu ändern". Ersetzt eigenen Zähl-Eingang, Firewallregel, Länderdatei und Tunnel |
| 24 | Zählweg nach der Überarbeitung | Weiter über endlech.lu | Deckel und Kürzung bleiben, Umami-Domain nie im Seitenquelltext, Werbeblocker greifen seltener; bereits gebaut und geprüft. Verworfen: Skript direkt von der Umami-Domain |
| 25 | Ortsgenauigkeit (ersetzt #10) | Land, Region und Stadt, offen in `/legal` | Umami nutzt ohne eigene Länderdatei seine Städtedatenbank; eine Länderdatei hätte eine Änderung am VPS gekostet. Tragfähigkeit mit unbegrenzter Aufbewahrung: OF-01 |
| 26 | Zugang des Growth-Loops (ersetzt #21) | Eigener, nur lesender Benutzer, Anmeldung über die Umami-Domain, ohne zweiten Faktor | Kein Tunnel mehr; der Loop läuft unbeaufsichtigt. AK-38 entfällt, AK-39 geändert |
| 27 | Umami-Oberfläche (ersetzt #11) | Öffentlich über die Umami-Domain; Betreiberkonto mit starkem Passwort **und** zweitem Faktor | Umami 3.3.1 deckelt Anmeldeversuche nicht (nachgesehen); der zweite Faktor macht ein erratenes Passwort wertlos. AK-26 geändert, AK-41 neu |
| 28 | Zählaufrufe direkt an die Umami-Domain | Hingenommen | Wie bei jedem Analytics-Dienst; verfälschbare Zahlen, keine Personendaten. Verworfen: Traefik-Regel „nur der Anwendungs-VPS darf zählen" (Änderung am VPS). EC-08, Betriebsfolge OF-08 |
| 29 | Aufbewahrung mit Region und Stadt (OF-01) | Unbegrenzt, Texte weiter vorsichtig | Betreiberentscheidung am 2026-09-14, ohne fachliche Prüfung; langfristiger Vergleich der Reichweite |
| 30 | Rechtsgrundlage in `/legal` (OF-06) | Berechtigtes Interesse, Art. 6 Abs. 1 lit. f DSGVO, wie im Text | Betreiberentscheidung am 2026-09-14 nach dem Muster der Abschnitte zu Hostinger und Sentry; keine geprüfte Rechtslage |
| 31 | Dubletten und Honeypot im Wartelisten-Trichter (OF-05) | Hingenommen, in AK-15 benannt | Unterscheiden gäbe die Anti-Enumeration auf; genaue Zahlen liefert die Verwaltung |
| 32 | Kein Deckel an der öffentlichen Umami-Anmeldung, Direktaufrufe an die Domain (OF-08) | Hingenommen: zweiter Faktor für den Betreiber, langes Zufallspasswort für `growth-loop` | Keine Änderung am VPS (Decision Log #23); verworfen: rateLimit-Middleware am Proxy |
