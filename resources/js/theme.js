const SCHLUESSEL = 'pegelstand-theme';

function gespeichert() {
  try {
    const wert = localStorage.getItem(SCHLUESSEL);
    return wert === 'light' || wert === 'dark' ? wert : 'system';
  } catch {
    return 'system';
  }
}

export function setzeModus(modus) {
  const wurzel = document.documentElement;
  if (modus === 'light' || modus === 'dark') {
    wurzel.dataset.theme = modus;
  } else {
    delete wurzel.dataset.theme;
  }
  try {
    if (modus === 'system') localStorage.removeItem(SCHLUESSEL);
    else localStorage.setItem(SCHLUESSEL, modus);
  } catch {
    // Wahl gilt nur für diese Sitzung der Seite.
  }
  markiere(modus);
}

function markiere(modus) {
  for (const eintrag of document.querySelectorAll('[data-ps-theme]')) {
    eintrag.setAttribute('aria-checked', String(eintrag.dataset.psTheme === modus));
  }
}

export function initTheme() {
  markiere(gespeichert());
  document.addEventListener('click', (e) => {
    const eintrag = e.target instanceof Element ? e.target.closest('[data-ps-theme]') : null;
    if (eintrag instanceof HTMLElement) setzeModus(eintrag.dataset.psTheme ?? 'system');
  });
}
