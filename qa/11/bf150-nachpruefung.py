#!/usr/bin/env python3
"""BF-150 · Nachprüfung gegen eine echte Umami-3.3.1-Instanz (Wegwerf-Container, siehe qa/11/umgebung.md, Abschnitt 1).

Zwei Besucher gehen die Trichter durch — einer auf Deutsch, einer auf Französisch —, gesendet direkt an
Umamis `/api/send` (die Frage ist Umamis Auswertung, nicht die Weiterleitung der Anwendung). Danach fragt
das Skript Umamis Trichter-Bericht zweimal ab: mit den Schritten aus `growth/config.json` und mit der
ursprünglichen Schreibweise (`/*/…`).

Erwartet mit der Konfiguration: Suche 2 · 2 · 2 · 2, App 1 · 1, Partner 1 · 1, Organisationen 2 · 2.

Aufruf: UMAMI=http://127.0.0.1:39301 python3 qa/11/bf150-nachpruefung.py
Die Instanz steht auf `admin`/`umami` — sie ist lokal, leer und wird danach entfernt.
"""
import datetime
import json
import os
import time
import urllib.request

UMAMI = os.environ.get('UMAMI', 'http://127.0.0.1:39301')
WURZEL = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))


def anfrage(methode, pfad, rumpf=None, token=None, ua=None):
    kopf = {'Content-Type': 'application/json'}
    if token:
        kopf['Authorization'] = f'Bearer {token}'
    if ua:
        kopf['User-Agent'] = ua
    daten = json.dumps(rumpf).encode() if rumpf is not None else None
    with urllib.request.urlopen(urllib.request.Request(UMAMI + pfad, data=daten, headers=kopf, method=methode)) as antwort:
        text = antwort.read().decode()
        return json.loads(text) if text else {}


token = anfrage('POST', '/api/auth/login', {'username': 'admin', 'password': 'umami'})['token']
website = anfrage('POST', '/api/websites', {'name': 'endlech.lu (BF-150)', 'domain': 'endlech.lu'}, token)['id']
beginn = datetime.datetime.now(datetime.UTC) - datetime.timedelta(minutes=1)

BESUCHER = {
    'de': ('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'app'),
    'fr': ('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'partner'),
}
for sprache, (ua, warteliste) in BESUCHER.items():
    ablauf = [
        (f'/{sprache}/restaurants', None),
        (f'/{sprache}/restaurants', ('filter_angewandt', {'filter': 'wheelchair'})),
        (f'/{sprache}/restaurants/1', None),
        (f'/{sprache}/restaurants/1', ('kontaktweg_genutzt', {'art': 'website'})),
        (f'/{sprache}/{warteliste}', None),
        (f'/{sprache}/{warteliste}', ('warteliste_eingetragen', {'liste': warteliste})),
        (f'/{sprache}/organisationen/gemeinden', None),
        (f'/{sprache}/organisationen/gemeinden', ('warteliste_eingetragen', {'liste': 'organisation'})),
    ]
    for pfad, ereignis in ablauf:
        nutzlast = {'website': website, 'hostname': 'endlech.lu', 'url': pfad, 'language': sprache, 'screen': '1440x900'}
        if ereignis:
            nutzlast['name'], nutzlast['data'] = ereignis
        anfrage('POST', '/api/send', {'type': 'event', 'payload': nutzlast}, ua=ua)
        time.sleep(1.1)  # Umami ordnet die Schritte nach `created_at`

ende = datetime.datetime.now(datetime.UTC) + datetime.timedelta(minutes=1)


def trichter(schritte):
    rumpf = {
        'websiteId': website, 'type': 'funnel',
        'filters': {'startAt': int(beginn.timestamp() * 1000), 'endAt': int(ende.timestamp() * 1000), 'timezone': 'Europe/Luxembourg'},
        'parameters': {
            'startDate': beginn.strftime('%Y-%m-%dT%H:%M:%SZ'), 'endDate': ende.strftime('%Y-%m-%dT%H:%M:%SZ'), 'window': 60,
            'steps': [{'type': 'path' if s.startswith(('/', '*')) else 'event', 'value': s} for s in schritte],
        },
    }
    return [(s['value'], s['visitors']) for s in anfrage('POST', '/api/reports/funnel', rumpf, token)]


config = json.load(open(os.path.join(WURZEL, 'growth', 'config.json'), encoding='utf-8'))
ketten = {'suche': config['umami']['conversion_events'], **config['weitere_trichter']}
erwartet = {'suche': [2, 2, 2, 2], 'warteliste_app': [1, 1], 'warteliste_partner': [1, 1], 'warteliste_organisationen': [2, 2]}
alt = {
    'suche': ['/*/restaurants', 'filter_angewandt', '/*/restaurants/*', 'kontaktweg_genutzt'],
    'warteliste_app': ['/*/app', 'warteliste_eingetragen'],
    'warteliste_partner': ['/*/partner', 'warteliste_eingetragen'],
    'warteliste_organisationen': ['/*/organisationen*', 'warteliste_eingetragen'],
}

gut = 0
print('== Schritte aus growth/config.json')
for name, schritte in ketten.items():
    ergebnis = trichter(schritte)
    ok = [z for _, z in ergebnis] == erwartet[name]
    gut += ok
    print(f"{'✅' if ok else '❌'} {name}: {ergebnis} (erwartet {erwartet[name]})")
print('== Ursprüngliche Schreibweise zum Vergleich')
for name, schritte in alt.items():
    print(f'   {name}: {trichter(schritte)}')
print(f'{gut} von {len(ketten)} Trichtern zählen wie erwartet')
