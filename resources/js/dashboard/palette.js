// Befehlspalette (Strg+K): Combobox-Muster mit Listbox in einem Dialog.
import { icon } from '../util.js';
import { h } from './dom.js';

const normal = (s) =>
  s
    .toLowerCase()
    .replace(/ß/g, 'ss')
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '');

export function initPalette(holeAktionen) {
  const dialog = document.getElementById('palette');
  const eingabe = dialog.querySelector('input');
  const liste = dialog.querySelector('[role="listbox"]');
  const leer = dialog.querySelector('.db-palette__leer');
  let treffer = [];
  let aktiv = 0;

  function waehle(i) {
    aktiv = i;
    liste.querySelectorAll('[role="option"]').forEach((el, n) => el.setAttribute('aria-selected', String(n === i)));
    const el = liste.querySelectorAll('[role="option"]')[i];
    eingabe.setAttribute('aria-activedescendant', el?.id ?? '');
    el?.scrollIntoView({ block: 'nearest' });
  }

  function zeichne() {
    const begriffe = normal(eingabe.value).split(/\s+/).filter(Boolean);
    treffer = holeAktionen().filter((a) => begriffe.every((b) => normal(`${a.gruppe} ${a.label} ${a.suche ?? ''}`).includes(b)));
    liste.replaceChildren();
    leer.hidden = treffer.length > 0;
    let gruppe = null;
    let behaelter = null;
    treffer.forEach((a, i) => {
      if (a.gruppe !== gruppe) {
        gruppe = a.gruppe;
        const id = `palette-gruppe-${i}`;
        behaelter = h('div', { role: 'group', 'aria-labelledby': id }, h('div', { class: 'db-palette__gruppe', id }, gruppe));
        liste.append(behaelter);
      }
      const option = h(
        'div',
        { role: 'option', id: `palette-option-${i}`, class: 'db-palette__eintrag', 'aria-selected': 'false' },
        icon(a.icon ?? 'arrow-right', 'ps-icon--s'),
        h('span', { class: 'db-palette__label' }, a.label),
        a.hinweis ? h('kbd', {}, a.hinweis) : null,
      );
      option.addEventListener('click', () => fuehreAus(a));
      option.addEventListener('mousemove', () => aktiv !== i && waehle(i));
      behaelter.append(option);
    });
    eingabe.setAttribute('aria-expanded', String(treffer.length > 0));
    if (treffer.length) waehle(0);
    else eingabe.setAttribute('aria-activedescendant', '');
  }

  function fuehreAus(a) {
    dialog.close();
    a.fuehreAus();
  }

  function oeffne() {
    if (dialog.open) return;
    eingabe.value = '';
    dialog.showModal();
    zeichne();
    eingabe.focus();
  }

  eingabe.addEventListener('input', zeichne);
  eingabe.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowDown' && treffer.length) {
      e.preventDefault();
      waehle((aktiv + 1) % treffer.length);
    } else if (e.key === 'ArrowUp' && treffer.length) {
      e.preventDefault();
      waehle((aktiv - 1 + treffer.length) % treffer.length);
    } else if (e.key === 'Enter' && treffer[aktiv]) {
      e.preventDefault();
      fuehreAus(treffer[aktiv]);
    }
  });
  dialog.addEventListener('click', (e) => {
    if (e.target === dialog) dialog.close();
  });
  document.addEventListener('click', (e) => {
    if (e.target instanceof Element && e.target.closest('[data-palette-open]')) oeffne();
  });
  const original = dialog.showModal.bind(dialog);
  dialog.showModal = () => {
    original();
    document.documentElement.dataset.psModal = '';
  };

  return { oeffne };
}
