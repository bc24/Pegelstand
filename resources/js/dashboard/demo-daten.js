// Deterministische Demo-Daten für den Dashboard-Prototyp (Phase 1).
// Schnittstelle: ladeAnsicht(abfrage) liefert, was später die API liefert (Phase 5).
// Alle Namen und Zahlen sind frei erfunden und nur zur Veranschaulichung.
import { FILTERARTEN } from './filterarten.js';
import { addTage, JETZT, tageZwischen, wochentag } from './zeitraum.js';

const MIN_DATUM = '2025-01-01';

export { FILTERARTEN };

function hash(text) {
  let h = 2166136261;
  for (let i = 0; i < text.length; i += 1) {
    h ^= text.charCodeAt(i);
    h = Math.imul(h, 16777619);
  }
  return h >>> 0;
}

function zufall(saat) {
  let a = hash(saat);
  return () => {
    a = (a + 0x6d2b79f5) | 0;
    let t = Math.imul(a ^ (a >>> 15), 1 | a);
    t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
  };
}

/* ---------- Stammdaten ---------- */

const S = (id, w, e, x, a = 1, p = 1, d = 1) => ({ id, name: id, w, e, x, a, p, d });
const Z = (id, name, w, a = 1, p = 1, d = 1) => ({ id, name, w, a, p, d });

const SEITENSAETZE = {
  produkt: [
    S('/', 30, 36, 14, 0.9, 1.1, 1),
    S('/preise', 14, 6, 14, 0.7, 1.2, 1.3),
    S('/funktionen', 12, 8, 9, 0.8, 1.3, 1.2),
    S('/blog/datenschutz-ohne-cookies', 11, 22, 17, 1.5, 0.7, 1.1),
    S('/blog/shared-hosting-tipps', 8, 15, 12, 1.5, 0.7, 1),
    S('/hilfe/installation', 7, 4, 10, 0.9, 1.4, 1.6),
    S('/downloads', 6, 3, 8, 0.8, 1, 0.9),
    S('/kontakt', 4, 2, 7, 1.1, 0.8, 0.8),
    S('/ueber-uns', 3, 2, 4, 1.2, 0.9, 0.9),
    S('/impressum', 2, 1, 4, 1.4, 0.5, 0.4),
    S('/datenschutz', 2, 1, 1, 1.4, 0.5, 0.6),
  ],
  firma: [
    S('/', 34, 42, 16, 0.9, 1.1, 1),
    S('/leistungen', 16, 10, 12, 0.7, 1.3, 1.2),
    S('/referenzen', 11, 8, 10, 0.8, 1.2, 1.3),
    S('/team', 7, 5, 7, 1, 1, 1),
    S('/kontakt', 9, 6, 20, 0.9, 0.9, 0.9),
    S('/karriere', 8, 12, 14, 1.2, 0.9, 1),
    S('/impressum', 3, 1, 6, 1.4, 0.5, 0.4),
    S('/datenschutz', 2, 1, 2, 1.4, 0.5, 0.6),
  ],
  verein: [
    S('/', 32, 38, 15, 0.9, 1.1, 1),
    S('/termine', 18, 14, 14, 0.8, 1.2, 1.1),
    S('/neuigkeiten', 14, 18, 14, 1.2, 0.9, 1),
    S('/galerie', 9, 8, 11, 0.9, 1.5, 1.4),
    S('/mitglied-werden', 7, 5, 15, 0.8, 1.1, 1.2),
    S('/verein/vorstand', 5, 3, 6, 1, 1, 0.9),
    S('/kontakt', 5, 3, 10, 1, 0.9, 0.9),
    S('/impressum', 2, 1, 5, 1.4, 0.5, 0.4),
  ],
};

export const SITES = [
  { id: 'beispiel-de', name: 'beispiel.de', basis: 420, seiten: 'produkt' },
  { id: 'meine-firma-de', name: 'meine-firma.de', basis: 96, seiten: 'firma' },
  { id: 'verein-bremen-de', name: 'verein-bremen.de', basis: 38, seiten: 'verein' },
  { id: 'neue-seite-de', name: 'neue-seite.de', basis: 0, seiten: 'firma', leer: true },
];

