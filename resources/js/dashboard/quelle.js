// Wählt die Datenquelle: Im echten Dashboard (data-quelle="live" am Hauptbereich) die JSON-Schnittstelle,
// im Prototyp die erfundenen Demo-Daten. Beide bieten dieselbe Schnittstelle.
import * as demo from './demo-daten.js';
import * as live from './quelle-live.js';

const istLive = document.getElementById('inhalt')?.dataset.quelle === 'live';
const q = istLive ? live : demo;

export const IST_DEMO = !istLive;
export const { alleSites, aktiveBesucher, filterName, ladeAnsicht } = q;
export const waehleSite = q.waehleSite ?? (() => {});
export { FILTERARTEN } from './filterarten.js';
