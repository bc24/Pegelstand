import { defineConfig } from '@playwright/test';

const port = 8080;
const executablePath = process.env.PEGELSTAND_CHROMIUM || undefined;

// Vier Varianten: Desktop und Smartphone (360 px), jeweils hell und dunkel.
const varianten = [
  { name: 'desktop-hell', viewport: { width: 1280, height: 800 }, colorScheme: 'light' as const },
  { name: 'desktop-dunkel', viewport: { width: 1280, height: 800 }, colorScheme: 'dark' as const },
  { name: 'smartphone-hell', viewport: { width: 360, height: 740 }, colorScheme: 'light' as const },
  { name: 'smartphone-dunkel', viewport: { width: 360, height: 740 }, colorScheme: 'dark' as const },
];

export default defineConfig({
  testDir: 'tests/e2e',
  outputDir: 'test-results',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  reporter: process.env.CI ? [['github'], ['html', { open: 'never' }]] : 'list',
  use: {
    baseURL: `http://127.0.0.1:${port}`,
    locale: 'de-DE',
    timezoneId: 'Europe/Berlin',
    launchOptions: { executablePath },
  },
  projects: varianten.map(({ name, viewport, colorScheme }) => ({
    name,
    use: { viewport, colorScheme },
  })),
  webServer: {
    command: `php -S 127.0.0.1:${port} -t .`,
    url: `http://127.0.0.1:${port}/`,
    reuseExistingServer: !process.env.CI,
    stdout: 'ignore',
    stderr: 'ignore',
  },
});
