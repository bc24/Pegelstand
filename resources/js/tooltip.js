import { neueId } from './util.js';

const VERZOEGERUNG = 250;
let tipp = null;
let ausloeser = null;
let timer = 0;

function element() {
  if (!tipp) {
    tipp = document.createElement('div');
    tipp.className = 'ps-tooltip';
    tipp.id = neueId('ps-tooltip');
    tipp.setAttribute('role', 'tooltip');
    tipp.hidden = true;
    document.body.append(tipp);
  }
  return tipp;
}

function zeige(ziel) {
  const text = ziel.dataset.psTooltip;
  if (!text) return;
  clearTimeout(timer);
  const t = element();
  t.textContent = text;
  t.hidden = false;
  ausloeser = ziel;
  ziel.setAttribute('aria-describedby', t.id);
  positioniere();
}

function positioniere() {
  if (!tipp || !ausloeser) return;
  const t = tipp;
  const r = ausloeser.getBoundingClientRect();
  if (r.bottom < 0 || r.top > window.innerHeight) {
    verberge();
    return;
  }
  const b = t.getBoundingClientRect();
  let oben = r.top - b.height - 8;
  if (oben < 8) oben = r.bottom + 8;
  let links = r.left + r.width / 2 - b.width / 2;
  links = Math.max(8, Math.min(links, window.innerWidth - b.width - 8));
  t.style.top = `${oben}px`;
  t.style.left = `${links}px`;
}

function verberge() {
  clearTimeout(timer);
  if (tipp) tipp.hidden = true;
  ausloeser?.removeAttribute('aria-describedby');
  ausloeser = null;
}

function ziel(e) {
  return e.target instanceof Element ? e.target.closest('[data-ps-tooltip]') : null;
}

export function initTooltip() {
  document.addEventListener('mouseover', (e) => {
    const z = ziel(e);
    if (z instanceof HTMLElement && z !== ausloeser) {
      clearTimeout(timer);
      timer = window.setTimeout(() => zeige(z), VERZOEGERUNG);
    }
  });
  document.addEventListener('mouseout', (e) => {
    const z = ziel(e);
    if (z && !(e.relatedTarget instanceof Node && z.contains(e.relatedTarget))) verberge();
  });
  document.addEventListener('focusin', (e) => {
    const z = ziel(e);
    if (z instanceof HTMLElement) zeige(z);
  });
  document.addEventListener('focusout', verberge);
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && ausloeser) verberge();
  });
  document.addEventListener('scroll', positioniere, true);
  document.addEventListener('pointerdown', (e) => {
    if (ausloeser && !ziel(e)) verberge();
  });
}
