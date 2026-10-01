# Designsystem

Stand: Phase 1 (Entwurf zur Freigabe). Die interne Komponentenseite `prototype/komponenten.html` zeigt alles in allen Zuständen. Aufruf lokal: `npm run build`, dann `php -S 127.0.0.1:8080 -t .` und `/prototype/komponenten.html` öffnen.

## Grundsätze

- Maritime, ruhige Optik: Petrol als Grundton, Bojen-Orange als einzige Signalfarbe.
- Wenige Stufen, keine Sonderlösungen außerhalb der Tokens.
- Zustände sind Teil des Designs: Laden (Skeleton), leer, Fehler, keine Daten im Zeitraum.
- Keine externen Ressourcen. Schrift und Icons liegen lokal.

## Tokens

Quelle: `resources/css/tokens.css`. Präfix `--ps-`. Hell und dunkel sind über `light-dark()` in einer Deklaration definiert. Die Darstellung folgt dem System und lässt sich über `data-theme="light"` oder `"dark"` am `<html>`-Element erzwingen (Schalter in der Oberfläche, gemerkt wird nur diese Wahl).

TODO(prüfen): Browserunterstützung von `light-dark()` (Annahme: in aktuellen Browsern seit 2024 verfügbar) gegen eine verlässliche Quelle abgleichen, bevor die unterstützten Browser in der Dokumentation genannt werden.

### Farben

| Token | Hell | Dunkel | Zweck |
|---|---|---|---|
| `bg` | `#f3f6f7` | `#06181f` | Seitenhintergrund |
| `surface` | `#ffffff` | `#0c2430` | Karten, Felder |
| `surface-sunken` | `#eaf0f2` | `#091d27` | vertiefte Flächen, Hover |
| `surface-raised` | `#ffffff` | `#11303e` | Menüs, Modals |
| `border` / `border-strong` | `#d3dfe3` / `#768e98` | `#1c3f4e` / `#4a7486` | Trenner / Feldrahmen |
| `text` / `text-muted` / `text-subtle` | `#0a2430` / `#44606d` / `#566e7b` | `#e8f0f3` / `#a3bac4` / `#8ca6b2` | Text |
| `primary` (+ `-hover`, `-active`) | `#0e6377` | `#5bc0d6` | Hauptaktionen |
| `primary-text` / `primary-subtle` | `#0b5a6d` / `#e0f0f4` | `#7ccde0` / `#0f3745` | Links, sanfte Flächen |
| `signal` / `signal-text` / `signal-subtle` | `#e8630a` / `#a94400` / `#ffefe0` | `#ff8f3d` / `#ffa45c` / `#3a2310` | Hervorhebung, Auswahl |
| `success`, `warning`, `danger` (+ `-subtle`) | siehe Quelle | siehe Quelle | Zustände |
| `focus` | `#0a5c70` | `#7dd0e2` | Fokusring |

Kontrast (berechnet nach WCAG): Alle Textpaare (Text, Gedämpft, Dezent auf Hintergrund, Fläche und vertiefter Fläche; Primär, Signal und Zustände auf ihren Flächen) erreichen mindestens 4,5:1 in beiden Modi. Feldrahmen (`border-strong`) und Grafikfarben (`primary`, `signal` auf Fläche) erreichen mindestens 3:1. Zusätzlich prüft axe-core im E2E-Test die gerenderte Seite in allen vier Varianten.

### Typografie

Inter (variabel, Latin), selbst gehostet, `font-display: swap`. Zahlen verwenden `font-variant-numeric: tabular-nums`.

| Token | Größe | Einsatz |
|---|---|---|
| `text-3xl` | 36 px | große Kennzahlen |
| `text-2xl` | 28 px | `h1` |
| `text-xl` | 22 px | `h2` |
| `text-lg` | 18 px | `h3` |
| `text-base` | 16 px | Fließtext |
| `text-sm` | 14 px | Tabellen, Felder |
| `text-xs` | 12 px | Spaltenköpfe, Badges |

### Abstände, Radien, Schatten, Bewegung

