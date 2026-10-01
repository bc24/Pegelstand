import { expect, test } from '@playwright/test';

// Erzeugt Bilder unter screenshots/<Projekt>/ zur eigenen Sichtprüfung.
// Aufruf: npm run screenshots
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
    await abschnitt.screenshot({ path: `${ordner}/${id}.png` });
  }

  // Geöffnete Zustände (nach Ende der Animationen aufnehmen)
  const warte = () => page.waitForTimeout(450);

  await page.getByRole('button', { name: 'Letzte 30 Tage', exact: true }).click();
  await warte();
  await page.screenshot({ path: `${ordner}/zustand-dropdown.png` });
  await page.keyboard.press('Escape');

  await page.getByRole('button', { name: 'Site löschen …' }).click();
  await warte();
  await page.screenshot({ path: `${ordner}/zustand-modal.png` });
  await page.keyboard.press('Escape');

  await page.getByRole('button', { name: 'Erfolg', exact: true }).click();
  await page.getByRole('button', { name: 'Fehler', exact: true }).click();
  await warte();
  await page.screenshot({ path: `${ordner}/zustand-toast.png` });

  await page.getByRole('button', { name: 'Erklärung zu Besucher' }).focus();
  await warte();
  await page.screenshot({ path: `${ordner}/zustand-tooltip.png` });
});
