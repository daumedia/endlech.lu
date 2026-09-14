#!/usr/bin/env node
/**
 * QA Feature 11 · AK-37 — erscheint der erste Inhalt bei ausgefallener oder blockierter Messung später,
 * und funktioniert die Seite weiter?
 *
 * Misst „first-contentful-paint" im echten Browser (Chromium über das DevTools-Protokoll), je Seite fünf
 * Aufrufe ohne Browser-Zwischenspeicher. Die Bedingung (normal, Umami angehalten, Eingang aus, Eingang
 * hängt, Skript blockiert) stellt qa/11/ladezeit.sh her; dieses Skript misst und prüft die Funktionen.
 *
 * Aufruf: CDP=http://127.0.0.1:9333 node qa/11/ladezeit.mjs <bedingung> [--blockiert] [--funktionen]
 */
import { execSync } from 'node:child_process';

const [, , bedingung = 'normal', ...optionen] = process.argv;
const blockiert = optionen.includes('--blockiert');
const funktionen = optionen.includes('--funktionen');
const CDP = process.env.CDP ?? 'http://127.0.0.1:9333';
const BASIS = 'http://endlech.lu:8765';
const BROWSER = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36';
const SEITEN = ['/de/', '/de/restaurants', '/de/restaurants/1'];
const DURCHLAEUFE = 5;
const warte = (ms) => new Promise((r) => setTimeout(r, ms));
const appSql = (sql) => execSync(`docker exec mika-database-1 mysql -uroot -proot endlech_test -N -e ${JSON.stringify(sql)} 2>/dev/null`).toString().trim();

const version = await (await fetch(`${CDP}/json/version`)).json();
const ws = new WebSocket(version.webSocketDebuggerUrl);
let naechsteId = 0;
const offen = new Map();
const abonnenten = new Map();
ws.onmessage = (m) => {
  const d = JSON.parse(m.data);
  if (d.id && offen.has(d.id)) { offen.get(d.id)(d); offen.delete(d.id); } else if (d.sessionId && abonnenten.has(d.sessionId)) abonnenten.get(d.sessionId)(d);
};
await new Promise((r) => (ws.onopen = r));
const cdp = (method, params = {}, sessionId) => new Promise((r, f) => {
  const id = ++naechsteId;
  offen.set(id, (d) => (d.error ? f(new Error(`${method}: ${d.error.message}`)) : r(d.result)));
  ws.send(JSON.stringify({ id, method, params, sessionId }));
});

async function neueSeite(kontext) {
  const { targetId } = await cdp('Target.createTarget', { url: 'about:blank', browserContextId: kontext });
  const { sessionId } = await cdp('Target.attachToTarget', { targetId, flatten: true });
  const s = { sessionId, targetId, geladen: null, fehler: [], zaehl: 0 };
  abonnenten.set(sessionId, (d) => {
    if (d.method === 'Page.loadEventFired' && s.geladen) s.geladen();
    if (d.method === 'Runtime.exceptionThrown') s.fehler.push(d.params.exceptionDetails.text);
    if (d.method === 'Network.requestWillBeSent' && new URL(d.params.request.url).pathname === '/api/send') s.zaehl++;
  });
  for (const m of ['Network.enable', 'Page.enable', 'Runtime.enable']) await cdp(m, {}, sessionId);
  await cdp('Network.setCacheDisabled', { cacheDisabled: true }, sessionId);
  await cdp('Network.setUserAgentOverride', { userAgent: BROWSER }, sessionId);
  if (blockiert) await cdp('Network.setBlockedURLs', { urls: ['*zaehler.js*'] }, sessionId);
  return s;
}
async function oeffne(s, url) {
  const geladen = new Promise((r) => (s.geladen = r));
  await cdp('Page.navigate', { url }, s.sessionId);
  await Promise.race([geladen, warte(20000)]);
}
const js = async (s, a) => (await cdp('Runtime.evaluate', { expression: a, awaitPromise: true, returnByValue: true }, s.sessionId)).result.value;

const kontext = (await cdp('Target.createBrowserContext')).browserContextId;
const zeilen = [];
for (const pfad of SEITEN) {
  const werte = [];
  for (let i = 0; i < DURCHLAEUFE; i++) {
    const s = await neueSeite(kontext);
    await oeffne(s, BASIS + pfad);
    await warte(300);
    werte.push(await js(s, `performance.getEntriesByName('first-contentful-paint')[0]?.startTime ?? -1`));
    await cdp('Target.closeTarget', { targetId: s.targetId });
  }
  const mittel = werte.reduce((a, b) => a + b, 0) / werte.length;
  zeilen.push({ pfad, mittel: Math.round(mittel), werte: werte.map(Math.round) });
}
console.log(JSON.stringify({ bedingung, blockiert, seiten: zeilen }));

if (funktionen) {
  const s = await neueSeite(kontext);
  // Filter: Die Ergebnisseite erscheint mit dem Filter in der Adresse.
  await oeffne(s, `${BASIS}/de/restaurants`);
  await warte(800);
  await js(s, `(() => { const f = document.querySelector('form[action$="/restaurants"]'); f.querySelector('input[name="wheelchair"]').checked = true; f.requestSubmit(); })()`);
  await warte(3000);
  const filterOk = (await js(s, 'location.search')).includes('wheelchair=1');
  // Kontaktweg: Klick auf den Telefon-Link des Restaurants löst keinen Fehler aus.
  await js(s, `Turbo.visit('/de/restaurants/1')`);
  await warte(2500);
  const kontaktOk = await js(s, `(() => { const a = document.querySelector('a[href^="tel:"]'); if (!a) return false; a.addEventListener('click', (e) => e.preventDefault(), { once: true }); a.click(); return true; })()`);
  // Warteliste: Die Erfolgsmeldung erscheint.
  await oeffne(s, `${BASIS}/de/app`);
  await warte(800);
  await js(s, `(() => { const e = document.querySelector('[name="app_waitlist[email]"]'); e.value = 'qa11-ausfall@example.lu';
    const p = document.querySelector('[name="app_waitlist[platform]"][value="android"]') ?? document.querySelector('[name="app_waitlist[platform]"]');
    if (p.tagName === 'SELECT') p.value = 'android'; else p.checked = true;
    document.querySelector('[name="app_waitlist[consent]"]').checked = true; e.form.requestSubmit(); })()`);
  await warte(4000);
  const wartelisteOk = await js(s, `!!document.querySelector('#app-waitlist-form[role="status"]')`);
  const meldungSichtbar = await js(s, `document.body.innerText.match(/Fehler|error|500|Messung/i)?.[0] ?? ''`);
  appSql("DELETE FROM app_waitlist_entry WHERE email='qa11-ausfall@example.lu'");
  appSql('DELETE FROM messenger_messages');
  console.log(JSON.stringify({ bedingung, funktionen: { filterOk, kontaktOk, wartelisteOk, jsFehler: s.fehler, fehlermeldungImText: meldungSichtbar } }));
}
ws.close();