- Abstände `space-1` bis `space-8`: 4, 8, 12, 16, 24, 32, 48, 64 px.
- Radien: `radius-s` 6 px, `radius-m` 10 px, `radius-l` 14 px, `radius-full`.
- Schatten: `shadow-1` (Karten), `shadow-2` (Tooltips), `shadow-3` (Menüs, Modals).
- Bewegung: 150 ms (Hover, Fokus), 200 ms (Menüs, Schalter), 250 ms (Modals, Toasts), eine Kurve. `prefers-reduced-motion` setzt alle Dauern praktisch auf null.

## Komponenten

Stile: `resources/css/components/`. Verhalten: `resources/js/`, ereignisgesteuert über Delegation, funktioniert daher auch für später eingefügte Elemente. Texte setzt das JavaScript immer über `textContent`.

| Komponente | Klassen | Verhalten und Barrierefreiheit |
|---|---|---|
| Button | `ps-btn` + `--primary`, `--secondary`, `--ghost`, `--danger`, `--s`, `--icon` | Zustände: Hover, Aktiv, Fokus, `disabled`, `aria-busy`. Mindestgröße 40 px, 44 px bei Touch. |
| Eingabe | `ps-field`, `ps-label`, `ps-hint`, `ps-error`, `ps-input`, `ps-select`, `ps-textarea` | Fehler über `aria-invalid` und `aria-describedby`. |
| Auswahl | `ps-check`, `ps-switch` | Native Elemente, Schalter mit `role="switch"`. |
| Tabelle | `ps-table`, `--rows`, `--bars`, `ps-table-wrap` | Zahlen rechtsbündig, tabellarisch. Zeile als Filter über Button in der ersten Zelle. Scrollbereich ist per Tastatur erreichbar. |
| Karte, Kennzahl | `ps-card`, `ps-kpi`, `ps-delta` | Kennzahl als Auswahl über `button.ps-kpi` mit `aria-pressed`. |
| Tabs | `ps-tabs`, `ps-tab`, `ps-tabpanel` | ARIA-Tabs, Pfeiltasten, Pos1, Ende. |
| Dropdown | `data-ps-menu`, `ps-menu`, `ps-menu__item` | Pfeiltasten, Esc, Fokus-Rückgabe, Auswahl-Einträge mit `aria-checked`. |
| Modal | `ps-modal` auf `<dialog>` | Natives `showModal()`, Esc, Fokus-Rückgabe. Auf dem Smartphone als Sheet. |
| Toast | `data-ps-toast` oder `toast()` | `aria-live`-Bereich, Fehler als `role="alert"`, pausiert bei Hover und Fokus. |
| Tooltip | `data-ps-tooltip` | Hover und Fokus, Esc, `aria-describedby`. |
| Skeleton | `ps-skeleton` | Feste Maße gegen Layout-Verschiebungen, ohne Animation bei reduzierter Bewegung. |
| Leerzustand | `ps-empty` | Grund und nächster Schritt. |
| Badge | `ps-badge` | Farbe nie als einziges Signal: Text oder Pfeil begleitet sie. |

## Icons

Lucide (ISC) als lokales SVG-Sprite `assets/icons.svg`. Neue Icons in `bin/build-assets.mjs` (`ICONS`) eintragen. Verwendung: `<svg class="ps-icon" aria-hidden="true"><use href="…/icons.svg#ps-icon-NAME"/></svg>`. Größen 16, 20, 24 px.

## Build

`npm run build` erzeugt `assets/` (nicht im Repository): `css/pegelstand.css`, `js/pegelstand.js`, `js/theme-init.js`, `fonts/`, `icons.svg`. Die Dateien `komponentenseite.*` sind nur für die interne Komponentenseite und gehören nicht ins Release-Zip.

`theme-init.js` ist ein eigenes kleines Script im `<head>` und setzt den gespeicherten Modus vor dem ersten Zeichnen. Die Anwendung kommt ohne Inline-Skripte aus, damit später eine strenge Content-Security-Policy möglich ist.

## Dashboard-Prototyp (Phase 1, Schritt 2)

