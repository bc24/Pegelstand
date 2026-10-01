// Darstellung der Dashboard-Daten. Alles entsteht über h() und textContent, nie über HTML-Text.
import { icon } from '../util.js';
import { h, versteckt } from './dom.js';
import { filterName, FILTERARTEN } from './quelle.js';
import {
  formatDatum,
  formatDauer,
  formatGanz,
  formatKompakt,
  formatProzent,
  formatVeraenderung,
} from './format.js';
import { KENNZAHLEN } from './kennzahlen.js';
import { JETZT } from './zeitraum.js';

const MAX_ZEILEN = 8;

/* ---------- Zahlen mit exaktem Wert im Tooltip ---------- */

function zahl(wert, exaktText) {
  const kurz = formatKompakt(wert);
  const exakt = exaktText ?? formatGanz(wert);
  if (kurz === exakt) return exakt;
  return h('span', {}, h('span', { 'aria-hidden': 'true', 'data-ps-tooltip': exakt }, kurz), versteckt(exakt));
}

/* ---------- Kennzahlen ---------- */

function veraenderung(kz, aktuell, vorher) {
  if (!(vorher > 0)) {
    if (aktuell > 0) return h('span', { class: 'ps-delta ps-delta--gut' }, 'neu');
    return h('span', { class: 'ps-delta' }, icon('minus', 'ps-icon--s'), '–');
  }
  const prozent = ((aktuell - vorher) / vorher) * 100;
  const gerundet = Math.round(prozent * 10) / 10;
  if (gerundet === 0) {
    return h('span', { class: 'ps-delta' }, icon('minus', 'ps-icon--s'), formatProzent(0), versteckt(' unverändert zur Vorperiode'));
  }
  const gut = Math.sign(gerundet) === kz.gut;
  return h(
    'span',
    { class: `ps-delta ${gut ? 'ps-delta--gut' : 'ps-delta--schlecht'}` },
    icon(gerundet > 0 ? 'arrow-up' : 'arrow-down', 'ps-icon--s'),
    formatVeraenderung(Math.abs(gerundet)).replace(/^[+−]/, ''),
    versteckt(` ${gerundet > 0 ? 'mehr' : 'weniger'} als in der Vorperiode`),
  );
}

export function zeichneKennzahlen(behaelter, daten, zustand) {
  const karten = KENNZAHLEN.map((kz) => {
    const aktuell = daten.kennzahlen.aktuell[kz.id];
    const vorher = daten.kennzahlen.vorher[kz.id];
    const aktiv = zustand.metrik === kz.id;
    const exakt = kz.exakt(aktuell);
    return h(
      'div',
      { class: 'ps-kpi ps-kpi--waehlbar', 'data-ps-tooltip': exakt, daten: { kennzahl: kz.id } },
      h(
        'div',
        { class: 'ps-kpi__label' },
        h('button', { type: 'button', class: 'ps-kpi__auswahl', 'aria-pressed': String(aktiv), daten: { metrik: kz.id } }, kz.label, versteckt(`: ${exakt}`)),
        h('button', { type: 'button', class: 'ps-info-btn', 'aria-label': `Erklärung zu ${kz.label}`, 'data-ps-tooltip': kz.erklaerung }, icon('info', 'ps-icon--s')),
      ),
      h('div', { class: 'ps-kpi__value', 'aria-hidden': 'true' }, kz.anzeige(aktuell)),
      h('div', { class: 'ps-kpi__meta' }, zustand.vergleich ? veraenderung(kz, aktuell, vorher) : null),
    );
  });
  behaelter.replaceChildren(...karten);
}

/* ---------- Tabellen ---------- */

const besucher = { titel: 'Besucher', wert: (z) => zahl(z.besucher) };
const aufrufe = { titel: 'Seitenaufrufe', wert: (z) => zahl(z.aufrufe) };
const absprung = { titel: 'Absprungrate', wert: (z) => formatProzent(z.absprung) };
const ausstieg = { titel: 'Ausstiegsrate', wert: (z) => formatProzent(z.ausstieg) };
const anteil = { titel: 'Anteil', wert: (z) => formatProzent(z.anteil) };

