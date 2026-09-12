#!/usr/bin/env python3
"""
QA Feature 10 · Prüfung von außen, wie ein Crawler sie sieht.

Absichtlich UNABHÄNGIG vom Anwendungscode: Die festen Seiten und die Ausschlusswege sind
hier aus features/10-sitemap-robots/spec.md abgeschrieben, nicht aus App\\Seo\\SeoRegistry
gelesen. Ein Prüfwerkzeug, das seine Erwartung aus dem Prüfling ableitet, prüft gegen sich
selbst.

Aufruf:  python3 qa/10/http-pruefung.py http://127.0.0.1:8765 <anzahl-restaurants> [--deckel]
         --deckel prüft zusätzlich AK-23 und VERBRAUCHT dabei das Kontingent der Adresse.
"""
import http.client, re, sys, urllib.parse, xml.etree.ElementTree as ET
from html.parser import HTMLParser

BASIS = sys.argv[1].rstrip('/')
N = int(sys.argv[2])
HAUPT = 'https://endlech.lu'
SPRACHEN = ['lb', 'de', 'fr', 'en']
NS = {'s': 'http://www.sitemaps.org/schemas/sitemap/0.9', 'x': 'http://www.w3.org/1999/xhtml'}
TOKEN = 'ab' * 32

# Tabelle „Die festen Seiten" aus spec.md
FESTE = ['/{s}/', '/{s}/about', '/{s}/criteria', '/{s}/accessibility', '/{s}/legal', '/{s}/open',
         '/{s}/presse', '/{s}/roadmap', '/{s}/changelog', '/{s}/vergleich', '/{s}/vergleich/google-maps',
         '/{s}/vergleich/tripadvisor', '/{s}/vergleich/wheelmap', '/{s}/restaurants', '/{s}/partner',
         '/{s}/organisationen', '/{s}/organisationen/gemeinden', '/{s}/organisationen/unternehmen',
         '/{s}/organisationen/vereine', '/{s}/app', '/{s}/community/ideen']

# AK-15, als Pfade (Routen aus `debug:router`, Token nach deren Anforderung [a-f0-9]{64})
AUSSCHLUSS = ['/{s}/login', '/{s}/register', '/{s}/passwort-vergessen', f'/{{s}}/passwort-zuruecksetzen/{TOKEN}',
              f'/{{s}}/verify/{TOKEN}', f'/{{s}}/verify/email-change/{TOKEN}', f'/{{s}}/partner/confirmation/{TOKEN}',
              f'/{{s}}/organisationen/confirmation/{TOKEN}', f'/{{s}}/app/confirmation/{TOKEN}',
              f'/{{s}}/partner/abmelden/{TOKEN}', f'/{{s}}/organisationen/abmelden/{TOKEN}',
              f'/{{s}}/app/abmelden/{TOKEN}', '/{s}/community/suggest', '/{s}/community/thanks',
              '/{s}/community/ideen/neu', '/{s}/community/ideen/eingereicht']

ergebnisse = []
sitemap_abrufe = []   # jeder Abruf von /sitemap.xml, in Reihenfolge — AK-23 zählt über alle

def abruf(pfad, methode='GET', kopf=None):
    """Ohne Weiterleitungen zu folgen — AK-04 und AK-15 brauchen die rohe Antwort."""
    u = urllib.parse.urlsplit(BASIS)
    c = http.client.HTTPConnection(u.hostname, u.port, timeout=20)
    c.request(methode, pfad, headers=kopf or {})
    r = c.getresponse()
    if pfad.split('?')[0] == '/sitemap.xml':
        sitemap_abrufe.append(r.status)
    koerper = r.read().decode('utf-8', 'replace')
    k = {n.lower(): v for n, v in r.getheaders()}
    c.close()
    return r.status, k, koerper

def melde(ak, ok, beleg):
    ergebnisse.append((ak, ok, beleg))
    print(f"{'✅' if ok else '❌'} {ak:6} {beleg}")

