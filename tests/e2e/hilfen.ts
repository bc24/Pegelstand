import AxeBuilder from '@axe-core/playwright';
import type { Page, TestInfo } from '@playwright/test';

export interface DbZugang {
  host: string;
  port: string;
  name: string;
  user: string;
  password: string;
}

/** Zugangsdaten der Test-Datenbank aus PEGELSTAND_TEST_DB_*; ohne sie werden Datenbank-Tests übersprungen. */
export function dbZugang(): DbZugang | null {
  const dsn = process.env.PEGELSTAND_TEST_DB_DSN ?? '';
  const host = /host=([^;]+)/.exec(dsn)?.[1];
  const name = /dbname=([^;]+)/.exec(dsn)?.[1];
  if (!host || !name) return null;
  return {
    host,
    port: /port=(\d+)/.exec(dsn)?.[1] ?? '3306',
    name,
    user: process.env.PEGELSTAND_TEST_DB_USER ?? '',
    password: process.env.PEGELSTAND_TEST_DB_PASSWORD ?? '',
  };
}

/** Eigener Tabellenpräfix je Darstellungsvariante, damit parallele Läufe sich nicht stören. */
export function praefix(info: TestInfo): string {
  const index = ['desktop-hell', 'desktop-dunkel', 'smartphone-hell', 'smartphone-dunkel'].indexOf(info.project.name);
  return `e2e${index + 1}_`;
}

export async function axeVerstoesse(page: Page): Promise<string[]> {
  const ergebnis = await new AxeBuilder({ page })
    .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'])
    .analyze();
  return ergebnis.violations.map((v) => `${v.id}: ${v.nodes.map((n) => n.target.join(' ')).join(' | ')}`);
}

export async function seitenUeberlauf(page: Page): Promise<number> {
  return page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
}