export const BEREICHE = {
  'seiten.top': { kopf: 'Seite', daten: (d) => d.tabellen.seiten.top, spalten: [besucher, aufrufe] },
  'seiten.einstieg': { kopf: 'Einstiegsseite', daten: (d) => d.tabellen.seiten.einstieg, spalten: [besucher, absprung] },
  'seiten.ausstieg': { kopf: 'Ausstiegsseite', daten: (d) => d.tabellen.seiten.ausstieg, spalten: [besucher, ausstieg] },
  'quellen.referrer': { kopf: 'Referrer', daten: (d) => d.tabellen.quellen.referrer, spalten: [besucher, anteil] },
  'quellen.suche': { kopf: 'Suchmaschine', daten: (d) => d.tabellen.quellen.suche, spalten: [besucher, anteil] },
  'quellen.sozial': { kopf: 'Soziales Netzwerk', daten: (d) => d.tabellen.quellen.sozial, spalten: [besucher, anteil] },
  'quellen.kampagnen': { kopf: 'Kampagne', daten: (d) => d.tabellen.quellen.kampagnen, spalten: [besucher, anteil] },
  laender: { kopf: 'Land', daten: (d) => d.tabellen.laender, spalten: [besucher, anteil] },
  'geraete.geraete': { kopf: 'Gerät', daten: (d) => d.tabellen.geraete.geraete, spalten: [besucher, anteil] },
  'geraete.browser': { kopf: 'Browser', daten: (d) => d.tabellen.geraete.browser, spalten: [besucher, anteil] },
  'geraete.os': { kopf: 'Betriebssystem', daten: (d) => d.tabellen.geraete.os, spalten: [besucher, anteil] },
  ziele: {
    kopf: 'Ziel',
    daten: (d) => d.tabellen.ziele,
    spalten: [besucher, { titel: 'Anzahl', wert: (z) => zahl(z.anzahl), nurGross: true }, { titel: 'Conversion-Rate', wert: (z) => formatProzent(z.rate) }],
    untertitel: (z) => z.art,
  },
  ereignisse: {
    kopf: 'Ereignis',
    daten: (d) => d.tabellen.ereignisse,
    spalten: [besucher, { titel: 'Anzahl', wert: (z) => zahl(z.anzahl), nurGross: true }, { titel: 'Rate', wert: (z) => formatProzent(z.rate) }],
  },
};

function zeile(z, spec, zustand, maximum) {
  const aktiv = zustand.filter.some((f) => f.typ === z.filterTyp && f.wert === z.id);
  const tr = h(
    'tr',
    { class: aktiv ? 'ist-filter' : null },
    h(
      'td',
      {},
      h(
        'button',
        { type: 'button', class: 'ps-table__link', 'aria-pressed': String(aktiv), 'data-ps-tooltip': z.name.length > 22 ? z.name : null, daten: { filterTyp: z.filterTyp, filterWert: z.id } },
        spec.untertitel ? h('span', { class: 'db-zeilenname' }, z.name) : z.name,
        spec.untertitel ? h('span', { class: 'db-zeilenart' }, spec.untertitel(z)) : null,
        h('span', { class: 'ps-visually-hidden' }, aktiv ? ' (Filter aktiv, zum Entfernen aktivieren)' : ' (als Filter setzen)'),
      ),
    ),
    ...spec.spalten.map((s) => h('td', { class: `num${s.nurGross ? ' db-nur-gross' : ''}` }, s.wert(z))),
  );
  tr.style.setProperty('--anteil', `${maximum ? Math.round((z.besucher / maximum) * 100) : 0}%`);
  return tr;
}

export function zeichneTabelle(behaelter, bereich, daten, zustand) {
  const spec = BEREICHE[bereich];
  const zeilen = spec.daten(daten).slice(0, MAX_ZEILEN);
  if (!zeilen.length) {
    behaelter.replaceChildren(
      h('div', { class: 'db-leer' }, h('p', { class: 'db-leer__titel' }, 'Keine Daten'), h('p', {}, 'Für diese Auswahl gibt es hier keine Einträge.')),
    );
    return;
  }
  const maximum = Math.max(...zeilen.map((z) => z.besucher));
  const tabelle = h(
    'table',
    { class: 'ps-table ps-table--rows ps-table--bars db-tabelle' },
    h('caption', { class: 'ps-visually-hidden' }, spec.kopf),
    h('thead', {}, h('tr', {}, h('th', { scope: 'col' }, spec.kopf), ...spec.spalten.map((s) => h('th', { scope: 'col', class: `num${s.nurGross ? ' db-nur-gross' : ''}` }, s.titel)))),
    h('tbody', {}, ...zeilen.map((z) => zeile(z, spec, zustand, maximum))),
  );
  behaelter.replaceChildren(tabelle);

  if (bereich === 'ereignisse') {
    const aktiv = zustand.filter.find((f) => f.typ === 'ereignis');
    const ereignis = aktiv ? daten.tabellen.ereignisse.find((e) => e.id === aktiv.wert) : null;
    if (ereignis) behaelter.append(eigenschaften(ereignis));
  }
}