class Kopf(HTMLParser):
    def __init__(self):
        super().__init__(); self.canonical = []; self.alternates = {}; self.robots_meta = []
    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == 'link' and (a.get('rel') or '').lower() == 'canonical':
            self.canonical.append(a.get('href'))
        if tag == 'link' and (a.get('rel') or '').lower() == 'alternate' and a.get('hreflang'):
            self.alternates[a['hreflang']] = a.get('href')
        if tag == 'meta' and (a.get('name') or '').lower() == 'robots':
            self.robots_meta.append(a.get('content') or '')

def kopf_von(html):
    p = Kopf(); p.feed(html); return p

# ── EC-06 zuerst: Der ERSTE Abruf nach leerem Speicher kommt über www. ─────────
# Käme der Host aus der Anfrage, stünde www in der gespeicherten Fassung — und damit auch in
# jedem folgenden, gewöhnlichen Abruf. Voraussetzung: cache.sitemap vor dem Lauf geleert.
st_www, _, xml_www = abruf('/sitemap.xml', kopf={'Host': 'www.endlech.lu'})

# ── Sitemap holen ────────────────────────────────────────────────────────────
st, k, xml = abruf('/sitemap.xml')
melde('EC-06', st_www == 200 and 'www.endlech.lu' not in xml_www and 'www.endlech.lu' not in xml and xml == xml_www,
      f"1. Abruf nach leerem Speicher mit Host www.endlech.lu → {st_www}; www im XML: {'www.endlech.lu' in xml_www}; "
      f"folgender gewöhnlicher Abruf identisch: {xml == xml_www}")
melde('AK-01', st == 200 and k.get('content-type', '').startswith('application/xml'),
      f"GET /sitemap.xml → {st}, Content-Type {k.get('content-type')} (Schema separat mit PHP)")
wurzel = ET.fromstring(xml)
eintraege = {}
for url in wurzel.findall('s:url', NS):
    loc = url.find('s:loc', NS).text
    eintraege[loc] = {l.get('hreflang'): l.get('href') for l in url.findall('x:link', NS)}
    for verboten in ('lastmod', 'priority', 'changefreq'):
        if url.find(f's:{verboten}', NS) is not None:
            melde('AK-07', False, f"{verboten} in {loc}")
erwartet_anzahl = (21 + N) * 4
melde('AK-02', len(wurzel.findall('s:url', NS)) == erwartet_anzahl,
      f"{len(wurzel.findall('s:url', NS))} <url>-Einträge, erwartet (21 + {N}) × 4 = {erwartet_anzahl}")
soll_fest = {HAUPT + p.format(s=s) for p in FESTE for s in SPRACHEN}
ist_fest = {l for l in eintraege if not re.search(r'/restaurants/\d+$', l)}
melde('AK-02', soll_fest == ist_fest, f"feste Einträge deckungsgleich mit der Spec-Tabelle: {len(ist_fest)} von {len(soll_fest)}"
      + ('' if soll_fest == ist_fest else f" · fehlt {sorted(soll_fest - ist_fest)[:3]} · zu viel {sorted(ist_fest - soll_fest)[:3]}"))
melde('AK-07', not re.search(r'<(lastmod|priority|changefreq)', xml), "kein lastmod, priority, changefreq im ausgelieferten XML")

schlecht3 = [l for l in eintraege if not re.match(r'^https://endlech\.lu/(lb|de|fr|en)/', l) or '?' in l]
melde('AK-03', not schlecht3, f"{len(eintraege)} Adressen, davon mit falschem Anfang oder '?': {len(schlecht3)}")

schlecht5 = []
for loc, v in eintraege.items():
    sprache = re.match(r'^https://endlech\.lu/(lb|de|fr|en)/', loc).group(1)
    if list(v) != ['lb', 'de', 'fr', 'en', 'x-default'] or v['x-default'] != v['lb'] or v.get(sprache) != loc:
        schlecht5.append(loc)
melde('AK-05', not schlecht5, f"Einträge ohne vollständige, sich selbst einschließende Sprachverweise: {len(schlecht5)}")

