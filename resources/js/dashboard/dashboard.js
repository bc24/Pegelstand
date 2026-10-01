// Dashboard: Steuerung der Oberfläche. Die Daten liefert quelle.js (Server-Schnittstelle oder Demo-Daten des Prototyps).
import '../main.js';
import { setzeModus } from '../theme.js';
import { toast } from '../toast.js';
import { icon } from '../util.js';
import { alleSites, aktiveBesucher, filterName, FILTERARTEN, IST_DEMO, ladeAnsicht, waehleSite } from './quelle.js';
import { Diagramm, langerText } from './diagramm.js';
import { h } from './dom.js';
import { formatDatum } from './format.js';
import { kennzahl, KENNZAHLEN } from './kennzahlen.js';
import { initKuerzel } from './kuerzel.js';
import { initPalette } from './palette.js';
import {
  BEREICHE,
  zeichneDiagrammTabelle,
  zeichneFehler,
  zeichneFilter,
  zeichneKeineDaten,
  zeichneKennzahlen,
  zeichneLeer,
  zeichneTabelle,
  zeitinfo,
} from './ansicht.js';
import { lese, schreibe } from './zustand.js';
import { JETZT, loese, ZEITRAEUME } from './zeitraum.js';

const $ = (id) => document.getElementById(id);

let z = lese(location.search);
let daten = null;
let anfrage = 0;
let tabellenAnsicht = false;

const diagramm = new Diagramm($('diagramm'), $('diagramm-ansage'));

/* ---------- Kopf, Werkzeugleiste, Filter ---------- */

function siteName() {
  return alleSites().find((s) => s.id === z.site)?.name ?? '';
}

/** Die Export-Links tragen Website, Zeitraum und Filter der aktuellen Ansicht. */
function aktualisiereExport(zr) {
  if (IST_DEMO) return;
  const parameter = new URLSearchParams({ site: z.site, von: zr.von, bis: zr.bis });
  for (const f of z.filter) parameter.append('f[]', `${f.typ}:${f.wert}`);
  for (const link of document.querySelectorAll('[data-export]')) {
    const pfad = new URL(link.getAttribute('href') ?? '', location.href).pathname;
    link.setAttribute('href', `${pfad}?${parameter}`);
  }
}

function zeichneKopf() {
  waehleSite(z.site);
  const zr = loese(z.zeitraum, z.von, z.bis);
  $('site-name').textContent = siteName();
  $('titel').textContent = siteName();
  document.title = `${siteName()} – Pegelstand`;
  const zeitraumLabel = ZEITRAEUME.find((r) => r.id === z.zeitraum)?.label ?? '';
  $('zeitraum-name').textContent =
    z.zeitraum === 'benutzerdefiniert' ? `${formatDatum(zr.von)} – ${formatDatum(zr.bis)}` : zeitraumLabel;
  for (const item of $('menue-site').querySelectorAll('[role="menuitemradio"]')) {
    item.setAttribute('aria-checked', String(item.dataset.wert === z.site));
  }
  for (const item of $('menue-zeitraum').querySelectorAll('[role="menuitemradio"]')) {
    item.setAttribute('aria-checked', String(item.dataset.wert === z.zeitraum));
  }
  $('vergleich').checked = z.vergleich;
  $('zeitinfo').textContent = zeitinfo(zr, z.vergleich);
  zeichneFilter($('filter'), z);
  aktualisiereExport(zr);
}

function baueMenues() {
  $('menue-site').replaceChildren(
    ...alleSites().map((s) =>
      h(
        'button',
        { type: 'button', class: 'ps-menu__item', role: 'menuitemradio', 'aria-checked': 'false', daten: { wert: s.id, psGruppe: 'site' } },
        icon('globe', 'ps-icon--s'),
        s.name,
        h('span', { class: 'ps-menu__check' }, icon('check', 'ps-icon--s')),
      ),
    ),
  );
  $('menue-zeitraum').replaceChildren(
    ...ZEITRAEUME.flatMap((r) => [
      r.id === 'benutzerdefiniert' ? h('div', { class: 'ps-menu__separator', role: 'separator' }) : null,
      h(
        'button',
        { type: 'button', class: 'ps-menu__item', role: 'menuitemradio', 'aria-checked': 'false', daten: { wert: r.id, psGruppe: 'zeitraum' } },
        r.label,
        r.taste ? h('kbd', {}, r.taste) : null,
        h('span', { class: 'ps-menu__check' }, icon('check', 'ps-icon--s')),
      ),
    ]),
  );
}

