import { expect, type Page, test } from '@playwright/test';
import { axeVerstoesse, dbZugang, praefix, seitenUeberlauf } from './hilfen';

// Die Tests laufen nacheinander durch den Installer. Jede Darstellungsvariante hat ihren eigenen Server.
test.describe.configure({ mode: 'serial' });

const zugang = dbZugang();
const bild = (page: Page, info: { project: { name: string } }, name: string) =>
  page.screenshot({ path: `screenshots/${info.project.name}/installer-${name}.png`, fullPage: true });

async function zuSchrittZwei(page: Page) {
  await page.goto('/install');
  await page.getByRole('button', { name: /Weiter zur Datenbank/ }).click();
  await expect(page.getByRole('heading', { level: 1, name: 'Datenbank verbinden' })).toBeVisible();
}

async function datenbankAusfuellen(page: Page, info: Parameters<typeof praefix>[0], ueberschreibe: Partial<Record<string, string>> = {}) {
  const z = zugang!;
  await page.getByLabel('Server').fill(ueberschreibe.host ?? z.host);
  await page.getByLabel('Port').fill(ueberschreibe.port ?? z.port);
  await page.getByLabel('Datenbankname').fill(ueberschreibe.name ?? z.name);
  await page.getByLabel('Benutzername').fill(ueberschreibe.user ?? z.user);
  await page.getByLabel('Passwort', { exact: true }).fill(ueberschreibe.password ?? z.password);
  await page.getByLabel('Tabellenpräfix').fill(ueberschreibe.prefix ?? praefix(info));
}

async function zuSchrittDrei(page: Page, info: Parameters<typeof praefix>[0]) {
  await zuSchrittZwei(page);
  await datenbankAusfuellen(page, info);
  await page.getByRole('button', { name: /Verbindung testen und weiter/ }).click();
  await expect(page.getByRole('heading', { level: 1, name: 'Administrator anlegen' })).toBeVisible();
}

test('Ohne Konfiguration führt jede Adresse zum Installer', async ({ page }) => {
  await page.goto('/');
  await expect(page).toHaveURL(/\/install$/);
  await page.goto('/irgendeine/seite');
  await expect(page).toHaveURL(/\/install$/);
});

test('Schritt 1: Systemprüfung ist vollständig, verständlich und barrierefrei', async ({ page }, info) => {
  const fremd: string[] = [];
  const fehler: string[] = [];
  page.on('request', (a) => {
    const url = new URL(a.url());
    if (url.protocol.startsWith('http') && url.hostname !== '127.0.0.1') fremd.push(a.url());
  });
  page.on('pageerror', (e) => fehler.push(e.message));

  await page.goto('/install');
  await expect(page.getByRole('heading', { level: 1, name: 'Willkommen bei Pegelstand' })).toBeVisible();
  await expect(page.locator('.in-punkt')).toHaveCount(10);
  await expect(page.locator('.in-punkt--ok')).not.toHaveCount(0);
  await expect(page.getByRole('navigation', { name: 'Fortschritt der Installation' }).locator('[aria-current="step"]')).toContainText('Prüfung');
  // Der Entwicklungsserver sperrt keine Ordner, deshalb meldet die Prüfung im Browser einen Hinweis mit Lösungsweg.
  const zugriff = page.locator('[data-zugriffscheck]');
  await expect(zugriff).toHaveClass(/in-punkt--warn/);
  await expect(zugriff).toContainText('von außen erreichbar');
  await expect(zugriff).toContainText('AllowOverride');
  expect(await axeVerstoesse(page)).toEqual([]);
  expect(await seitenUeberlauf(page)).toBeLessThanOrEqual(0);
  await bild(page, info, '1-pruefung');
  expect(fremd).toEqual([]);
  expect(fehler).toEqual([]);
});

