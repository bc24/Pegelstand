import AxeBuilder from '@axe-core/playwright';
import { expect, type Page, test } from '@playwright/test';

const BASIS = '/prototype/index.html?verzoegerung=0';
const kpiWert = (page: Page, id: string) => page.locator(`.ps-kpi--waehlbar[data-kennzahl="${id}"] .ps-kpi__value`);

async function oeffne(page: Page, abfrage = '') {
  await page.goto(`${BASIS}${abfrage}`);
  await expect(page.locator('#ansicht-normal[aria-busy="false"]:not([hidden]), #ansicht-sonder:not([hidden])').first()).toBeVisible();
}

async function axe(page: Page) {
  const ergebnis = await new AxeBuilder({ page })
    .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'])
    .analyze();
  return ergebnis.violations.map((v) => `${v.id}: ${v.nodes.map((n) => n.target.join(' ')).join(' | ')}`);
}

async function ueberlauf(page: Page) {
  return page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
}

test.describe('Darstellung und Zustände', () => {
  test('Normalansicht: Kennzahlen, Diagramm und Tabellen', async ({ page }) => {
    await oeffne(page);
    await expect(page.locator('.ps-kpi--waehlbar')).toHaveCount(5);
    await expect(page.locator('#diagramm canvas').first()).toBeVisible();
    await expect(page.locator('[data-bereich="seiten.top"] tbody tr')).toHaveCount(8);
    await expect(page.locator('#live-text')).toContainText('aktive');
    expect(await axe(page)).toEqual([]);
    expect(await ueberlauf(page)).toBeLessThanOrEqual(0);
  });

  test('Ladezustand zeigt Skeletons', async ({ page }) => {
    await page.goto('/prototype/index.html?zustand=laden');
    await expect(page.locator('#kpis .ps-skeleton').first()).toBeVisible();
    await expect(page.locator('[data-bereich="laender"] .ps-skeleton').first()).toBeVisible();
    expect(await axe(page)).toEqual([]);
    expect(await ueberlauf(page)).toBeLessThanOrEqual(0);
  });

  test('Leere Site zeigt die Anleitung', async ({ page }) => {
    await oeffne(page, '&site=neue-seite-de');
    await expect(page.getByRole('heading', { name: 'Noch keine Besucher bei neue-seite.de' })).toBeVisible();
    await expect(page.locator('.db-code code')).toContainText('data-site="neue-seite-de"');
    expect(await axe(page)).toEqual([]);
    expect(await ueberlauf(page)).toBeLessThanOrEqual(0);
  });

  test('Fehlerzustand nennt den Lösungsweg und lässt sich wiederholen', async ({ page }) => {
    await oeffne(page, '&zustand=fehler');
    await expect(page.getByRole('heading', { name: 'Daten konnten nicht geladen werden' })).toBeVisible();
    expect(await axe(page)).toEqual([]);
    await page.getByRole('button', { name: 'Erneut versuchen' }).click();
    await expect(page.locator('.ps-kpi--waehlbar')).toHaveCount(5);
    expect(page.url()).not.toContain('zustand');
  });

  test('Keine Daten im Zeitraum bietet nächste Schritte', async ({ page }) => {
    await oeffne(page, '&zustand=keine-daten&f=land:DE');
    await expect(page.getByRole('heading', { name: 'Keine Daten in diesem Zeitraum' })).toBeVisible();
    expect(await axe(page)).toEqual([]);
    await page.getByRole('button', { name: 'Filter entfernen' }).click();
    expect(page.url()).not.toContain('f=');
  });

  test('Diagramm lässt sich als Tabelle anzeigen', async ({ page }) => {
    await oeffne(page);
    await page.getByRole('button', { name: 'Als Tabelle' }).click();
    await expect(page.getByRole('table', { name: 'Besucher im Zeitverlauf' })).toBeVisible();
    await expect(page.locator('#diagramm')).toBeHidden();
    expect(await axe(page)).toEqual([]);
    await page.getByRole('button', { name: 'Als Diagramm' }).click();
    await expect(page.locator('#diagramm canvas').first()).toBeVisible();
  });

  test('Dunkel: Diagramm zeichnet mit Token-Farben neu', async ({ page }) => {
    await oeffne(page);
    await page.getByRole('button', { name: 'Darstellung' }).click();
    await page.getByRole('menuitemradio', { name: 'Dunkel' }).click();
    await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
    await expect(page.locator('#diagramm canvas').first()).toBeVisible();
    expect(await axe(page)).toEqual([]);
  });
});