const QUELLEN = {
  referrer: {
    anteil: 38,
    zeilen: [
      Z('direkt', 'Direkt / keine Angabe', 30, 1.1, 0.95),
      Z('github.com', 'github.com', 2.5, 0.8, 1.2),
      Z('heise.de', 'heise.de', 3, 1.3, 0.85),
      Z('golem.de', 'golem.de', 2, 1.3, 0.85),
      Z('t3n.de', 't3n.de', 1.5, 1.2, 0.9),
      Z('de.wikipedia.org', 'de.wikipedia.org', 1.2, 1.4, 0.8),
      Z('stackoverflow.com', 'stackoverflow.com', 1, 1.1, 1.1),
      Z('reddit.com', 'reddit.com', 0.9, 1.5, 0.7),
    ],
  },
  suche: {
    anteil: 40,
    zeilen: [
      Z('google', 'Google', 100, 1, 1),
      Z('bing', 'Bing', 9, 1, 1),
      Z('duckduckgo', 'DuckDuckGo', 7, 0.95, 1.05),
      Z('ecosia', 'Ecosia', 4, 1.05, 0.95),
      Z('startpage', 'Startpage', 1.5, 0.9, 1.1),
      Z('brave', 'Brave Search', 0.7, 0.9, 1.1),
    ],
  },
  sozial: {
    anteil: 14,
    zeilen: [
      Z('facebook', 'Facebook', 30, 1.35, 0.7),
      Z('instagram', 'Instagram', 22, 1.4, 0.65),
      Z('linkedin', 'LinkedIn', 20, 1.05, 1),
      Z('youtube', 'YouTube', 10, 1.2, 0.9),
      Z('xing', 'XING', 6, 1, 1),
      Z('x', 'X (Twitter)', 5, 1.4, 0.7),
      Z('whatsapp', 'WhatsApp', 4, 1.3, 0.75),
      Z('mastodon', 'Mastodon', 3, 1.1, 0.95),
    ],
  },
  kampagnen: {
    anteil: 8,
    zeilen: [
      Z('newsletter-september', 'newsletter-september', 5, 0.7, 1.3),
      Z('herbst-aktion', 'herbst-aktion', 3, 0.85, 1.15),
      Z('linkedin-ads-q3', 'linkedin-ads-q3', 2, 1.2, 0.9),
      Z('partner-heise', 'partner-heise', 1, 1, 1),
    ],
  },
};

const LAENDER = [
  Z('DE', 'Deutschland', 71),
  Z('AT', 'Österreich', 8.5),
  Z('CH', 'Schweiz', 6.5),
  Z('US', 'USA', 3.2, 1.15, 0.85),
  Z('NL', 'Niederlande', 2.1),
  Z('GB', 'Vereinigtes Königreich', 1.4),
  Z('FR', 'Frankreich', 1.2),
  Z('PL', 'Polen', 1),
  Z('XX', 'Sonstige', 5.1),
];

const GERAETE = [Z('desktop', 'Desktop', 52, 0.9, 1.2, 1.25), Z('smartphone', 'Smartphone', 43, 1.12, 0.85, 0.8), Z('tablet', 'Tablet', 5)];
const BROWSER = [Z('chrome', 'Chrome', 56), Z('safari', 'Safari', 22, 1.1, 0.9), Z('firefox', 'Firefox', 9, 0.95, 1.05), Z('edge', 'Edge', 8), Z('samsung', 'Samsung Internet', 3, 1.1, 0.9), Z('sonstige-browser', 'Sonstige', 2)];
const SYSTEME = [Z('windows', 'Windows', 34, 0.95, 1.1, 1.1), Z('android', 'Android', 29, 1.1, 0.85, 0.85), Z('ios', 'iOS', 20, 1.1, 0.9, 0.85), Z('macos', 'macOS', 11, 0.9, 1.15, 1.1), Z('linux', 'Linux', 4, 0.85, 1.2, 1.2), Z('sonstige-os', 'Sonstige', 2)];

const ZIELE = [
  { id: 'kontakt-gesendet', name: 'Kontaktformular gesendet', art: 'Seite /danke', w: 0.021 },
  { id: 'newsletter', name: 'Newsletter-Anmeldung', art: 'Ereignis', w: 0.034 },
  { id: 'tarif-gewaehlt', name: 'Tarif gewählt', art: 'Ereignis', w: 0.012 },
];

