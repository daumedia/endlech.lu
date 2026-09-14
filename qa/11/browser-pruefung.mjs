#!/usr/bin/env node
/**
 * QA Feature 11 · Nutzungsmessung im echten Browser — was den Browser verlässt und was Umami speichert.
 *
 * Absichtlich UNABHÄNGIG vom Anwendungscode: gesteuert wird Chromium über das DevTools-Protokoll
 * (kein npm-Paket), beobachtet werden die tatsächlichen Netzwerkanfragen an /api/send und die
 * Datenbank einer echten Umami-Instanz. Die Erwartungen stehen in features/11-nutzungsmessung/spec.md.
 *
 * Vorbedingungen (siehe qa/11/umgebung.md):
 *   - Chromium headless mit --remote-debugging-port und --host-resolver-rules="MAP endlech.lu 127.0.0.1, MAP www.endlech.lu 127.0.0.1"
 *   - Anwendung im Produktionsmodus auf Port 8765, Zähl-Eingang (TLS-Nachbau) und Umami 3.3.1 im Container qa11-umami
 *
 * Aufruf:  CDP=http://127.0.0.1:9333 node qa/11/browser-pruefung.mjs
 */
import { execSync } from 'node:child_process';

const CDP = process.env.CDP ?? 'http://127.0.0.1:9333';
const BASIS = 'http://endlech.lu:8765';
const WWW = 'http://www.endlech.lu:8765';
const TOKEN = 'ab'.repeat(32);
// ⚠ Umami verwirft Aufrufe mit „HeadlessChrome" in der Browserkennung als Bot (isbot). Beim ersten Lauf
// am 2026-09-13 kamen deshalb 34 Zählaufrufe am Eingang an und keiner in der Datenbank. Jede Seite
// meldet sich deshalb als gewöhnlicher Chrome — außer im Bot-Szenario (AK-10).
const BROWSER = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36';

const ergebnisse = [];
const melde = (ak, ok, beleg) => {
  ergebnisse.push({ ak, ok });
  console.log(`${ok ? '✅' : '❌'} ${ak.padEnd(6)} ${beleg}`);
};
const warte = (ms) => new Promise((r) => setTimeout(r, ms));
const umamiSql = (sql) => execSync(`docker exec qa11-db psql -U umami -tAF '|' -c ${JSON.stringify(sql)}`).toString().trim();
const appSql = (sql) => execSync(`docker exec mika-database-1 mysql -uroot -proot endlech_test -N -e ${JSON.stringify(sql)} 2>/dev/null`).toString().trim();

// ── DevTools-Protokoll ───────────────────────────────────────────────────────
const version = await (await fetch(`${CDP}/json/version`)).json();
const ws = new WebSocket(version.webSocketDebuggerUrl);
let naechsteId = 0;
const offen = new Map();
const abonnenten = new Map(); // sessionId → Handler
ws.onmessage = (m) => {
  const d = JSON.parse(m.data);
  if (d.id && offen.has(d.id)) {
    offen.get(d.id)(d);
    offen.delete(d.id);
  } else if (d.sessionId && abonnenten.has(d.sessionId)) {
    abonnenten.get(d.sessionId)(d);
  }
};
await new Promise((r) => (ws.onopen = r));
const cdp = (method, params = {}, sessionId) =>
  new Promise((r, f) => {
    const id = ++naechsteId;
    offen.set(id, (d) => (d.error ? f(new Error(`${method}: ${d.error.message}`)) : r(d.result)));
    ws.send(JSON.stringify({ id, method, params, sessionId }));
  });

async function kontext() {
  return (await cdp('Target.createBrowserContext', { disposeOnDetach: false })).browserContextId;
}

