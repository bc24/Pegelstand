import { icon } from './util.js';

const ICONS = { info: 'info', success: 'circle-check', warning: 'triangle-alert', danger: 'circle-alert' };
const MAX_SICHTBAR = 3;

function container() {
  let c = document.querySelector('.ps-toasts');
  if (!c) {
    c = document.createElement('div');
    c.className = 'ps-toasts';
    c.setAttribute('role', 'region');
    c.setAttribute('aria-label', 'Benachrichtigungen');
    c.setAttribute('aria-live', 'polite');
    document.body.append(c);
  }
  return c;
}

/**
 * Zeigt eine Benachrichtigung.
 * @param {{typ?: 'info'|'success'|'warning'|'danger', titel: string, text?: string, aktion?: {label: string, onClick: () => void}, dauer?: number}} optionen
 */
export function toast({ typ = 'info', titel, text = '', aktion, dauer = 6000 }) {
  const c = container();
  while (c.children.length >= MAX_SICHTBAR) c.firstElementChild?.remove();

  const el = document.createElement('div');
  el.className = `ps-toast ps-toast--${typ}`;
  if (typ === 'danger') el.setAttribute('role', 'alert');

  const symbol = icon(ICONS[typ], 'ps-toast__icon');
  const inhalt = document.createElement('div');
  inhalt.className = 'ps-toast__content';
  const kopf = document.createElement('p');
  kopf.className = 'ps-toast__title';
  kopf.textContent = titel;
  inhalt.append(kopf);
  if (text) {
    const t = document.createElement('p');
    t.className = 'ps-toast__text';
    t.textContent = text;
    inhalt.append(t);
  }
  if (aktion) {
    const knopf = document.createElement('button');
    knopf.type = 'button';
    knopf.className = 'ps-btn ps-btn--secondary ps-btn--s ps-toast__action';
    knopf.textContent = aktion.label;
    knopf.addEventListener('click', () => {
      aktion.onClick();
      entferne();
    });
    inhalt.append(knopf);
  }

  const zu = document.createElement('button');
  zu.type = 'button';
  zu.className = 'ps-btn ps-btn--ghost ps-btn--s ps-btn--icon';
  zu.setAttribute('aria-label', 'Benachrichtigung schließen');
  zu.append(icon('x', 'ps-icon--s'));
  zu.addEventListener('click', () => entferne());

  el.append(symbol, inhalt, zu);
  c.append(el);

  let timer;
  function entferne() {
    clearTimeout(timer);
    el.dataset.leaving = '';
    setTimeout(() => el.remove(), 200);
  }
  function starte() {
    if (dauer > 0) timer = setTimeout(entferne, dauer);
  }
  el.addEventListener('mouseenter', () => clearTimeout(timer));
  el.addEventListener('mouseleave', starte);
  el.addEventListener('focusin', () => clearTimeout(timer));
  el.addEventListener('focusout', starte);
  starte();
  return { schliesse: entferne };
}

export function initToast() {
  document.addEventListener('click', (e) => {
    const ausloeser = e.target instanceof Element ? e.target.closest('[data-ps-toast]') : null;
    if (!(ausloeser instanceof HTMLElement)) return;
    try {
      toast(JSON.parse(ausloeser.dataset.psToast ?? '{}'));
    } catch {
      toast({ typ: 'danger', titel: 'Ungültige Toast-Angabe' });
    }
  });
}
