import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';

test('Startseite lädt und ist barrierefrei', async ({ page }, testInfo) => {
  await page.goto('/');

  await expect(page).toHaveTitle('Pegelstand');
  await expect(page.getByRole('heading', { level: 1, name: 'Pegelstand' })).toBeVisible();

  const ergebnis = await new AxeBuilder({ page })
    .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'])
    .analyze();
  expect(ergebnis.violations).toEqual([]);

  await page.screenshot({ path: testInfo.outputPath('startseite.png'), fullPage: true });
});

test('Kein horizontaler Scrollbalken', async ({ page }) => {
  await page.goto('/');

  const ueberlauf = await page.evaluate(
    () => document.documentElement.scrollWidth - document.documentElement.clientWidth,
  );
  expect(ueberlauf).toBeLessThanOrEqual(0);
});
