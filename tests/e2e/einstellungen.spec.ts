import { expect, type Page, test } from '@playwright/test';
import { axeVerstoesse, dbZugang, seitenUeberlauf } from './hilfen';

// Einstellungen gegen die vorbereitete Instanz auf Port 8095 (siehe bin/e2e-prepare.php).
const LIVE = 'http://127.0.0.1:8095';
test.skip(dbZugang() === null, 'PEGELSTAND_TEST_DB_DSN ist nicht gesetzt.');

const bild = (page: Page, info: { project: { name: string } }, name: string) =>
  page.screenshot({ path: `screenshots/${info.project.name}/einstellungen-${name}.png`, fullPage: true });

async function anmelden(page: Page) {
  await page.goto(`${LIVE}/login`);
  await page.getByLabel('E-Mail-Adresse').fill('e2e@beispiel.de');
  await page.getByLabel('Passwort', { exact: true }).fill('ein sehr langer satz');
  await page.getByRole('button', { name: 'Anmelden' }).click();
  await expect(page.locator('#inhalt[data-quelle="live"]')).toBeVisible();
}

async function pruefe(page: Page) {
  expect(await seitenUeberlauf(page)).toBeLessThanOrEqual(0);
  expect(await axeVerstoesse(page)).toEqual([]);
}

test('Websites: Liste, Anlegen mit Fehlern, Bearbeiten, Ausschluss, Löschen', async ({ page }, info) => {
  const name = `E2E ${info.project.name}`;
  const domain = `${info.project.name}.e2e.example`;
  await anmelden(page);
  await page.getByRole('button', { name: /^Konto und Darstellung/ }).click();
  await page.getByRole('menuitem', { name: 'Einstellungen' }).click();
  await expect(page.getByRole('heading', { level: 1, name: 'Websites' })).toBeVisible();
  await expect(page.getByRole('cell', { name: 'beispiel.de' }).first()).toBeVisible();
  await pruefe(page);
  await bild(page, info, 'websites');

  // Fehler zuerst
  await page.getByLabel('Name', { exact: true }).fill(name);
  await page.getByLabel('Domain', { exact: true }).fill('keine domain');
  await page.getByRole('button', { name: 'Website anlegen' }).click();
  await expect(page.getByRole('alert')).toContainText('Bitte prüfe deine Angaben');
  await expect(page.getByLabel('Domain', { exact: true })).toHaveAttribute('aria-invalid', 'true');
  await expect(page.getByLabel('Name', { exact: true })).toHaveValue(name);
  await pruefe(page);

  await page.getByLabel('Domain', { exact: true }).fill(domain);
  await page.getByRole('button', { name: 'Website anlegen' }).click();
  await expect(page.getByRole('heading', { level: 1, name })).toBeVisible();
  await expect(page.getByRole('status')).toContainText('wurde angelegt');
  await expect(page.locator('.es-code')).toContainText(`${domain.length ? 'data-site="' : ''}`);
  await pruefe(page);
  await bild(page, info, 'website');

  await page.getByLabel('IP-Adresse oder Bereich').fill('192.0.2.0/24');
  await page.getByLabel('Bezeichnung (optional)').fill('Büro');
  await page.getByRole('button', { name: 'Adresse ausschließen' }).click();
  await expect(page.getByRole('cell', { name: '192.0.2.0/24', exact: true })).toBeVisible();
  await page.getByRole('button', { name: /Entfernen/ }).click();
  await expect(page.getByRole('cell', { name: '192.0.2.0/24', exact: true })).toHaveCount(0);

  await page.getByLabel(/Tippe zur Bestätigung/).fill('falsch');
  await page.getByRole('button', { name: 'Website endgültig löschen' }).click();
  await expect(page.getByRole('alert')).toContainText('Die Domain stimmt nicht');
  await page.getByLabel(/Tippe zur Bestätigung/).fill(domain);
  await page.getByRole('button', { name: 'Website endgültig löschen' }).click();
  await expect(page.getByRole('status')).toContainText('wurde gelöscht');
  await expect(page.getByRole('cell', { name: domain, exact: true })).toHaveCount(0);
});

test('Benutzer und Konto', async ({ page }, info) => {
  const mail = `${info.project.name}@e2e.example`;
  await anmelden(page);
  await page.goto(`${LIVE}/einstellungen/benutzer`);
  await expect(page.getByRole('heading', { level: 1, name: 'Benutzer' })).toBeVisible();
  await pruefe(page);
  await bild(page, info, 'benutzer');

  await page.getByLabel('Name', { exact: true }).fill('Test Person');
  await page.getByLabel('E-Mail-Adresse').fill(mail);
  await page.getByLabel('Passwort', { exact: true }).fill('ein anderer langer satz');
  await page.getByLabel('Passwort wiederholen').fill('ein anderer langer satz');
  await page.getByRole('button', { name: 'Benutzer anlegen' }).click();
  await expect(page.getByRole('status')).toContainText('wurde angelegt');
  await page.getByRole('row', { name: new RegExp(mail) }).getByRole('link', { name: /Bearbeiten/ }).click();
  await expect(page.getByRole('heading', { level: 1, name: 'Test Person' })).toBeVisible();
  await pruefe(page);
  await page.getByRole('button', { name: 'Benutzer löschen' }).click();
  await expect(page.getByRole('status')).toContainText('gelöscht');

  await page.goto(`${LIVE}/einstellungen/konto`);
  await expect(page.getByRole('heading', { level: 1, name: 'Mein Konto' })).toBeVisible();
  await pruefe(page);
  await bild(page, info, 'konto');
});
