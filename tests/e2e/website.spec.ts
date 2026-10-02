import { createServer, type Server } from 'node:http';
import { readFile } from 'node:fs/promises';
import { extname, join } from 'node:path';
import { expect, test } from '@playwright/test';
import { axeVerstoesse, seitenUeberlauf } from './hilfen';

// Die Projekt-Website ist statisch. Der Test liefert den Ordner website/ über einen kleinen Server aus
// (Voraussetzung: npm run build:website; Icons per <use> laden nur über http).
const TYPEN: Record<string, string> = { '.html': 'text/html', '.css': 'text/css', '.svg': 'image/svg+xml', '.png': 'image/png', '.woff2': 'font/woff2' };
let server: Server;
let basis = '';
test.beforeAll(async () => {
  server = createServer(async (anfrage, antwort) => {
    const pfad = new URL(anfrage.url ?? '/', 'http://x').pathname.replace(/\/$/, '/index.html');
    try {
      const inhalt = await readFile(join(process.cwd(), 'website', pfad));
      antwort.writeHead(200, { 'content-type': TYPEN[extname(pfad)] ?? 'application/octet-stream' }).end(inhalt);
    } catch {
      antwort.writeHead(404).end();
    }
  });
  await new Promise<void>((ok) => server.listen(0, '127.0.0.1', ok));
  basis = `http://127.0.0.1:${(server.address() as { port: number }).port}`;
});
test.afterAll(() => server.close());
const seite = (name: string) => `${basis}/${name}`;

test.describe('Projekt-Website', () => {
  test('Startseite: Inhalt, Bilder, keine Fremdanfragen, barrierefrei', async ({ page }, info) => {
    const fremd: string[] = [];
    const fehlgeschlagen: string[] = [];
    page.on('request', (a) => {
      if (!a.url().startsWith(basis)) fremd.push(a.url());
    });
    page.on('requestfailed', (a) => fehlgeschlagen.push(a.url()));
    await page.goto(seite('index.html'));
    await expect(page.getByRole('heading', { level: 1 })).toContainText('Webanalyse ohne Cookies');
    await expect(page.getByRole('link', { name: 'Auf GitHub ansehen' })).toHaveAttribute('href', 'https://github.com/bc24/Pegelstand');
    // Bilder weiter unten laden erst beim Scrollen.
    for (const bild of await page.locator('img').all()) {
      await bild.scrollIntoViewIfNeeded();
      await expect.poll(() => bild.evaluate((e: HTMLImageElement) => e.complete && e.naturalWidth > 0)).toBe(true);
    }
    for (const abschnitt of ['funktionen', 'datenschutz', 'installation', 'stand']) {
      await expect(page.locator(`#${abschnitt}`)).toBeVisible();
    }
    expect(await seitenUeberlauf(page)).toBeLessThanOrEqual(0);
    expect(await axeVerstoesse(page)).toEqual([]);
    expect(fremd).toEqual([]);
    expect(fehlgeschlagen).toEqual([]);
    await page.screenshot({ path: `screenshots/${info.project.name}/website-start.png`, fullPage: true });
  });

  for (const name of ['datenschutz.html', 'impressum.html']) {
    test(`${name}: lesbar und barrierefrei`, async ({ page }) => {
      await page.goto(seite(name));
      await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
      expect(await seitenUeberlauf(page)).toBeLessThanOrEqual(0);
      expect(await axeVerstoesse(page)).toEqual([]);
    });
  }

  test('Die Seiten enthalten kein JavaScript und keine Inline-Skripte', async ({ page }) => {
    for (const name of ['index.html', 'datenschutz.html', 'impressum.html']) {
      await page.goto(seite(name));
      expect(await page.locator('script').count(), name).toBe(0);
    }
  });
});