test.describe('Filter, Zeitraum und Kennzahlen', () => {
  test('Klick auf eine Zeile setzt einen Filter, Chips sind entfernbar, URL ist teilbar', async ({ page }) => {
    await oeffne(page);
    const vorher = await kpiWert(page, 'besucher').innerText();

    await page.locator('[data-filter-typ="land"][data-filter-wert="DE"]').click();
    await expect(page.locator('.ps-chip')).toHaveCount(1);
    await expect(page.locator('.ps-chip')).toContainText('Land: Deutschland');
    expect(page.url()).toContain('f=land%3ADE');
    await expect(kpiWert(page, 'besucher')).not.toHaveText(vorher);
    await expect(page.locator('[data-bereich="laender"] tbody tr')).toHaveCount(1);

    await page.locator('[data-filter-typ="geraet"][data-filter-wert="smartphone"]').click();
    await expect(page.locator('.ps-chip')).toHaveCount(2);
    await expect(page.getByRole('button', { name: 'Alle Filter entfernen' })).toBeVisible();
    expect(await axe(page)).toEqual([]);

    await page.getByRole('button', { name: 'Filter Land: Deutschland entfernen' }).click();
    await expect(page.locator('.ps-chip')).toHaveCount(1);
    await page.getByRole('button', { name: 'Filter Gerät: Smartphone entfernen' }).click();
    await expect(page.locator('.ps-chip')).toHaveCount(0);
    expect(page.url()).not.toContain('f=');
    await expect(kpiWert(page, 'besucher')).toHaveText(vorher);
  });

  test('Filterzustand wird aus der Adresse gelesen und der Zurück-Knopf funktioniert', async ({ page }) => {
    await oeffne(page, '&f=land:AT&f=os:ios&zeitraum=7t');
    await expect(page.locator('.ps-chip')).toHaveCount(2);
    await expect(page.locator('#zeitraum-name')).toHaveText('7 Tage');
    await page.getByRole('button', { name: 'Alle Filter entfernen' }).click();
    await expect(page.locator('.ps-chip')).toHaveCount(0);
    await page.goBack();
    await expect(page.locator('.ps-chip')).toHaveCount(2);
  });

  test('Zeitraum über Menü und Kürzel wechseln', async ({ page }) => {
    await oeffne(page);
    await page.getByRole('button', { name: /Zeitraum/ }).click();
    await page.getByRole('menuitemradio', { name: /^Gestern/ }).click();
    await expect(page.locator('#zeitraum-name')).toHaveText('Gestern');
    await expect(page.locator('#zeitinfo')).toContainText('28.09.2026');
    await page.keyboard.press('Escape');
    await page.locator('body').click({ position: { x: 5, y: 300 } });
    await page.keyboard.press('3');
    await expect(page.locator('#zeitraum-name')).toHaveText('7 Tage');
    expect(page.url()).toContain('zeitraum=7t');
  });

  test('Heute zeigt stündliche Werte', async ({ page }) => {
    await oeffne(page, '&zeitraum=heute');
    await page.getByRole('button', { name: 'Als Tabelle' }).click();
    await expect(page.locator('#diagramm-tabelle tbody tr')).toHaveCount(15);
    await expect(page.locator('#diagramm-tabelle tbody tr').first()).toContainText('0 Uhr');
  });

  test('Benutzerdefinierter Zeitraum mit Fehlerhinweis', async ({ page }) => {
    await oeffne(page);
    await page.getByRole('button', { name: /Zeitraum/ }).click();
    await page.getByRole('menuitemradio', { name: /Benutzerdefiniert/ }).click();
    const dialog = page.getByRole('dialog', { name: 'Zeitraum wählen' });
    await dialog.getByLabel('Von').fill('2026-09-20');
    await dialog.getByLabel('Bis').fill('2026-09-10');
    await dialog.getByRole('button', { name: 'Übernehmen' }).click();
    await expect(dialog.getByRole('alert')).toContainText('Startdatum liegt nach dem Enddatum');
    await dialog.getByLabel('Von').fill('2026-09-10');
    await dialog.getByLabel('Bis').fill('2026-09-20');
    await dialog.getByRole('button', { name: 'Übernehmen' }).click();
    await expect(dialog).toBeHidden();
    await expect(page.locator('#zeitraum-name')).toHaveText('10.09.2026 – 20.09.2026');
  });

  test('Vergleich lässt sich ausschalten', async ({ page }) => {
    await oeffne(page);
    await expect(page.locator('.ps-delta').first()).toBeVisible();
    await page.getByRole('switch', { name: 'Mit Vorperiode vergleichen' }).uncheck();
    await expect(page.locator('.ps-delta')).toHaveCount(0);
    await expect(page.locator('#legende-vorher')).toBeHidden();
    expect(page.url()).toContain('vergleich=0');
  });

  test('Kennzahl wählt die Diagrammdarstellung', async ({ page }) => {
    await oeffne(page);
    await page.locator('.ps-kpi__auswahl', { hasText: 'Absprungrate' }).click();
    await expect(page.locator('#diagramm-titel')).toHaveText('Absprungrate im Zeitverlauf');
    expect(page.url()).toContain('metrik=absprungrate');
  });

  test('Ereignis als Filter zeigt dessen Eigenschaften', async ({ page }) => {
    await oeffne(page);
    await page.getByRole('tab', { name: 'Ereignisse' }).click();
    await page.locator('[data-filter-typ="ereignis"][data-filter-wert="anmeldung"]').click();
    await expect(page.getByRole('heading', { name: 'Eigenschaften von „Anmeldung“' })).toBeVisible();
    await expect(page.getByRole('table', { name: 'Eigenschaft tarif' })).toBeVisible();
  });

  test('Site wechseln setzt die Filter zurück', async ({ page }) => {
    await oeffne(page, '&f=land:DE');
    await page.getByRole('button', { name: /beispiel\.de/ }).first().click();
    await page.getByRole('menuitemradio', { name: 'meine-firma.de' }).click();
    await expect(page.locator('h1')).toHaveText('meine-firma.de');
    await expect(page.locator('.ps-chip')).toHaveCount(0);
  });

  test('Quellen sind nach Gruppen geteilt und beziehen sich auf alle Besucher', async ({ page }) => {
    await oeffne(page);
    await page.getByRole('tab', { name: 'Suchmaschinen' }).click();
    const google = page.locator('[data-bereich="quellen.suche"] tbody tr').first();
    await expect(google).toContainText('Google');
    const anteil = await google.locator('td').last().innerText();
    expect(parseFloat(anteil.replace(',', '.'))).toBeGreaterThan(25);
    expect(parseFloat(anteil.replace(',', '.'))).toBeLessThan(45);
  });
});

