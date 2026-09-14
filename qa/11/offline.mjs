#!/usr/bin/env node
/**
 * QA Feature 11 · EC-06 — installierte App offline: kein Zählaufruf, kein Fehler; online wird gezählt.
 *
 * Zwei Fallstricke, beide beim Prüfen gemessen:
 *  1 · Ein Service Worker registriert sich nur in einem sicheren Kontext. `http://endlech.lu:8765` ist keiner;
 *      ohne `--unsafely-treat-insecure-origin-as-secure=http://endlech.lu:8765` lieferte der Lauf die
 *      Chromium-Fehlerseite statt `offline.html`. `chrome-headless-shell` ignoriert die Option — nötig ist
 *      „Chrome for Testing" mit `--headless=new`. Der Host muss `endlech.lu` bleiben (sonst zählt der Tracker
 *      wegen `data-domains` gar nicht, und „kein Zählaufruf" wäre wertlos).
 *  2 · `Network.emulateNetworkConditions` auf der SEITE trennt den Service Worker nicht vom Netz — er lud die
 *      Detailseite am Emulator vorbei, und es sah aus, als würde offline gezählt. Die Emulation wird deshalb
 *      zusätzlich am Service-Worker-Ziel gesetzt.
 *
 * Aufruf: CDP=http://127.0.0.1:9334 node qa/11/offline.mjs
 */
const CDP = process.env.CDP ?? 'http://127.0.0.1:9334';
const BASIS = 'http://endlech.lu:8765';
const v = await (await fetch(`${CDP}/json/version`)).json();
const ws = new WebSocket(v.webSocketDebuggerUrl); let id = 0; const offen = new Map(); const ev = [];
ws.onmessage = (m) => { const d = JSON.parse(m.data); if (d.id && offen.has(d.id)) { offen.get(d.id)(d); offen.delete(d.id); } else ev.push(d); };
await new Promise((r) => (ws.onopen = r));
const send = (method, params = {}, sessionId) => new Promise((r) => { const i = ++id; offen.set(i, (d) => r(d.result ?? d)); ws.send(JSON.stringify({ id: i, method, params, sessionId })); });
const warte = (ms) => new Promise((r) => setTimeout(r, ms));
const js = async (a, sessionId) => (await send('Runtime.evaluate', { expression: a, awaitPromise: true, returnByValue: true }, sessionId)).result?.value;
const istZaehl = (e) => e.method === 'Network.requestWillBeSent' && new URL(e.params.request.url).pathname === '/api/send';
const zaehl = (liste) => {
  const ids = liste.filter(istZaehl).map((e) => e.params.requestId);
  const gescheitert = liste.filter((e) => e.method === 'Network.loadingFailed' && ids.includes(e.params.requestId)).length;
  const beantwortet = liste.filter((e) => e.method === 'Network.responseReceived' && ids.includes(e.params.requestId)).length;
  return { abgesetzt: ids.length, beantwortet, gescheitert };
};
const fehlerIn = (liste) => [
  ...liste.filter((e) => e.method === 'Runtime.exceptionThrown').map((e) => e.params.exceptionDetails.exception?.description ?? e.params.exceptionDetails.text),
  ...liste.filter((e) => e.method === 'Runtime.consoleAPICalled' && e.params.type === 'error').map((e) => e.params.args.map((a) => a.value ?? a.description).join(' ')),
];

const { browserContextId } = await send('Target.createBrowserContext');
const { targetId } = await send('Target.createTarget', { url: 'about:blank', browserContextId });
const { sessionId } = await send('Target.attachToTarget', { targetId, flatten: true });
for (const m of ['Network.enable', 'Page.enable', 'Runtime.enable']) await send(m, {}, sessionId);
await send('Network.setUserAgentOverride', { userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36' }, sessionId);

let swSitzung = null;
async function netz(online) {
  const bedingung = { offline: !online, latency: 0, downloadThroughput: -1, uploadThroughput: -1 };
  await send('Network.emulateNetworkConditions', bedingung, sessionId);
  if (!swSitzung) {
    const { targetInfos } = await send('Target.getTargets');
    const sw = targetInfos.find((t) => t.type === 'service_worker' && t.url.endsWith('/sw.js'));
    if (sw) { swSitzung = (await send('Target.attachToTarget', { targetId: sw.targetId, flatten: true })).sessionId; await send('Network.enable', {}, swSitzung); }
  }
  if (swSitzung) await send('Network.emulateNetworkConditions', bedingung, swSitzung);
}

// 1 · online: Seite laden, Service Worker aktiv werden lassen, Zählaufruf beobachten
await send('Page.navigate', { url: `${BASIS}/de/restaurants` }, sessionId);
let swAktiv = false;
for (let i = 0; i < 30 && !swAktiv; i++) { await warte(500); swAktiv = !!(await js(`navigator.serviceWorker?.getRegistration().then((r) => !!r?.active) ?? false`, sessionId)); }
await warte(1500);
const ergebnis = { sichererKontext: await js('window.isSecureContext', sessionId), serviceWorkerAktiv: swAktiv, online: zaehl(ev) };

// 2 · offen gelassene Seite, Netz weg, Besucher klickt einen Kontaktweg und filtert (Ereignisse ohne Netz)
await send('Page.navigate', { url: `${BASIS}/de/restaurants/1` }, sessionId);
await warte(2500);
let start = ev.length;
await netz(false);
ergebnis.swGetrennt = !!swSitzung;
const geklickt = await js(`(() => { const a = document.querySelector('a[href^="tel:"]'); if (!a) return false; a.addEventListener('click', (e) => e.preventDefault(), { once: true }); a.click(); return true; })()`, sessionId);
await warte(2500);
ergebnis.offeneSeiteKontaktKlick = { geklickt, ...zaehl(ev.slice(start)), fehler: fehlerIn(ev.slice(start)) };

// 3 · offline zu einer neuen Seite: App-Hülle statt Seite, kein Zählaufruf
start = ev.length;
await send('Page.navigate', { url: `${BASIS}/de/restaurants/2` }, sessionId);
await warte(4000);
ergebnis.offlineNavigation = {
  titel: await js('document.title', sessionId),
  istOfflineHtml: await js(`!!document.querySelector('script[src^="/zaehler.js"]') === false && location.pathname === '/de/restaurants/2'`, sessionId),
  trackerAufSeite: await js(`!!document.querySelector('script[src^="/zaehler.js"]')`, sessionId),
  ...zaehl(ev.slice(start)),
  fehler: fehlerIn(ev.slice(start)),
};

// 4 · wieder online: gezählt wird wieder
await netz(true);
start = ev.length;
await send('Page.navigate', { url: `${BASIS}/de/restaurants/1` }, sessionId);
await warte(3000);
ergebnis.wiederOnline = zaehl(ev.slice(start));

console.log(JSON.stringify(ergebnis));
await send('Target.disposeBrowserContext', { browserContextId });
ws.close();
