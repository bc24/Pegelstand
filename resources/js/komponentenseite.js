// Hilfsskript der internen Komponentenseite: Farbfelder, Icon-Übersicht, kleine Demos.
const GRUPPEN = {
  flaechen: ['bg', 'surface', 'surface-sunken', 'surface-raised', 'border', 'border-strong'],
  text: ['text', 'text-muted', 'text-subtle'],
  primaer: ['primary', 'primary-hover', 'primary-active', 'on-primary', 'primary-text', 'primary-subtle'],
  signal: ['signal', 'on-signal', 'signal-text', 'signal-subtle'],
  zustaende: ['success', 'success-subtle', 'warning', 'warning-subtle', 'danger', 'danger-subtle', 'focus'],
};

function alsHex(farbe) {
  const probe = document.createElement('span');
  probe.style.color = farbe;
  document.body.append(probe);
  const rgb = getComputedStyle(probe).color.match(/[\d.]+/g)?.map(Number) ?? [0, 0, 0];
  probe.remove();
  return `#${rgb.slice(0, 3).map((n) => Math.round(n).toString(16).padStart(2, '0')).join('')}`;
}

function farbfelder() {
  for (const [gruppe, namen] of Object.entries(GRUPPEN)) {
    const ziel = document.querySelector(`[data-farben="${gruppe}"]`);
    if (!ziel) continue;
    ziel.replaceChildren();
    for (const name of namen) {
      const wrap = document.createElement('div');
      wrap.className = 'kp-swatch';
      const farbe = document.createElement('div');
      farbe.className = 'kp-swatch__farbe';
      farbe.style.background = `var(--ps-${name})`;
      const titel = document.createElement('span');
      titel.className = 'kp-swatch__name';
      titel.textContent = `--ps-${name}`;
      const wert = document.createElement('span');
      wert.className = 'kp-swatch__wert';
      wert.textContent = alsHex(`var(--ps-${name})`);
      wrap.append(farbe, titel, wert);
      ziel.append(wrap);
    }
  }
}

async function iconUebersicht() {
  const ziel = document.querySelector('[data-icons]');
  if (!ziel) return;
  const basis = document.documentElement.dataset.psAssets ?? '/assets';
  const text = await (await fetch(`${basis}/icons.svg`)).text();
  const dok = new DOMParser().parseFromString(text, 'image/svg+xml');
  for (const symbol of dok.querySelectorAll('symbol')) {
    const name = symbol.id.replace('ps-icon-', '');
    const kachel = document.createElement('div');
    kachel.className = 'kp-icon';
    kachel.innerHTML = `<svg class="ps-icon" aria-hidden="true" focusable="false"><use href="${basis}/icons.svg#${symbol.id}"/></svg>`;
    const beschriftung = document.createElement('span');
    beschriftung.textContent = name;
    kachel.append(beschriftung);
    ziel.append(kachel);
  }
}

function demos() {
  document.addEventListener('click', (e) => {
    if (!(e.target instanceof Element)) return;
    const busy = e.target.closest('[data-demo-busy]');
    if (busy) {
      busy.setAttribute('aria-busy', 'true');
      setTimeout(() => busy.removeAttribute('aria-busy'), 2000);
    }
    const bewegung = e.target.closest('[data-demo-bewegung]');
    if (bewegung) document.querySelector('.kp-bewegung')?.classList.toggle('is-bewegt');
    const kpi = e.target.closest('button.ps-kpi');
    if (kpi) {
      for (const andere of document.querySelectorAll('button.ps-kpi')) andere.setAttribute('aria-pressed', String(andere === kpi));
    }
  });
}

farbfelder();
iconUebersicht();
demos();
new MutationObserver(farbfelder).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
matchMedia('(prefers-color-scheme: dark)').addEventListener('change', farbfelder);

const gemischt = document.getElementById('f-gemischt');
if (gemischt instanceof HTMLInputElement) gemischt.indeterminate = true;
