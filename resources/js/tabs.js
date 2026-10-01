function waehle(tab) {
  const liste = tab.closest('[role="tablist"]');
  if (!liste) return;
  for (const andere of liste.querySelectorAll('[role="tab"]')) {
    const aktiv = andere === tab;
    andere.setAttribute('aria-selected', String(aktiv));
    andere.tabIndex = aktiv ? 0 : -1;
    const panel = document.getElementById(andere.getAttribute('aria-controls') ?? '');
    if (panel) panel.hidden = !aktiv;
  }
  liste.dispatchEvent(new CustomEvent('ps-tab-wahl', { bubbles: true, detail: { tab } }));
}

export function initTabs() {
  document.addEventListener('click', (e) => {
    const tab = e.target instanceof Element ? e.target.closest('[role="tab"]') : null;
    if (tab) waehle(tab);
  });

  document.addEventListener('keydown', (e) => {
    const tab = e.target instanceof Element ? e.target.closest('[role="tab"]') : null;
    if (!tab) return;
    const tabs = [...(tab.closest('[role="tablist"]')?.querySelectorAll('[role="tab"]') ?? [])];
    const index = tabs.indexOf(tab);
    let ziel;
    if (e.key === 'ArrowRight') ziel = tabs[(index + 1) % tabs.length];
    else if (e.key === 'ArrowLeft') ziel = tabs[(index - 1 + tabs.length) % tabs.length];
    else if (e.key === 'Home') ziel = tabs[0];
    else if (e.key === 'End') ziel = tabs.at(-1);
    if (ziel) {
      e.preventDefault();
      ziel.focus();
      waehle(ziel);
    }
  });
}
