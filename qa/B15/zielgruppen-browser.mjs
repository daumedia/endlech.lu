#!/usr/bin/env node
/**
 * QA B15 · Nachprüfung BF-151 im echten Browser — eintragen von Übersicht und Zielgruppenseiten.
 *
 * Gesteuert wird Chromium über das DevTools-Protokoll (kein npm-Paket); beobachtet werden die tatsächliche Antwort
 * auf das Absenden, der sichtbare Zustand, die Zeile in `organisation_waitlist_entry` und die Nachricht in
 * `messenger_messages` (Bestätigungsmail, AK-09). Vor jeder Eintragung wird der Deckel geleert, damit 16 Eintragungen
 * nicht am Limit von fünf je Stunde scheitern — außer im Deckel-Fall selbst.
 *
 * Vorbedingungen: Anwendung aus dem zu prüfenden Stand im Produktionsmodus auf BASIS, Test-Datenbank im Container
 * `mika-database-1`, Chromium headless mit `--remote-debugging-port`. `APP_DIR` zeigt auf das Arbeitsverzeichnis der
 * Anwendung, `ENV_FILE` auf die Umgebungsdatei (APP_ENV=prod, APP_SECRET, DATABASE_URL) — für `cache:pool:clear`.
 *
 * Aufruf: BASIS=http://127.0.0.1:8766 APP_DIR=… ENV_FILE=… CDP=http://127.0.0.1:9333 node qa/B15/zielgruppen-browser.mjs
 */
import { execSync } from 'node:child_process';

const CDP = process.env.CDP ?? 'http://127.0.0.1:9333';
const BASIS = process.env.BASIS ?? 'http://127.0.0.1:8766';
const APP_DIR = process.env.APP_DIR;
const ENV_FILE = process.env.ENV_FILE;
const UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36';
const warte = (ms) => new Promise((r) => setTimeout(r, ms));
const sql = (q) => execSync(`docker exec mika-database-1 mysql -uroot -proot endlech_test -N -e ${JSON.stringify(q)} 2>/dev/null`).toString().trim();
const deckelLeeren = () => execSync(`bash -c 'source ${ENV_FILE} && cd ${APP_DIR} && php bin/console cache:pool:clear cache.rate_limiter --env=prod >/dev/null 2>&1'`);
let bestanden = 0;
let gesamt = 0;
const melde = (was, ok, beleg) => { gesamt++; bestanden += ok ? 1 : 0; console.log(`${ok ? '✅' : '❌'} ${was} · ${beleg}`); };

const version = await (await fetch(`${CDP}/json/version`)).json();
const ws = new WebSocket(version.webSocketDebuggerUrl);
let id = 0;
const offen = new Map();
const abo = new Map();
ws.onmessage = (m) => { const d = JSON.parse(m.data); if (d.id && offen.has(d.id)) { offen.get(d.id)(d); offen.delete(d.id); } else if (d.sessionId && abo.has(d.sessionId)) abo.get(d.sessionId)(d); };
await new Promise((r) => (ws.onopen = r));
const cdp = (method, params = {}, sessionId) => new Promise((r, f) => { const i = ++id; offen.set(i, (d) => (d.error ? f(new Error(`${method}: ${d.error.message}`)) : r(d.result))); ws.send(JSON.stringify({ id: i, method, params, sessionId })); });