async function seite(browserContextId, { vorSkript, blockiert, userAgent = BROWSER } = {}) {
  const { targetId } = await cdp('Target.createTarget', { url: 'about:blank', browserContextId });
  const { sessionId } = await cdp('Target.attachToTarget', { targetId, flatten: true });
  const s = { sessionId, targetId, zaehl: [], hosts: new Set(), konsole: [], geladen: null };
  abonnenten.set(sessionId, (d) => {
    if (d.method === 'Network.requestWillBeSent') {
      const u = new URL(d.params.request.url);
      if (u.protocol.startsWith('http')) s.hosts.add(u.hostname);
      if (u.pathname === '/api/send') s.zaehl.push(JSON.parse(d.params.request.postData ?? '{}'));
    }
    if (d.method === 'Log.entryAdded') s.konsole.push(d.params.entry.text);
    if (d.method === 'Runtime.consoleAPICalled') s.konsole.push(d.params.args.map((a) => a.value).join(' '));
    if (d.method === 'Page.loadEventFired' && s.geladen) s.geladen();
  });
  await cdp('Network.enable', {}, sessionId);
  await cdp('Page.enable', {}, sessionId);
  await cdp('Runtime.enable', {}, sessionId);
  await cdp('Log.enable', {}, sessionId);
  if (vorSkript) await cdp('Page.addScriptToEvaluateOnNewDocument', { source: vorSkript }, sessionId);
  if (blockiert) await cdp('Network.setBlockedURLs', { urls: blockiert }, sessionId);
  if (userAgent) await cdp('Network.setUserAgentOverride', { userAgent }, sessionId);
  return s;
}

async function oeffne(s, url, { referrer, ruhe = 1200 } = {}) {
  const geladen = new Promise((r) => (s.geladen = r));
  await cdp('Page.navigate', { url, ...(referrer && { referrer }) }, s.sessionId);
  await Promise.race([geladen, warte(15000)]);
  await warte(ruhe);
}

async function js(s, ausdruck) {
  const r = await cdp('Runtime.evaluate', { expression: ausdruck, awaitPromise: true, returnByValue: true }, s.sessionId);
  if (r.exceptionDetails) throw new Error(`JS: ${r.exceptionDetails.text} ${r.exceptionDetails.exception?.description ?? ''}`);
  return r.result.value;
}

const pfadVon = (z) => new URL(z.payload.url, BASIS).pathname;
const ereignisse = (s, name) => s.zaehl.filter((z) => z.payload.name === name);
const seitenaufrufe = (s) => s.zaehl.filter((z) => !z.payload.name);

const beginn = umamiSql('select now()');
const website = process.env.APP_UMAMI_WEBSITE_ID ?? umamiSql("select website_id from website where domain='endlech.lu' limit 1");