const EREIGNISSE = [
  { id: 'download', name: 'Download', w: 0.047, eig: { datei: [['preisliste.pdf', 58], ['installationsanleitung.pdf', 42]] } },
  { id: 'ausgehender-link', name: 'Ausgehender Link', w: 0.083, eig: { ziel: [['github.com', 46], ['panzerit.de', 32], ['heise.de', 22]] } },
  { id: 'newsletter', name: 'Newsletter-Anmeldung', w: 0.034, eig: { quelle: [['Fußzeile', 52], ['Blogbeitrag', 48]] } },
  { id: 'kontakt-klick', name: 'Kontakt-Klick', w: 0.026, eig: { art: [['E-Mail', 60], ['Telefon', 40]] } },
  { id: 'anmeldung', name: 'Anmeldung', w: 0.018, eig: { tarif: [['Basis', 50], ['Pro', 35], ['Team', 15]] } },
  { id: '404', name: '404-Seite', w: 0.009, eig: { pfad: [['/alt/preise', 44], ['/blog/alt', 34], ['/wp-login.php', 22]] } },
];

/* ---------- Tageswerte ---------- */

const WOCHE = [1.05, 1.08, 1.06, 1.03, 0.95, 0.72, 0.68];
const STUNDEN = [0.6, 0.4, 0.3, 0.25, 0.25, 0.4, 0.9, 1.9, 3.6, 5.2, 6, 5.8, 5.2, 5.4, 6, 6.2, 5.8, 5.2, 4.8, 5.4, 5.9, 5, 3.4, 1.8];
const STUNDEN_SUMME = STUNDEN.reduce((s, v) => s + v, 0);

function kumulativ(bisStunde) {
  return STUNDEN.slice(0, bisStunde + 1).reduce((s, v) => s + v, 0) / STUNDEN_SUMME;
}

function siteById(id) {
  return SITES.find((s) => s.id === id) ?? SITES[0];
}

function tagWerte(site, iso) {
  const r = zufall(`${site.id}|${iso}`);
  const index = tageZwischen(MIN_DATUM, iso);
  const saison = 1 + 0.08 * Math.sin((2 * Math.PI * index) / 365);
  const spitze = hash(`${site.id}${iso}`) % 47 === 0 ? 1.6 : 1;
  const faktor = WOCHE[wochentag(iso)] * (1 + 0.0009 * index) * saison * (0.88 + r() * 0.24) * spitze;
  const teil = iso === JETZT.datum ? kumulativ(JETZT.stunde) : 1;
  const besucher = site.basis * faktor * teil;
  const seitenProBesuch = 1.5 + r() * 0.35;
  const besuche = besucher * 1.07;
  return {
    besucher,
    aufrufe: besuche * seitenProBesuch,
    besuche,
    absprungRate: 0.4 + (r() - 0.5) * 0.08,
    dauerMittel: 150 + r() * 50,
  };
}

function stundenWerte(site, iso, stunde) {
  const tag = tagWerte(site, iso);
  const vollerTag = iso === JETZT.datum ? tag.besucher / kumulativ(JETZT.stunde) : tag.besucher;
  const r = zufall(`${site.id}|${iso}|${stunde}`);
  const anteil = (STUNDEN[stunde] / STUNDEN_SUMME) * (0.85 + r() * 0.3);
  const faktor = (vollerTag * anteil) / (tag.besucher || 1);
  return { ...tag, besucher: tag.besucher * faktor, aufrufe: tag.aufrufe * faktor, besuche: tag.besuche * faktor };
}

/* ---------- Filter ---------- */

