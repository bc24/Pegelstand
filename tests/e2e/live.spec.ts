import { expect, type Page, test } from '@playwright/test';
import { axeVerstoesse, dbZugang, seitenUeberlauf } from './hilfen';

// Das Dashboard mit echten Daten: Anmeldung, Schnittstelle, Zeiträume, Filter. Die Instanz auf Port 8095 hat
// bin/e2e-prepare.php vorbereitet (eine Site mit 60 Tagen erfundener Daten, eine leere Site).
const LIVE = 'http://127.0.0.1:8095';
const MAIL = 'e2e@beispiel.de';
const PASSWORT = 'ein sehr langer satz';

test.skip(dbZugang() === null, 'PEGELSTAND_TEST_DB_DSN ist nicht gesetzt.');
test.describe.configure({ mode: 'parallel' });

async function anmelden(page: Page) {
  await page.goto(`${LIVE}/login`);
  await page.getByLabel('E-Mail-Adresse').fill(MAIL);
  await page.getByLabel('Passwort', { exact: true }).fill(PASSWORT);
  await page.getByRole('button', { name: 'Anmelden' }).click();
  await expect(page.locator('#inhalt[data-quelle="live"]')).toBeVisible();
}

async function geladen(page: Page) {
  await expect(page.locator('#ansicht-normal[aria-busy="false"]:not([hidden]), #ansicht-sonder:not([hidden])').first()).toBeVisible();
}

const bild = (page: Page, info: { project: { name: string } }, name: string) =>
  page.screenshot({ path: `screenshots/${info.project.name}/dashboard-live-${name}.png`, fullPage: true });

test.describe('Anmeldung', () => {
  test('ohne Anmeldung führt die Startseite zum Login, die Schnittstelle antwortet mit 401', async ({ page, request }) => {
    await page.goto(`${LIVE}/`);
    await expect(page).toHaveURL(/\/login$/);
    await expect(page.getByRole('heading', { level: 1, name: 'Anmelden' })).toBeVisible();
    expect(await axeVerstoesse(page)).toEqual([]);

    const antwort = await request.get(`${LIVE}/api/dashboard?site=livedemo00000001`);
    expect(antwort.status()).toBe(401);
    expect(await antwort.json()).toEqual({ fehler: 'nicht_angemeldet' });
  });

  test('falsche Zugangsdaten zeigen eine verständliche Meldung, richtige führen zum Dashboard, Abmelden sperrt wieder', async ({ page }) => {
    await page.goto(`${LIVE}/login`);
    await page.getByLabel('E-Mail-Adresse').fill(MAIL);
    await page.getByLabel('Passwort', { exact: true }).fill('falsches passwort');
    await page.getByRole('button', { name: 'Anmelden' }).click();
    await expect(page.getByRole('alert')).toContainText('E-Mail-Adresse oder Passwort stimmt nicht');
    await expect(page.getByLabel('E-Mail-Adresse')).toHaveValue(MAIL);

    await anmelden(page);
    await geladen(page);
    await page.getByRole('button', { name: /^Konto und Darstellung/ }).click();
    await page.getByRole('menuitem', { name: 'Abmelden' }).click();
    await expect(page).toHaveURL(/\/login$/);
    await page.goto(`${LIVE}/`);
    await expect(page).toHaveURL(/\/login$/);
  });
});