// ── A · Seitenaufrufe, Herkunft, Suchtrichter ────────────────────────────────
{
  const k = await kontext();
  const s = await seite(k);

  // ⚠ Herkunft über http: Beim Wechsel https → http schickt der Browser gar keine Herkunft (Referrer-Richtlinie),
  // die lokale Prüfumgebung läuft aber über http. Die Kürzung eines vollständigen Herkunftspfads prüft
  // qa/11/angriff.sh mit einem selbst gebauten Zählaufruf.
  await oeffne(s, `${BASIS}/de/restaurants?city=Esch&wheelchair=1`, { referrer: 'http://www.google.com/search?q=barrierefrei+esch' });
  const erster = seitenaufrufe(s)[0];
  melde('AK-01', !!erster && pfadVon(erster) === '/de/restaurants', `Seitenaufruf aus dem Browser: ${erster ? erster.payload.url : 'keiner'}`);
  melde('AK-03', !!erster && !erster.payload.url.includes('?') && !JSON.stringify(s.zaehl).includes('Esch'),
    `Zählaufruf ohne Abfrage, „Esch" im Browser-Payload: ${JSON.stringify(s.zaehl).includes('Esch')}`);

  // Filter anwenden: Rollstuhl, Ort als Freitext
  const vorFilter = s.zaehl.length;
  await js(s, `(() => { const f = document.querySelector('form[data-controller="usage-event"]');
    f.querySelector('input[name="wheelchair"]').checked = true; f.querySelector('input[name="toilet"]').checked = true;
    f.querySelector('input[name="city"]').value = 'Esch-sur-Alzette'; f.requestSubmit(); })()`);
  await warte(3000);
  const filter = ereignisse(s, 'filter_angewandt');
  melde('AK-13', filter.length === 1 && filter[0].payload.data.filter.split(',').sort().join(',') === 'ort,toilet,wheelchair'
    && !JSON.stringify(filter).includes('Esch'), `Ereignis: ${JSON.stringify(filter.map((f) => f.payload.data))}`);
  melde('AK-11', s.zaehl.length > vorFilter, `Filter angewandt → ${s.zaehl.length - vorFilter} Zählaufrufe (Ereignis + Ergebnisseite)`);

  // Detailseite über Turbo, dann Kontaktwege
  await js(s, `Turbo.visit('/de/restaurants/1')`);
  await warte(2500);
  const detail = seitenaufrufe(s).filter((z) => pfadVon(z) === '/de/restaurants/1');
  melde('AK-36', detail.length === 1, `Detailseite per Turbo gezählt: ${detail.length} × /de/restaurants/1`);

  const kontaktVorher = ereignisse(s, 'kontaktweg_genutzt').length;
  // Jeder Kontaktweg der Seite einmal — Telefon und Bestellweg per Telefon sind beide `tel:`-Links.
  await js(s, `(() => { for (const a of document.querySelectorAll('a[data-usage-event-name-value="kontaktweg_genutzt"]')) {
      a.addEventListener('click', (e) => e.preventDefault(), { once: true }); a.click(); } })()`);
  await warte(2500);
  const kontakt = ereignisse(s, 'kontaktweg_genutzt').slice(kontaktVorher).map((z) => z.payload.data);
  const arten = new Set(kontakt.map((d) => d.art));
  const kontaktLinks = await js(s, `document.querySelectorAll('a[data-usage-event-name-value="kontaktweg_genutzt"]').length`);
  melde('AK-12', kontakt.length === kontaktLinks && ['telefon', 'email', 'website', 'bestellweg'].every((a) => arten.has(a)) && !/@|\d{3}|:\/\//.test(JSON.stringify(kontakt)),
    `${kontaktLinks} Kontaktwege geklickt → ${kontakt.length} Ereignisse: ${JSON.stringify(kontakt)}`);

  // Sprache wechseln
  await oeffne(s, `${BASIS}/fr/restaurants`);
  await js(s, `Turbo.visit('/fr/restaurants/1')`);
  await warte(2500);
  const pfade = seitenaufrufe(s).map(pfadVon);
  melde('AK-05', pfade.includes('/de/restaurants') && pfade.includes('/fr/restaurants'), `Pfade im Browser: ${pfade.join(', ')}`);

  melde('AK-24', [...s.hosts].every((h) => h === 'endlech.lu') && !s.konsole.some((t) => /Content Security Policy/i.test(t)),
    `Angefragte Hosts: ${[...s.hosts].join(', ')} · CSP-Meldungen in der Konsole: ${s.konsole.filter((t) => /Content Security Policy/i.test(t)).length}`);
}

// ── B · Turbo-Navigation nach Verwaltung, Profil, Token-Seite (AK-06, AK-07, AK-21) ──
{
  const k = await kontext();
  const s = await seite(k);
  await oeffne(s, `${BASIS}/de/login`);
  const anmeldungGezaehlt = seitenaufrufe(s).some((z) => pfadVon(z) === '/de/login');
  melde('AK-08', anmeldungGezaehlt, `Anmeldeseite gezählt: ${anmeldungGezaehlt}`);
  await js(s, `(() => { const u = document.querySelector('input[name="_username"]'); u.value = 'admin@endlech.lu';
    u.form.querySelector('input[name="_password"]').value = 'admin123'; u.form.requestSubmit(); })()`);
  await warte(4000);
  await oeffne(s, `${BASIS}/de/restaurants`);
  const angemeldet = seitenaufrufe(s).at(-1);
  melde('AK-21', !!angemeldet && !('id' in angemeldet.payload) && !JSON.stringify(angemeldet).includes('admin@'),
    `Zählaufruf als angemeldeter Admin: Felder ${angemeldet ? Object.keys(angemeldet.payload).join(',') : '—'}`);

  const vorher = s.zaehl.length;
  for (const ziel of ['/de/admin', '/de/admin/restaurants', '/de/profile', `/de/app/abmelden/${TOKEN}`]) {
    await js(s, `Turbo.visit(${JSON.stringify(ziel)})`);
    await warte(2500);
  }
  const verboten = s.zaehl.slice(vorher).filter((z) => /\/(admin|profile)(\/|$)|[a-f0-9]{64}/.test(pfadVon(z)));
  const aktuellerPfad = await js(s, 'location.pathname');
  melde('AK-06', verboten.filter((z) => /admin|profile/.test(pfadVon(z))).length === 0,
    `Turbo-Besuche Verwaltung/Profil, Zählaufrufe dorthin: ${verboten.length} (zuletzt auf ${aktuellerPfad.replace(/[a-f0-9]{64}/, '<token>')})`);
  melde('AK-07', verboten.filter((z) => /[a-f0-9]{64}/.test(pfadVon(z))).length === 0, 'Turbo-Besuch einer Token-Seite ohne Zählaufruf');

  await js(s, `Turbo.visit('/de/about')`);
  await warte(2500);
  const danach = s.zaehl.slice(vorher).map(pfadVon);
  melde('AK-06', danach.includes('/de/about'), `Tracker lebt danach weiter und zählt /de/about: ${danach.join(', ')}`);

  // Direkt aufgerufene Token-Seite trägt kein Skript
  const t = await seite(k);
  await oeffne(t, `${BASIS}/de/app/abmelden/${TOKEN}`);
  melde('AK-07', t.zaehl.length === 0 && !(await js(t, `!!document.querySelector('script[src^="/zaehler.js"]')`)), 'Direkt geöffnete Token-Seite: kein Skript, kein Zählaufruf');
}