function eigenschaften(ereignis) {
  return h(
    'div',
    { class: 'db-eigenschaften' },
    h('h3', {}, `Eigenschaften von „${ereignis.name}“`),
    ...ereignis.eigenschaften.map((e) =>
      h(
        'table',
        { class: 'ps-table db-tabelle db-tabelle--klein' },
        h('caption', { class: 'ps-visually-hidden' }, `Eigenschaft ${e.schluessel}`),
        h('thead', {}, h('tr', {}, h('th', { scope: 'col' }, e.schluessel), h('th', { scope: 'col', class: 'num' }, 'Anzahl'))),
        h('tbody', {}, ...e.werte.map((w) => h('tr', {}, h('td', {}, w.wert), h('td', { class: 'num' }, zahl(w.anzahl))))),
      ),
    ),
  );
}

/* ---------- Diagramm als Tabelle ---------- */

export function zeichneDiagrammTabelle(behaelter, daten, zustand, kz, langerText) {
  const d = daten.diagramm;
  const reihe = d.reihen[kz.id];
  const zeilen = d.etiketten.map((e, i) =>
    h(
      'tr',
      {},
      h('th', { scope: 'row' }, langerText(e)),
      h('td', { class: 'num' }, kz.anzeige(reihe.aktuell[i])),
      zustand.vergleich ? h('td', { class: 'num' }, kz.anzeige(reihe.vorher[i])) : null,
    ),
  );
  behaelter.replaceChildren(
    h(
      'table',
      { class: 'ps-table' },
      h('caption', { class: 'ps-visually-hidden' }, `${kz.label} im Zeitverlauf`),
      h('thead', {}, h('tr', {}, h('th', { scope: 'col' }, d.stundenweise ? 'Zeitpunkt' : 'Datum'), h('th', { scope: 'col', class: 'num' }, 'Aktueller Zeitraum'), zustand.vergleich ? h('th', { scope: 'col', class: 'num' }, 'Vorperiode') : null)),
      h('tbody', {}, ...zeilen),
    ),
  );
}

/* ---------- Filterleiste ---------- */

export function zeichneFilter(behaelter, zustand) {
  const chips = zustand.filter.map((f) => {
    const art = FILTERARTEN[f.typ];
    const name = filterName(zustand.site, f.typ, f.wert);
    return h(
      'span',
      { class: 'ps-chip' },
      h('span', { class: 'ps-chip__text' }, h('span', { class: 'ps-chip__art' }, `${art}:`), ` ${name}`),
      h('button', { type: 'button', class: 'ps-chip__x', 'aria-label': `Filter ${art}: ${name} entfernen`, daten: { filterEntfernen: f.typ } }, icon('x', 'ps-icon--s')),
    );
  });
  if (zustand.filter.length >= 2) {
    chips.push(h('button', { type: 'button', class: 'ps-btn ps-btn--ghost ps-btn--s', daten: { filterAlle: '' } }, 'Alle Filter entfernen'));
  }
  behaelter.hidden = chips.length === 0;
  behaelter.replaceChildren(...chips);
}

/* ---------- Sonderansichten ---------- */

const CODE = '<script defer data-site="SITE_ID" src="https://example.de/p.js"></script>';