async function seite({ ohneJs = false } = {}) {
  const { browserContextId } = await cdp('Target.createBrowserContext');
  const { targetId } = await cdp('Target.createTarget', { url: 'about:blank', browserContextId });
  const { sessionId } = await cdp('Target.attachToTarget', { targetId, flatten: true });
  const s = { sessionId, browserContextId, posts: [], fehler: [] };
  abo.set(sessionId, (d) => {
    // ⚠ Eine Weiterleitung meldet Chromium nicht als `responseReceived`, sondern als `redirectResponse` am FOLGENDEN
    // `requestWillBeSent` derselben Anfrage-Kennung. Ohne diesen Zweig stand im ersten Lauf 200 statt 302 da.
    if (d.method === 'Network.requestWillBeSent' && d.params.redirectResponse) { const p = s.posts.find((x) => x.id === d.params.requestId); if (p) p.status = d.params.redirectResponse.status; }
    if (d.method === 'Network.requestWillBeSent' && d.params.request.method === 'POST') s.posts.push({ id: d.params.requestId, pfad: new URL(d.params.request.url).pathname, status: null });
    if (d.method === 'Network.responseReceived') { const p = s.posts.find((x) => x.id === d.params.requestId); if (p && p.status === null) p.status = d.params.response.status; }
    if (d.method === 'Runtime.exceptionThrown') s.fehler.push(d.params.exceptionDetails.text);
  });
  for (const m of ['Network.enable', 'Page.enable', 'Runtime.enable']) await cdp(m, {}, sessionId);
  await cdp('Network.setUserAgentOverride', { userAgent: UA }, sessionId);
  if (ohneJs) await cdp('Emulation.setScriptExecutionDisabled', { value: true }, sessionId);
  return s;
}
const js = async (s, a) => (await cdp('Runtime.evaluate', { expression: a, awaitPromise: true, returnByValue: true }, s.sessionId)).result.value;
const oeffne = async (s, pfad) => { await cdp('Page.navigate', { url: BASIS + pfad }, s.sessionId); await warte(2000); };
const schliesse = (s) => cdp('Target.disposeBrowserContext', { browserContextId: s.browserContextId });

/** Füllt Pflichtfelder (optional: Typ wählen, Feld leeren, Honeypot füllen) und schickt ab — mit oder ohne Submit-Ereignis. */
const absenden = (s, { email, typ = null, name = 'QA B15 Organisation', honeypot = '', nativ = false }) => js(s, `(() => {
  const setze = (n, w) => { const e = document.querySelector('[name="' + n + '"]'); if (!e) throw new Error('fehlt: ' + n); e.value = w; };
  ${typ ? `const r = document.querySelector('[name="organisation_waitlist[type]"][value="${typ}"]'); r.checked = true; r.dispatchEvent(new Event('change', { bubbles: true }));` : ''}
  setze('organisation_waitlist[organisationName]', ${JSON.stringify(name)});
  setze('organisation_waitlist[contactName]', 'QA Kontakt');
  setze('organisation_waitlist[email]', ${JSON.stringify(email)});
  document.querySelector('[name="organisation_waitlist[consent]"]').checked = true;
  if (${JSON.stringify(honeypot)}) setze('organisation_waitlist[companyWebsite]', ${JSON.stringify(honeypot)});
  const form = document.querySelector('[name="organisation_waitlist[email]"]').form;
  ${nativ ? 'form.submit();' : 'form.requestSubmit();'}
  return form.getAttribute('action');
})()`);
const zeile = (email) => sql(`SELECT CONCAT(type, '|', locale) FROM organisation_waitlist_entry WHERE email='${email}'`);
const nachrichten = () => Number(sql('SELECT COUNT(*) FROM messenger_messages'));
const mailVorlage = (typ) => Number(sql(`SELECT COUNT(*) FROM messenger_messages WHERE body LIKE '%organisation/${typ}.html.twig%'`));