// ── C/D · Global Privacy Control und Do Not Track (AK-22) ─────────────────────
for (const [name, vor] of [
  ['GPC', "Object.defineProperty(Navigator.prototype, 'globalPrivacyControl', { get: () => true });"],
  ['DNT', "Object.defineProperty(Navigator.prototype, 'doNotTrack', { get: () => '1' });"],
]) {
  const k = await kontext();
  const s = await seite(k, { vorSkript: vor });
  await oeffne(s, `${BASIS}/de/restaurants`);
  await js(s, `(() => { const f = document.querySelector('form[data-controller="usage-event"]'); f.querySelector('input[name="vegan"]').checked = true; f.requestSubmit(); })()`);
  await warte(2500);
  await js(s, `Turbo.visit('/de/restaurants/1')`);
  await warte(2000);
  await js(s, `(() => { const a = document.querySelector('a[href^="tel:"]'); a.addEventListener('click', (e) => e.preventDefault(), { once: true }); a.click(); })()`);
  await warte(1500);
  melde('AK-22', s.zaehl.length === 0, `${name}: Seitenaufrufe, Filter, Turbo-Besuch, Kontaktweg → ${s.zaehl.length} Zählaufrufe`);
}

// ── E · Widerspruchsschalter (AK-20, AK-23, EC-02) ────────────────────────────
{
  const k = await kontext();
  const s = await seite(k);
  await oeffne(s, `${BASIS}/de/legal`);
  const kekseVorher = (await cdp('Storage.getCookies', { browserContextId: k })).cookies.map((c) => c.name).sort();
  const zustand = () => js(s, `(() => { const b = document.querySelector('[data-usage-opt-out-target="button"]');
    return { sichtbar: !b.hidden, gedrueckt: b.getAttribute('aria-pressed'), wort: document.querySelector('[data-usage-opt-out-target="state"]').textContent.trim(), speicher: localStorage.getItem('umami.disabled') }; })()`);
  const anfang = await zustand();
  await js(s, `document.querySelector('[data-usage-opt-out-target="button"]').click()`);
  const aus = await zustand();
  await oeffne(s, `${BASIS}/de/legal`);
  const nachNeuladen = await zustand();
  const kekseNachher = (await cdp('Storage.getCookies', { browserContextId: k })).cookies.map((c) => c.name).sort();

  const tab = await seite(k);
  await oeffne(tab, `${BASIS}/de/restaurants`);
  await js(tab, `Turbo.visit('/de/restaurants/1')`);
  await warte(2000);
  const imAus = tab.zaehl.length;

  await js(s, `document.querySelector('[data-usage-opt-out-target="button"]').click()`);
  const wiederAn = await zustand();
  const tab2 = await seite(k);
  await oeffne(tab2, `${BASIS}/de/restaurants`);

  melde('AK-23', anfang.sichtbar && anfang.gedrueckt === 'true' && anfang.wort === 'an'
    && aus.gedrueckt === 'false' && aus.wort === 'aus' && aus.speicher === '1'
    && nachNeuladen.gedrueckt === 'false' && imAus === 0 && wiederAn.gedrueckt === 'true' && tab2.zaehl.length === 1,
    `an → aus (${JSON.stringify(aus)}) · nach Neuladen ${nachNeuladen.wort} · neuer Tab im Aus: ${imAus} Zählaufrufe · wieder an → neuer Tab: ${tab2.zaehl.length}`);
  melde('AK-20', JSON.stringify(kekseVorher) === JSON.stringify(kekseNachher) && !kekseNachher.some((n) => /umami|nutzung|usage/i.test(n)),
    `Cookies vor/nach dem Umschalten: [${kekseVorher.join(', ')}] / [${kekseNachher.join(', ')}]`);

  // Gesperrter Browserspeicher
  const g = await seite(await kontext(), { vorSkript: "Storage.prototype.setItem = function () { throw new DOMException('gesperrt', 'SecurityError'); };" });
  await oeffne(g, `${BASIS}/de/legal`);
  const gesperrt = await js(g, `({ knopf: !document.querySelector('[data-usage-opt-out-target="button"]').hidden, hinweis: !document.querySelector('[data-usage-opt-out-target="unavailable"]').hidden })`);
  melde('AK-23', !gesperrt.knopf && gesperrt.hinweis, `Browserspeicher gesperrt: Knopf sichtbar ${gesperrt.knopf}, Hinweis „nicht verfügbar" sichtbar ${gesperrt.hinweis}`);

  // Ohne JavaScript
  const o = await seite(await kontext());
  await cdp('Emulation.setScriptExecutionDisabled', { value: true }, o.sessionId);
  await oeffne(o, `${BASIS}/de/legal`);
  const ohneJs = await cdp('Runtime.evaluate', { expression: '1' }, o.sessionId).catch(() => null);
  melde('EC-02', o.zaehl.length === 0, `Ohne JavaScript: ${o.zaehl.length} Zählaufrufe${ohneJs ? '' : ''}`);
}