muster6 = re.compile(r'/(admin|profile|api|login|register|passwort-vergessen|passwort-zuruecksetzen|verify|confirmation|abmelden|thanks|suggest|eingereicht|neu)(/|$)|/community/ideen/.+')
schlecht6 = [l for l in eintraege if muster6.search(urllib.parse.urlsplit(l).path)]
melde('AK-06', not schlecht6, f"Adressen aus ausgeschlossenen Bereichen: {len(schlecht6)}")
alle_adressen = set(eintraege) | {h for v in eintraege.values() for h in v.values()}
schlecht22 = [a for a in alle_adressen if '@' in a or re.search(r'[0-9a-f]{24,}', a, re.I)]
melde('AK-22', not schlecht22, f"{len(alle_adressen)} Adressen (loc + hreflang), mit '@' oder Token-Muster: {len(schlecht22)}")

# ── Jede Adresse aufrufen: AK-04, AK-12, AK-14, AK-16 ─────────────────────────
f4, f12, f14, f16 = [], [], [], []
for loc, v in eintraege.items():
    pfad = loc[len(HAUPT):]
    st, k, html = abruf(pfad)
    if st != 200:
        f4.append(f"{pfad}→{st}"); continue
    kp = kopf_von(html)
    if kp.canonical != [loc]:
        f12.append(f"{pfad}→{kp.canonical}")
    if kp.alternates != v:
        f14.append(pfad)
    if 'x-robots-tag' in k or any('noindex' in m for m in kp.robots_meta):
        f16.append(pfad)
melde('AK-04', not f4, f"{len(eintraege)} Adressen einzeln abgerufen, ohne Weiterleitung zu folgen · nicht 200: {len(f4)} {f4[:3]}")
melde('AK-12', not f12, f"canonical ≠ Sitemap-Adresse: {len(f12)} {f12[:2]}")
melde('AK-14', not f14, f"hreflang der Seite ≠ Sitemap-Eintrag: {len(f14)} {f14[:2]}")
melde('AK-16', not f16, f"Sitemap-Seiten mit X-Robots-Tag oder noindex-Meta: {len(f16)}")

# ── AK-13 ────────────────────────────────────────────────────────────────────
for aufruf, soll in [('/de/restaurants?sort=name', f'{HAUPT}/de/restaurants'),
                     ('/de/restaurants?wheelchair=1&city=Esch', f'{HAUPT}/de/restaurants'),
                     ('/de/restaurants?page=2&sort=name', f'{HAUPT}/de/restaurants?page=2'),
                     ('/de/restaurants?page=1', f'{HAUPT}/de/restaurants'),
                     ('/de/restaurants?page=0', f'{HAUPT}/de/restaurants'),
                     ('/de/restaurants?page=abc', None)]:
    st, k, html = abruf(aufruf)
    ist = kopf_von(html).canonical
    if soll is None:
        melde('AK-13', st != 200 and ist == [], f"{aufruf} → {st}, canonical {ist} (Spec-Zeile: {HAUPT}/de/restaurants · OF-06)")
    else:
        melde('AK-13', st == 200 and ist == [soll], f"{aufruf} → {st}, canonical {ist}")

# ── AK-15 ────────────────────────────────────────────────────────────────────
f15, codes = [], {}
for muster in AUSSCHLUSS:
    for s in SPRACHEN:
        pfad = muster.format(s=s)
        st, k, _ = abruf(pfad)
        codes.setdefault(st, 0); codes[st] += 1
        if k.get('x-robots-tag') != 'noindex':
            f15.append(f"{pfad}→{st}:{k.get('x-robots-tag')}")
melde('AK-15', not f15, f"16 Wege × 4 Sprachen = {16*4} Abrufe · Statuscodes {dict(sorted(codes.items()))} · ohne 'X-Robots-Tag: noindex': {len(f15)} {f15[:3]}")

