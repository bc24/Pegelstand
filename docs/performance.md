# Messungen zur Leistung

Gemessen mit `bin/benchmark.php` in der Entwicklungsumgebung: PHP 8.3.6, MariaDB 10.11.14 mit Standardeinstellungen (kleiner Pufferpool, wie auf einem einfachen Hosting), alles auf einer Maschine, eine Anfrage nach der anderen. Das sind **Richtwerte, keine Zusagen**. Andere Hoster, MySQL 8 und echtes Netzwerk liefern andere Zahlen, geprüft wurde nur MariaDB 10.11 (`TODO(prüfen)`: MySQL 8.0/8.4, MariaDB 10.6/11.4 in der CI und auf echtem Hosting).

## Datenmenge

Erzeugt mit `php bin/benchmark.php --sessions=4400000 --days=90`: 3,92 Mio. Sitzungen und 8,99 Mio. Ereignisse (90 Tage, erfundene Daten).

| Tabelle | Größe (Daten + Index) |
|---|---|
| `events` | 1.506 MB |
| `sessions` | 782 MB |
| `agg_daily_dim` (31 Tage) | 0,5 MB |

Grobe Faustregel aus dieser Messung: rund 170 Byte Daten und Index pro Ereignis plus rund 200 Byte pro Sitzung. Die Aufbewahrungsfrist der Rohdaten (Standard 730 Tage) bestimmt also den Speicherbedarf, die Aggregate fallen kaum ins Gewicht.

## Erfassung (Collector ohne HTTP)

| Zustand | Median | 95. Perzentil | Maximum |
|---|---|---|---|
| leere Tabellen | 1,98 ms | 3,63 ms | 11,2 ms |
| 8,99 Mio. Ereignisse in der Tabelle | 2,35 ms | 3,80 ms | 27,4 ms |

Das Ziel war unter 50 ms. Mit dem eingebauten PHP-Webserver und HTTP lag der Median im Kleinversuch bei 4,9 ms (95. Perzentil 7,5 ms). Der Zeitaufwand wächst kaum mit der Tabellengröße, weil die Zuordnung zur Sitzung über einen Index läuft. Nicht gemessen: viele gleichzeitige Anfragen (Sperren, Verbindungsaufbau, PHP-FPM-Prozesse).

## Aggregation

31 Tage mit zusammen rund 3 Mio. Ereignissen in 71,6 s (Nachholen des ersten Mals). Im laufenden Betrieb rechnet der Job nur den Vortag und heute, bei 100.000 Ereignissen pro Tag also Sekundenbruchteile bis wenige Sekunden.

## Abfragen für das Dashboard (90 Tage Daten, 31 Tage Auswahl)

| Abfrage | Zeit |
|---|---|
| Kennzahlen aus Tagesaggregaten | 0,4 ms |
| Diagramm (Tageswerte) aus Aggregaten | 0,3 ms |
| Top-Seiten aus Aggregaten | 2,1 ms |
| Aktive Besucher (letzte 5 Minuten, aus Rohdaten) | 40,7 ms |
| Zum Vergleich: dieselben Kennzahlen direkt aus den Rohdaten | 14.750 ms |

Das bestätigt die Entscheidung für Aggregate: Das Dashboard liest nie Rohdaten für Zeiträume, nur die kurze Abfrage "aktive Besucher" und gefilterte Ansichten (Phase 5) greifen darauf zu.

## Grenzen dieser Messung

- Eine Maschine, kein Netzwerk, keine parallelen Anfragen, ein Datenbanktyp.
- Erfundene Daten mit gleichmäßigerer Verteilung als echter Verkehr.
- Die Erzeugung der 9 Mio. Ereignisse dauerte 492 s (kein Maßstab für die Erfassung, sie schreibt direkt in Blöcken).
