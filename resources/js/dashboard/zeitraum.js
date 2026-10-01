// Zeiträume und Datumsrechnung auf ISO-Datumstexten (JJJJ-MM-TT), unabhängig von Zeitzonen.
const TAG = 86400000;

/** Festes „Jetzt“ der Demo, damit Prototyp, Tests und Screenshots reproduzierbar sind. */
export const JETZT = { datum: '2026-09-29', stunde: 14 };
export const MIN_DATUM = '2025-01-01';

const parse = (iso) => {
  const [j, m, t] = iso.split('-').map(Number);
  return Date.UTC(j, m - 1, t);
};
const zuIso = (ms) => new Date(ms).toISOString().slice(0, 10);

export const addTage = (iso, n) => zuIso(parse(iso) + n * TAG);
export const tageZwischen = (von, bis) => Math.round((parse(bis) - parse(von)) / TAG) + 1;
/** 0 = Montag … 6 = Sonntag */
export const wochentag = (iso) => (new Date(parse(iso)).getUTCDay() + 6) % 7;
const ersterDesMonats = (iso) => `${iso.slice(0, 8)}01`;

export const ZEITRAEUME = [
  { id: 'heute', label: 'Heute', taste: '1' },
  { id: 'gestern', label: 'Gestern', taste: '2' },
  { id: '7t', label: '7 Tage', taste: '3' },
  { id: '30t', label: '30 Tage', taste: '4' },
  { id: 'monat', label: 'Dieser Monat', taste: '5' },
  { id: 'letzter-monat', label: 'Letzter Monat', taste: '6' },
  { id: 'jahr', label: 'Dieses Jahr', taste: '7' },
  { id: 'benutzerdefiniert', label: 'Benutzerdefiniert …', taste: null },
];

export function begrenze(von, bis) {
  let v = von && von >= MIN_DATUM ? von : MIN_DATUM;
  let b = bis && bis <= JETZT.datum ? bis : JETZT.datum;
  if (v > b) [v, b] = [b, v];
  return [v, b];
}

/** Löst einen Zeitraum auf und liefert zugleich die gleich lange Vorperiode direkt davor. */
export function loese(id, vonRoh, bisRoh) {
  let von;
  let bis;
  switch (id) {
    case 'heute':
      von = bis = JETZT.datum;
      break;
    case 'gestern':
      von = bis = addTage(JETZT.datum, -1);
      break;
    case '7t':
      bis = JETZT.datum;
      von = addTage(bis, -6);
      break;
    case 'monat':
      von = ersterDesMonats(JETZT.datum);
      bis = JETZT.datum;
      break;
    case 'letzter-monat': {
      bis = addTage(ersterDesMonats(JETZT.datum), -1);
      von = ersterDesMonats(bis);
      break;
    }
    case 'jahr':
      von = `${JETZT.datum.slice(0, 4)}-01-01`;
      bis = JETZT.datum;
      break;
    case 'benutzerdefiniert':
      [von, bis] = begrenze(vonRoh, bisRoh);
      break;
    default:
      id = '30t';
      bis = JETZT.datum;
      von = addTage(bis, -29);
  }
  const tage = tageZwischen(von, bis);
  const vorBis = addTage(von, -1);
  const vorVon = addTage(vorBis, -(tage - 1));
  return {
    id,
    von,
    bis,
    vorVon,
    vorBis,
    tage,
    // Bis zu zwei Tage werden stündlich gezeigt, längere Zeiträume täglich.
    stundenweise: tage <= 2,
    // Der laufende Tag ist erst bis zur aktuellen Stunde vollständig.
    bisStunde: bis === JETZT.datum ? JETZT.stunde : 23,
  };
}
