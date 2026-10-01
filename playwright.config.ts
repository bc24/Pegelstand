import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { defineConfig } from '@playwright/test';

const executablePath = process.env.PEGELSTAND_CHROMIUM || undefined;

// Vier Varianten: Desktop und Smartphone (360 px), jeweils hell und dunkel.
// Jede Variante bekommt einen eigenen Server mit eigener Konfiguration, damit sich die Installer-Tests
// nicht gegenseitig beeinflussen. Vor dem Lauf baut "npm run build" die Assets (pretest:e2e).
const varianten = [
  { name: 'desktop-hell', port: 8091, viewport: { width: 1280, height: 800 }, colorScheme: 'light' as const },
  { name: 'desktop-dunkel', port: 8092, viewport: { width: 1280, height: 800 }, colorScheme: 'dark' as const },
  { name: 'smartphone-hell', port: 8093, viewport: { width: 360, height: 740 }, colorScheme: 'light' as const },
  { name: 'smartphone-dunkel', port: 8094, viewport: { width: 360, height: 740 }, colorScheme: 'dark' as const },
];

export const arbeitsverzeichnis = join(tmpdir(), 'pegelstand-e2e');

export default defineConfig({
  testDir: 'tests/e2e',
  outputDir: 'test-results',
  globalSetup: './tests/e2e/global-setup.ts',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  reporter: process.env.CI ? [['github'], ['html', { open: 'never' }]] : 'list',
  use: {
    locale: 'de-DE',
    timezoneId: 'Europe/Berlin',
    launchOptions: { executablePath },
  },
  projects: varianten.map(({ name, port, viewport, colorScheme }) => ({
    name,
    use: { viewport, colorScheme, baseURL: `http://127.0.0.1:${port}` },
  })),
  webServer: [...varianten.map(({ name, port }) => ({
    command: `php -S 127.0.0.1:${port} -t .`,
    url: `http://127.0.0.1:${port}/assets/icons.svg`,
    reuseExistingServer: false,
    stdout: 'ignore' as const,
    stderr: 'ignore' as const,
    env: {
      PEGELSTAND_CONFIG_DIR: join(arbeitsverzeichnis, name, 'config'),
      PEGELSTAND_STORAGE_DIR: join(arbeitsverzeichnis, name, 'storage'),
    },
  })), {
    // Fertig installierte Instanz mit Beispieldaten für die Dashboard-Tests (live.spec.ts), siehe bin/e2e-prepare.php.
    command: 'php -S 127.0.0.1:8095 -t .',
    url: 'http://127.0.0.1:8095/assets/icons.svg',
    reuseExistingServer: false,
    stdout: 'ignore' as const,
    stderr: 'ignore' as const,
    env: {
      PEGELSTAND_CONFIG_DIR: join(arbeitsverzeichnis, 'live', 'config'),
      PEGELSTAND_STORAGE_DIR: join(arbeitsverzeichnis, 'live', 'storage'),
    },
  }],
});
