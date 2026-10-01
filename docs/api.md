# REST-Schnittstelle (Version 1)

Lesender Zugriff auf die Zahlen, die auch das Dashboard zeigt. Es gibt nur Abfragen, keine Änderungen.

## Schlüssel

Erzeuge in **Einstellungen, Mein Konto, API-Schlüssel** einen Schlüssel (`psk_` plus 40 Zeichen). Pegelstand zeigt ihn nur einmal an und speichert nur seinen Hash. Ein Schlüssel sieht dieselben Websites wie sein Benutzer. Sperrst oder löschst du den Benutzer oder den Schlüssel, endet der Zugriff sofort.

Jede Anfrage trägt den Schlüssel im Kopf:

```
Authorization: Bearer psk_…
```

Pro Schlüssel sind 120 Anfragen pro Minute erlaubt (`api.rate_limit` in `config/config.php`). Darüber antwortet die Schnittstelle mit `429` und `Retry-After: 60`.

## Abfragen

### `GET /api/v1/sites`

```json
{ "sites": [ { "id": "420c05cdbf64c973", "name": "Mein Blog", "domain": "blog.beispiel.de", "zeitzone": "Europe/Berlin" } ] }
```

### `GET /api/v1/sites/{id}/stats`

Parameter, alle optional:

| Parameter | Bedeutung |
|---|---|
| `von`, `bis` | Zeitraum als `JJJJ-MM-TT` in der Zeitzone der Website (Standard: letzte 30 Tage) |
| `f[]` | Filter der Form `art:wert`, z. B. `f[]=land:DE` oder `f[]=ziel:3`. Arten: `seite`, `einstieg`, `ausstieg`, `quelle`, `kampagne`, `land`, `geraet`, `browser`, `os`, `ziel`, `ereignis` |

Die Antwort ist dasselbe JSON, das das Dashboard lädt: `kennzahlen` (aktuell und Vorperiode), `diagramm` (Zeitreihe) und `tabellen` (Seiten, Quellen, Länder, Geräte, Ziele, Ereignisse). Ohne Daten steht dort `"leer": true` oder `"keineDaten": true`. Die Namen der Felder sind deutsch, wie im Dashboard.

Beispiel:

```bash
curl -H "Authorization: Bearer psk_…" \
  "https://stats.beispiel.de/api/v1/sites/420c05cdbf64c973/stats?von=2026-09-01&bis=2026-09-30&f[]=land:DE"
```

## Fehler

| Status | `fehler` | Bedeutung |
|---|---|---|
| 401 | `nicht_autorisiert` | Schlüssel fehlt, ist falsch, gelöscht oder der Benutzer ist gesperrt |
| 404 | `site_unbekannt` | Es gibt keine solche Website oder sie ist nicht für den Benutzer freigegeben |
| 429 | `zu_viele_anfragen` | Ratenbegrenzung |

## Hinweise

- Zahlen ohne Filter und für mehr als zwei Tage kommen aus vorberechneten Tageswerten, `quelle` nennt `aggregate` oder `rohdaten`.
- Besucher über mehrere Tage sind die Summe der Tageswerte (siehe [docs/tracking.md](tracking.md)).
- Die Schnittstelle ist in Version 1.0 stabil gedacht, bis dahin können sich Felder ändern.
