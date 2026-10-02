<!-- Automatisch aus docs/tracking.md erzeugt (php bin/build-wiki.php). Änderungen bitte dort vornehmen. -->
# Tracking: Einbindung, Betrieb und Datenschutz

Stand: Phase 3. Prüfe Aussagen zu Recht und Hostern selbst, hier steht nur, was Pegelstand technisch tut.

## Tracking-Code einbinden

```html
<script defer src="https://stats.beispiel.de/p.js" data-site="DEINE-SITE-ID"></script>
```

`data-site` ist die öffentliche ID deiner Site (ab Phase 6 findest du den fertigen Code in den Einstellungen). Das Script ist kleiner als 2 KB (gzip), setzt keine Cookies und schreibt nichts in den Browser-Speicher.

| Attribut | Wirkung |
|---|---|
| `data-api="https://…/api/event"` | Endpunkt, falls er nicht neben dem Script liegt oder umbenannt wurde. Standard: `<Ordner des Scripts>/api/event`. |
| `data-hash` | Zählt auch Wechsel des Anker-Teils (`#/seite`) als Seitenaufruf, für Seiten mit Hash-Routing. |
| `data-outbound` | Zählt Klicks auf ausgehende Links (`Outbound Link`) und Downloads (`File Download`: pdf, zip, doc, xls, ppt, csv, mp3, mp4, dmg, exe). |
| `data-404` | Meldet das Ereignis `404`, wenn der Seitentitel nach einer Fehlerseite aussieht. Zählt dann keinen normalen Aufruf. |
| `data-local` | Erfasst auch auf `localhost` (sonst bleibt das Script dort still). |

Einzelseiten-Anwendungen müssen nichts tun: Das Script erkennt `history.pushState` und `popstate`.

### Eigene Ereignisse

```js
pegelstand('Anmeldung', { props: { plan: 'pro' } });
```

Bis zu 10 Eigenschaften, Namen bis 64 Zeichen (Buchstaben, Ziffern, `_ . - Leerzeichen`), Werte bis 255 Zeichen. Der Name `pageview` ist für Seitenaufrufe reserviert. Schicke keine personenbezogenen Daten als Eigenschaften.

### Eigene Besuche ausschließen

- Im Browser, in dem du Pegelstand nicht zählen willst, einmalig in der Konsole: `localStorage.pegelstand_ignore = 'true'`.
- Feste Adressen (Büro): ab Phase 6 in den Site-Einstellungen. Das Datenmodell (`site_ip_exclusions`) gibt es schon, Einträge nimmt der Erfassungs-Endpunkt bereits ernst.
- Das Script bleibt außerdem still in automatisierten Browsern (`navigator.webdriver`, "Headless") und auf `localhost`.

## Was der Server speichert

Pro Aufruf: Zeitpunkt (UTC), Pfad **ohne** Query-String, Referrer nur als Domain (eigene Hosts ausgenommen), UTM-Werte, Land, Gerätetyp, Browser- und Betriebssystemname **ohne Version**, und einen anonymen Besucher-Hash.

Nicht gespeichert und nicht protokolliert: IP-Adresse, vollständiger User-Agent, Query-Strings, Anker, Cookies, Bildschirmgröße, Sprache.

### Besucher-Hash

`HMAC-SHA-256(Tages-Salt, Site-ID | IP | User-Agent)`, auf 16 Byte gekürzt. IPv6-Adressen werden vorher auf das /64-Netz gekürzt. Das Tages-Salt wird zufällig erzeugt, wechselt um Mitternacht (Standard: Europe/Berlin, Konfiguration `rotation_timezone`) und das alte Salt wird gelöscht. Danach lässt sich aus einem Hash keine Adresse mehr nachrechnen, und derselbe Besucher erscheint am nächsten Tag als neuer Besucher. Folge: "Besucher" über mehrere Tage sind die Summe der Tageswerte.

### Sitzungen

Ein Aufruf gehört zur Sitzung desselben Hashs, wenn der letzte Aufruf weniger als 30 Minuten zurückliegt. Der Absprung (genau ein Seitenaufruf, kein Ereignis) und die Dauer ergeben sich daraus. Wenn zwei erste Aufrufe eines Besuchers exakt gleichzeitig eintreffen, können zwei Sitzungen entstehen.