// ── 1 · Mit JavaScript: Übersicht und drei Zielgruppenseiten in vier Sprachen ─────────────────────────────────────
const SEITEN = { '': 'commune', '/gemeinden': 'commune', '/unternehmen': 'company', '/vereine': 'association' };
console.log('── 1 · Eintragen mit JavaScript, 4 Sprachen × 4 Seiten (AK-03, AK-09, BF-151)');
for (const sprache of ['de', 'en', 'fr', 'lb']) {
  for (const [seiteSlug, typ] of Object.entries(SEITEN)) {
    deckelLeeren();
    const pfad = `/${sprache}/organisationen${seiteSlug}`;
    const email = `qa-b15-${sprache}${seiteSlug.replace('/', '-') || '-uebersicht'}@example.lu`;
    const s = await seite();
    await oeffne(s, pfad);
    const vorgewaehlt = await js(s, `document.querySelector('[name="organisation_waitlist[type]"]:checked')?.value ?? null`);
    const mailsVorher = nachrichten();
    const vorlagenVorher = mailVorlage(typ);
    const action = await absenden(s, { email, typ: seiteSlug === '' ? typ : null });
    await warte(3500);
    const post = s.posts.find((p) => p.pfad.endsWith('/organisationen'));
    const erfolg = await js(s, `!!document.querySelector('#organisation-waitlist-form[role="status"]')`);
    const adresse = await js(s, 'location.pathname');
    const gespeichert = zeile(email);
    const ok = post?.status === 200 && erfolg && gespeichert === `${typ}|${sprache}` && nachrichten() === mailsVorher + 1
      && mailVorlage(typ) === vorlagenVorher + 1 && s.fehler.length === 0 && (seiteSlug === '' || vorgewaehlt === typ);
    melde(`${pfad}`, ok, `action ${action} · POST ${post?.pfad} → ${post?.status} · Erfolgsmeldung ${erfolg} · Adresse danach ${adresse} · Zeile ${gespeichert || '—'} · Mail +${nachrichten() - mailsVorher} (Vorlage ${typ} +${mailVorlage(typ) - vorlagenVorher}) · vorgewählt ${vorgewaehlt} · JS-Fehler ${s.fehler.length}`);
    await schliesse(s);
  }
}

// ── 2 · Ohne JavaScript: Zielgruppenseiten schicken als gewöhnliches Formular ─────────────────────────────────────
console.log('── 2 · Eintragen ohne JavaScript (AK-05, BF-151)');
for (const [slug, typ] of [['gemeinden', 'commune'], ['unternehmen', 'company'], ['vereine', 'association']]) {
  deckelLeeren();
  const email = `qa-b15-nojs-${slug}@example.lu`;
  const s = await seite({ ohneJs: true });
  await oeffne(s, `/de/organisationen/${slug}`);
  const gruppen = await js(s, `['communeName','sponsorshipInterests][]','collaborationInterests][]'].map((f) => !!document.querySelector('[name^="organisation_waitlist[' + f + '"]')).join(',')`);
  await absenden(s, { email, nativ: true });
  await warte(3500);
  const post = s.posts.find((p) => p.pfad.endsWith('/organisationen'));
  const adresse = await js(s, 'location.pathname');
  const flash = await js(s, `(document.body.innerText.match(/Fast geschafft![^\\n]*/) ?? [''])[0]`);
  melde(`/de/organisationen/${slug} ohne JS`, post?.status === 302 && adresse === '/de/organisationen' && zeile(email) === `${typ}|de` && flash.startsWith('Fast geschafft!') && gruppen === 'true,true,true',
    `POST ${post?.pfad} → ${post?.status} · Adresse danach ${adresse} · Zeile ${zeile(email) || '—'} · alle drei Feldgruppen im Formular ${gruppen} · Hinweis „${flash.trim()}"`);
  await schliesse(s);
}

// ── 3 · Falsch gelandet: Typ auf der Zielgruppenseite wechseln ────────────────────────────────────────────────────
console.log('── 3 · Typwechsel auf der Zielgruppenseite (AK-03, AK-06)');
{
  deckelLeeren();
  const email = 'qa-b15-wechsel@example.lu';
  const s = await seite();
  await oeffne(s, '/fr/organisationen/gemeinden');
  await absenden(s, { email, typ: 'association' });
  await warte(3500);
  const post = s.posts.find((p) => p.pfad.endsWith('/organisationen'));
  melde('/fr/organisationen/gemeinden → Verein gewählt', post?.status === 200 && zeile(email) === 'association|fr' && mailVorlage('association') > 0,
    `POST ${post?.pfad} → ${post?.status} · Zeile ${zeile(email) || '—'}`);
  await schliesse(s);
}