Aufruf lokal: `npm run build`, `php -S 127.0.0.1:8080 -t .`, dann `/prototype/index.html` öffnen. Alle Daten sind erfundene, deterministische Demo-Daten aus `resources/js/dashboard/demo-daten.js`. Diese Datei bildet die spätere API-Schnittstelle nach (`ladeAnsicht({ site, zeitraum, filter })`), in Phase 5 wird nur sie ersetzt.

### Aufbau

Kopfzeile (Wortmarke, Site-Wechsel, Befehlspalette, Darstellung, Kürzel), Werkzeugleiste (Titel, aktive Besucher, Zeitraum, Vergleich), Filterchips, fünf Kennzahlen (wählen das Diagramm), Zeitreihen-Diagramm mit Vorperiode, Karten für Seiten, Quellen, Länder, Geräte sowie Ziele und Ereignisse.

### Zustand in der Adresse

| Parameter | Bedeutung |
|---|---|
| `site` | Site-ID, z. B. `meine-firma-de` |
| `zeitraum` | `heute`, `gestern`, `7t`, `30t` (Standard), `monat`, `letzter-monat`, `jahr`, `benutzerdefiniert` mit `von` und `bis` |
| `f` | Filter, mehrfach möglich, Form `art:wert`, z. B. `f=land:DE&f=geraet:smartphone` |
| `vergleich` | `0` schaltet die Vorperiode aus |
| `metrik` | Kennzahl im Diagramm (`besucher`, `aufrufe`, `seitenProBesuch`, `absprungrate`, `dauer`) |
| `zustand` | erzwingt `laden`, `leer`, `fehler` oder `keine-daten` (zum Prüfen der Zustände) |
| `verzoegerung` | künstliche Ladezeit in ms (Standard 500, in Tests 0) |

Das „Jetzt“ der Demo ist fest auf den 29.09.2026, 14:30 Uhr gesetzt, damit Prototyp, Tests und Screenshots reproduzierbar sind.

### Entscheidungen

- **Zeitraum „Jahr“** heißt in der Oberfläche „Dieses Jahr“ und reicht vom 1. Januar bis heute. Die Vorperiode ist immer der gleich lange Zeitraum direkt davor. Bitte bestätigen oder ändern.
- **Stündliche Ansicht** bis zwei Tage, sonst täglich. Der laufende Tag ist unvollständig. Im echten Dashboard sollte er kenntlich gemacht werden. `TODO(prüfen)`
- **Filter im Prototyp** skalieren die Demo-Zahlen plausibel (Anteile, Absprungrate, Dauer) und verteilen sie mit gleichbleibender Summe auf die Tabellen. Die echte Filterung über Rohdaten folgt in Phase 5.
- **Tabellen** zeigen die acht größten Zeilen. Eine Ansicht für alle Zeilen ist im Lastenheft nicht genannt und fehlt deshalb.
- **Länderkarte** ist zurückgestellt (Kartendaten brauchen eine geprüfte Lizenz).
- **Diagramm** nutzt uPlot (MIT). Es ist per Pfeiltasten bedienbar und lässt sich als Tabelle anzeigen. Farben kommen aus den Tokens und das Diagramm zeichnet bei Moduswechsel neu.
- **Neuladen** lässt den Inhalt stehen und zeigt oben einen schmalen Fortschrittsbalken. Abdunkeln würde den Kontrast verletzen.
- **Größenbudget:** Dashboard-JavaScript unter 100 KB gzip, geprüft von `npm run check:groesse` (aktuell rund 40 KB).

### Tastaturkürzel

`Strg K` Befehlspalette, `?` Übersicht, `1` bis `7` Zeitraum, `V` Vergleich, `F` Filter entfernen, Pfeiltasten im Diagramm, `Esc` schließt.

### Bewusst nicht enthalten

Öffentlicher Link, Einbettung, CSV-Export (Phase 7), Einrichtungsassistent mit Live-Verbindungsprüfung (Phase 6), Einstellungen (Phase 6). Der Befehl „Einstellungen“ zeigt im Prototyp nur einen Hinweis.
