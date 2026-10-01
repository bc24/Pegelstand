const EINTRAEGE = '[role="menuitem"]:not([aria-disabled="true"]), [role="menuitemradio"]:not([aria-disabled="true"]), [role="menuitemcheckbox"]:not([aria-disabled="true"])';

function teile(wrap) {
  return { knopf: wrap.querySelector('[aria-haspopup="menu"]'), menue: wrap.querySelector('[role="menu"]') };
}

export function oeffne(wrap, fokus = 'erster') {
  const { knopf, menue } = teile(wrap);
  if (!knopf || !menue) return;
  schliesseAlle(wrap);
  menue.hidden = false;
  knopf.setAttribute('aria-expanded', 'true');
  menue.classList.remove('ps-menu--end');
  if (menue.getBoundingClientRect().right > window.innerWidth - 8) menue.classList.add('ps-menu--end');
  const eintraege = [...menue.querySelectorAll(EINTRAEGE)];
  const ziel =
    fokus === 'letzter' ? eintraege.at(-1) : (eintraege.find((e) => e.getAttribute('aria-checked') === 'true') ?? eintraege[0]);
  ziel?.focus();
}

export function schliesse(wrap, fokusZurueck = false) {
  const { knopf, menue } = teile(wrap);
  if (!knopf || !menue || menue.hidden) return;
  menue.hidden = true;
  knopf.setAttribute('aria-expanded', 'false');
  if (fokusZurueck) knopf.focus();
}

function schliesseAlle(ausser) {
  for (const wrap of document.querySelectorAll('[data-ps-menu]')) {
    if (wrap !== ausser) schliesse(wrap);
  }
}

export function initMenu() {
  document.addEventListener('click', (e) => {
    if (!(e.target instanceof Element)) return;
    const wrap = e.target.closest('[data-ps-menu]');
    if (!wrap) {
      schliesseAlle(null);
      return;
    }
    const { knopf, menue } = teile(wrap);
    if (knopf?.contains(e.target)) {
      if (menue?.hidden) oeffne(wrap);
      else schliesse(wrap, true);
      return;
    }
    const eintrag = e.target.closest(EINTRAEGE);
    if (!eintrag || !menue?.contains(eintrag)) return;
    if (eintrag.getAttribute('role') === 'menuitemradio') {
      for (const geschwister of menue.querySelectorAll('[role="menuitemradio"]')) {
        if (geschwister.dataset.psGruppe === eintrag.dataset.psGruppe) geschwister.setAttribute('aria-checked', String(geschwister === eintrag));
      }
    }
    wrap.dispatchEvent(new CustomEvent('ps-menu-wahl', { bubbles: true, detail: { eintrag, wert: eintrag.dataset.wert ?? null } }));
    schliesse(wrap, true);
  });

  document.addEventListener('keydown', (e) => {
    if (!(e.target instanceof Element)) return;
    const wrap = e.target.closest('[data-ps-menu]');
    if (!wrap) return;
    const { knopf, menue } = teile(wrap);
    if (!knopf || !menue) return;

    if (e.target === knopf && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
      e.preventDefault();
      oeffne(wrap, e.key === 'ArrowUp' ? 'letzter' : 'erster');
      return;
    }
    if (menue.hidden || !menue.contains(e.target)) return;

    const eintraege = [...menue.querySelectorAll(EINTRAEGE)];
    const index = eintraege.indexOf(/** @type {HTMLElement} */ (document.activeElement));
    switch (e.key) {
      case 'ArrowDown':
        e.preventDefault();
        eintraege[(index + 1) % eintraege.length]?.focus();
        break;
      case 'ArrowUp':
        e.preventDefault();
        eintraege[(index - 1 + eintraege.length) % eintraege.length]?.focus();
        break;
      case 'Home':
        e.preventDefault();
        eintraege[0]?.focus();
        break;
      case 'End':
        e.preventDefault();
        eintraege.at(-1)?.focus();
        break;
      case 'Escape':
        e.preventDefault();
        schliesse(wrap, true);
        break;
      case 'Tab':
        schliesse(wrap);
        break;
      default: {
        if (e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
          const treffer = eintraege.find((el, i) => i > index && el.textContent?.trim().toLowerCase().startsWith(e.key.toLowerCase()))
            ?? eintraege.find((el) => el.textContent?.trim().toLowerCase().startsWith(e.key.toLowerCase()));
          treffer?.focus();
        }
      }
    }
  });
}
