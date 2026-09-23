// Kontrolní snímky návrhů přes Playwright (Chromium nebo WebKit).
// Použití:
//   node nastroje/snimek.mjs <soubor.html> <vystup.png> [--w=1440] [--h=900] [--full] [--hash=kronika]
//                            [--engine=chromium|webkit] [--mobile] [--motion] [--scroll=1200] [--dark]
// Vypíše chyby z konzole, chybějící soubory (404) a vodorovné přetečení stránky.
import { chromium, webkit, devices } from 'playwright';
import { pathToFileURL } from 'node:url';
import path from 'node:path';

const [, , soubor, vystup, ...volby] = process.argv;
if (!soubor || !vystup) {
  console.error('node nastroje/snimek.mjs <soubor.html> <vystup.png> [volby]');
  process.exit(2);
}
const opt = Object.fromEntries(volby.map(v => {
  const [k, val] = v.replace(/^--/, '').split('=');
  return [k, val === undefined ? true : val];
}));

const engine = opt.engine === 'webkit' ? webkit : chromium;
const browser = await engine.launch();
const kontext = opt.mobile
  ? await browser.newContext({ ...devices['iPhone 13'], reducedMotion: opt.motion ? 'no-preference' : 'reduce', colorScheme: opt.dark ? 'dark' : 'light' })
  : await browser.newContext({
      viewport: { width: +(opt.w || 1440), height: +(opt.h || 900) },
      deviceScaleFactor: +(opt.dpr || 1),
      reducedMotion: opt.motion ? 'no-preference' : 'reduce',
      colorScheme: opt.dark ? 'dark' : 'light',
    });
const page = await kontext.newPage();
const chyby = [];
page.on('console', m => { if (m.type() === 'error') chyby.push('console: ' + m.text()); });
page.on('pageerror', e => chyby.push('pageerror: ' + e.message));
page.on('requestfailed', r => chyby.push('requestfailed: ' + r.url()));
page.on('response', r => { if (r.status() >= 400) chyby.push(`HTTP ${r.status()}: ${r.url()}`); });

let url = pathToFileURL(path.resolve(soubor)).href;
if (opt.hash) url += '#' + opt.hash;
await page.goto(url, { waitUntil: 'networkidle', timeout: 45000 }).catch(e => chyby.push('goto: ' + e.message));
await page.evaluate(() => document.fonts && document.fonts.ready).catch(() => {});
// Reveal animace: odkrýt vše, co čeká na IntersectionObserver
await page.evaluate(() => {
  document.documentElement.classList.add('snimek');
  document.querySelectorAll('[data-reveal], .rv, .reveal').forEach(el => el.classList.add('in', 'is-in', 'visible', 'shown'));
});
if (opt.scroll) { await page.evaluate(y => window.scrollTo(0, +y), opt.scroll); }
await page.waitForTimeout(+(opt.wait || 900));

const mereni = await page.evaluate(() => ({
  scrollW: document.documentElement.scrollWidth,
  clientW: document.documentElement.clientWidth,
  vyska: document.documentElement.scrollHeight,
  siroke: [...document.querySelectorAll('body *')]
    .filter(el => { const r = el.getBoundingClientRect(); return r.right > document.documentElement.clientWidth + 1 && getComputedStyle(el).position !== 'fixed'; })
    .slice(0, 8).map(el => el.tagName.toLowerCase() + (el.className && typeof el.className === 'string' ? '.' + el.className.split(' ').join('.') : '')),
}));
await page.screenshot({ path: vystup, fullPage: !!opt.full });
await browser.close();

console.log(`snímek: ${vystup}  výška stránky: ${mereni.vyska}px`);
if (mereni.scrollW > mereni.clientW) console.log(`PŘETEČENÍ: scrollWidth ${mereni.scrollW} > ${mereni.clientW}; prvky: ${mereni.siroke.join(', ')}`);
if (chyby.length) console.log('CHYBY:\n  ' + [...new Set(chyby)].join('\n  '));
else console.log('bez chyb');
