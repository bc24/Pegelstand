#!/usr/bin/env node
// Prüft das Größenbudget: Dashboard-JavaScript unter 100 KB gzip (1 KB = 1024 Byte).
// Aufruf nach "npm run build".
import { existsSync, readFileSync } from 'node:fs';
import { gzipSync } from 'node:zlib';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const wurzel = join(dirname(fileURLToPath(import.meta.url)), '..');
const GRENZE_JS = 100 * 1024;
const datei = join(wurzel, 'assets/js/dashboard.js');

if (!existsSync(datei)) {
  console.error('assets/js/dashboard.js fehlt. Bitte zuerst "npm run build" ausführen.');
  process.exit(1);
}

const groesse = gzipSync(readFileSync(datei), { level: 9 }).length;
console.log(`Dashboard-JavaScript: ${(groesse / 1024).toFixed(1)} KB gzip (Grenze: ${GRENZE_JS / 1024} KB)`);
if (groesse >= GRENZE_JS) {
  console.error('::error::Das Dashboard-JavaScript überschreitet das Größenbudget.');
  process.exit(1);
}