// ── F · Wartelisten (AK-15, AK-16, AK-17) ─────────────────────────────────────
{
  const s = await seite(await kontext());
  await oeffne(s, `${BASIS}/de/app`);
  await js(s, `(() => { const e = document.querySelector('[name="app_waitlist[email]"]'); e.value = 'keine-adresse';
    e.form.noValidate = true; e.form.requestSubmit(); })()`);
  await warte(3000);
  const nachFehler = ereignisse(s, 'warteliste_eingetragen').length;
  melde('AK-16', nachFehler === 0, `Fehlerhafte Absendung: ${nachFehler} × warteliste_eingetragen`);

  await oeffne(s, `${BASIS}/de/app`);
  await js(s, `(() => { const e = document.querySelector('[name="app_waitlist[email]"]'); e.value = 'qa11-messung@example.lu';
    const p = document.querySelector('[name="app_waitlist[platform]"][value="ios"]') ?? document.querySelector('[name="app_waitlist[platform]"]');
    if (p.tagName === 'SELECT') p.value = 'ios'; else p.checked = true;
    document.querySelector('[name="app_waitlist[consent]"]').checked = true; e.form.requestSubmit(); })()`);
  await warte(4000);
  const eintrag = ereignisse(s, 'warteliste_eingetragen');
  melde('AK-15', eintrag.length === 1 && eintrag[0].payload.data.liste === 'app', `Erfolgreiche Eintragung: ${JSON.stringify(eintrag.map((z) => z.payload.data))}`);
  melde('AK-17', !JSON.stringify(s.zaehl).includes('qa11-messung') && !JSON.stringify(s.zaehl).includes('@'), 'Keine Adresse in den Zählaufrufen der Warteliste');
}