/* ---------- Laden und Zeichnen ---------- */

function ansage(textInhalt) {
  $('status').textContent = textInhalt;
}

async function lade() {
  const nr = ++anfrage;
  waehleSite(z.site);
  $('ansicht-normal').setAttribute('aria-busy', 'true');
  if (daten) document.documentElement.dataset.laedt = '';
  const zr = loese(z.zeitraum, z.von, z.bis);
  try {
    const ergebnis = await ladeAnsicht({ site: z.site, zeitraum: zr, filter: z.filter, zustand: z.zustand, verzoegerung: z.verzoegerung });
    if (nr !== anfrage) return;
    daten = ergebnis;
    zeichne();
  } catch {
    if (nr !== anfrage) return;
    delete document.documentElement.dataset.laedt;
    daten = null;
    zeigeSonder(() => zeichneFehler($('ansicht-sonder')));
    ansage('Die Daten konnten nicht geladen werden.');
  }
}

function zeigeSonder(fuelle) {
  $('ansicht-normal').hidden = true;
  $('ansicht-sonder').hidden = false;
  fuelle();
}

function zeichne() {
  $('ansicht-normal').setAttribute('aria-busy', 'false');
  delete document.documentElement.dataset.laedt;
  $('live').hidden = daten.leer;

  if (daten.leer) {
    zeigeSonder(() => zeichneLeer($('ansicht-sonder'), daten.site));
    ansage(`Für ${daten.site.name} liegen noch keine Daten vor.`);
    return;
  }
  if (daten.keineDaten) {
    zeigeSonder(() => zeichneKeineDaten($('ansicht-sonder'), z));
    aktualisiereLive(daten.aktive);
    ansage('Keine Daten in diesem Zeitraum.');
    return;
  }

  $('ansicht-sonder').hidden = true;
  $('ansicht-normal').hidden = false;
  zeichneKennzahlen($('kpis'), daten, z);
  zeichneDiagrammBereich();
  for (const behaelter of document.querySelectorAll('[data-bereich]')) {
    zeichneTabelle(behaelter, behaelter.dataset.bereich, daten, z);
  }
  aktualisiereLive(daten.aktive);
  ansage(`Daten für ${siteName()} aktualisiert: ${$('zeitinfo').textContent}.`);
}

function zeichneDiagrammBereich() {
  if (!daten || daten.leer || daten.keineDaten) return;
  const kz = kennzahl(z.metrik);
  const d = daten.diagramm;
  $('diagramm-titel').textContent = `${kz.label} im Zeitverlauf`;
  $('legende-vorher').hidden = !z.vergleich;
  $('diagramm').querySelector('.ps-skeleton')?.remove();
  $('diagramm').setAttribute(
    'aria-label',
    `Liniendiagramm: ${kz.label}. Mit den Pfeiltasten gehst du durch die einzelnen Werte.`,
  );
  diagramm.zeige({
    etiketten: d.etiketten,
    etikettenVorher: d.etikettenVorher,
    aktuell: d.reihen[kz.id].aktuell,
    vorher: d.reihen[kz.id].vorher,
    kennzahl: kz,
    stundenweise: d.stundenweise,
    vergleich: z.vergleich,
  });
  zeichneDiagrammTabelle($('diagramm-tabelle'), daten, z, kz, (e) => langerText(e, d.stundenweise));
  zeigeTabellenAnsicht();
}

function zeigeTabellenAnsicht() {
  $('diagramm').hidden = tabellenAnsicht;
  $('diagramm-tabelle').hidden = !tabellenAnsicht;
  $('tabellen-knopf').setAttribute('aria-pressed', String(tabellenAnsicht));
  $('tabellen-knopf-text').textContent = tabellenAnsicht ? 'Als Diagramm' : 'Als Tabelle';
}

function aktualisiereLive(n) {
  $('live-text').textContent = n === 1 ? '1 aktiver Besucher' : `${n} aktive Besucher`;
}

/* ---------- Zustandsänderungen ---------- */

function setze(patch, { neuLaden = true } = {}) {
  z = { ...z, ...patch };
  history.pushState(null, '', schreibe(z));
  zeichneKopf();
  if (neuLaden) lade();
}

function schalteFilter(typ, wert) {
  const vorhanden = z.filter.find((f) => f.typ === typ);
  let filter;
  if (vorhanden && vorhanden.wert === wert) filter = z.filter.filter((f) => f.typ !== typ);
  else filter = [...z.filter.filter((f) => f.typ !== typ), { typ, wert }];
  setze({ filter });
  ansage(
    vorhanden && vorhanden.wert === wert
      ? `Filter ${FILTERARTEN[typ]} entfernt.`
      : `Filter gesetzt: ${FILTERARTEN[typ]} ${filterName(z.site, typ, wert)}.`,
  );
}