test.describe('Tastatur', () => {
  test('Befehlspalette: öffnen, suchen, ausführen, schließen', async ({ page }) => {
    await oeffne(page);
    await page.keyboard.press('Control+k');
    const dialog = page.getByRole('dialog', { name: 'Befehlspalette' });
    await expect(dialog).toBeVisible();
    await expect(page.getByRole('combobox', { name: 'Befehl suchen' })).toBeFocused();
    expect(await axe(page)).toEqual([]);

    await page.keyboard.type('dunkel');
    await expect(dialog.getByRole('option')).toHaveCount(1);
    await page.keyboard.press('Enter');
    await expect(dialog).toBeHidden();
    await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');

    await page.keyboard.press('Control+k');
    await page.keyboard.type('firma');
    await page.keyboard.press('Enter');
    await expect(page.locator('h1')).toHaveText('meine-firma.de');

    await page.keyboard.press('Control+k');
    await page.keyboard.press('Escape');
    await expect(dialog).toBeHidden();
  });

  test('Befehlspalette ohne Treffer erklärt sich', async ({ page }) => {
    await oeffne(page);
    await page.keyboard.press('Control+k');
    await page.keyboard.type('xyzxyz');
    await expect(page.getByText('Keine passenden Befehle')).toBeVisible();
  });

  test('Fragezeichen zeigt die Tastaturkürzel', async ({ page }) => {
    await oeffne(page);
    await page.locator('body').click({ position: { x: 5, y: 300 } });
    await page.keyboard.press('?');
    const dialog = page.getByRole('dialog', { name: 'Tastaturkürzel' });
    await expect(dialog).toBeVisible();
    expect(await axe(page)).toEqual([]);
    await page.keyboard.press('Escape');
    await expect(dialog).toBeHidden();
  });

  test('Diagramm per Pfeiltasten durchgehen', async ({ page }) => {
    await oeffne(page);
    await page.locator('#diagramm').focus();
    await page.keyboard.press('ArrowRight');
    await expect(page.locator('#diagramm-ansage')).toContainText('Besucher');
    await expect(page.locator('.db-diagrammtipp')).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(page.locator('.db-diagrammtipp')).toBeHidden();
  });

  test('Filterzeilen sind per Tastatur bedienbar', async ({ page }) => {
    await oeffne(page);
    await page.locator('[data-filter-typ="land"][data-filter-wert="CH"]').focus();
    await page.keyboard.press('Enter');
    await expect(page.locator('.ps-chip')).toContainText('Schweiz');
  });
});

