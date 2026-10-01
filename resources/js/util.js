/** Basispfad der Assets; PHP setzt data-ps-assets am <html>-Element (wichtig bei Betrieb im Unterverzeichnis). */
export function assetBasis() {
  return document.documentElement.dataset.psAssets ?? '/assets';
}

/** Erzeugt ein Icon aus dem Sprite. Der Name muss in bin/build-assets.mjs registriert sein. */
export function icon(name, klassen = '') {
  const ns = 'http://www.w3.org/2000/svg';
  const svg = document.createElementNS(ns, 'svg');
  svg.setAttribute('class', `ps-icon ${klassen}`.trim());
  svg.setAttribute('aria-hidden', 'true');
  svg.setAttribute('focusable', 'false');
  const use = document.createElementNS(ns, 'use');
  use.setAttribute('href', `${assetBasis()}/icons.svg#ps-icon-${name}`);
  svg.append(use);
  return svg;
}

let zaehler = 0;
export function neueId(praefix) {
  zaehler += 1;
  return `${praefix}-${zaehler}`;
}

export const FOKUSSIERBAR =
  'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