function zeilenFuer(site, typ) {
  switch (typ) {
    case 'seite':
      return normiere(SEITENSAETZE[site.seiten].map((s) => ({ ...s, anteilGewicht: s.w })));
    case 'einstieg':
      return normiere(SEITENSAETZE[site.seiten].map((s) => ({ ...s, anteilGewicht: s.e })));
    case 'ausstieg':
      return normiere(SEITENSAETZE[site.seiten].map((s) => ({ ...s, anteilGewicht: s.x })));
    case 'quelle':
      return normiere(flacheQuellen(['referrer', 'suche', 'sozial', 'kampagnen'])).filter((z) => z.gruppe !== 'kampagnen');
    case 'kampagne':
      return normiere(flacheQuellen(['referrer', 'suche', 'sozial', 'kampagnen'])).filter((z) => z.gruppe === 'kampagnen');
    case 'land':
      return normiere(LAENDER.map((z) => ({ ...z, anteilGewicht: z.w })));
    case 'geraet':
      return normiere(GERAETE.map((z) => ({ ...z, anteilGewicht: z.w })));
    case 'browser':
      return normiere(BROWSER.map((z) => ({ ...z, anteilGewicht: z.w })));
    case 'os':
      return normiere(SYSTEME.map((z) => ({ ...z, anteilGewicht: z.w })));
    case 'ziel':
      return ZIELE.map((z) => ({ ...z, anteil: z.w, a: 1, p: 1, d: 1 }));
    case 'ereignis':
      return EREIGNISSE.map((z) => ({ ...z, anteil: z.w, a: 1, p: 1, d: 1 }));
    default:
      return [];
  }
}

function flacheQuellen(gruppen) {
  return gruppen.flatMap((g) => {
    const summe = QUELLEN[g].zeilen.reduce((s, z) => s + z.w, 0);
    return QUELLEN[g].zeilen.map((z) => ({ ...z, anteilGewicht: (QUELLEN[g].anteil * z.w) / summe, gruppe: g }));
  });
}

function normiere(zeilen) {
  const summe = zeilen.reduce((s, z) => s + z.anteilGewicht, 0);
  return zeilen.map((z) => ({ ...z, anteil: z.anteilGewicht / summe }));
}

function wirkung(site, filter) {
  const w = { anteil: 1, a: 1, p: 1, d: 1 };
  for (const f of filter) {
    const zeile = zeilenFuer(site, f.typ).find((z) => z.id === f.wert);
    if (!zeile) continue;
    w.anteil *= zeile.anteil;
    w.a *= zeile.a ?? 1;
    w.p *= zeile.p ?? 1;
    w.d *= zeile.d ?? 1;
  }
  return w;
}

/* ---------- Zeitreihen ---------- */

function summiere(punkte) {
  const t = { besucher: 0, aufrufe: 0, besuche: 0, bounces: 0, dauerSumme: 0 };
  for (const p of punkte) {
    t.besucher += p.besucher;
    t.aufrufe += p.aufrufe;
    t.besuche += p.besuche;
    t.bounces += p.bounces;
    t.dauerSumme += p.dauerSumme;
  }
  return t;
}

function punkt(werte, w) {
  const besucher = Math.round(werte.besucher * w.anteil);
  const besuche = Math.round(werte.besuche * w.anteil);
  const aufrufe = Math.max(besuche, Math.round(werte.aufrufe * w.anteil * w.p));
  const rate = Math.min(0.97, werte.absprungRate * w.a);
  return {
    besucher,
    aufrufe,
    besuche,
    bounces: Math.round(besuche * rate),
    dauerSumme: besuche * werte.dauerMittel * w.d,
  };
}

function punkteFuer(site, zr, von, bis, w) {
  const liste = [];
  const etiketten = [];
  if (zr.stundenweise) {
    for (let tag = von; tag <= bis; tag = addTage(tag, 1)) {
      const letzte = tag === JETZT.datum ? JETZT.stunde : 23;
      for (let h = 0; h <= letzte; h += 1) {
        liste.push(punkt(stundenWerte(site, tag, h), w));
        etiketten.push({ datum: tag, stunde: h });
      }
    }
  } else {
    for (let tag = von; tag <= bis; tag = addTage(tag, 1)) {
      liste.push(punkt(tagWerte(site, tag), w));
      etiketten.push({ datum: tag });
    }
  }
  return { liste, etiketten };
}

function metriken(p) {
  return {
    besucher: p.besucher,
    aufrufe: p.aufrufe,
    seitenProBesuch: p.besuche ? p.aufrufe / p.besuche : 0,
    absprungrate: p.besuche ? (p.bounces / p.besuche) * 100 : 0,
    dauer: p.besuche ? p.dauerSumme / p.besuche : 0,
  };
}

/* ---------- Verteilung auf Tabellenzeilen ---------- */