test('Schritt 1: gesperrte Ordner werden als erfüllt gemeldet', async ({ page }) => {
  for (const muster of ['**/config/config.example.php', '**/src/Version.php', '**/storage/.htaccess']) {
    await page.route(muster, (route) => route.fulfill({ status: 403, body: 'gesperrt' }));
  }
  await page.goto('/install');
  const zugriff = page.locator('[data-zugriffscheck]');
  await expect(zugriff).toHaveClass(/in-punkt--ok/);
  await expect(zugriff).toContainText('aus dem Internet nicht abrufbar');
  await expect(zugriff.locator('[data-hinweis]')).toBeHidden();
  expect(await axeVerstoesse(page)).toEqual([]);
});

test('Spätere Schritte sind ohne die früheren nicht erreichbar', async ({ page }) => {
  await page.goto('/install/datenbank');
  await expect(page).toHaveURL(/\/install$/);
  await page.goto('/install/administrator');
  await expect(page).toHaveURL(/\/install$/);
});

test('Schritt 2: leere Eingaben zeigen Fehler mit Lösungsweg und Fokus', async ({ page }, info) => {
  await zuSchrittZwei(page);
  await page.getByLabel('Server').fill('');
  await page.getByLabel('Port').fill('abc');
  await page.getByRole('button', { name: /Verbindung testen und weiter/ }).click();

  const zusammenfassung = page.getByRole('alert').filter({ hasText: 'Bitte prüfe deine Angaben' });
  await expect(zusammenfassung).toBeVisible();
  await expect(zusammenfassung).toBeFocused();
  await expect(zusammenfassung).toContainText('Der Port muss eine Zahl zwischen 1 und 65535 sein');
  await expect(zusammenfassung).toContainText('Der Datenbankname darf nur');
  await expect(page.getByLabel('Port')).toHaveAttribute('aria-invalid', 'true');
  expect(await axeVerstoesse(page)).toEqual([]);
  expect(await seitenUeberlauf(page)).toBeLessThanOrEqual(0);
  await bild(page, info, '2b-datenbank-fehler');

  // Der Link in der Zusammenfassung springt zum Feld.
  await zusammenfassung.getByRole('link', { name: /Der Port muss/ }).click();
  await expect(page.getByLabel('Port')).toBeFocused();
});

test('Schritt 2: Passwort lässt sich anzeigen und verbergen', async ({ page }, info) => {
  await zuSchrittZwei(page);
  const feld = page.getByLabel('Passwort', { exact: true });
  const knopf = page.getByRole('button', { name: 'Anzeigen' });
  await feld.fill('geheim');
  await expect(feld).toHaveAttribute('type', 'password');
  await knopf.click();
  await expect(feld).toHaveAttribute('type', 'text');
  await expect(page.getByRole('button', { name: 'Verbergen' })).toHaveAttribute('aria-pressed', 'true');
  await page.getByRole('button', { name: 'Verbergen' }).click();
  await expect(feld).toHaveAttribute('type', 'password');
  expect(await axeVerstoesse(page)).toEqual([]);
  await bild(page, info, '2-datenbank');
});

