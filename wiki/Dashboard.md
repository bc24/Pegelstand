# Das Dashboard bedienen

![Dashboard](https://raw.githubusercontent.com/bc24/Pegelstand/main/docs/bilder/dashboard-hell.png)

## Kennzahlen

Oben stehen fünf Kennzahlen, jeweils mit Veränderung zur Vorperiode (gleich lange Zeit direkt davor):

| Kennzahl | Bedeutung |
|---|---|
| Besucher | Anonyme Besucher. Über mehrere Tage ist es die **Summe der Tageswerte**, weil sich die Kennung täglich ändert. |
| Seitenaufrufe | Alle gezählten Seitenaufrufe |
| Seiten pro Besuch | Seitenaufrufe geteilt durch Besuche |
| Absprungrate | Anteil der Besuche mit genau einem Seitenaufruf und ohne Ereignis |
| Besuchsdauer | Zeit zwischen erstem und letztem Aufruf eines Besuchs, Einzelseiten-Besuche zählen mit 0 Sekunden |

Ein Klick auf eine Kennzahl zeigt sie im Diagramm.

## Zeitraum

Über das Menü oder die Tasten `1` bis `7`: Heute, Gestern, 7 Tage, 30 Tage, Dieser Monat, Letzter Monat, Dieses Jahr. Dazu ein frei wählbarer Bereich. „Heute“ und „Gestern“ zeigen Stunden, alles andere Tage, in der Zeitzone der Website. Mit dem Schalter oder der Taste `V` blendest du den Vergleich mit der Vorperiode ein und aus.

## Filter

Ein Klick auf eine Zeile in einer Tabelle (Seite, Quelle, Land, Gerät, Browser, Ziel, Ereignis …) filtert das **ganze** Dashboard. Mehrere Filter gelten zusammen. Sie erscheinen als Chips und lassen sich einzeln oder mit `F` gemeinsam entfernen. Der Zustand steckt in der Adresse, du kannst die Ansicht also als Link weitergeben (an Menschen mit Zugang zu dieser Website).

![Dashboard mit Filtern](https://raw.githubusercontent.com/bc24/Pegelstand/main/docs/bilder/dashboard-filter.png)

## Tabellen

- **Seiten:** Top-Seiten, Einstiegsseiten (mit Absprungrate), Ausstiegsseiten (mit Ausstiegsrate)
- **Quellen:** Referrer, Suchmaschinen, soziale Netzwerke, Kampagnen (UTM)
- **Länder, Geräte, Browser, Betriebssysteme**
- **Ziele und Ereignisse** mit Eigenschaften der Ereignisse

Die Tabellen zeigen die stärksten Einträge. Alle Tabellen lassen sich über **Exportieren** als CSV für Excel herunterladen.

## Tastatur

| Taste | Wirkung |
|---|---|
| `Strg` + `K` | Befehlspalette: Website wechseln, Zeitraum, Diagramm, Darstellung |
| `?` | Alle Kürzel anzeigen |
| `1` bis `7` | Zeitraum wählen |
| `V` | Vergleich umschalten |
| `F` | Alle Filter entfernen |
| Pfeiltasten | Im Diagramm durch die Werte gehen |

## Aktive Besucher

Neben dem Titel steht die Zahl der Besucher mit mindestens einem Aufruf in den letzten 5 Minuten. Sie aktualisiert sich alle 30 Sekunden.

## Woher kommen die Zahlen?

Ohne Filter und bei mehr als zwei Tagen liest das Dashboard vorberechnete Tageswerte (sehr schnell, alle 5 Minuten aktualisiert). „Heute“, „Gestern“ und jeder Filter lesen die einzelnen Aufrufe und sind in Echtzeit korrekt, bei sehr vielen Daten aber langsamer. Mehr dazu unter [[Hintergrundjobs]] und [[Leistung]].
