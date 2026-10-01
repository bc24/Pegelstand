// Zeitreihen-Diagramm auf Basis von uPlot (MIT). Farben kommen aus den Design-Tokens.
import uPlot from 'uplot';
import { h, versteckt } from './dom.js';
import { formatDatumKurz, formatDatumMitTag } from './format.js';

const SCHRIFT = '12px "Inter Variable", system-ui, sans-serif';

function tokenFarbe(name) {
  const probe = document.createElement('span');
  probe.style.color = `var(--ps-${name})`;
  document.body.append(probe);
  const farbe = getComputedStyle(probe).color;
  probe.remove();
  return farbe;
}

function mitAlpha(rgb, alpha) {
  const [r, g, b] = rgb.match(/[\d.]+/g) ?? [0, 0, 0];
  return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

function etikettText(e, stundenweise, mehrereTage) {
  if (!stundenweise) return formatDatumKurz(e.datum);
  if (e.stunde === 0 && mehrereTage) return formatDatumKurz(e.datum);
  return `${e.stunde} Uhr`;
}

export function langerText(e, stundenweise) {
  const tag = formatDatumMitTag(e.datum);
  return stundenweise ? `${tag}, ${e.stunde} Uhr` : tag;
}

export class Diagramm {
  /**
   * @param {HTMLElement} huelle Behälter mit fester Höhe (per CSS)
   * @param {HTMLElement} ansage Live-Bereich für Tastaturbedienung
   */
  constructor(huelle, ansage) {
    this.huelle = huelle;
    this.ansage = ansage;
    this.plot = null;
    this.letzte = null;
    this.index = null;

    this.groesse = new ResizeObserver(() => {
      if (this.plot) this.plot.setSize({ width: this.huelle.clientWidth, height: this.huelle.clientHeight });
    });
    this.groesse.observe(huelle);

    huelle.addEventListener('keydown', (e) => this.taste(e));
    huelle.addEventListener('blur', () => this.verberge());
  }

  zeige(parameter) {
    this.letzte = parameter;
    this.baue();
  }

  neuZeichnen() {
    if (this.letzte) this.baue();
  }

  baue() {
    const { etiketten, etikettenVorher, aktuell, vorher, kennzahl, stundenweise, vergleich } = this.letzte;
    this.plot?.destroy();
    this.huelle.querySelector('.u-wrap')?.remove();

    const n = etiketten.length;
    const mehrereTage = new Set(etiketten.map((e) => e.datum)).size > 1;
    const primaer = tokenFarbe('primary');
    const gedaempft = tokenFarbe('text-muted');
    const dezent = tokenFarbe('text-subtle');
    const gitter = tokenFarbe('border');
    const signal = tokenFarbe('signal');
    const flaeche = tokenFarbe('surface');
    const breite = this.huelle.clientWidth;

    const spalten = [
      Array.from({ length: n }, (_, i) => i),
      aktuell,
      vergleich ? vorher : vorher.map(() => null),
    ];

    const optionen = {
      width: breite,
      height: this.huelle.clientHeight,
      padding: [12, 16, 0, 0],
      legend: { show: false },
      cursor: {
        x: true,
        y: false,
        drag: { x: false, y: false, setScale: false },
        points: { size: 9, width: 2, stroke: () => signal, fill: () => flaeche },
      },
      scales: {
        x: { time: false },
        y: { range: (_u, _min, max) => [0, Math.max(max * 1.12, 1)] },
      },
      axes: [
        {
          stroke: gedaempft,
          font: SCHRIFT,
          size: 32,
          gap: 6,
          grid: { show: false },
          ticks: { show: false },
          border: { show: true, stroke: gitter, width: 1 },
          splits: (u) => {
            const px = u.bbox.width / devicePixelRatio;
            const maximal = Math.max(2, Math.floor(px / (stundenweise ? 56 : 64)));
            let schritt = Math.max(1, Math.ceil(n / maximal));
            if (stundenweise) schritt = [1, 2, 3, 4, 6, 8, 12, 24].find((s) => s >= schritt) ?? 24;
            const stellen = [];
            for (let i = 0; i < n; i += schritt) stellen.push(i);
            return stellen;
          },
          values: (_u, stellen) => stellen.map((i) => etikettText(etiketten[i], stundenweise, mehrereTage)),
        },
        {
          stroke: gedaempft,
          font: SCHRIFT,
          size: 56,
          gap: 8,
          grid: { stroke: gitter, width: 1 },
          ticks: { show: false },
          border: { show: false },
          values: (_u, stellen) => stellen.map((v) => kennzahl.achse(v)),
        },
      ],
      series: [
        {},
        {
          label: 'Aktueller Zeitraum',
          stroke: primaer,
          width: 2,
          points: { show: n <= 16, size: 5, fill: primaer, stroke: primaer },
          fill: (u) => {
            const g = u.ctx.createLinearGradient(0, u.bbox.top, 0, u.bbox.top + u.bbox.height);
            g.addColorStop(0, mitAlpha(primaer, 0.26));
            g.addColorStop(1, mitAlpha(primaer, 0));
            return g;
          },
        },
        {
          label: 'Vorperiode',
          stroke: dezent,
          width: 1.5,
          dash: [6, 5],
          points: { show: false },
        },
      ],
      hooks: {
        setCursor: [(u) => this.aktualisiere(u)],
      },
    };

    this.etiketten = etiketten;
    this.etikettenVorher = etikettenVorher;
    this.kennzahl = kennzahl;
    this.stundenweise = stundenweise;
    this.vergleich = vergleich;
    this.aktuell = aktuell;
    this.vorher = vorher;

    this.plot = new uPlot(optionen, spalten, this.huelle);
    this.tipp = h('div', { class: 'db-diagrammtipp', hidden: true, role: 'presentation' });
    this.plot.over.append(this.tipp);
  }

  aktualisiere(u) {
    const i = u.cursor.idx;
    if (i == null || u.cursor.left < 0) {
      this.tipp.hidden = true;
      return;
    }
    const zeilen = [
      h('div', { class: 'db-diagrammtipp__kopf' }, langerText(this.etiketten[i], this.stundenweise)),
      h(
        'div',
        { class: 'db-diagrammtipp__zeile' },
        h('i', { class: 'db-diagrammtipp__marke' }),
        h('span', {}, this.kennzahl.label ?? ''),
        h('strong', {}, this.aktuell[i] == null ? '–' : this.kennzahl.anzeige(this.aktuell[i])),
      ),
    ];
    if (this.vergleich && this.vorher[i] != null) {
      zeilen.push(
        h(
          'div',
          { class: 'db-diagrammtipp__zeile db-diagrammtipp__zeile--vorher' },
          h('i', { class: 'db-diagrammtipp__marke db-diagrammtipp__marke--gestrichelt' }),
          h('span', {}, langerText(this.etikettenVorher[i], this.stundenweise)),
          h('strong', {}, this.kennzahl.anzeige(this.vorher[i])),
        ),
      );
    }
    this.tipp.replaceChildren(...zeilen);
    this.tipp.hidden = false;
    const x = u.cursor.left;
    const rechts = x > u.over.clientWidth - this.tipp.offsetWidth - 24;
    this.tipp.style.transform = `translate(${rechts ? x - this.tipp.offsetWidth - 14 : x + 14}px, 8px)`;
  }

  taste(e) {
    if (!this.plot) return;
    const n = this.etiketten.length;
    let i = this.index;
    if (e.key === 'ArrowRight') i = i == null ? 0 : Math.min(n - 1, i + 1);
    else if (e.key === 'ArrowLeft') i = i == null ? n - 1 : Math.max(0, i - 1);
    else if (e.key === 'Home') i = 0;
    else if (e.key === 'End') i = n - 1;
    else if (e.key === 'Escape') {
      this.verberge();
      return;
    } else return;
    e.preventDefault();
    this.index = i;
    this.plot.setCursor({ left: this.plot.valToPos(i, 'x'), top: 20 });
    this.ansage.replaceChildren(
      versteckt(`${langerText(this.etiketten[i], this.stundenweise)}: ${this.kennzahl.exakt(this.aktuell[i])}`),
    );
  }

  verberge() {
    this.index = null;
    this.plot?.setCursor({ left: -10, top: -10 });
    this.ansage.replaceChildren();
  }

  zerstoere() {
    this.groesse.disconnect();
    this.plot?.destroy();
  }
}
