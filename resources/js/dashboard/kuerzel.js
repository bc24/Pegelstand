// Globale Tastaturkürzel. Sie greifen nicht, solange du tippst oder ein Dialog offen ist.
export function initKuerzel({ palette, zeitraumPerTaste, vergleichUmschalten, filterEntfernen, hilfeOeffnen }) {
  document.addEventListener('keydown', (e) => {
    if (e.defaultPrevented) return;
    if ((e.ctrlKey || e.metaKey) && !e.altKey && e.key.toLowerCase() === 'k') {
      e.preventDefault();
      palette.oeffne();
      return;
    }
    if (e.ctrlKey || e.metaKey || e.altKey) return;
    const ziel = e.target;
    const tippt =
      ziel instanceof HTMLElement && (ziel.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(ziel.tagName));
    if (tippt || document.querySelector('dialog[open]')) return;

    if (e.key === '?') {
      e.preventDefault();
      hilfeOeffnen();
    } else if (/^[1-7]$/.test(e.key)) {
      zeitraumPerTaste(e.key);
    } else if (e.key === 'v' || e.key === 'V') {
      vergleichUmschalten();
    } else if (e.key === 'f' || e.key === 'F') {
      filterEntfernen();
    }
  });
}
