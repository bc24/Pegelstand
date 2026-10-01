import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';

const SEITE = '/prototype/komponenten.html';

const fremdeAnfragen: string[] = [];

test.beforeEach(async ({ page }) => {
  fremdeAnfragen.length = 0;
  page.on('request', (anfrage) => {
    const url = new URL(anfrage.url());
    if (!['127.0.0.1', 'localhost'].includes(url.hostname) && url.protocol.startsWith('http')) {
      fremdeAnfragen.push(anfrage.url());
    }
  });
  await page.goto(SEITE);
  // Warten, bis die Icon-Übersicht und die Farbfelder von der Seite aufgebaut sind.
  await expect(page.locator('.kp-icon').first()).toBeVisible();
  await expect(page.locator('.kp-swatch').first()).toBeVisible();
});

test('Komponentenseite ist barrierefrei (WCAG 2.2 AA)', async ({ page }) => {
  const ergebnis = await new AxeBuilder({ page })
    .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'])
    .analyze();
  expect(ergebnis.violations.map((v) => `${v.id}: ${v.nodes.map((n) => n.target.join(' ')).join(' | ')}`)).toEqual([]);
});

test('Kein horizontaler Überlauf der Seite', async ({ page }) => {
  const ueberlauf = await page.evaluate(
    () => document.documentElement.scrollWidth - document.documentElement.clientWidth,
  );
  expect(ueberlauf).toBeLessThanOrEqual(0);
});

test('Tabs lassen sich mit Pfeiltasten bedienen', async ({ page }) => {
  const erster = page.getByRole('tab', { name: 'Referrer' });
  await erster.focus();
  await page.keyboard.press('ArrowRight');
  const zweiter = page.getByRole('tab', { name: 'Suchmaschinen' });
  await expect(zweiter).toBeFocused();
  await expect(zweiter).toHaveAttribute('aria-selected', 'true');
  await expect(page.getByRole('tabpanel', { name: 'Suchmaschinen' })).toBeVisible();
  await expect(page.getByRole('tabpanel', { name: 'Referrer' })).toBeHidden();
  await page.keyboard.press('End');
  await expect(page.getByRole('tab', { name: 'Kampagnen' })).toHaveAttribute('aria-selected', 'true');
});

test('Dropdown: Tastatur, Auswahl und Fokus-Rückgabe', async ({ page }) => {
  const knopf = page.getByRole('button', { name: 'Letzte 30 Tage', exact: true });
  await knopf.focus();
  await page.keyboard.press('ArrowDown');
  const menue = page.getByRole('menu', { name: 'Zeitraum' });
  await expect(menue).toBeVisible();
  await expect(page.getByRole('menuitemradio', { name: '30 Tage' })).toBeFocused();
  await page.keyboard.press('ArrowUp');
  await page.keyboard.press('Enter');
  await expect(menue).toBeHidden();
  await expect(knopf).toBeFocused();
  await knopf.click();
  await expect(page.getByRole('menuitemradio', { name: '7 Tage' })).toHaveAttribute('aria-checked', 'true');
  await page.keyboard.press('Escape');
  await expect(menue).toBeHidden();
  await expect(knopf).toBeFocused();
});

test('Modal: Fokus im Fenster, Esc schließt, Fokus kehrt zurück', async ({ page }) => {
  const ausloeser = page.getByRole('button', { name: 'Hinweis öffnen' });
  await ausloeser.click();
  const dialog = page.getByRole('dialog', { name: 'Tastaturkürzel' });
  await expect(dialog).toBeVisible();
  await expect(dialog.locator(':focus')).toHaveCount(1);
  await page.keyboard.press('Escape');
  await expect(dialog).toBeHidden();
  await expect(ausloeser).toBeFocused();
});

test('Modal schließt über den Button', async ({ page }) => {
  await page.getByRole('button', { name: 'Site löschen …' }).click();
  const dialog = page.getByRole('dialog', { name: /löschen/ });
  await dialog.getByRole('button', { name: 'Abbrechen' }).click();
  await expect(dialog).toBeHidden();
});

test('Toast erscheint, ist ansagbar und lässt sich schließen', async ({ page }) => {
  await page.getByRole('button', { name: 'Fehler', exact: true }).click();
  const region = page.getByRole('region', { name: 'Benachrichtigungen' });
  await expect(region.getByRole('alert')).toContainText('Speichern fehlgeschlagen');
  await region.getByRole('button', { name: 'Benachrichtigung schließen' }).click();
  await expect(region.getByRole('alert')).toBeHidden();
});

test('Tooltip erscheint bei Fokus und verschwindet mit Esc', async ({ page }) => {
  const knopf = page.getByRole('button', { name: 'Erklärung zu Besucher' });
  await knopf.focus();
  const tipp = page.getByRole('tooltip');
  await expect(tipp).toContainText('Anzahl unterschiedlicher Besucher');
  await expect(knopf).toHaveAttribute('aria-describedby', /ps-tooltip/);
  await page.keyboard.press('Escape');
  await expect(tipp).toBeHidden();
});

test('Darstellung lässt sich auf Dunkel und Hell stellen', async ({ page }) => {
  await page.getByRole('button', { name: /Darstellung/ }).click();
  await page.getByRole('menuitemradio', { name: 'Dunkel' }).click();
  await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
  await page.getByRole('button', { name: /Darstellung/ }).click();
  await page.getByRole('menuitemradio', { name: 'Hell' }).click();
  await expect(page.locator('html')).toHaveAttribute('data-theme', 'light');
  await page.reload();
  await expect(page.locator('html')).toHaveAttribute('data-theme', 'light');
});

test('Sichtbarer Fokusring bei Tastaturbedienung', async ({ page }) => {
  await page.keyboard.press('Tab');
  await expect(page.getByRole('link', { name: 'Zum Inhalt springen' })).toBeFocused();
  const kontur = await page.locator(':focus').evaluate((el) => getComputedStyle(el).outlineStyle);
  expect(kontur).not.toBe('none');
});

test('Die Seite stellt keine Anfragen an Dritte', async ({ page }) => {
  await page.reload();
  await expect(page.locator('.kp-icon').first()).toBeVisible();
  expect(fremdeAnfragen).toEqual([]);
});
