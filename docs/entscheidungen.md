# Entscheidungslog

Frank hat die Entscheidungen ab Phase 3 an den Entwickler delegiert ("löse alles für mich und mache weiter ohne mich ständig zu fragen"). Alles Folgenreiche steht hier, damit es nachvollziehbar und änderbar bleibt. Rückfragen gibt es nur bei Irreversiblem oder Außenwirksamem (Pull Request, externe Dienste, Veröffentlichung).

## Phase 3: Erfassung

| # | Entscheidung | Begründung |
|---|---|---|
| 1 | GeoIP: kein automatischer Download. Der Betreiber legt eine MMDB-Datei (DB-IP Lite Country oder MaxMind GeoLite2 Country) nach `storage/geoip/country.mmdb`. Ohne Datei bleibt das Land unbekannt. | Lizenz und Bezugsquelle sind Sache des Betreibers (DB-IP Lite: CC BY 4.0 mit Namensnennung, `TODO(prüfen)`). Kein Request an Dritte durch Pegelstand. |
| 2 | Eigener MMDB-Leser (`src/Geo/MmdbReader.php`), keine Laufzeit-Abhängigkeit. Geprüft gegen die offiziellen Testdateien von MaxMind (lokal, nicht im Repository) und gegen eigene, synthetisch erzeugte Fixtures. | CLAUDE.md: keine neuen Laufzeit-Abhängigkeiten ohne Not. |
| 3 | IPv6-Adressen werden für den Besucher-Hash auf /64 gekürzt. | Ein Endgerät wechselt innerhalb seines /64 durch Privacy Extensions ständig die Adresse. |
| 4 | Ratenbegrenzung: 300 Anfragen pro Minute und gesalzenem IP-Hash (`ingest.rate_limit` in der Konfiguration änderbar). Abgelaufene Zeilen löscht die Anwendung nebenbei. | Schützt vor Fluten, ohne Klartext-IP zu speichern. |
| 5 | Bot-Liste als Datei `resources/data/bots.php` (Teilstrings, klein geschrieben). Leerer oder sehr kurzer User-Agent gilt als Bot. | Erweiterbar ohne Codeänderung. |
| 6 | Das Tracking-Script liegt unter `/p.js`, der Endpunkt unter `/api/event`. Beide Pfade sind per Konfiguration änderbar (`tracker.script_path`, `tracker.endpoint_path`), damit Blocker-Listen sie nicht trivial treffen. Das Script nimmt standardmäßig `<Verzeichnis des Scripts>/api/event`, sonst `data-api`. | Lastenheft: konfigurierbare Namen. |
| 7 | Übertragung per `fetch` mit `keepalive` und `credentials: omit`, nicht per `sendBeacon`. Content-Type `text/plain`, damit keine Preflight-Anfrage entsteht. | Kleiner, kein Cookie-/Credentials-Problem mit `Access-Control-Allow-Origin: *`. |
| 8 | Ungültige, gefilterte (Bot, DNT, GPC, Host, ausgeschlossene IP) und fehlerhafte Anfragen: Antwort 202 ohne Inhalt, nur unbekannte Site 404, zu große Anfrage 413, Überschreitung der Rate 429. | Ein Angreifer lernt aus den Antworten möglichst wenig. |
| 9 | UTM-Quelle und -Medium werden klein geschrieben gespeichert, die übrigen UTM-Felder unverändert. UTM und Referrer gelten nur für den Beginn einer Sitzung. | Gruppierung "Facebook"/"facebook". |
| 10 | Hash-Routing: Nur mit `data-hash` sendet das Script das Fragment mit. Der Server übernimmt es nur, wenn es mit `/` oder `!/` beginnt (Pfad wird `/seite#/unterseite`). | Normale Anker (`#kapitel`) erzeugen sonst falsche Seiten. |
| 11 | Keine eigene Opt-out-Seite. Das Script ignoriert Besucher mit `localStorage.pegelstand_ignore = "true"` (in der Doku erklärt, ein Befehl für die Browser-Konsole des Betreibers). | Eine Seite auf der Pegelstand-Domain kann den Speicher der fremden Website nicht setzen. |
| 12 | Der Sitzungs-Zuordnung bleibt es bei einem Nebenläufigkeits-Restrisiko: Zwei exakt gleichzeitige erste Aufrufe eines Besuchers können zwei Sitzungen erzeugen. | Eine Sperre würde jeden Aufruf bremsen. In der Doku vermerkt. |
| 13 | iPadOS meldet sich als Mac und wird als Desktop gezählt. | Der User-Agent allein unterscheidet es nicht, das Script soll klein bleiben. |
| 14 | Die PHP-Erweiterung `session` bleibt Pflicht (Installer, Login). | Siehe Phase 2. |

## Zurückgestellt

- Länderkarte im Dashboard (Phase 5, ohne externe Kartendaten nur mit eigener Geometrie).
- "Jahr" im Zeitraum-Menü bedeutet "Dieses Jahr" (bis heute).
