import { expect, type Page, test } from '@playwright/test';

// Erzeugt Bilder unter screenshots/<Projekt>/ zur eigenen Sichtprüfung.
// Aufruf: npm run screenshots
const warte = (page: Page) => page.waitForTimeout(450);

test('Screenshots der Komponentenseite', async ({ page }, testInfo) => {
  await page.goto('/prototype/komponenten.html');
  await expect(page.locator('.kp-icon').first()).toBeVisible();
  await expect(page.locator('.kp-swatch').first()).toBeVisible();
  await page.evaluate(() => document.fonts.ready);
  // Klebende Kopfzeilen würden in Abschnittsbildern über dem Inhalt liegen.
  await page.addStyleTag({ content: '.kp-header, .kp-nav { position: static !important; }' });

  const ordner = `screenshots/${testInfo.project.name}`;
  for (const abschnitt of await page.locator('section.kp-section').all()) {
    const id = await abschnitt.getAttribute('id');
    await abschnitt.screenshot({ path: `${ordner}/komponenten-${id}.png` });
  }
});

test('Screenshots des Dashboard-Prototyps', async ({ page }, testInfo) => {
  const ordner = `screenshots/${testInfo.project.name}`;
  const lade = async (abfrage: string) => {
    await page.goto(`/prototype/index.html?verzoegerung=0${abfrage}`);
    await expect(page.locator('#ansicht-normal[aria-busy="false"]:not([hidden]), #ansicht-sonder:not([hidden])').first()).toBeVisible();
    await page.evaluate(() => document.fonts.ready);
    await page.addStyleTag({ content: '.db-header { position: static !important; }' });
    await warte(page);
  };

  await lade('');
  await page.screenshot({ path: `${ordner}/dashboard.png`, fullPage: true });

  await lade('&f=land:DE&f=geraet:smartphone&zeitraum=7t');
  await page.screenshot({ path: `${ordner}/dashboard-gefiltert.png`, fullPage: true });

  await lade('&zeitraum=heute');
  await page.screenshot({ path: `${ordner}/dashboard-heute.png`, clip: { x: 0, y: 0, width: page.viewportSize()!.width, height: 900 } });

  await page.goto('/prototype/index.html?zustand=laden');
  await page.evaluate(() => document.fonts.ready);
  await page.screenshot({ path: `${ordner}/dashboard-laden.png`, fullPage: true });

  await lade('&site=neue-seite-de');
  await page.screenshot({ path: `${ordner}/dashboard-leer.png`, fullPage: true });

  await lade('&zustand=fehler');
  await page.screenshot({ path: `${ordner}/dashboard-fehler.png`, fullPage: true });

  await lade('&zustand=keine-daten&f=land:DE');
  await page.screenshot({ path: `${ordner}/dashboard-keine-daten.png`, fullPage: true });

  await lade('');
  await page.keyboard.press('Control+k');
  await warte(page);
  await page.screenshot({ path: `${ordner}/dashboard-palette.png` });
  await page.keyboard.press('Escape');

  await page.locator('#diagramm').focus();
  await page.keyboard.press('ArrowRight');
  await page.keyboard.press('ArrowRight');
  await page.keyboard.press('ArrowRight');
  await page.locator('#diagramm').scrollIntoViewIfNeeded();
  await warte(page);
  await page.screenshot({ path: `${ordner}/dashboard-diagramm-tooltip.png` });
});