function verteile(gesamt, zeilen, saat) {
  const gewichte = zeilen.map((z) => z.anteil * (0.88 + zufall(`${saat}|${z.id}`)() * 0.24));
  const summe = gewichte.reduce((s, v) => s + v, 0) || 1;
  const roh = gewichte.map((g) => (g / summe) * gesamt);
  const ganze = roh.map(Math.floor);
  let rest = gesamt - ganze.reduce((s, v) => s + v, 0);
  [...roh.keys()]
    .sort((a, b) => roh[b] - ganze[b] - (roh[a] - ganze[a]))
    .forEach((i) => {
      if (rest > 0) {
        ganze[i] += 1;
        rest -= 1;
      }
    });
  return ganze;
}

function liste(site, typ, gesamt, aufrufeGesamt, filter, saat, gruppen) {
  const aktiv = filter.find((f) => f.typ === typ);
  let zeilen = zeilenFuer(site, typ);
  if (gruppen) zeilen = zeilen.filter((z) => gruppen.includes(z.gruppe));
  if (aktiv) zeilen = zeilen.filter((z) => z.id === aktiv.wert);
  if (!zeilen.length || gesamt === 0) return [];
  // Quellen-Gruppen decken nur einen Teil aller Besucher ab, die Anteile gelten aber immer bezogen auf alle.
  const summe = zeilen.reduce((s, z) => s + z.anteil, 0);
  const teilGesamt = aktiv ? gesamt : Math.round(gesamt * summe);
  const normal = zeilen.map((z) => ({ ...z, anteil: z.anteil / summe }));
  const zaehler = aktiv ? [gesamt] : verteile(teilGesamt, normal, saat);
  const AM_ENDE = ['XX', 'sonstige-browser', 'sonstige-os'];
  return normal
    .map((z, i) => {
      const besucher = zaehler[i];
      const rZ = zufall(`${saat}|${typ}|${z.id}|x`);
      return {
        id: z.id,
        name: z.name,
        filterTyp: typ,
        besucher,
        anteil: (besucher / gesamt) * 100,
        aufrufe: Math.round(Math.max(besucher, (aufrufeGesamt / gesamt) * besucher * (z.p ?? 1) * (0.95 + rZ() * 0.1))),
        absprung: Math.min(97, Math.max(5, (0.4 * (z.a ?? 1) + (rZ() - 0.5) * 0.06) * 100)),
        ausstieg: (0.3 + rZ() * 0.25) * 100,
      };
    })
    .filter((z) => z.besucher > 0)
    .sort((a, b) => AM_ENDE.includes(a.id) - AM_ENDE.includes(b.id) || b.besucher - a.besucher);
}

function zieleUndEreignisse(site, gesamt, filter, saat) {
  const rate = (id, w) => Math.min(1, w * (0.75 + zufall(`${saat}|${id}`)() * 0.5));
  const zFilter = filter.find((f) => f.typ === 'ziel');
  const eFilter = filter.find((f) => f.typ === 'ereignis');
  const ziele = ZIELE.filter((z) => !zFilter || z.id === zFilter.wert).map((z) => {
    const besucher = Math.round(gesamt * rate(z.id, z.w));
    return {
      id: z.id,
      name: z.name,
      art: z.art,
      filterTyp: 'ziel',
      besucher,
      anzahl: Math.round(besucher * 1.1),
      rate: gesamt ? (besucher / gesamt) * 100 : 0,
    };
  });
  const ereignisse = EREIGNISSE.filter((e) => !eFilter || e.id === eFilter.wert).map((e) => {
    const besucher = Math.round(gesamt * rate(e.id, e.w));
    const anzahl = Math.round(besucher * (1.05 + zufall(`${saat}|${e.id}|n`)() * 0.4));
    return {
      id: e.id,
      name: e.name,
      filterTyp: 'ereignis',
      besucher,
      anzahl,
      rate: gesamt ? (besucher / gesamt) * 100 : 0,
      eigenschaften: Object.entries(e.eig).map(([schluessel, werte]) => {
        const summe = werte.reduce((s, [, g]) => s + g, 0);
        return {
          schluessel,
          werte: werte.map(([wert, g]) => ({ wert, anzahl: Math.round((anzahl * g) / summe) })),
        };
      }),
    };
  });
  return { ziele: ziele.filter((z) => z.besucher > 0), ereignisse: ereignisse.filter((e) => e.besucher > 0) };
}

