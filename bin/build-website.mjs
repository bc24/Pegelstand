#!/usr/bin/env node
// Baut die Projekt-Website nach website/assets/: CSS, Schrift, Icons und Bilder.
// Voraussetzung: "npm run build" ist gelaufen (liefert assets/icons.svg). Aufruf: npm run build:website
import { build } from 'esbuild';
import { copyFile, mkdir, rm } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const wurzel = join(dirname(fileURLToPath(import.meta.url)), '..');
const pfad = (...teile) => join(wurzel, ...teile);
const ziel = pfad('website/assets');

const BILDER = [
  'dashboard-hell.png',
  'dashboard-dunkel.png',
  'dashboard-smartphone-hell.png',
  'dashboard-smartphone-dunkel.png',
  'dashboard-filter.png',
  'installer-pruefung.png',
  'einstellungen-website.png',
];

await rm(ziel, { recursive: true, force: true });
await mkdir(join(ziel, 'fonts'), { recursive: true });
await mkdir(join(ziel, 'bilder'), { recursive: true });
await build({
  entryPoints: { site: pfad('resources/css/website.css') },
  outdir: join(ziel, 'css'),
  bundle: true,
  minify: true,
  external: ['*.woff2'],
  target: ['chrome120', 'firefox120', 'safari17.5'],
  logLevel: 'warning',
});
await copyFile(pfad('node_modules/@fontsource-variable/inter/files/inter-latin-wght-normal.woff2'), join(ziel, 'fonts/inter-latin-wght-normal.woff2'));
await copyFile(pfad('assets/icons.svg'), join(ziel, 'icons.svg'));
for (const bild of BILDER) await copyFile(pfad('docs/bilder', bild), join(ziel, 'bilder', bild));
console.log('Website gebaut: website/assets');
