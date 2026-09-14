// Deploy-Nachprüfung B15 / BF-151 auf der Produktion, im Browser mit JavaScript (Turbo).
// Legt KEINEN Eintrag an: Pflichtfelder bleiben leer, die Anwendung antwortet mit 422 vor dem Kontingentverbrauch.
const CDP = 'http://127.0.0.1:9334';
const BASIS = 'https://endlech.lu';
const warte = (ms) => new Promise((r) => setTimeout(r, ms));
let bestanden = 0, gesamt = 0;
const melde = (was, ok, beleg) => { gesamt++; bestanden += ok ? 1 : 0; console.log(`${ok ? '✅' : '❌'} ${was} · ${beleg}`); };

const version = await (await fetch(`${CDP}/json/version`)).json();
const ws = new WebSocket(version.webSocketDebuggerUrl);
let id = 0; const offen = new Map(); const abo = new Map();
ws.onmessage = (m) => { const d = JSON.parse(m.data); if (d.id && offen.has(d.id)) { offen.get(d.id)(d); offen.delete(d.id); } else if (d.sessionId && abo.has(d.sessionId)) abo.get(d.sessionId)(d); };
await new Promise((r) => (ws.onopen = r));
const cdp = (method, params = {}, sessionId) => new Promise((r, f) => { const i = ++id; offen.set(i, (d) => (d.error ? f(new Error(`${method}: ${d.error.message}`)) : r(d.result))); ws.send(JSON.stringify({ id: i, method, params, sessionId })); });

async function seite() {
  const { browserContextId } = await cdp('Target.createBrowserContext');
  const { targetId } = await cdp('Target.createTarget', { url: 'about:blank', browserContextId });
  const { sessionId } = await cdp('Target.attachToTarget', { targetId, flatten: true });
  const s = { sessionId, browserContextId, posts: [], fehler: [], konsole: [] };
  abo.set(sessionId, (d) => {
    if (d.method === 'Network.requestWillBeSent' && d.params.redirectResponse) { const p = s.posts.find((x) => x.id === d.params.requestId); if (p) p.status = d.params.redirectResponse.status; }
    if (d.method === 'Network.requestWillBeSent' && d.params.request.method === 'POST' && new URL(d.params.request.url).host === 'endlech.lu' && !d.params.request.url.includes('/api/send')) s.posts.push({ id: d.params.requestId, pfad: new URL(d.params.request.url).pathname, status: null });
    if (d.method === 'Network.responseReceived') { const p = s.posts.find((x) => x.id === d.params.requestId); if (p && p.status === null) p.status = d.params.response.status; }
    if (d.method === 'Runtime.exceptionThrown') s.fehler.push(d.params.exceptionDetails.text);
    if (d.method === 'Runtime.consoleAPICalled' && d.params.type === 'error') s.konsole.push(JSON.stringify(d.params.args.map((a) => a.value)));
  });
  for (const m of ['Network.enable', 'Page.enable', 'Runtime.enable']) await cdp(m, {}, sessionId);
  return s;
}
const js = async (s, a) => (await cdp('Runtime.evaluate', { expression: a, awaitPromise: true, returnByValue: true }, s.sessionId)).result.value;

for (const [sprache, pfad] of [['de', '/gemeinden'], ['fr', '/unternehmen'], ['en', '/vereine'], ['lb', '/vereine']]) {
  const s = await seite();
  await cdp('Page.navigate', { url: `${BASIS}/${sprache}/organisationen${pfad}` }, s.sessionId);
  await warte(2500);
  const vorher = await js(s, `({ turbo: !!window.Turbo, action: document.querySelector('form[name="organisation_waitlist"]')?.getAttribute('action') })`);
  await js(s, `(() => { const f = document.querySelector('form[name="organisation_waitlist"]'); f.requestSubmit(); })()`);
  await warte(3000);
  const nachher = await js(s, `({ fehlerfelder: document.querySelectorAll('[aria-invalid="true"]').length, oops: /Oops|An Error Occurred/.test(document.body.innerText), url: location.pathname, formular: !!document.querySelector('form[name="organisation_waitlist"]') })`);
  const post = s.posts[0];
  const ok = vorher.turbo && post && post.status === 422 && post.pfad === `/${sprache}/organisationen` && nachher.fehlerfelder > 0 && !nachher.oops && nachher.formular && s.fehler.length === 0;
  melde(`${sprache}${pfad} mit JavaScript, Pflichtfelder leer`, ok,
    `Turbo ${vorher.turbo} · action ${vorher.action} · POST ${post?.pfad} → ${post?.status} · Fehlerfelder ${nachher.fehlerfelder} · Fehlerseite ${nachher.oops} · Adresse ${nachher.url} · JS-Ausnahmen ${s.fehler.length} · Konsolenfehler ${s.konsole.length}${s.konsole.length ? ' ' + s.konsole.join(' | ').slice(0, 200) : ''}`);
  await cdp('Target.disposeBrowserContext', { browserContextId: s.browserContextId });
}
console.log(`\n${bestanden} von ${gesamt} bestanden`);
ws.close();
process.exit(bestanden === gesamt ? 0 : 1);
