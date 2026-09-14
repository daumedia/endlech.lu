#!/usr/bin/env node
/**
 * QA Feature 11 · AK-15 für die Partner- und die Organisations-Warteliste im echten Browser — und danach die
 * Trichter aus `growth/config.json` über Umamis Auswertung (Nachprüfung BF-150).
 *
 * `browser-pruefung.mjs` spielt nur die App-Warteliste durch; die beiden anderen Trichter blieben dort ohne
 * Daten und zählten deshalb 0, gleich ob die Schritte stimmen. Hier: Partner auf Französisch, Organisationen
 * über die Übersicht, je mit eigener Browserkennung — und die drei Zielgruppenseiten.
 *
 * ⚠ Der erste Lauf trug sich über `/de/organisationen/gemeinden` ein und bekam kein Ereignis: Das Formular
 * dort hat kein `action`, der Browser schickt an die Zielgruppenseite, und die kennt nur GET → 405 (BF-151,
 * B15). Die Zielgruppenseiten werden deshalb getrennt geprüft: Antwortcode des Absendens und was sichtbar ist.
 *
 * Vorbedingungen: qa/11/umgebung.md. Aufruf: QA=<Prüfordner mit admin-token.txt, website-id.txt> CDP=http://127.0.0.1:9333 node qa/11/wartelisten-browser.mjs
 */
import { execSync } from 'node:child_process';
import { readFileSync } from 'node:fs';

const CDP = process.env.CDP ?? 'http://127.0.0.1:9333';
const QA = process.env.QA;
const BASIS = 'http://endlech.lu:8765';
const warte = (ms) => new Promise((r) => setTimeout(r, ms));
const appSql = (sql) => execSync(`docker exec mika-database-1 mysql -uroot -proot endlech_test -N -e ${JSON.stringify(sql)} 2>/dev/null`).toString().trim();
let bestanden = 0;
let gesamt = 0;
const melde = (ak, ok, beleg) => { gesamt++; bestanden += ok ? 1 : 0; console.log(`${ok ? '✅' : '❌'} ${ak} · ${beleg}`); };

const version = await (await fetch(`${CDP}/json/version`)).json();
const ws = new WebSocket(version.webSocketDebuggerUrl);
let id = 0;
const offen = new Map();
const abo = new Map();
ws.onmessage = (m) => { const d = JSON.parse(m.data); if (d.id && offen.has(d.id)) { offen.get(d.id)(d); offen.delete(d.id); } else if (d.sessionId && abo.has(d.sessionId)) abo.get(d.sessionId)(d); };
await new Promise((r) => (ws.onopen = r));
const cdp = (method, params = {}, sessionId) => new Promise((r, f) => { const i = ++id; offen.set(i, (d) => (d.error ? f(new Error(`${method}: ${d.error.message}`)) : r(d.result))); ws.send(JSON.stringify({ id: i, method, params, sessionId })); });
const js = async (s, a) => (await cdp('Runtime.evaluate', { expression: a, awaitPromise: true, returnByValue: true }, s.sessionId)).result.value;

async function seite(ua) {
  const { browserContextId } = await cdp('Target.createBrowserContext');
  const { targetId } = await cdp('Target.createTarget', { url: 'about:blank', browserContextId });
  const { sessionId } = await cdp('Target.attachToTarget', { targetId, flatten: true });
  const s = { sessionId, zaehl: [], fehler: [] };
  abo.set(sessionId, (d) => {
    if (d.method === 'Network.requestWillBeSent' && new URL(d.params.request.url).pathname === '/api/send') s.zaehl.push(JSON.parse(d.params.request.postData ?? '{}').payload ?? {});
    if (d.method === 'Runtime.exceptionThrown') s.fehler.push(d.params.exceptionDetails.text);
  });
  for (const m of ['Network.enable', 'Page.enable', 'Runtime.enable']) await cdp(m, {}, sessionId);
  await cdp('Network.setUserAgentOverride', { userAgent: ua }, sessionId);
  return s;
}

async function eintragen(ua, pfad, felder, liste) {
  const s = await seite(ua);
  await cdp('Page.navigate', { url: BASIS + pfad }, s.sessionId);
  await warte(2500);
  const vorher = s.zaehl.length;
  const gefuellt = await js(s, `(() => {
    const felder = ${JSON.stringify(felder)};
    let form = null;
    for (const [name, wert] of Object.entries(felder)) {
      const el = [...document.querySelectorAll('[name="' + name + '"]')].find((e) => e.type !== 'radio' || e.value === wert) ?? null;
      if (!el) return 'fehlt: ' + name;
      if (el.type === 'checkbox' || el.type === 'radio') el.checked = true; else el.value = wert;
      form = el.form;
    }
    form.requestSubmit();
    return 'ok';
  })()`);
  await warte(4500);
  const ereignisse = s.zaehl.slice(vorher).filter((p) => p.name === 'warteliste_eingetragen');
  melde('AK-15', gefuellt === 'ok' && ereignisse.length === 1 && ereignisse[0].data?.liste === liste,
    `${pfad}: Formular ${gefuellt}, Ereignisse ${JSON.stringify(ereignisse.map((p) => ({ url: p.url, data: p.data })))}`);
  melde('AK-17', !JSON.stringify(s.zaehl).includes('@') && !JSON.stringify(s.zaehl).includes('QA11'), `${pfad}: keine Adresse, kein Name in ${s.zaehl.length} Zählaufrufen`);
  melde('AK-15', s.fehler.length === 0, `${pfad}: JS-Fehler ${JSON.stringify(s.fehler)}`);
}

