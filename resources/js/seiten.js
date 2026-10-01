// Kleine Verbesserungen für Installer und Hinweisseiten. Ohne JavaScript bleiben alle Formulare nutzbar.
import './main.js';
import { icon } from './util.js';

/* Passwort anzeigen und verbergen */
for (const knopf of document.querySelectorAll('[data-passwort-umschalten]')) {
  const feld = document.getElementById(knopf.dataset.passwortUmschalten ?? '');
  if (!(feld instanceof HTMLInputElement)) continue;
  knopf.hidden = false;
  knopf.addEventListener('click', () => {
    const sichtbar = feld.type === 'password';
    feld.type = sichtbar ? 'text' : 'password';
    knopf.setAttribute('aria-pressed', String(sichtbar));
    knopf.textContent = sichtbar ? knopf.dataset.textVerbergen ?? '' : knopf.dataset.textAnzeigen ?? '';
  });
}

/* Fehlerzusammenfassung erhält den Fokus, damit Screenreader sie sofort vorlesen */
document.querySelector('[data-fehlerzusammenfassung]')?.focus();

/* Doppeltes Absenden verhindern */
for (const formular of document.querySelectorAll('form')) {
  formular.addEventListener('submit', () => {
    const knopf = formular.querySelector('[data-beschaeftigt-beim-senden]');
    if (knopf instanceof HTMLButtonElement) {
      knopf.setAttribute('aria-busy', 'true');
      setTimeout(() => {
        knopf.disabled = true;
      }, 0);
    }
  });
}

/* Prüft im Browser, ob die internen Ordner von außen erreichbar sind */
const zugriff = document.querySelector('[data-zugriffscheck]');
if (zugriff instanceof HTMLElement) {
  const urls = (zugriff.dataset.urls ?? '').split(' ').filter(Boolean);
  const detail = zugriff.querySelector('[data-detail]');
  Promise.all(
    urls.map((url) =>
      fetch(url, { cache: 'no-store', redirect: 'manual' })
        .then((antwort) => antwort.status === 200)
        .catch(() => false),
    ),
  ).then((ergebnisse) => {
    const offen = ergebnisse.some(Boolean);
    zugriff.classList.remove('in-punkt--pruefe');
    zugriff.classList.add(offen ? 'in-punkt--warn' : 'in-punkt--ok');
    zugriff.querySelector('[data-symbole]')?.replaceChildren(icon(offen ? 'triangle-alert' : 'circle-check'));
    if (detail instanceof HTMLElement) {
      detail.textContent = (offen ? detail.dataset.textWarn : detail.dataset.textOk) ?? '';
      const status = zugriff.querySelector('[data-status]');
      if (status) status.textContent = `: ${(offen ? detail.dataset.statusWarn : detail.dataset.statusOk) ?? ''}`;
    }
    const hinweis = zugriff.querySelector('[data-hinweis]');
    if (hinweis instanceof HTMLElement) hinweis.hidden = !offen;
  });
}
