#!/usr/bin/env python3
"""QA Feature 11 · AK-31 — Datenschutzabschnitt „Nutzungsmessung" auf /legal in vier Sprachen.

Schneidet den Abschnitt ab der Überschrift `id="nutzungsmessung"` bis zur nächsten Überschrift aus
und prüft, dass jede in AK-31 verlangte Angabe darin steht und keine der ausgeschlossenen
Behauptungen („anonym", „keine Kennung"). Ob die Angaben stimmen, belegen die übrigen Prüfläufe
(browser-pruefung.mjs, angriff.sh); Standort und Land nur die echte Instanz.

Aufruf gegen die lokale Anwendung im Produktionsmodus: python3 qa/11/legal.py [basis]
"""
import html
import re
import sys
import urllib.request

BASIS = sys.argv[1] if len(sys.argv) > 1 else 'http://127.0.0.1:8765'
PRUEF = {
    'Dienst Umami, selbst betrieben': r'Umami',
    'Anbieter Hostinger': r'Hostinger',
    'Standort Deutschland': r'Deutschland|Germany|Allemagne|Däitschland',
    'Pfad ohne Abfrage': r'Suchparameter|search parameters|paramètres|Sichparameter|query',
    'Herkunftsdomain': r'Domain|domaine',
    'Land': r'\bLand\b|country|pays',
    'Sitzungskennung offen genannt': r'kennung|identifier|identifiant',
    'Cookies (was nicht)': r'Cookie',
    'IP nicht gespeichert': r'IP',
    'Aufbewahrung ohne Grenze': r'ohne zeitliche Grenze|without (a )?time limit|sans limite|ouni zäitlech Grenz|indefinite',
    'Do Not Track': r'Do Not Track',
    'Global Privacy Control': r'Global Privacy Control',
    'Schalter im Abschnitt': r'data-controller="usage-opt-out"',
}
VERBOTEN = [r'\banonym', r'keine Kennung', r'no identifier', r'aucun identifiant', r'keng Kennung']

print('Prüfpunkte:', ', '.join(PRUEF))
for sprache in ['de', 'en', 'fr', 'lb']:
    roh = urllib.request.urlopen(f'{BASIS}/{sprache}/legal').read().decode()
    start = roh.find('id="nutzungsmessung"')
    ende = min(i for i in (roh.find('<h3', start + 10), roh.find('</section>', start)) if i > 0)
    abschnitt = roh[start:ende]
    text = html.unescape(re.sub(r'\s+', ' ', re.sub(r'<[^>]+>', ' ', abschnitt)))
    fehlt = [k for k, p in PRUEF.items() if not re.search(p, abschnitt if k.startswith('Schalter') else text, re.I)]
    verboten = [p for p in VERBOTEN if re.search(p, text, re.I)]
    print(f'{sprache}: {len(text)} Zeichen · fehlt: {fehlt or "nichts"} · „anonym"/„keine Kennung": {verboten or "keine"}')
