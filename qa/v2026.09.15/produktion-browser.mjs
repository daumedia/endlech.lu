// Aufruf: node qa/v2026.09.15/produktion-browser.mjs  (braucht playwright-core, z. B. global installiert)
// Zählskript und Zählweg werden blockiert, damit der Prüflauf nicht in Umami erscheint.
import { chromium } from 'playwright-core';
const browser = await chromium.launch();
const faelle = [
  ['/pt/', 1280], ['/pt/', 390], ['/de/community/suggest', 1280], ['/de/community/suggest', 390],
  ['/fr/', 1024], ['/de/about', 1440], ['/lb/restaurants', 390],
];
let ok = 0;
for (const [pfad, width] of faelle) {
  const ctx = await browser.newContext({ viewport: { width, height: 900 } });
  await ctx.addCookies([{ name: 'cookie_consent', value: 'declined', url: 'https://endlech.lu' }]);
  await ctx.route(/zaehler\.js|\/api\/send/, r => r.abort());
  const page = await ctx.newPage();
  const fehler = [];
  page.on('pageerror', e => fehler.push('JS: ' + e.message));
  page.on('console', m => { if (m.type() === 'error' && !/zaehler|api\/send|ERR_FAILED/.test(m.text())) fehler.push('Konsole: ' + m.text()); });
  const r = await page.goto('https://endlech.lu' + pfad, { waitUntil: 'networkidle' });
  const info = await page.evaluate(() => {
    const sections = [...document.querySelectorAll('main section')].map(s => s.getBoundingClientRect().top);
    const knopf = [...document.querySelectorAll('header a')].find(a => a.getAttribute('href')?.endsWith('/register'));
    return {
      lang: document.documentElement.lang,
      scroll: document.documentElement.scrollWidth - document.documentElement.clientWidth,
      knopf: knopf ? knopf.textContent.trim() + (knopf.getBoundingClientRect().height > 50 ? ' (UMBRUCH)' : '') : '-',
      footerGruppen: [...document.querySelectorAll('footer h2')].map(h => h.textContent.trim()).join(', '),
      zweiSpalten: sections.length === 2 ? Math.abs(sections[0] - sections[1]) < 2 : null,
      css: getComputedStyle(document.body).fontFamily.includes('Inter'),
    };
  });
  const gut = r.status() === 200 && info.scroll === 0 && info.css && fehler.length === 0 && !info.knopf.includes('UMBRUCH');
  if (gut) ok++;
  console.log(`${gut ? '✅' : '❌'} ${pfad} @${width} · ${r.status()} · lang=${info.lang} · Scroll ${info.scroll} · Knopf „${info.knopf}" · zwei Spalten ${info.zweiSpalten} · Fehler ${fehler.length}${fehler.length ? ' ' + fehler.join(' | ') : ''}`);
  if (pfad === '/de/about' ) console.log('   Fußzeile: ' + info.footerGruppen);
  await ctx.close();
}
console.log(`\n${ok} von ${faelle.length} bestanden`);
await browser.close();
