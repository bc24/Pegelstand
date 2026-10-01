// Datenquelle des echten Dashboards: liest die JSON-Schnittstelle des Servers (/api/dashboard).
import { JETZT } from './zeitraum.js';

const wurzel = document.getElementById('inhalt');
const basis = wurzel?.dataset.basis ?? '/';
const api = wurzel?.dataset.api || 'api/dashboard';
let sites = [];
try {
  sites = JSON.parse(wurzel?.dataset.bootstrap ?? '{}').sites ?? [];
} catch {
  sites = [];
}

const namen = new Map();

export const alleSites = () => sites;

/** Setzt „Jetzt“ und das früheste Datum auf die Werte der Site (jede Site hat eine eigene Zeitzone). */
export function waehleSite(id) {
  const site = sites.find((s) => s.id === id) ?? sites[0];
  if (!site) return;
  Object.assign(JETZT, { datum: site.jetzt.datum, stunde: site.jetzt.stunde, zeit: site.jetzt.zeit, min: site.min });
}

/** Anzeigename eines Filterwerts. Bekannt wird er, sobald er einmal in einer Tabelle vorkam. */
export function filterName(_siteId, typ, wert) {
  return namen.get(`${typ}|${wert}`) ?? wert;
}

function merkeNamen(daten) {
  const merke = (liste) => {
    for (const z of liste ?? []) namen.set(`${z.filterTyp}|${z.id}`, z.name);
  };
  const t = daten.tabellen ?? {};
  for (const gruppe of [t.seiten, t.quellen, t.geraete]) {
    for (const liste of Object.values(gruppe ?? {})) merke(liste);
  }
  merke(t.laender);
  merke(t.ziele);
  merke(t.ereignisse);
}

function zuLogin() {
  location.assign(`${basis}login`);
}

async function hole(pfad, parameter) {
  const antwort = await fetch(`${basis}${pfad}?${parameter}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
  if (antwort.status === 401) {
    zuLogin();
    return new Promise(() => {});
  }
  if (!antwort.ok) throw new Error(`Antwort ${antwort.status}`);
  return antwort.json();
}

export async function aktiveBesucher(siteId) {
  const antwort = await hole(`${api}/live`, new URLSearchParams({ site: siteId }).toString());
  return antwort.aktive;
}

/**
 * @param {{site: string, zeitraum: object, filter: Array<{typ: string, wert: string}>, zustand?: string}} abfrage
 */
export async function ladeAnsicht({ site, zeitraum, filter, zustand }) {
  if (zustand === 'laden') return new Promise(() => {});
  if (zustand === 'fehler') throw new Error('Simulierter Fehler');
  const parameter = new URLSearchParams({ site, von: zeitraum.von, bis: zeitraum.bis });
  for (const f of filter) parameter.append('f[]', `${f.typ}:${f.wert}`);
  const daten = await hole(api, parameter.toString());
  const eintrag = sites.find((s) => s.id === site);
  if (daten.leer) return { ...daten, site: eintrag ?? daten.site };
  if (daten.jetzt && eintrag) {
    eintrag.jetzt = daten.jetzt;
    Object.assign(JETZT, daten.jetzt);
  }
  merkeNamen(daten);
  return { ...daten, site: eintrag ?? daten.site };
}