// ── 4 · Eingabefehler auf der Zielgruppenseite (OF-BF151a) ────────────────────────────────────────────────────────
console.log('── 4 · Eingabefehler auf der Zielgruppenseite (AK-08, OF-BF151a)');
{
  deckelLeeren();
  const email = 'qa-b15-fehler@example.lu';
  const s = await seite();
  await oeffne(s, '/de/organisationen/vereine');
  const titelVorher = await js(s, `document.querySelector('h1')?.innerText`);
  const mailsVorher = nachrichten();
  await absenden(s, { email, name: '' });
  await warte(3500);
  const post = s.posts.find((p) => p.pfad.endsWith('/organisationen'));
  const titel = await js(s, `document.querySelector('h1')?.innerText`);
  const adresse = await js(s, 'location.pathname');
  const fehler = await js(s, `[...document.querySelectorAll('[id$="_error"]')].map((e) => e.innerText.trim()).filter(Boolean).length`);
  const typ = await js(s, `document.querySelector('[name="organisation_waitlist[type]"]:checked')?.value`);
  const fokus = await js(s, `document.activeElement?.name ?? document.activeElement?.tagName`);
  melde('/de/organisationen/vereine mit leerem Organisationsnamen', post?.status === 422 && !zeile(email) && nachrichten() === mailsVorher && fehler > 0 && typ === 'association',
    `POST → ${post?.status} · Fehlermeldungen ${fehler} · Typ bleibt ${typ} · Fokus ${fokus} · Adresse ${adresse} · Überschrift vorher „${titelVorher}" → nachher „${titel}" · keine Zeile, keine Mail`);
  await schliesse(s);
}

// ── 5 · Honeypot von der Zielgruppenseite ─────────────────────────────────────────────────────────────────────────
console.log('── 5 · Honeypot von der Zielgruppenseite (AK-11)');
{
  deckelLeeren();
  const email = 'qa-b15-bot@example.lu';
  const s = await seite();
  await oeffne(s, '/de/organisationen/unternehmen');
  const mailsVorher = nachrichten();
  await absenden(s, { email, honeypot: 'https://bot.example' });
  await warte(3500);
  const post = s.posts.find((p) => p.pfad.endsWith('/organisationen'));
  const erfolg = await js(s, `!!document.querySelector('#organisation-waitlist-form[role="status"]')`);
  melde('/de/organisationen/unternehmen mit gefülltem Honeypot', post?.status === 200 && erfolg && !zeile(email) && nachrichten() === mailsVorher,
    `POST → ${post?.status} · Erfolgsmeldung ${erfolg} · Zeile ${zeile(email) || '—'} · Mail +${nachrichten() - mailsVorher}`);
  await schliesse(s);
}

// ── 6 · Deckel greift auch von der Zielgruppenseite ───────────────────────────────────────────────────────────────
console.log('── 6 · Deckel von der Zielgruppenseite (Missbrauchsschutz)');
{
  deckelLeeren();
  const codes = [];
  for (let i = 1; i <= 6; i++) {
    const s = await seite();
    await oeffne(s, '/de/organisationen/gemeinden');
    await absenden(s, { email: `qa-b15-deckel-${i}@example.lu` });
    await warte(3000);
    codes.push(s.posts.find((p) => p.pfad.endsWith('/organisationen'))?.status);
    if (i === 6) {
      const meldung = await js(s, `(document.body.innerText.match(/.{0,60}(Verbindung|mehrere Anmeldungen).{0,60}/) ?? [''])[0]`);
      const gespeichert = sql("SELECT COUNT(*) FROM organisation_waitlist_entry WHERE email LIKE 'qa-b15-deckel-%'");
      melde('6 Eintragungen von /de/organisationen/gemeinden', codes.slice(0, 5).every((c) => c === 200) && codes[5] === 429 && gespeichert === '5',
        `Antworten ${JSON.stringify(codes)} · gespeichert ${gespeichert} · Meldung „${meldung.trim()}"`);
    }
    await schliesse(s);
  }
}

// ── Aufräumen ─────────────────────────────────────────────────────────────────────────────────────────────────────
deckelLeeren();
sql("DELETE FROM organisation_waitlist_entry WHERE email LIKE 'qa-b15-%@example.lu'");
sql('DELETE FROM messenger_messages');
console.log(`\nAufgeräumt: Zeilen ${sql("SELECT COUNT(*) FROM organisation_waitlist_entry WHERE email LIKE 'qa-b15-%'")}, Nachrichten ${sql('SELECT COUNT(*) FROM messenger_messages')}`);
console.log(`${bestanden} von ${gesamt} Prüfungen bestanden`);
ws.close();