function waehleZeitraum(id) {
  if (id === 'benutzerdefiniert') {
    oeffneBereichsdialog();
    return;
  }
  setze({ zeitraum: id, von: null, bis: null });
}

function oeffneBereichsdialog() {
  const zr = loese(z.zeitraum, z.von, z.bis);
  $('bereich-von').value = zr.von;
  $('bereich-bis').value = zr.bis;
  $('bereich-fehler').hidden = true;
  $('bereich-von').min = $('bereich-bis').min = JETZT.min;
  $('bereich-von').max = $('bereich-bis').max = JETZT.datum;
  $('bereich-dialog').showModal();
  document.documentElement.dataset.psModal = '';
}

function wendeBereichAn(e) {
  e.preventDefault();
  const von = $('bereich-von').value;
  const bis = $('bereich-bis').value;
  let fehler = '';
  if (!von || !bis) fehler = 'Gib ein Start- und ein Enddatum an.';
  else if (von > bis) fehler = 'Das Startdatum liegt nach dem Enddatum. Tausche die beiden Daten.';
  else if (von < JETZT.min) fehler = `${IST_DEMO ? 'Die Demo-Daten beginnen' : 'Die Daten beginnen'} am ${formatDatum(JETZT.min)}. Wähle ein späteres Startdatum.`;
  else if (bis > JETZT.datum) fehler = `Das Enddatum darf nicht nach dem ${formatDatum(JETZT.datum)} liegen.`;
  if (fehler) {
    $('bereich-fehler-text').textContent = fehler;
    $('bereich-fehler').hidden = false;
    return;
  }
  $('bereich-dialog').close();
  setze({ zeitraum: 'benutzerdefiniert', von, bis });
}

/* ---------- Ereignisse ---------- */

document.addEventListener('click', (e) => {
  if (!(e.target instanceof Element)) return;
  const filterZeile = e.target.closest('[data-filter-typ]');
  if (filterZeile instanceof HTMLElement) {
    schalteFilter(filterZeile.dataset.filterTyp, filterZeile.dataset.filterWert);
    return;
  }
  const entfernen = e.target.closest('[data-filter-entfernen]');
  if (entfernen instanceof HTMLElement) {
    setze({ filter: z.filter.filter((f) => f.typ !== entfernen.dataset.filterEntfernen) });
    ansage('Filter entfernt.');
    $('titel').focus();
    return;
  }
  if (e.target.closest('[data-filter-alle]')) {
    setze({ filter: [] });
    ansage('Alle Filter entfernt.');
    return;
  }
  const metrik = e.target.closest('[data-metrik]');
  if (metrik instanceof HTMLElement) {
    setze({ metrik: metrik.dataset.metrik }, { neuLaden: false });
    zeichneKennzahlen($('kpis'), daten, z);
    zeichneDiagrammBereich();
    $('kpis').querySelector(`[data-metrik="${metrik.dataset.metrik}"]`)?.focus();
    return;
  }
  const aktion = e.target.closest('[data-aktion]');
  if (aktion instanceof HTMLElement) {
    if (aktion.dataset.aktion === 'neu-laden') setze({ zustand: null });
    if (aktion.dataset.aktion === 'zeitraum-30t') setze({ zeitraum: '30t', von: null, bis: null });
    return;
  }
  const kopieren = e.target.closest('[data-kopiere]');
  if (kopieren instanceof HTMLElement) {
    navigator.clipboard
      .writeText(kopieren.dataset.kopiere ?? '')
      .then(() => toast({ typ: 'success', titel: 'Tracking-Code kopiert', text: 'Füge ihn in den Kopfbereich (head) deiner Seite ein.' }))
      .catch(() => toast({ typ: 'warning', titel: 'Kopieren nicht möglich', text: 'Markiere den Code und kopiere ihn von Hand.' }));
  }
});

document.addEventListener('ps-menu-wahl', (e) => {
  const wert = e.detail.wert;
  if (e.target.id === 'site-wahl') setze({ site: wert, filter: [] });
  else if (e.target.id === 'zeitraum-wahl') waehleZeitraum(wert);
});