test.describe('Qualität', () => {
  test('Keine Layout-Verschiebung beim Laden', async ({ page }) => {
    await page.addInitScript(() => {
      (window as unknown as { __cls: number }).__cls = 0;
      new PerformanceObserver((liste) => {
        for (const eintrag of liste.getEntries() as unknown as Array<{ value: number; hadRecentInput: boolean }>) {
          if (!eintrag.hadRecentInput) (window as unknown as { __cls: number }).__cls += eintrag.value;
        }
      }).observe({ type: 'layout-shift', buffered: true });
    });
    await page.goto('/prototype/index.html?verzoegerung=600');
    await expect(page.locator('#ansicht-normal[aria-busy="false"]')).toBeVisible();
    await expect(page.locator('#diagramm canvas').first()).toBeVisible();
    await page.waitForTimeout(500);
    const cls = await page.evaluate(() => (window as unknown as { __cls: number }).__cls);
    expect(cls).toBeLessThan(0.02);
  });

  test('Keine Anfragen an Dritte und keine Konsolenfehler', async ({ page }) => {
    const fremd: string[] = [];
    const fehler: string[] = [];
    page.on('request', (a) => {
      const url = new URL(a.url());
      if (url.protocol.startsWith('http') && !['127.0.0.1', 'localhost'].includes(url.hostname)) fremd.push(a.url());
    });
    page.on('console', (m) => {
      if (m.type() === 'error') fehler.push(m.text());
    });
    page.on('pageerror', (e) => fehler.push(e.message));
    await oeffne(page);
    await page.locator('[data-filter-typ="land"][data-filter-wert="DE"]').click();
    await page.keyboard.press('Control+k');
    await page.keyboard.press('Escape');
    expect(fremd).toEqual([]);
    expect(fehler).toEqual([]);
  });

  test('Zahlen sind deutsch formatiert und große Werte abgekürzt', async ({ page }) => {
    await oeffne(page);
    await expect(kpiWert(page, 'besucher')).toHaveText(/^\d{1,3},\d\sTsd\.$|^\d{1,3}(\.\d{3})*$/);
    await expect(kpiWert(page, 'absprungrate')).toHaveText(/^\d{2},\d\s%$/);
    await expect(kpiWert(page, 'dauer')).toHaveText(/^\d:\d{2}$/);
  });
});