/* ---------- Öffentliche Schnittstelle ---------- */

export const alleSites = () => SITES;

/** Anzeigename eines Filterwerts, z. B. „DE“ wird zu „Deutschland“. */
export function filterName(siteId, typ, wert) {
  return zeilenFuer(siteById(siteId), typ).find((z) => z.id === wert)?.name ?? wert;
}

export function filterGueltig(siteId, typ, wert) {
  return zeilenFuer(siteById(siteId), typ).some((z) => z.id === wert);
}


export function aktiveBesucher(siteId) {
  const site = siteById(siteId);
  if (site.leer) return 0;
  const r = zufall(`${site.id}|live|${Math.floor(Date.now() / 30000)}`)();
  return Math.max(1, Math.round((site.basis / 45) * (0.75 + r * 0.5)));
}

const warte = (ms) => new Promise((ok) => setTimeout(ok, ms));

/**
 * @param {{site: string, zeitraum: object, filter: Array<{typ: string, wert: string}>, zustand?: string, verzoegerung?: number}} abfrage
 */
export async function ladeAnsicht({ site: siteId, zeitraum, filter, zustand, verzoegerung = 500 }) {
  await warte(verzoegerung);
  if (zustand === 'laden') return new Promise(() => {});
  if (zustand === 'fehler') throw new Error('Demo-Fehler');
  const site = siteById(siteId);
  if (site.leer || zustand === 'leer') return { leer: true, site };

  const w = zustand === 'keine-daten' ? { anteil: 0, a: 1, p: 1, d: 1 } : wirkung(site, filter);
  const aktuell = punkteFuer(site, zeitraum, zeitraum.von, zeitraum.bis, w);
  const vorher = punkteFuer(site, zeitraum, zeitraum.vorVon, zeitraum.vorBis, w);
  const summeA = summiere(aktuell.liste);
  const summeV = summiere(vorher.liste);

  const reihen = {};
  for (const name of ['besucher', 'aufrufe', 'seitenProBesuch', 'absprungrate', 'dauer']) {
    reihen[name] = {
      aktuell: aktuell.liste.map((p) => metriken(p)[name]),
      vorher: vorher.liste.map((p) => metriken(p)[name]),
    };
  }

  const saat = `${site.id}|${zeitraum.von}|${zeitraum.bis}|${filter.map((f) => `${f.typ}=${f.wert}`).join(',')}`;
  const gesamt = summeA.besucher;
  const aufrufe = summeA.aufrufe;
  const tabellen = {
    seiten: {
      top: liste(site, 'seite', gesamt, aufrufe, filter, saat),
      einstieg: liste(site, 'einstieg', gesamt, aufrufe, filter, saat),
      ausstieg: liste(site, 'ausstieg', gesamt, aufrufe, filter, saat),
    },
    quellen: {
      referrer: liste(site, 'quelle', gesamt, aufrufe, filter, saat, ['referrer']),
      suche: liste(site, 'quelle', gesamt, aufrufe, filter, saat, ['suche']),
      sozial: liste(site, 'quelle', gesamt, aufrufe, filter, saat, ['sozial']),
      kampagnen: liste(site, 'kampagne', gesamt, aufrufe, filter, saat),
    },
    laender: liste(site, 'land', gesamt, aufrufe, filter, saat),
    geraete: {
      geraete: liste(site, 'geraet', gesamt, aufrufe, filter, saat),
      browser: liste(site, 'browser', gesamt, aufrufe, filter, saat),
      os: liste(site, 'os', gesamt, aufrufe, filter, saat),
    },
    ...zieleUndEreignisse(site, gesamt, filter, saat),
  };

  return {
    leer: false,
    site,
    keineDaten: gesamt === 0,
    zeitraum,
    kennzahlen: { aktuell: metriken(summeA), vorher: metriken(summeV) },
    diagramm: {
      stundenweise: zeitraum.stundenweise,
      etiketten: aktuell.etiketten,
      etikettenVorher: vorher.etiketten,
      reihen,
    },
    tabellen,
    aktive: aktiveBesucher(site.id),
  };
}
