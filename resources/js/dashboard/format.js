// Deutsche Zahlen-, Prozent-, Dauer- und Datumsformate (1.234,5 und 29.09.2026).
const ganz = new Intl.NumberFormat('de-DE', { maximumFractionDigits: 0 });
const eine = new Intl.NumberFormat('de-DE', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
const NBSP = ' ';

export const formatGanz = (n) => ganz.format(Math.round(n));
export const formatEine = (n) => eine.format(n);

/** Große Zahlen lesbar abgekürzt: 12.438 wird zu „12,4 Tsd.“, ab einer Million „Mio.“. */
export function formatKompakt(n, schwelle = 10000) {
  const a = Math.abs(n);
  if (a >= 1e6) return `${eine.format(n / 1e6)}${NBSP}Mio.`;
  if (a >= schwelle) return `${eine.format(n / 1e3)}${NBSP}Tsd.`;
  return ganz.format(Math.round(n));
}

export const formatProzent = (n) => `${eine.format(n)}${NBSP}%`;

/** Dauer in Sekunden als m:ss oder h:mm:ss. */
export function formatDauer(sekunden) {
  const s = Math.max(0, Math.round(sekunden));
  const h = Math.floor(s / 3600);
  const m = Math.floor((s % 3600) / 60);
  const r = String(s % 60).padStart(2, '0');
  return h > 0 ? `${h}:${String(m).padStart(2, '0')}:${r}` : `${m}:${r}`;
}

export function formatDatum(iso) {
  const [j, m, t] = iso.split('-');
  return `${t}.${m}.${j}`;
}

export function formatDatumKurz(iso) {
  const [, m, t] = iso.split('-');
  return `${t}.${m}.`;
}

const WOCHENTAGE = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
export function formatDatumMitTag(iso) {
  const tag = WOCHENTAGE[new Date(`${iso}T12:00:00Z`).getUTCDay()];
  return `${tag}, ${formatDatum(iso)}`;
}

/** Veränderung in Prozent mit Vorzeichen, z. B. „+8,2 %“ oder „−3,1 %“. */
export function formatVeraenderung(prozent) {
  const vorzeichen = prozent > 0 ? '+' : prozent < 0 ? '−' : '';
  return `${vorzeichen}${eine.format(Math.abs(prozent))}${NBSP}%`;
}