test.describe('Dashboard mit echten Daten', () => {
  test.beforeEach(async ({ page }) => {
    await anmelden(page);
  });

  test('zeigt Kennzahlen, Diagramm und Tabellen aus der Datenbank', async ({ page }, info) => {
    const fremd: string[] = [];
    const fehler: string[] = [];
    page.on('request', (a) => {
      const url = new URL(a.url());
      if (url.protocol.startsWith('http') && url.hostname !== '127.0.0.1') fremd.push(a.url());
    });
    page.on('pageerror', (e) => fehler.push(e.message));
    await page.reload();
    await geladen(page);

    await expect(page.locator('.ps-kpi--waehlbar')).toHaveCount(5);
    await expect(page.locator('#diagramm canvas').first()).toBeVisible();
    await expect(page.locator('[data-bereich="seiten.top"] tbody tr').first()).toBeVisible();
    await expect(page.locator('[data-bereich="laender"] tbody tr').first()).toContainText('Deutschland');
    await expect(page.locator('[data-bereich="geraete.geraete"] tbody tr').first()).toBeVisible();
    await expect(page.locator('#live-text')).toContainText('aktive');
    const besucher = await page.locator('.ps-kpi--waehlbar[data-kennzahl="besucher"] .ps-kpi__value').innerText();
    expect(besucher).toMatch(/\d/);
    expect(await seitenUeberlauf(page)).toBeLessThanOrEqual(0);
    expect(await axeVerstoesse(page)).toEqual([]);
    expect(fremd).toEqual([]);
    expect(fehler).toEqual([]);
    await bild(page, info, 'normal');
  });

  test('Zeiträume: Heute und Gestern zeigen Stunden, 7 Tage zeigt Tage', async ({ page }) => {
    await geladen(page);
    await page.keyboard.press('1');
    await expect(page.locator('#zeitraum-name')).toHaveText('Heute');
    await geladen(page);
    await page.getByRole('button', { name: 'Als Tabelle' }).click();
    await expect(page.locator('#diagramm-tabelle tbody tr').first()).toContainText('0 Uhr');
    await page.getByRole('button', { name: 'Als Diagramm' }).click();

    await page.keyboard.press('2');
    await expect(page.locator('#zeitraum-name')).toHaveText('Gestern');
    await geladen(page);
    await page.getByRole('button', { name: 'Als Tabelle' }).click();
    await expect(page.locator('#diagramm-tabelle tbody tr')).toHaveCount(24);
    await page.getByRole('button', { name: 'Als Diagramm' }).click();

    await page.keyboard.press('3');
    await expect(page.locator('#zeitraum-name')).toHaveText('7 Tage');
    await geladen(page);
    await page.getByRole('button', { name: 'Als Tabelle' }).click();
    await expect(page.locator('#diagramm-tabelle tbody tr')).toHaveCount(7);
  });

  test('Filter wirken auf Kennzahlen und Tabellen und lassen sich entfernen', async ({ page }, info) => {
    await geladen(page);
    // Der genaue Wert steht im Tooltip der Karte (die Anzeige ist gekürzt).
    const wert = async () => Number(((await page.locator('.ps-kpi--waehlbar[data-kennzahl="besucher"]').getAttribute('data-ps-tooltip')) ?? '').replace(/\D+/g, ''));
    const alle = await wert();
    expect(alle).toBeGreaterThan(0);

    await page.locator('[data-filter-typ="land"][data-filter-wert="DE"]').click();
    await expect(page.locator('.ps-chip')).toContainText('Land: Deutschland');
    await expect(page).toHaveURL(/f=land%3ADE/);
    await geladen(page);
    await expect(page.locator('[data-bereich="laender"] tbody tr')).toHaveCount(1);
    expect(await wert()).toBeLessThan(alle);
    expect(await wert()).toBeGreaterThan(0);

    await page.locator('[data-filter-typ="geraet"][data-filter-wert="smartphone"]').click();
    await expect(page.locator('.ps-chip')).toHaveCount(2);
    await geladen(page);
    await bild(page, info, 'gefiltert');

    await page.getByRole('button', { name: 'Alle Filter entfernen' }).click();
    await expect(page.locator('.ps-chip')).toHaveCount(0);
    await geladen(page);
    expect(await wert()).toBe(alle);
  });

  test('Seitenfilter und Ereignisfilter zeigen Eigenschaften', async ({ page }) => {
    await geladen(page);
    await page.getByRole('tab', { name: 'Ereignisse' }).click();
    const zeile = page.locator('[data-bereich="ereignisse"] tbody tr').first();
    await expect(zeile).toBeVisible();
    await zeile.getByRole('button').click();
    await geladen(page);
    await expect(page.locator('.db-eigenschaften')).toBeVisible();

    await page.getByRole('button', { name: 'Alle Filter entfernen' }).or(page.getByRole('button', { name: /^Filter Ereignis/ })).first().click();
    await page.locator('[data-bereich="seiten.top"] [data-filter-typ="seite"]').first().click();
    await geladen(page);
    await expect(page.locator('.ps-chip')).toContainText('Seite:');
  });

  test('Ziele zeigen Conversion-Rate und lassen sich als Filter setzen', async ({ page }) => {
    await geladen(page);
    const zeile = page.locator('[data-bereich="ziele"] tbody tr', { hasText: 'Preise angesehen' });
    await expect(zeile).toBeVisible();
    await expect(zeile).toContainText('%');
    await zeile.getByRole('button').click();
    await expect(page.locator('.ps-chip')).toContainText('Ziel: Preise angesehen');
    await geladen(page);
    await expect(page.locator('.ps-kpi--waehlbar').first()).toBeVisible();
  });

  test('CSV-Export lädt die Tabelle zur aktuellen Ansicht herunter', async ({ page }) => {
    await geladen(page);
    await page.getByRole('button', { name: /Exportieren/ }).click();
    const download = page.waitForEvent('download');
    await page.getByRole('menuitem', { name: 'Top-Seiten (CSV)' }).click();
    const datei = await download;
    expect(datei.suggestedFilename()).toMatch(/^pegelstand-.*-seiten-\d{4}-\d{2}-\d{2}-\d{4}-\d{2}-\d{2}\.csv$/);
  });

  test('benutzerdefinierter Zeitraum und Prüfung der Eingabe', async ({ page }) => {
    await geladen(page);
    await page.getByRole('button', { name: /Zeitraum/ }).click();
    await page.getByRole('menuitemradio', { name: /Benutzerdefiniert/ }).click();
    await page.locator('#bereich-von').fill('2000-01-01');
    await page.getByRole('button', { name: 'Übernehmen' }).click();
    await expect(page.locator('#bereich-fehler')).toContainText('Die Daten beginnen am');
  });

  test('Site-Wechsel zur leeren Site zeigt die Einrichtung mit echtem Tracking-Code', async ({ page }, info) => {
    await geladen(page);
    await page.getByRole('button', { name: /beispiel\.de/ }).first().click();
    await page.getByRole('menuitemradio', { name: 'neue-seite.de' }).click();
    await expect(page.getByRole('heading', { name: 'Noch keine Besucher bei neue-seite.de' })).toBeVisible();
    const code = page.locator('.db-code code');
    await expect(code).toContainText('data-site="liveleer00000002"');
    await expect(code).toContainText('/p.js');
    expect(await seitenUeberlauf(page)).toBeLessThanOrEqual(0);
    expect(await axeVerstoesse(page)).toEqual([]);
    await bild(page, info, 'leer');
  });

  test('unbekannte Site und manipulierte Filter führen nicht zu Fehlern', async ({ page, request }) => {
    const ok = await page.request.get(`${LIVE}/api/dashboard?site=livedemo00000001&f[]=seite:/gibt-es-nicht&f[]=unsinn&f[]=land:`);
    expect(ok.status()).toBe(200);
    expect((await ok.json()).keineDaten).toBe(true);
    expect((await page.request.get(`${LIVE}/api/dashboard?site=unbekannt`)).status()).toBe(404);
    expect((await page.request.get(`${LIVE}/api/dashboard?site=livedemo00000001&von=quatsch&bis=2099-01-01`)).status()).toBe(200);
    expect((await request.get(`${LIVE}/api/dashboard/live?site=livedemo00000001`)).status()).toBe(401);
  });
});
