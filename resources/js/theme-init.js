// Läuft synchron im <head>, bevor die Seite gezeichnet wird, damit der Modus nicht aufblitzt.
// Gespeichert wird nur die Darstellungswahl (hell, dunkel), nichts zu Besuchern.
try {
  const gespeichert = localStorage.getItem('pegelstand-theme');
  if (gespeichert === 'light' || gespeichert === 'dark') {
    document.documentElement.dataset.theme = gespeichert;
  }
} catch {
  // Speicher nicht verfügbar (z. B. blockiert): Systemeinstellung gilt.
}
