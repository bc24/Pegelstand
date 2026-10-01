#!/usr/bin/env node
// Baut CSS, JavaScript, Schrift und Icon-Sprite nach assets/.
// Nur für Entwickler und CI. Endnutzer erhalten die gebauten Dateien im Release-Zip.
import { build } from 'esbuild';
import { copyFile, mkdir, readFile, readdir, rm, writeFile } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const wurzel = join(dirname(fileURLToPath(import.meta.url)), '..');
const pfad = (...teile) => join(wurzel, ...teile);

// Verwendete Icons (Lucide). Neue Icons hier ergänzen.
const ICONS = [
  'layout-dashboard', 'chart-line', 'users', 'eye', 'clock', 'arrow-up', 'arrow-down', 'arrow-right', 'arrow-left',
  'arrow-up-right', 'minus', 'search', 'x', 'check', 'chevron-down', 'chevron-right', 'chevron-up',
  'calendar', 'settings', 'sun', 'moon', 'monitor', 'globe', 'link', 'external-link', 'funnel',
  'download', 'copy', 'command', 'info', 'triangle-alert', 'circle-alert', 'circle-check', 'circle-x',
  'plus', 'trash-2', 'pencil', 'ellipsis', 'menu', 'log-out', 'user', 'shield', 'smartphone', 'tablet',
  'laptop', 'mouse-pointer-click', 'flag', 'zap', 'refresh-cw', 'inbox', 'keyboard',
];

async function leeren() {
  for (const ziel of ['css', 'js', 'fonts', 'icons.svg', 'p.js']) {
    await rm(pfad('assets', ziel), { recursive: true, force: true });
  }
}

async function bundles() {
  const gemein = { bundle: true, minify: true, sourcemap: false, logLevel: 'warning' };
  await build({
    ...gemein,
    entryPoints: {
      pegelstand: pfad('resources/css/main.css'),
      dashboard: pfad('resources/css/dashboard.css'),
      seiten: pfad('resources/css/seiten.css'),
      komponentenseite: pfad('resources/css/komponentenseite.css'),
    },
    outdir: pfad('assets/css'),
    external: ['*.woff2'],
    target: ['chrome120', 'firefox120', 'safari17.5'],
  });
  await build({
    ...gemein,
    entryPoints: {
      pegelstand: pfad('resources/js/main.js'),
      'theme-init': pfad('resources/js/theme-init.js'),
      dashboard: pfad('resources/js/dashboard/dashboard.js'),
      seiten: pfad('resources/js/seiten.js'),
      komponentenseite: pfad('resources/js/komponentenseite.js'),
    },
    outdir: pfad('assets/js'),
    format: 'esm',
    target: 'es2022',
  });
}

// Das Tracking-Script läuft auf fremden Seiten: eine einzelne, kleine Datei ohne Module (Grenze: 2 KB gzip).
async function tracker() {
  await build({
    entryPoints: [pfad('resources/js/tracker/p.js')],
    outfile: pfad('assets/p.js'),
    bundle: true,
    minify: true,
    format: 'iife',
    target: 'es2018',
    logLevel: 'warning',
  });
}

async function schrift() {
  const quelle = pfad('node_modules/@fontsource-variable/inter/files/inter-latin-wght-normal.woff2');
  await mkdir(pfad('assets/fonts'), { recursive: true });
  await copyFile(quelle, pfad('assets/fonts/inter-latin-wght-normal.woff2'));
}

async function iconSprite() {
  const vorhanden = new Set((await readdir(pfad('node_modules/lucide-static/icons'))).map((d) => d.replace(/\.svg$/, '')));
  const symbole = [];
  for (const name of ICONS) {
    if (!vorhanden.has(name)) throw new Error(`Icon "${name}" existiert in lucide-static nicht.`);
    const svg = await readFile(pfad('node_modules/lucide-static/icons', `${name}.svg`), 'utf8');
    const innen = svg
      .replace(/<!--[\s\S]*?-->/g, '')
      .replace(/^[\s\S]*?<svg[^>]*>/, '')
      .replace(/<\/svg>\s*$/, '')
      .replace(/\s*\n\s*/g, '');
    symbole.push(`<symbol id="ps-icon-${name}" viewBox="0 0 24 24">${innen}</symbol>`);
  }
  const sprite = `<svg xmlns="http://www.w3.org/2000/svg" style="display:none">${symbole.join('')}</svg>\n`;
  await writeFile(pfad('assets/icons.svg'), sprite);
}

await leeren();
await Promise.all([bundles(), tracker(), schrift(), iconSprite()]);
console.log('Assets gebaut: assets/css, assets/js, assets/fonts, assets/icons.svg');
