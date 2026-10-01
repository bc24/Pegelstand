import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { expect, type Page, test } from '@playwright/test';

// Das Tracking-Script läuft hier auf einer Fantasie-Website ("site.test"), der Pegelstand-Server ("pegel.test")
// wird per page.route nachgebildet. Geprüft wird, was das Script sendet und wann es schweigt.
const SCRIPT = readFileSync(join(process.cwd(), 'assets/p.js'), 'utf8');

type Messpunkt = { s: string; u: string; r: string; n?: string; p?: Record<string, string> };

async function vorbereiten(page: Page, attribute = '', seite = '<h1>Hallo</h1><a id="extern" href="https://andere.example/x">extern</a><a id="datei" href="/dateien/preise.pdf">pdf</a>') {
  const gesendet: Messpunkt[] = [];
  await page.route('https://pegel.test/p.js', (route) =>
    route.fulfill({ contentType: 'text/javascript', body: SCRIPT }));
  await page.route('https://pegel.test/api/event', async (route) => {
    gesendet.push(JSON.parse(route.request().postData() ?? '{}'));
    await route.fulfill({ status: 202, headers: { 'access-control-allow-origin': '*' }, body: '' });
  });
  await page.route('https://site.test/**', (route) =>
    route.fulfill({
      contentType: 'text/html',
      body: `<!doctype html><html><head><title>Test</title><script defer src="https://pegel.test/p.js" data-site="abcd1234abcd1234" ${attribute}></script></head><body>${seite}</body></html>`,
    }));
  return gesendet;
}

const ECHTER_BROWSER = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';

