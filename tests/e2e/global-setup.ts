import { execFileSync } from 'node:child_process';
import { mkdirSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const VARIANTEN = ['desktop-hell', 'desktop-dunkel', 'smartphone-hell', 'smartphone-dunkel'];

// Frischer Zustand für jeden Lauf: leere Konfigurationsordner und keine Tabellen früherer Läufe.
export default function globalSetup() {
  const wurzel = join(tmpdir(), 'pegelstand-e2e');
  rmSync(wurzel, { recursive: true, force: true });
  for (const name of VARIANTEN) {
    mkdirSync(join(wurzel, name, 'config'), { recursive: true });
    mkdirSync(join(wurzel, name, 'storage'), { recursive: true });
  }
  execFileSync('php', ['bin/e2e-prepare.php'], { stdio: 'inherit' });
}