// ── G · Engagement (AK-18, AK-19) ─────────────────────────────────────────────
{
  appSql("INSERT INTO board_idea (title, description, slug, status, locale, published_at, created_at, updated_at) VALUES ('QA11 Zustimmung', 'Probe der Nutzungsmessung', 'qa11-zustimmung', 'new', 'de', NOW(), NOW(), NOW())");
  const ideeId = appSql("SELECT id FROM board_idea WHERE slug='qa11-zustimmung'");
  const s = await seite(await kontext());
  await oeffne(s, `${BASIS}/de/login`);
  await js(s, `(() => { const u = document.querySelector('input[name="_username"]'); u.value = 'user@endlech.lu';
    u.form.querySelector('input[name="_password"]').value = 'user123'; u.form.requestSubmit(); })()`);
  await warte(4000);
  await oeffne(s, `${BASIS}/de/community/ideen/${ideeId}-qa11-zustimmung`);
  await js(s, `document.querySelector('form[action$="/zustimmen"]').requestSubmit()`);
  await warte(4000);
  const erste = ereignisse(s, 'zustimmung_gegeben').length;
  await js(s, `document.querySelector('form[action$="/zustimmen"]').requestSubmit()`);
  await warte(4000);
  const zweite = ereignisse(s, 'zustimmung_gegeben').length;
  melde('AK-18', erste === 1 && zweite === 1 && !('data' in ereignisse(s, 'zustimmung_gegeben')[0].payload),
    `Zustimmen → ${erste} Ereignis, Zurückziehen → weiterhin ${zweite}; ohne Daten`);

  await oeffne(s, `${BASIS}/de/presse`);
  await js(s, `(() => { const a = document.querySelector('a[download]'); a.addEventListener('click', (e) => e.preventDefault(), { once: true }); a.click(); })()`);
  await oeffne(s, `${BASIS}/de/open`);
  await js(s, `(() => { for (const a of document.querySelectorAll('a[data-usage-event-name-value="datensatz_geladen"]')) { a.addEventListener('click', (e) => e.preventDefault(), { once: true }); a.click(); } })()`);
  await warte(2500);
  melde('AK-19', ereignisse(s, 'presse_kit_geladen').length === 1
    && JSON.stringify(ereignisse(s, 'datensatz_geladen').map((z) => z.payload.data)) === JSON.stringify([{ format: 'csv' }, { format: 'json' }]),
    `Presse-Kit ${ereignisse(s, 'presse_kit_geladen').length} · Datensatz ${JSON.stringify(ereignisse(s, 'datensatz_geladen').map((z) => z.payload.data))}`);
}

// ── H · www.endlech.lu (AK-09) ────────────────────────────────────────────────
{
  const s = await seite(await kontext());
  await oeffne(s, `${WWW}/de/restaurants`);
  melde('AK-09', s.zaehl.length === 0, `Aufruf über www.endlech.lu: ${s.zaehl.length} Zählaufrufe`);
}