// Playwright steuert den Browser per WebDriver, das Script schweigt dann mit Absicht (siehe letzter Test).
// Für die übrigen Tests tarnen wir die Automatisierung.
test.describe('Tracking-Script', () => {
  test.use({ userAgent: ECHTER_BROWSER });

  test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => Object.defineProperty(navigator, 'webdriver', { get: () => false, configurable: true }));
  });

  test('sendet einen Seitenaufruf mit Adresse und Referrer, ohne Cookies', async ({ page, context }) => {
    const gesendet = await vorbereiten(page);
    await page.goto('https://site.test/preise?x=1', { referer: 'https://www.google.com/' });
    await expect.poll(() => gesendet.length).toBe(1);

    expect(gesendet[0]).toMatchObject({ s: 'abcd1234abcd1234', u: 'https://site.test/preise?x=1' });
    expect(gesendet[0].n).toBeUndefined();
    expect(await context.cookies()).toEqual([]);
    expect(await page.evaluate(() => window.localStorage.length)).toBe(0);
  });

  test('zählt Navigation per History-API, aber keine doppelten Aufrufe', async ({ page }) => {
    const gesendet = await vorbereiten(page);
    await page.goto('https://site.test/');
    await expect.poll(() => gesendet.length).toBe(1);

    await page.evaluate(() => history.pushState({}, '', '/zweite'));
    await expect.poll(() => gesendet.length).toBe(2);
    await page.evaluate(() => history.pushState({}, '', '/zweite'));
    await page.waitForTimeout(200);
    expect(gesendet).toHaveLength(2);
    expect(gesendet[1].u).toBe('https://site.test/zweite');
    expect(gesendet[1].r).toBe('https://site.test/');

    await page.goBack(); // landet auf dem doppelten Eintrag derselben Adresse: kein weiterer Aufruf
    await page.goBack();
    await expect.poll(() => gesendet.length).toBe(3);
    expect(gesendet[2].u).toBe('https://site.test/');
  });

  test('Hash-Routing nur mit data-hash', async ({ page }) => {
    const ohne = await vorbereiten(page);
    await page.goto('https://site.test/app');
    await expect.poll(() => ohne.length).toBe(1);
    await page.evaluate(() => (location.hash = '#/konto'));
    await page.waitForTimeout(200);
    expect(ohne).toHaveLength(1);

    const page2 = await page.context().newPage();
    await page2.addInitScript(() => Object.defineProperty(navigator, 'webdriver', { get: () => false, configurable: true }));
    const mit = await vorbereiten(page2, 'data-hash');
    await page2.goto('https://site.test/app');
    await expect.poll(() => mit.length).toBe(1);
    await page2.evaluate(() => (location.hash = '#/konto'));
    await expect.poll(() => mit.length).toBe(2);
    expect(mit[1].u).toBe('https://site.test/app#/konto');
  });

  test('eigene Ereignisse mit Eigenschaften', async ({ page }) => {
    const gesendet = await vorbereiten(page);
    await page.goto('https://site.test/');
    await expect.poll(() => gesendet.length).toBe(1);

    await page.evaluate(() => (window as unknown as { pegelstand: (n: string, o?: object) => void }).pegelstand('Signup', { props: { plan: 'pro' } }));
    await expect.poll(() => gesendet.length).toBe(2);
    expect(gesendet[1]).toMatchObject({ n: 'Signup', p: { plan: 'pro' }, r: '' });
  });

  test('Ausgehende Links und Downloads nur mit data-outbound', async ({ page }) => {
    const gesendet = await vorbereiten(page, 'data-outbound');
    await page.route('https://andere.example/**', (route) => route.fulfill({ contentType: 'text/html', body: 'x' }));
    await page.goto('https://site.test/');
    await expect.poll(() => gesendet.length).toBe(1);

    await page.locator('#datei').evaluate((a) => a.addEventListener('click', (e) => e.preventDefault()));
    await page.locator('#datei').click();
    await expect.poll(() => gesendet.length).toBe(2);
    expect(gesendet[1]).toMatchObject({ n: 'File Download', p: { url: '/dateien/preise.pdf' } });

    await page.locator('#extern').click();
    await expect.poll(() => gesendet.length).toBe(3);
    expect(gesendet[2]).toMatchObject({ n: 'Outbound Link', p: { url: 'https://andere.example/x' } });
  });

  test('schweigt bei Opt-out per localStorage', async ({ page }) => {
    const gesendet = await vorbereiten(page);
    await page.addInitScript(() => window.localStorage.setItem('pegelstand_ignore', 'true'));
    await page.goto('https://site.test/');
    await page.waitForTimeout(300);

    expect(gesendet).toHaveLength(0);
  });

  test('schweigt auf localhost, außer mit data-local', async ({ page }) => {
    const gesendet: string[] = [];
    await page.route('https://pegel.test/**', async (route) => {
      if (route.request().url().endsWith('p.js')) return route.fulfill({ contentType: 'text/javascript', body: SCRIPT });
      gesendet.push(route.request().url());
      return route.fulfill({ status: 202, headers: { 'access-control-allow-origin': '*' }, body: '' });
    });
    const seite = (attribute: string) => `<!doctype html><script defer src="https://pegel.test/p.js" data-site="abcd1234abcd1234" ${attribute}></script>`;
    await page.route('http://localhost:9999/**', (route) => route.fulfill({ contentType: 'text/html', body: seite('') }));
    await page.goto('http://localhost:9999/');
    await page.waitForTimeout(300);
    expect(gesendet).toHaveLength(0);

    await page.route('http://localhost:9998/**', (route) => route.fulfill({ contentType: 'text/html', body: seite('data-local') }));
    await page.goto('http://localhost:9998/');
    await expect.poll(() => gesendet.length).toBe(1);
  });

  test('ohne data-site passiert nichts und es gibt keine Fehler', async ({ page }) => {
    const fehler: string[] = [];
    page.on('pageerror', (e) => fehler.push(e.message));
    await page.route('https://pegel.test/p.js', (route) => route.fulfill({ contentType: 'text/javascript', body: SCRIPT }));
    await page.route('https://site.test/**', (route) =>
      route.fulfill({ contentType: 'text/html', body: '<!doctype html><script defer src="https://pegel.test/p.js"></script>' }));
    await page.goto('https://site.test/');
    await page.waitForTimeout(200);

    expect(fehler).toEqual([]);
  });

  test('ein fehlschlagender Server stört die Seite nicht', async ({ page }) => {
    const fehler: string[] = [];
    page.on('pageerror', (e) => fehler.push(e.message));
    await page.route('https://pegel.test/p.js', (route) => route.fulfill({ contentType: 'text/javascript', body: SCRIPT }));
    await page.route('https://pegel.test/api/event', (route) => route.abort());
    await page.route('https://site.test/**', (route) =>
      route.fulfill({ contentType: 'text/html', body: '<!doctype html><script defer src="https://pegel.test/p.js" data-site="abcd1234abcd1234"></script><h1>ok</h1>' }));
    await page.goto('https://site.test/');
    await page.waitForTimeout(200);

    await expect(page.locator('h1')).toHaveText('ok');
    expect(fehler).toEqual([]);
  });

  test('schweigt in automatisierten Browsern (webdriver)', async ({ page }) => {
    const gesendet = await vorbereiten(page);
    await page.addInitScript(() => Object.defineProperty(navigator, 'webdriver', { get: () => true, configurable: true }));
    await page.goto('https://site.test/');
    await page.waitForTimeout(300);

    expect(gesendet).toHaveLength(0);
  });
});