await eintragen('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '/fr/partner', {
  'partner_waitlist[restaurantName]': 'QA11 Brasserie', 'partner_waitlist[contactName]': 'QA11 Kontakt',
  'partner_waitlist[email]': 'qa11-partner@example.lu', 'partner_waitlist[locality]': 'Esch-sur-Alzette', 'partner_waitlist[consent]': '1',
}, 'partner');
await eintragen('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '/de/organisationen', {
  'organisation_waitlist[type]': 'commune', 'organisation_waitlist[organisationName]': 'QA11 Gemeinde', 'organisation_waitlist[contactName]': 'QA11 Kontakt',
  'organisation_waitlist[email]': 'qa11-gemeinde@example.lu', 'organisation_waitlist[consent]': '1',
}, 'organisation');

// BF-151 · Absenden von den Zielgruppenseiten: Antwortcode und sichtbarer Text
for (const slug of ['gemeinden', 'unternehmen', 'vereine']) {
  const s = await seite('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36');
  const antworten = [];
  abo.set(s.sessionId, ((alt) => (d) => { alt(d); if (d.method === 'Network.responseReceived' && d.params.type !== 'Fetch' || (d.method === 'Network.responseReceived' && d.params.response.url.includes('/organisationen'))) antworten.push([d.params.response.status, new URL(d.params.response.url).pathname]); })(abo.get(s.sessionId)));
  await cdp('Page.navigate', { url: `${BASIS}/de/organisationen/${slug}` }, s.sessionId);
  await warte(2500);
  const vorher = antworten.length;
  const typ = { gemeinden: 'commune', unternehmen: 'company', vereine: 'association' }[slug];
  await js(s, `(() => { const f = { 'organisation_waitlist[organisationName]': 'QA11 ${slug}', 'organisation_waitlist[contactName]': 'QA11 Kontakt', 'organisation_waitlist[email]': 'qa11-${slug}@example.lu' };
    for (const [n, w] of Object.entries(f)) document.querySelector('[name="' + n + '"]').value = w;
    document.querySelector('[name="organisation_waitlist[type]"][value="${typ}"]').checked = true;
    document.querySelector('[name="organisation_waitlist[consent]"]').checked = true;
    document.querySelector('[name="organisation_waitlist[email]"]').form.requestSubmit(); })()`);
  await warte(4000);
  const absenden = antworten.slice(vorher).filter(([, p]) => p.startsWith('/de/organisationen'));
  const sichtbar = (await js(s, `document.querySelector('main')?.innerText ?? document.body.innerText`)).replace(/\s+/g, ' ').slice(0, 160);
  const gespeichert = appSql(`SELECT COUNT(*) FROM organisation_waitlist_entry WHERE email='qa11-${slug}@example.lu'`);
  const ok = absenden.some(([c]) => c < 400) && gespeichert === '1';
  melde('BF-151', ok, `/de/organisationen/${slug} absenden → ${JSON.stringify(absenden)} · gespeichert ${gespeichert} · Ereignisse ${s.zaehl.filter((p) => p.name === 'warteliste_eingetragen').length} · sichtbar: „${sichtbar}"`);
}

await warte(1500);
appSql("DELETE FROM partner_waitlist_entry WHERE email='qa11-partner@example.lu'");
appSql("DELETE FROM organisation_waitlist_entry WHERE email LIKE 'qa11-%@example.lu'");
appSql('DELETE FROM messenger_messages');

// Trichter über Umamis Auswertungsschnittstelle, Schritte aus growth/config.json
const token = readFileSync(`${QA}/admin-token.txt`, 'utf8').trim();
const website = readFileSync(`${QA}/website-id.txt`, 'utf8').trim();
const config = JSON.parse(readFileSync(new URL('../../growth/config.json', import.meta.url), 'utf8'));
const beginn = new Date(Date.now() - 3 * 3600e3);
const ende = new Date(Date.now() + 5 * 60e3);
async function trichter(schritte) {
  const antwort = await fetch('http://127.0.0.1:39300/api/reports/funnel', {
    method: 'POST', headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
    body: JSON.stringify({ websiteId: website, type: 'funnel', filters: { startAt: +beginn, endAt: +ende, timezone: 'Europe/Luxembourg' },
      parameters: { startDate: beginn.toISOString(), endDate: ende.toISOString(), window: 60, steps: schritte.map((v) => ({ type: /^[/*]/.test(v) ? 'path' : 'event', value: v })) } }),
  });
  return (await antwort.json()).map((x) => x.visitors);
}
for (const name of ['warteliste_partner', 'warteliste_organisationen']) {
  const schritte = config.weitere_trichter[name];
  const zahlen = await trichter(schritte);
  melde('AK-15', zahlen.every((z) => z >= 1), `Umami-Trichter ${name} ${JSON.stringify(schritte)} → ${JSON.stringify(zahlen)}`);
}
console.log(`\nAufgeräumt: partner/organisation/messenger → ${appSql("SELECT (SELECT COUNT(*) FROM partner_waitlist_entry WHERE email LIKE 'qa11-%') + (SELECT COUNT(*) FROM organisation_waitlist_entry WHERE email LIKE 'qa11-%') + (SELECT COUNT(*) FROM messenger_messages)")}`);
console.log(`${bestanden} von ${gesamt} Prüfungen bestanden`);
ws.close();