test.describe('mit Datenbank', () => {
  test.skip(zugang === null, 'PEGELSTAND_TEST_DB_DSN ist nicht gesetzt.');

  test('Schritt 2: falsches Passwort wird verständlich erklärt', async ({ page }, info) => {
    await zuSchrittZwei(page);
    await datenbankAusfuellen(page, info, { password: 'falsches-passwort-xyz' });
    await page.getByRole('button', { name: /Verbindung testen und weiter/ }).click();

    const hinweis = page.getByRole('alert').filter({ hasText: 'Das hat nicht geklappt' });
    await expect(hinweis).toContainText('Benutzername oder Passwort stimmen nicht');
    await expect(page.getByLabel('Passwort', { exact: true })).toHaveValue('');
    await expect(page.getByText('Aus Sicherheitsgründen gibst du das Passwort bei jedem Versuch neu ein.')).toBeVisible();
    expect(await axeVerstoesse(page)).toEqual([]);
  });

  test('Schritt 2: unerreichbarer Server wird verständlich erklärt', async ({ page }, info) => {
    await zuSchrittZwei(page);
    await datenbankAusfuellen(page, info, { port: '1' });
    await page.getByRole('button', { name: /Verbindung testen und weiter/ }).click();

    await expect(page.getByRole('alert').filter({ hasText: 'Das hat nicht geklappt' })).toContainText('Datenbankserver antwortet nicht');
  });

  test('Schritt 3: Eingabefehler werden an den Feldern erklärt', async ({ page }, info) => {
    await zuSchrittDrei(page, info);
    await page.getByLabel('Name', { exact: true }).fill('Frank Panzer');
    await page.getByLabel('E-Mail-Adresse').fill('frank@beispiel');
    await page.getByLabel('Passwort', { exact: true }).fill('kurz');
    await page.getByLabel('Passwort wiederholen').fill('kurz');
    await page.getByRole('button', { name: 'Pegelstand installieren' }).click();

    const zusammenfassung = page.getByRole('alert').filter({ hasText: 'Bitte prüfe deine Angaben' });
    await expect(zusammenfassung).toBeFocused();
    await expect(zusammenfassung).toContainText('E-Mail-Adresse ist ungültig');
    await expect(zusammenfassung).toContainText('Wähle mindestens 12 Zeichen');
    await expect(page.getByLabel('E-Mail-Adresse')).toHaveAttribute('aria-invalid', 'true');
    // Der Name bleibt erhalten, Passwörter nicht.
    await expect(page.getByLabel('Name', { exact: true })).toHaveValue('Frank Panzer');
    await expect(page.getByLabel('Passwort', { exact: true })).toHaveValue('');
    expect(await axeVerstoesse(page)).toEqual([]);
    expect(await seitenUeberlauf(page)).toBeLessThanOrEqual(0);
    await bild(page, info, '3b-admin-fehler');

    await page.getByLabel('E-Mail-Adresse').fill('frank@beispiel.de');
    await page.getByLabel('Passwort', { exact: true }).fill('ein sehr langer satz');
    await page.getByLabel('Passwort wiederholen').fill('ein ganz anderer satz');
    await page.getByRole('button', { name: 'Pegelstand installieren' }).click();
    await expect(page.getByRole('alert').filter({ hasText: 'Bitte prüfe deine Angaben' })).toContainText('stimmen nicht überein');
  });

  test('Vollständige Installation, danach ist der Installer gesperrt', async ({ page }, info) => {
    await zuSchrittDrei(page, info);
    await page.getByLabel('Name', { exact: true }).fill('Frank Panzer');
    await page.getByLabel('E-Mail-Adresse').fill('frank@beispiel.de');
    await page.getByLabel('Passwort', { exact: true }).fill('ein sehr langer satz');
    await page.getByLabel('Passwort wiederholen').fill('ein sehr langer satz');
    expect(await axeVerstoesse(page)).toEqual([]);
    await bild(page, info, '3-admin');
    await page.getByRole('button', { name: 'Pegelstand installieren' }).click();

    await expect(page.getByRole('heading', { level: 1, name: 'Pegelstand ist installiert' })).toBeVisible();
    await expect(page.locator('[aria-current="step"]')).toContainText('Fertig');
    expect(await axeVerstoesse(page)).toEqual([]);
    expect(await seitenUeberlauf(page)).toBeLessThanOrEqual(0);
    await bild(page, info, '4-fertig');

    await page.getByRole('link', { name: /Zu Pegelstand/ }).click();
    await expect(page).toHaveURL(/\/login$/);
    await expect(page.getByRole('heading', { level: 1, name: 'Anmelden' })).toBeVisible();
    expect(await axeVerstoesse(page)).toEqual([]);

    const gesperrt = await page.goto('/install');
    expect(gesperrt?.status()).toBe(404);
    await expect(page.getByRole('heading', { level: 1, name: 'Seite nicht gefunden' })).toBeVisible();
    expect(await axeVerstoesse(page)).toEqual([]);
    await bild(page, info, '5-gesperrt');
  });
});
