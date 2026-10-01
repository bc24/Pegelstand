# Drittanbieter-Komponenten

Pegelstand liefert die folgenden Fremdkomponenten selbst gehostet aus. Es gibt keine Requests an Dritte.
Die vollständigen Lizenztexte liegen im Ordner `licenses/`.

| Komponente | Verwendung | Lizenz | Lizenztext |
|---|---|---|---|
| [Inter](https://github.com/rsms/inter) (über `@fontsource-variable/inter`) | Schrift, nur Latin-Teilsatz als woff2 | SIL Open Font License 1.1 | `licenses/inter-OFL-1.1.txt` |
| [Lucide](https://lucide.dev) (über `lucide-static`) | Icons als SVG-Sprite | ISC, für einzelne von Feather abgeleitete Icons MIT | `licenses/lucide-LICENSE.txt` |
| [uPlot](https://github.com/leeoniya/uPlot) | Zeitreihen-Diagramm im Dashboard (im JavaScript-Bundle enthalten) | MIT | `licenses/uplot-LICENSE.txt` |

Die Lizenzangaben stammen aus den `package.json`-Dateien und Lizenzdateien der installierten Pakete.
TODO(prüfen): Vor der Veröffentlichung prüfen, ob die OFL-Bedingungen (u. a. Weitergabe des Lizenztexts, Reservierte Schriftnamen) für die Auslieferung im Release-Zip vollständig erfüllt sind.

Nur für die Entwicklung (nicht im Release-Zip): esbuild (MIT), Playwright (Apache-2.0), axe-core (MPL-2.0), PHPUnit, PHPStan, PHP-CS-Fixer.
TODO(prüfen): Lizenzen der Entwicklungswerkzeuge gegen die jeweiligen Pakete bestätigen.
