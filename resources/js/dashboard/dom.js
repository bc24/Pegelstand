/** Kleiner Helfer zum Aufbau von DOM-Elementen. Texte werden immer als Text eingefügt, nie als HTML. */
export function h(tag, eigenschaften = {}, ...kinder) {
  const el = document.createElement(tag);
  for (const [name, wert] of Object.entries(eigenschaften ?? {})) {
    if (wert === false || wert == null) continue;
    if (name === 'class') el.className = wert;
    else if (name === 'daten') Object.assign(el.dataset, wert);
    else if (wert === true) el.setAttribute(name, '');
    else el.setAttribute(name, String(wert));
  }
  el.append(...kinder.flat().filter((k) => k != null && k !== false));
  return el;
}

export const text = (inhalt) => document.createTextNode(inhalt);
export const versteckt = (inhalt) => h('span', { class: 'ps-visually-hidden' }, inhalt);