### Ratenbegrenzung

Je Besucher-Adresse höchstens 300 Anfragen pro Minute (`ingest.rate_limit`). Gezählt wird über einen gesalzenen Hash der Adresse, die Zeilen sind nach Minuten wieder weg.

## Antworten des Endpunkts

`POST /api/event`, Body ist JSON (Content-Type `text/plain`, damit keine Preflight-Anfrage nötig ist).

| Status | Bedeutung |
|---|---|
| 202 | Angenommen oder bewusst ignoriert (Bot, fremder Host, DNT/GPC, ausgeschlossene Adresse). Von außen nicht zu unterscheiden. |
| 400 | Kein gültiges JSON oder Pflichtfelder fehlen. |
| 404 | Unbekannte Site-ID. |
| 413 | Anfrage größer als 8 KB. |
| 429 | Ratenbegrenzung. |

Ein Aufruf zählt nur, wenn der Hostname der Seite zur Site gehört: Hauptdomain (mit und ohne `www.`), zusätzlich erlaubte Hostnamen und Wildcards wie `*.beispiel.de`.

`Do Not Track` und `Sec-GPC` beachtet Pegelstand nur, wenn du es pro Site einschaltest (Standard: aus, Einstellung ab Phase 6). Ob das für dich nötig ist, klärst du selbst.

## Länder (GeoIP)

Pegelstand lädt nichts selbst herunter. Wenn du Länder auswerten willst, lege eine Datenbank im MMDB-Format ab:

1. Lade eine Länder-Datenbank, z. B. DB-IP "IP to Country Lite" oder MaxMind GeoLite2 Country, selbst herunter. Beachte deren Lizenz (DB-IP Lite: CC BY 4.0 mit Namensnennung, `TODO(prüfen)`; GeoLite2 verlangt ein Konto und eine Zustimmung).
2. Speichere sie als `storage/geoip/country.mmdb`.

Ohne Datei bleibt das Land unbekannt, die Erfassung läuft trotzdem. Die Adresse wird nur für die Abfrage im Arbeitsspeicher benutzt.

## Hinter einem Reverse-Proxy

Steht Pegelstand hinter nginx, Cloudflare o. Ä., sieht PHP nur die Adresse des Proxys. Trage die Kopfzeile und die Adressen deiner Proxys in `config/config.php` ein:

```php
'proxy' => ['header' => 'X-Forwarded-For', 'trusted' => ['127.0.0.1', '10.0.0.0/8']],
```

Die Kopfzeile gilt nur, wenn die Verbindung von einer vertrauten Adresse kommt. Sonst könnte jeder Besucher seine Adresse fälschen. Bei mehreren Einträgen zählt der erste von rechts, der kein vertrauter Proxy ist.

## Namen ändern

Blocker-Listen kennen `/p.js` und `/api/event`. Du kannst beides umbenennen:

```php
'tracker' => ['script_path' => '/stats.js', 'endpoint_path' => '/stats/senden'],
```

Dann im Tracking-Code `data-api="https://stats.beispiel.de/stats/senden"` ergänzen. Bei Apache braucht das nichts weiter, alles Unbekannte geht an `index.php`.

## Bot-Erkennung

`resources/data/bots.php` enthält Teilstrings (klein geschrieben), die einen User-Agent als Automaten markieren, dazu gilt ein leerer oder sehr kurzer User-Agent als Automat. Die Liste darfst du erweitern. Sie ist bewusst einfach und erkennt nicht jeden Bot.

## Bekannte Grenzen

- iPadOS meldet sich als Mac und zählt als Desktop.
- Browser mit Datenschutz-Erweiterungen blockieren das Script oder den Endpunkt. Dann fehlen diese Besucher in den Zahlen.
- `fetch` mit `keepalive` wird nicht von allen alten Browsern unterstützt. Dann wird der Aufruf normal gesendet und kann beim Verlassen der Seite verloren gehen.
