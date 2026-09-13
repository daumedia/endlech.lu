#!/usr/bin/env python3
"""
QA Feature 10 · Nachprüfung BF-147 von außen, wie ein Crawler sie sieht.

Frage: Behält nach der Reparatur NUR eine blätternde Seite ihre Seitenzahl im canonical-Verweis
und in den Sprachverweisen — und verliert jede andere Seite jede Abfrage?

Absichtlich UNABHÄNGIG vom Anwendungscode: Die blätternden Seiten stehen hier aus AK-13
(Restaurantliste) und OF-07 (Board-Übersicht) abgeschrieben, nicht aus App\\Seo\\SeoRegistry
gelesen. Die Seiten selbst kommen aus der ausgelieferten Sitemap, die Erwartung für jede Seite
aus ihrem eigenen Sitemap-Eintrag.

Aufruf:  python3 qa/10/bf147-nachpruefung.py http://127.0.0.1:8765
"""
import http.client, sys, urllib.parse, xml.etree.ElementTree as ET
from html.parser import HTMLParser

BASIS = sys.argv[1].rstrip('/')
HAUPT = 'https://endlech.lu'
SPRACHEN = ['lb', 'de', 'fr', 'en']
NS = {'s': 'http://www.sitemaps.org/schemas/sitemap/0.9', 'x': 'http://www.w3.org/1999/xhtml'}
TOKEN = 'ab' * 32

# AK-13 und OF-07: die Seiten, die blättern
BLAETTERND = {f'/{s}/restaurants' for s in SPRACHEN} | {f'/{s}/community/ideen' for s in SPRACHEN}

# Abfragen, die an jede Seite gehängt werden. Die zweite trägt Fremdes mit (BF-68/BF-110).
ABFRAGEN = ['page=2', 'page=2&sort=name&foo=%2F%2Ffremd.example', 'page=37']

# Ausschlusswege aus AK-15, die anonym im Produktionsmodus eine Seite rendern — am Server ermittelt,
# alle 16 Wege einzeln: 5 × 200, der Rest 302 (Anmeldung verlangt, Weiterleitung) oder 404.
# ⚠ Die erste Fassung führte `/community/suggest`; das verlangt eine Anmeldung und antwortet mit 302.
AUSSCHLUSS_SEITEN = ['/{s}/login', '/{s}/register', '/{s}/passwort-vergessen',
                     f'/{{s}}/app/abmelden/{TOKEN}', '/{s}/community/thanks']

fehler = []
zaehler = {'seiten': 0, 'blaetternd': 0, 'nicht_blaetternd': 0, 'ausschluss': 0}


class Kopf(HTMLParser):
    def __init__(self):
        super().__init__()
        self.canonical, self.alternates = [], {}

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag != 'link':
            return
        if a.get('rel') == 'canonical':
            self.canonical.append(a.get('href'))
        elif a.get('rel') == 'alternate' and a.get('hreflang'):
            self.alternates[a['hreflang']] = a.get('href')


def abruf(pfad):
    teile = urllib.parse.urlsplit(BASIS)
    v = http.client.HTTPConnection(teile.hostname, teile.port, timeout=20)
    v.request('GET', pfad)
    r = v.getresponse()
    rumpf = r.read().decode('utf-8', 'replace')
    v.close()
    return r.status, rumpf


def kopf(pfad):
    status, rumpf = abruf(pfad)
    k = Kopf()
    k.feed(rumpf)
    return status, k


def pruefe(bedingung, text):
    if not bedingung:
        fehler.append(text)


def mit(adresse, abfrage):
    return adresse + ('?' + abfrage if abfrage else '')


# ── Sitemap lesen: Seite → (canonical, Sprachverweise) ohne Abfrage
status, xml = abruf('/sitemap.xml')
assert status == 200, f'/sitemap.xml → {status}'
eintraege = {}
for url in ET.fromstring(xml).findall('s:url', NS):
    loc = url.find('s:loc', NS).text
    alt = {l.get('hreflang'): l.get('href') for l in url.findall('x:link', NS)}
    eintraege[loc] = alt
print(f'Sitemap: {len(eintraege)} Einträge')