export function zeichneLeer(behaelter, site) {
  if (site.code === '') {
    behaelter.replaceChildren(
      h(
        'section',
        { class: 'ps-card', 'aria-labelledby': 'onboarding-titel' },
        h('div', { class: 'ps-empty' }, h('span', { class: 'ps-empty__icon' }, icon('chart-line')), h('h2', { class: 'ps-empty__title', id: 'onboarding-titel' }, `Noch keine Besucher bei ${site.name}`), h('p', { class: 'ps-empty__text' }, 'Sobald die ersten Aufrufe eintreffen, erscheinen hier die Zahlen.')),
      ),
    );
    return;
  }
  const code = site.code ?? CODE.replace('SITE_ID', site.id);
  const schritt = (nr, titel, ...inhalt) =>
    h('li', { class: 'db-schritt' }, h('span', { class: 'db-schritt__nr', 'aria-hidden': 'true' }, String(nr)), h('div', {}, h('h3', {}, titel), ...inhalt));
  behaelter.replaceChildren(
    h(
      'section',
      { class: 'ps-card db-onboarding', 'aria-labelledby': 'onboarding-titel' },
      h(
        'div',
        { class: 'ps-card__body' },
        h('span', { class: 'ps-empty__icon' }, icon('chart-line')),
        h('h2', { id: 'onboarding-titel' }, `Noch keine Besucher bei ${site.name}`),
        h('p', { class: 'db-onboarding__text' }, 'Binde den Tracking-Code ein. Sobald der erste Seitenaufruf eintrifft, erscheinen hier deine Zahlen.'),
        h(
          'ol',
          { class: 'db-schritte' },
          schritt(
            1,
            'Tracking-Code kopieren',
            h('div', { class: 'db-code' }, h('pre', { tabindex: '0' }, h('code', {}, code)), h('button', { type: 'button', class: 'ps-btn ps-btn--secondary ps-btn--s', daten: { kopiere: code } }, icon('copy', 'ps-icon--s'), 'Kopieren')),
          ),
          schritt(
            2,
            'In deine Seite einfügen',
            h('p', {}, 'Setze die Zeile in den Kopfbereich (head) jeder Seite, die du messen willst.'),
          ),
          schritt(
            3,
            'Verbindung prüfen',
            h('p', {}, 'Rufe deine Seite einmal auf. Der Assistent zur Verbindungsprüfung folgt in einer späteren Phase.'),
            h('span', { class: 'ps-badge ps-badge--warning' }, h('span', { class: 'ps-badge__dot' }), 'Wartet auf Daten'),
          ),
        ),
      ),
    ),
  );
}

export function zeichneFehler(behaelter) {
  behaelter.replaceChildren(
    h(
      'section',
      { class: 'ps-card', 'aria-labelledby': 'fehler-titel' },
      h(
        'div',
        { class: 'ps-empty ps-empty--danger' },
        h('span', { class: 'ps-empty__icon' }, icon('circle-alert')),
        h('h2', { class: 'ps-empty__title', id: 'fehler-titel' }, 'Daten konnten nicht geladen werden'),
        h('p', { class: 'ps-empty__text' }, 'Die Verbindung zur Datenbank ist fehlgeschlagen. Prüfe die Zugangsdaten in der Konfiguration oder versuche es gleich noch einmal.'),
        h('div', { class: 'ps-empty__actions' }, h('button', { type: 'button', class: 'ps-btn ps-btn--primary', daten: { aktion: 'neu-laden' } }, icon('refresh-cw', 'ps-icon--s'), 'Erneut versuchen')),
      ),
    ),
  );
}

export function zeichneKeineDaten(behaelter, zustand) {
  const aktionen = [h('button', { type: 'button', class: 'ps-btn ps-btn--secondary', daten: { aktion: 'zeitraum-30t' } }, 'Letzte 30 Tage wählen')];
  if (zustand.filter.length) aktionen.unshift(h('button', { type: 'button', class: 'ps-btn ps-btn--primary', daten: { filterAlle: '' } }, 'Filter entfernen'));
  behaelter.replaceChildren(
    h(
      'section',
      { class: 'ps-card', 'aria-labelledby': 'keine-daten-titel' },
      h(
        'div',
        { class: 'ps-empty' },
        h('span', { class: 'ps-empty__icon' }, icon('calendar')),
        h('h2', { class: 'ps-empty__title', id: 'keine-daten-titel' }, 'Keine Daten in diesem Zeitraum'),
        h('p', { class: 'ps-empty__text' }, 'Für die gewählte Auswahl liegen keine Aufrufe vor. Wähle einen längeren Zeitraum oder entferne Filter.'),
        h('div', { class: 'ps-empty__actions' }, ...aktionen),
      ),
    ),
  );
}

/* ---------- Zeitangaben ---------- */

export function zeitinfo(zr, vergleich) {
  const spanne = (von, bis) => (von === bis ? formatDatum(von) : `${formatDatum(von)} – ${formatDatum(bis)}`);
  const stand = zr.bis === JETZT.datum ? ` (Stand ${JETZT.zeit} Uhr)` : '';
  const haupt = `${spanne(zr.von, zr.bis)}${stand}`;
  return vergleich ? `${haupt} · Vergleich mit ${spanne(zr.vorVon, zr.vorBis)}` : haupt;
}

export { formatDauer };