// ── I · Bot-Kennung (AK-10) ───────────────────────────────────────────────────
{
  const s = await seite(await kontext(), { userAgent: 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)' });
  // Ein Pfad, den kein anderes Szenario öffnet — sonst zählte ein gewöhnlicher Besuch mit.
  await oeffne(s, `${BASIS}/de/criteria`);
  await warte(1500);
  const gespeichert = umamiSql(`select count(*) from website_event where website_id = '${website}' and created_at >= '${beginn}' and url_path = '/de/criteria'`);
  melde('AK-10', s.zaehl.length >= 1 && gespeichert === '0', `Googlebot: ${s.zaehl.length} Zählaufruf(e) gesendet, in Umami gespeichert: ${gespeichert}`);
}

// ── Umami-Datenbank: Was tatsächlich gespeichert wurde ───────────────────────
await warte(2000);
const ereignisZeilen = umamiSql(`select url_path, coalesce(url_query,''), coalesce(referrer_domain,''), coalesce(referrer_path,''), coalesce(referrer_query,''), coalesce(event_name,'') from website_event where website_id='${website}' and created_at >= '${beginn}'`).split('\n').filter(Boolean);
const mitAbfrage = ereignisZeilen.filter((z) => z.split('|')[1] !== '');
melde('AK-03', ereignisZeilen.length > 0 && mitAbfrage.length === 0 && !ereignisZeilen.join().includes('Esch'),
  `Umami: ${ereignisZeilen.length} gespeicherte Aufrufe, davon mit Abfrage: ${mitAbfrage.length}, „Esch" gespeichert: ${ereignisZeilen.join().includes('Esch')}`);
const google = ereignisZeilen.filter((z) => z.split('|')[2].includes('google'));
melde('AK-02', google.length >= 1 && google.every((z) => ['', '/'].includes(z.split('|')[3]) && z.split('|')[4] === ''),
  `Umami, Herkunft Google: ${google.map((z) => z.split('|').slice(2, 5).join(' ')).join(' ; ') || 'keine'}`);
const verbotenGespeichert = ereignisZeilen.filter((z) => /\/(admin|profile)(\/|$)|[a-f0-9]{64}/.test(z.split('|')[0]));
melde('AK-06', verbotenGespeichert.length === 0, `Umami: Pfade aus Verwaltung/Profil/Token gespeichert: ${verbotenGespeichert.length}`);
const sprachen = umamiSql(`select count(*) filter (where url_path like '/de/restaurants%'), count(*) filter (where url_path like '/fr/restaurants%'), count(*) filter (where url_path like '/%/restaurants%') from website_event where website_id='${website}' and created_at >= '${beginn}' and event_name is null`);
melde('AK-05', sprachen.split('|').every((n) => Number(n) > 0), `Umami je Sprache /de, /fr und über alle (Muster /%/restaurants%): ${sprachen}`);
const daten = umamiSql(`select d.data_key || '=' || coalesce(d.string_value,'') from event_data d where d.website_id='${website}' and d.created_at >= '${beginn}'`).split('\n').filter(Boolean);
melde('AK-17', !daten.some((d) => /@|\d{3}|Esch|:\/\//.test(d)), `Umami-Ereignisdaten: ${[...new Set(daten)].join(', ')}`);
// Über die Aufrufe dieses Laufs, nicht über das Anlagedatum der Sitzung: Mit SALT_ROTATION=day ergeben dieselbe
// Adresse und Browserkennung am selben Tag dieselbe Sitzung wie in einem früheren Lauf.
const sitzungen = umamiSql(`select coalesce(country,'∅'), coalesce(region,'∅'), coalesce(city,'∅'), coalesce(distinct_id,'∅') from session where session_id in (select session_id from website_event where website_id='${website}' and created_at >= '${beginn}')`).split('\n').filter(Boolean);
melde('AK-21', sitzungen.length > 0 && sitzungen.every((z) => z.split('|')[3] === '∅'), `Umami-Sitzungen: ${sitzungen.length}, mit distinct_id: ${sitzungen.filter((z) => z.split('|')[3] !== '∅').length}`);
const ipSpalten = umamiSql("select count(*) from information_schema.columns where table_schema='public' and column_name ilike '%ip%' and table_name in ('session','website_event','event_data')");
melde('AK-31', ipSpalten === '0', `Umami-Tabellen session/website_event/event_data: Spalten mit „ip" im Namen: ${ipSpalten} (Aussage „IP nicht gespeichert")`);

// ── Aufräumen in der Testdatenbank der Anwendung ──────────────────────────────
appSql("DELETE FROM board_vote WHERE idea_id IN (SELECT id FROM board_idea WHERE slug='qa11-zustimmung')");
appSql("DELETE FROM board_idea WHERE slug='qa11-zustimmung'");
appSql("DELETE FROM app_waitlist_entry WHERE email='qa11-messung@example.lu'");
appSql('DELETE FROM messenger_messages');
console.log(`\nAufgeräumt: board_idea ${appSql('SELECT COUNT(*) FROM board_idea')}, board_vote ${appSql('SELECT COUNT(*) FROM board_vote')}, app_waitlist_entry ${appSql('SELECT COUNT(*) FROM app_waitlist_entry')}, messenger_messages ${appSql('SELECT COUNT(*) FROM messenger_messages')}`);

const bestanden = ergebnisse.filter((e) => e.ok).length;
console.log(`\n${bestanden} von ${ergebnisse.length} Prüfungen bestanden`);
ws.close();
process.exit(bestanden === ergebnisse.length ? 0 : 1);
