// Zustand der Ansicht ↔ Adresszeile. So lassen sich Ansichten teilen und der Zurück-Knopf funktioniert.
import { alleSites, FILTERARTEN } from './demo-daten.js';
import { KENNZAHLEN } from './kennzahlen.js';
import { ZEITRAEUME } from './zeitraum.js';

const ZUSTAENDE = ['laden', 'leer', 'fehler', 'keine-daten'];

export function lese(suche) {
  const p = new URLSearchParams(suche);
  const sites = alleSites();
  const filter = [];
  for (const eintrag of p.getAll('f')) {
    const stelle = eintrag.indexOf(':');
    const typ = eintrag.slice(0, stelle);
    const wert = eintrag.slice(stelle + 1);
    if (stelle > 0 && typ in FILTERARTEN && wert && !filter.some((f) => f.typ === typ)) filter.push({ typ, wert });
  }
  const verzoegerung = Number(p.get('verzoegerung'));
  return {
    site: sites.some((s) => s.id === p.get('site')) ? p.get('site') : sites[0].id,
    zeitraum: ZEITRAEUME.some((z) => z.id === p.get('zeitraum')) ? p.get('zeitraum') : '30t',
    von: /^\d{4}-\d{2}-\d{2}$/.test(p.get('von') ?? '') ? p.get('von') : null,
    bis: /^\d{4}-\d{2}-\d{2}$/.test(p.get('bis') ?? '') ? p.get('bis') : null,
    vergleich: p.get('vergleich') !== '0',
    metrik: KENNZAHLEN.some((k) => k.id === p.get('metrik')) ? p.get('metrik') : 'besucher',
    filter,
    zustand: ZUSTAENDE.includes(p.get('zustand') ?? '') ? p.get('zustand') : null,
    verzoegerung: p.has('verzoegerung') && Number.isFinite(verzoegerung) ? verzoegerung : 500,
  };
}

export function schreibe(z) {
  const p = new URLSearchParams();
  if (z.site !== alleSites()[0].id) p.set('site', z.site);
  if (z.zeitraum !== '30t') p.set('zeitraum', z.zeitraum);
  if (z.zeitraum === 'benutzerdefiniert') {
    if (z.von) p.set('von', z.von);
    if (z.bis) p.set('bis', z.bis);
  }
  if (!z.vergleich) p.set('vergleich', '0');
  if (z.metrik !== 'besucher') p.set('metrik', z.metrik);
  for (const f of z.filter) p.append('f', `${f.typ}:${f.wert}`);
  if (z.zustand) p.set('zustand', z.zustand);
  if (z.verzoegerung !== 500) p.set('verzoegerung', String(z.verzoegerung));
  const s = p.toString();
  return s ? `?${s}` : location.pathname;
}