$('vergleich').addEventListener('change', () => umschaltenVergleich());
$('tabellen-knopf').addEventListener('click', () => {
  tabellenAnsicht = !tabellenAnsicht;
  zeigeTabellenAnsicht();
});
$('bereich-formular').addEventListener('submit', wendeBereichAn);
window.addEventListener('popstate', () => {
  z = lese(location.search);
  zeichneKopf();
  lade();
});

function umschaltenVergleich() {
  setze({ vergleich: $('vergleich').checked }, { neuLaden: false });
  if (daten && !daten.leer && !daten.keineDaten) {
    zeichneKennzahlen($('kpis'), daten, z);
    zeichneDiagrammBereich();
    $('zeitinfo').textContent = zeitinfo(loese(z.zeitraum, z.von, z.bis), z.vergleich);
  }
}

/* ---------- Befehlspalette und Kürzel ---------- */

function aktionen() {
  const liste = [];
  for (const s of alleSites()) {
    liste.push({ gruppe: 'Sites', label: `Zu ${s.name} wechseln`, icon: 'globe', suche: 'site wechseln', fuehreAus: () => setze({ site: s.id, filter: [] }) });
  }
  for (const r of ZEITRAEUME) {
    liste.push({ gruppe: 'Zeitraum', label: r.id === 'benutzerdefiniert' ? 'Zeitraum: Benutzerdefiniert …' : `Zeitraum: ${r.label}`, icon: 'calendar', hinweis: r.taste ?? undefined, fuehreAus: () => waehleZeitraum(r.id) });
  }
  for (const k of KENNZAHLEN) {
    liste.push({ gruppe: 'Diagramm', label: `Diagramm: ${k.label}`, icon: 'chart-line', fuehreAus: () => daten && !daten.leer && !daten.keineDaten && (setze({ metrik: k.id }, { neuLaden: false }), zeichneKennzahlen($('kpis'), daten, z), zeichneDiagrammBereich()) });
  }
  liste.push(
    { gruppe: 'Gehe zu', label: 'Dashboard', icon: 'layout-dashboard', fuehreAus: () => $('titel').focus() },
    { gruppe: 'Gehe zu', label: 'Einstellungen', icon: 'settings', fuehreAus: () => toast({ typ: 'info', titel: 'Einstellungen folgen später', text: 'Dieser Bereich gehört zu Phase 6 und ist im Prototyp noch nicht enthalten.' }) },
    { gruppe: 'Darstellung', label: 'Darstellung: Hell', icon: 'sun', fuehreAus: () => setzeModus('light') },
    { gruppe: 'Darstellung', label: 'Darstellung: Dunkel', icon: 'moon', fuehreAus: () => setzeModus('dark') },
    { gruppe: 'Darstellung', label: 'Darstellung: System', icon: 'monitor', fuehreAus: () => setzeModus('system') },
    { gruppe: 'Aktionen', label: z.vergleich ? 'Vergleich mit Vorperiode ausschalten' : 'Vergleich mit Vorperiode einschalten', icon: 'chart-line', hinweis: 'V', fuehreAus: () => { $('vergleich').checked = !$('vergleich').checked; umschaltenVergleich(); } },
    { gruppe: 'Aktionen', label: 'Tastaturkürzel anzeigen', icon: 'keyboard', hinweis: '?', fuehreAus: hilfeOeffnen },
  );
  if (z.filter.length) liste.push({ gruppe: 'Aktionen', label: 'Alle Filter entfernen', icon: 'x', hinweis: 'F', fuehreAus: () => setze({ filter: [] }) });
  return liste;
}

function hilfeOeffnen() {
  $('kuerzel-dialog').showModal();
  document.documentElement.dataset.psModal = '';
}

const palette = initPalette(aktionen);
initKuerzel({
  palette,
  hilfeOeffnen,
  zeitraumPerTaste: (taste) => {
    const r = ZEITRAEUME.find((x) => x.taste === taste);
    if (r) waehleZeitraum(r.id);
  },
  vergleichUmschalten: () => {
    $('vergleich').checked = !$('vergleich').checked;
    umschaltenVergleich();
  },
  filterEntfernen: () => z.filter.length && setze({ filter: [] }),
});

/* ---------- Darstellungswechsel und Live-Anzeige ---------- */

new MutationObserver(() => diagramm.neuZeichnen()).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => diagramm.neuZeichnen());
setInterval(async () => {
  if (!daten || daten.leer) return;
  try {
    aktualisiereLive(await aktiveBesucher(z.site));
  } catch {
    // Die Anzeige bleibt beim letzten Wert.
  }
}, 30000);

baueMenues();
zeichneKopf();
lade();
