<!-- Automatisch aus docs/hintergrundjobs.md erzeugt (php bin/build-wiki.php). Änderungen bitte dort vornehmen. -->
# Hintergrundjobs und Aggregation

Pegelstand speichert jeden Besuch als Rohdatensatz. Das Dashboard liest dagegen fertig verdichtete **Aggregate** (Tages- und Stundenwerte, Aufschlüsselungen). Zwei Jobs halten sie aktuell.

| Job | Intervall | Aufgabe |
|---|---|---|
| `aggregate` | alle 5 Minuten | Berechnet Tages-, Stunden- und Aufschlüsselungswerte neu (Vortag und heute immer, ältere Tage nur einmal). |
| `cleanup` | stündlich | Löscht Rohdaten nach Ablauf der Aufbewahrungsfrist (Standard 730 Tage, pro Site änderbar) und abgelaufene Ratenbegrenzungs-Zeilen. Aggregate bleiben. |
| `reports` | stündlich | Verschickt fällige E-Mail-Berichte (Montag ab 7 Uhr für die Vorwoche, am Ersten ab 7 Uhr für den Vormonat, nach Ortszeit der Website). Schlägt ein Versand fehl, versucht der nächste Lauf es erneut. Ohne eingerichteten E-Mail-Versand passiert nichts. |

## Ohne Cronjob (Standard)

Pegelstand prüft nach jeder Antwort an einen Besucher, ob ein Job fällig ist (höchstens einmal pro Minute). Du musst nichts einrichten. Nachteil: Ohne Besucher läuft nichts, und auf Servern ohne `fastcgi_finish_request` (reines mod_php) wartet der Besucher, dessen Aufruf den Job anstößt, auf dessen Ende. Pro Lauf ist die Arbeit begrenzt.

## Mit Cronjob (empfohlen für viel Verkehr)

```
*/5 * * * * php /pfad/zu/pegelstand/bin/cron.php
```

Trage dann in `config/config.php` ein, damit nicht zusätzlich der Pseudo-Cron läuft:

```php
'cron' => ['mode' => 'external'],
```

`php bin/cron.php --force` führt alle Jobs sofort aus.

## Wie die Zahlen entstehen

- Ein **Besucher** ist ein anonymer Hash, der täglich wechselt. Besucher über mehrere Tage sind die Summe der Tageswerte.
- Ein **Absprung** ist eine Sitzung mit genau einem Seitenaufruf und ohne eigenes Ereignis.
- Die **Dauer** einer Sitzung ist die Zeit zwischen erstem und letztem Aufruf. Einzelseiten-Besuche zählen mit 0 Sekunden.
- Sitzungswerte zählen an dem Tag, an dem die Sitzung begann. Seitenaufrufe und Ereignisse zählen an dem Tag, an dem sie geschahen.
- Tage richten sich nach der Zeitzone der Site. Rohdaten liegen in UTC.

## Beispieldaten und Messungen

```
php bin/demo-data.php --create-site --days=90 --sessions=300   # erfundene Daten für eine Demo-Site
php bin/benchmark.php --sessions=150000 --days=30               # Messung, legt eine Wegwerf-Site an
```

Messwerte stehen in [Leistung](Leistung).