# ── 1 · Jede Sitemap-Seite mit jeder Abfrage
for loc, alt in eintraege.items():
    pfad = urllib.parse.urlsplit(loc).path
    blaettert = pfad in BLAETTERND
    for abfrage in ABFRAGEN:
        status, k = kopf(mit(pfad, abfrage))
        zaehler['seiten'] += 1
        aufruf = mit(pfad, abfrage)

        if blaettert:
            seite = urllib.parse.parse_qs(abfrage)['page'][0]
            if status == 404:
                # Restaurantliste jenseits der letzten Seite: keine Seite, kein Verweis (BF-148)
                pruefe(k.canonical == [], f'{aufruf}: 404 mit canonical {k.canonical}')
                continue
            zaehler['blaetternd'] += 1
            pruefe(status == 200, f'{aufruf}: Status {status}')
            pruefe(k.canonical == [f'{loc}?page={seite}'], f'{aufruf}: canonical {k.canonical}, erwartet {loc}?page={seite}')
            for sprache, href in alt.items():
                pruefe(k.alternates.get(sprache) == f'{href}?page={seite}',
                       f'{aufruf}: Sprachverweis {sprache} = {k.alternates.get(sprache)}')
        else:
            zaehler['nicht_blaetternd'] += 1
            pruefe(status == 200, f'{aufruf}: Status {status}')
            pruefe(k.canonical == [loc], f'{aufruf}: canonical {k.canonical}, erwartet {loc}')
            pruefe(k.alternates == alt, f'{aufruf}: Sprachverweise weichen vom Sitemap-Eintrag ab: {k.alternates}')

        for href in k.canonical + list(k.alternates.values()):
            pruefe('sort=' not in href and 'foo=' not in href and 'fremd' not in href,
                   f'{aufruf}: Fremdparameter in {href}')

# ── 2 · Seite 1 und ungültige Seitenzahlen auf den blätternden Seiten
for pfad in sorted(BLAETTERND):
    loc = HAUPT + pfad
    for abfrage in ['page=1', 'page=0', 'page=-2', 'page=2%0A', 'page=%2B2']:
        status, k = kopf(mit(pfad, abfrage))
        if status == 200:
            pruefe(k.canonical == [loc], f'{mit(pfad, abfrage)}: canonical {k.canonical}, erwartet {loc}')
            pruefe(all('?' not in h for h in k.alternates.values()), f'{mit(pfad, abfrage)}: Abfrage in Sprachverweisen')
        else:
            pruefe(k.canonical == [], f'{mit(pfad, abfrage)}: {status} mit canonical')

# ── 3 · Ausschlusswege mit Seitenzahl: kein canonical, Sprachverweise ohne Abfrage
for vorlage in AUSSCHLUSS_SEITEN:
    for s in SPRACHEN:
        pfad = vorlage.format(s=s)
        status, k = kopf(mit(pfad, 'page=2'))
        zaehler['ausschluss'] += 1
        pruefe(status == 200, f'{pfad}?page=2: Status {status}')
        pruefe(k.canonical == [], f'{pfad}?page=2: canonical {k.canonical}')
        pruefe(len(k.alternates) == 5, f'{pfad}?page=2: {len(k.alternates)} Sprachverweise')
        pruefe(all('?' not in h for h in k.alternates.values()), f'{pfad}?page=2: Abfrage in Sprachverweisen {k.alternates}')

print(f"Abrufe: {zaehler['seiten']} Sitemap-Seiten mit Abfrage "
      f"({zaehler['nicht_blaetternd']} nicht blätternd, {zaehler['blaetternd']} blätternd mit Seite), "
      f"{zaehler['ausschluss']} Ausschlusswege")

# ── Beobachtung für OF-07, kein Prüfpunkt
for pfad in ['/de/restaurants?page=3', '/de/restaurants?page=99', '/de/community/ideen?page=2', '/de/community/ideen?page=99']:
    status, k = kopf(pfad)
    print(f'Beobachtung · {pfad} → {status}, canonical {k.canonical}')

if fehler:
    print(f'\n❌ {len(fehler)} Abweichungen:')
    for f in fehler[:40]:
        print('  ' + f)
    sys.exit(1)
print('\n✅ BF-147: keine Abweichung')
