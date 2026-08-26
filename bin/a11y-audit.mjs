#!/usr/bin/env node
/**
 * Barrierefreiheits-Prüflauf (Feature 02, AK-54).
 *
 * Fährt eine kuratierte Routenliste mit einem headless-Browser an, injiziert
 * axe-core (WCAG 2.2 AA) und meldet jede Regelverletzung. Exit-Code 1, sobald ein
 * Verstoß gefunden wird — dadurch färbt ein neu hinzugefügter, regelwidriger
 * Screen den Prüflauf rot.
 *
 * Deckt das maschinell prüfbare Drittel ab (Kontrast, Alt-Texte, lang, doppelte
 * Titel, ARIA-Fehler, Label-Verknüpfung). Tastaturweg und Screenreader-Ausgaben
 * prüft zusätzlich die manuelle Konformitätsmatrix (docs/barrierefreiheit-pruefung.md).
 *
 * Bewusst KEINE App-Abhängigkeit: playwright-core und axe-core werden global (oder
 * als devDependency) installiert, nicht in package.json des Projekts geführt.
 *
 * AUSFÜHRUNG:
 *   npm i -g playwright-core axe-core
 *   symfony server:start -d                    # App unter https://127.0.0.1:8000
 *   NODE_PATH=$(npm root -g) node bin/a11y-audit.mjs
 *
 * Umgebungsvariablen:
 *   A11Y_BASE_URL  Basis-URL der laufenden App (Default https://127.0.0.1:8000)
 *   BRAVE_PATH     Pfad zur Browser-Binary (Default: Brave auf macOS)
 */

import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const { chromium } = require('playwright-core');
const axe = require('axe-core');

const BASE = process.env.A11Y_BASE_URL ?? 'https://127.0.0.1:8000';
const LOCALE = '/de';
const BRAVE = process.env.BRAVE_PATH
    ?? '/Applications/Brave Browser.app/Contents/MacOS/Brave Browser';

// Je öffentlicher Seitentyp einmal, dazu die Offline-Seite. Admin-Routen bräuchten
// eine angemeldete Sitzung und bleiben der manuellen Prüfung vorbehalten.
const ROUTES = [
    `${LOCALE}/`,
    `${LOCALE}/restaurants`,
    `${LOCALE}/about`,
    `${LOCALE}/partner`,
    `${LOCALE}/organisationen`,
    `${LOCALE}/open`,
    `${LOCALE}/criteria`,
    `${LOCALE}/legal`,
    `${LOCALE}/accessibility`,
    `${LOCALE}/login`,
    `${LOCALE}/register`,
    '/offline.html',
];

const TAGS = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'];

const browser = await chromium.launch({ executablePath: BRAVE, headless: true });
const context = await browser.newContext({ ignoreHTTPSErrors: true });
const page = await context.newPage();

let total = 0;
for (const route of ROUTES) {
    const url = BASE + route;
    let response;
    try {
        response = await page.goto(url, { waitUntil: 'load', timeout: 20000 });
    } catch (err) {
        console.error(`✗ ${route} — konnte nicht geladen werden: ${err.message}`);
        total += 1;
        continue;
    }
    if (response && response.status() >= 400) {
        console.error(`✗ ${route} — HTTP ${response.status()}`);
        total += 1;
        continue;
    }

    await page.addScriptTag({ content: axe.source });
    const results = await page.evaluate(
        async (tags) => window.axe.run(
            // Symfony-Debug-Toolbar ausschließen: reines dev-Instrument, in prod fehlt sie.
            { exclude: [['.sf-toolbar'], ['#sfToolbarToggleButton']] },
            { runOnly: { type: 'tag', values: tags } },
        ),
        TAGS,
    );

    if (results.violations.length === 0) {
        console.log(`✓ ${route} — keine Verstöße`);
        continue;
    }
    for (const v of results.violations) {
        total += v.nodes.length;
        console.error(`✗ ${route} — ${v.id} (${v.impact}): ${v.help} [${v.nodes.length}×]`);
        const sample = v.nodes[0];
        if (sample) {
            console.error(`    z. B. ${sample.target.join(' ')}`);
        }
        console.error(`    ${v.helpUrl}`);
    }
}

await browser.close();

if (total > 0) {
    console.error(`\nBarrierefreiheits-Prüflauf fehlgeschlagen: ${total} Verstoß/Verstöße.`);
    process.exit(1);
}
console.log('\nBarrierefreiheits-Prüflauf bestanden: keine Verstöße.');