# ── AK-17 … AK-21 ────────────────────────────────────────────────────────────
st, k, robots = abruf('/robots.txt')
repo = open(__file__.rsplit('/qa/', 1)[0] + '/public/robots.txt', encoding='utf-8').read()
melde('AK-17', st == 200 and k.get('content-type', '').startswith('text/plain') and 'Sitemap: https://endlech.lu/sitemap.xml' in robots.splitlines(),
      f"GET /robots.txt → {st}, {k.get('content-type')}, Sitemap-Zeile vorhanden, ausgeliefert = Repository: {robots == repo}")

def regeln(text):
    r, alle = [], False
    for z in text.splitlines():
        z = z.split('#', 1)[0].strip()
        if ':' not in z: continue
        f, w = [t.strip() for t in z.split(':', 1)]
        if f.lower() == 'user-agent': alle = (w == '*')
        elif alle and f.lower() in ('allow', 'disallow') and w: r.append((f.lower(), w))
    return r
R = regeln(robots)
def erlaubt(pfad):
    beste = None
    for art, pr in R:
        if '*' in pr or '$' in pr: raise SystemExit(f'Platzhalter in Regel {pr} — Präfixauswertung ungültig')
        if pfad.startswith(pr) and (beste is None or len(pr) > len(beste[1]) or (len(pr) == len(beste[1]) and art == 'allow')):
            beste = (art, pr)
    return beste is None or beste[0] == 'allow'

gesperrt_soll = ['/api/v1/restaurants', '/api/docs'] + [p.format(s=s) for s in SPRACHEN for p in ('/{s}/admin', '/{s}/admin/restaurants', '/{s}/profile', '/{s}/profile/edit', '/{s}/api/cuisines/search')]
melde('AK-18', all(not erlaubt(p) for p in gesperrt_soll), f"{len(gesperrt_soll)} Pfade aus Verwaltung/Profil/Schnittstelle, davon erlaubt: {sum(erlaubt(p) for p in gesperrt_soll)}")
erlaubt_soll = [l[len(HAUPT):] for l in eintraege] + ['/open.json', '/open/dataset.csv', '/open/dataset.json']
melde('AK-19', all(erlaubt(p) for p in erlaubt_soll), f"{len(erlaubt_soll)} Pfade (alle Sitemap-Adressen + offene Daten), davon gesperrt: {sum(not erlaubt(p) for p in erlaubt_soll)}")
aus = [m.format(s=s) for m in AUSSCHLUSS for s in SPRACHEN]
melde('AK-20', all(erlaubt(p) for p in aus), f"{len(aus)} Ausschlusswege, davon gesperrt: {sum(not erlaubt(p) for p in aus)}")
melde('AK-21', ('disallow', '/') not in R and erlaubt('/') and erlaubt('/lb/'), f"Regeln: {R.__len__()}, keine Sperre der ganzen Seite")

# ── AK-23 (verbraucht das Kontingent) ─────────────────────────────────────────
if '--deckel' in sys.argv:
    # Gezählt wird über ALLE Sitemap-Abrufe dieses Laufs, auch die zwei vom Anfang.
    ra, keks = None, False
    while len(sitemap_abrufe) < 62:
        st, k, _ = abruf('/sitemap.xml')
        if st == 429 and ra is None:
            ra = k.get('retry-after')
        keks = keks or ('set-cookie' in k)
    erster429 = next((i + 1 for i, s in enumerate(sitemap_abrufe) if s == 429), None)
    vor429 = sitemap_abrufe[:(erster429 or 63) - 1]
    dst, _, _ = abruf('/open/dataset.json')
    melde('AK-23', erster429 == 61 and vor429 == [200] * 60 and ra is not None and int(ra) >= 1,
          f"{len(sitemap_abrufe)} Abrufe in diesem Lauf · davor {vor429.count(200)}× 200 · erster 429 bei Nr. {erster429}, "
          f"Retry-After {ra} s · Set-Cookie auf gedeckelten Antworten: {keks} · /open/dataset.json danach: {dst}")

print(f"\n{sum(1 for _, ok, _ in ergebnisse if ok)} von {len(ergebnisse)} Prüfungen bestanden")
