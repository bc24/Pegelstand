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

## Phase 4: Aggregation und Hintergrundjobs

| # | Entscheidung | Begründung |
|---|---|---|
| 15 | Aggregate werden pro Site und Tag komplett neu berechnet und ersetzen die alten Zeilen (wiederholbar, fängt Nachzügler ab). | Einfacher und robuster als inkrementelle Zähler. |
| 16 | Sitzungswerte (Besucher, Sitzungen, Absprünge, Dauer, Herkunft, Gerät, Land, Einstiegs- und Ausstiegsseite) zählen an dem Tag, an dem die Sitzung begann. Seitenaufrufe und Ereignisse zählen an dem Tag, an dem sie geschahen. | Eine Sitzung hat genau einen Beginn. Die Abweichung betrifft nur Sitzungen über Mitternacht. |
| 17 | Stundenwerte werden in UTC-Stundengrenzen gerechnet und in die Ortszeit der Site umgerechnet. Bei Zeitzonen mit halbstündigem Versatz (z. B. Indien) beginnen die Stunden daher um :30. Dadurch braucht Pegelstand keine Zeitzonen-Tabellen in MySQL (`CONVERT_TZ`), die auf Shared Hosting oft fehlen. | Läuft überall. |
| 18 | Der Aggregationsjob merkt sich je Site, ab welchem Tag alles endgültig ist (`agg_cursor_<id>` in `settings`). Ab dem Vortag wird bei jedem Lauf neu gerechnet, höchstens 31 Tage pro Lauf (Nachholen älterer Daten). | Begrenzt die Laufzeit pro Lauf. |
| 19 | Aufräumen löscht Rohdaten nur, wenn sie älter als die Aufbewahrungsfrist **und** schon aggregiert sind. Aggregate und Wörterbücher bleiben. | Sonst gingen Zahlen verloren. Wörterbuch-Einträge werden von Aggregaten referenziert und bleiben deshalb bestehen. |
| 20 | Pseudo-Cron: Nach der Antwort an den Besucher prüft Pegelstand höchstens einmal pro Minute (Marker-Datei `storage/cache/cron-last`), ob Jobs fällig sind. Mit `cron.mode = external` in der Konfiguration schaltest du das ab und nutzt `php bin/cron.php`. | Läuft auf jedem Hoster ohne Cron. Ohne `fastcgi_finish_request` (reines mod_php) wartet der Besucher auf den Job, der pro Lauf begrenzt ist. |
| 21 | Jobs sperren sich über die Tabelle `job_runs` (abgelaufene Sperren abgestürzter Läufe werden übernommen), nicht über Dateisperren. | Funktioniert auf Hostern mit Netzwerk-Dateisystem. |
| 22 | `bin/cron.php` bleibt im Release-Zip, die übrigen Skripte in `bin/` nicht. `bin/demo-data.php` und `bin/benchmark.php` sind Entwicklungswerkzeuge. | Endnutzer brauchen nur den Cron-Aufruf. |

## Phase 5: Dashboard mit echten Daten

| # | Entscheidung | Begründung |
|---|---|---|
| 23 | Das Dashboard liest ohne Filter und bei mehr als 2 Tagen aus den Aggregaten, sonst (Heute, Gestern, jeder Filter) aus den Rohdaten. | Aggregate sind in Millisekunden da, Rohdaten stimmen in Echtzeit und erlauben Filter (siehe `docs/performance.md`). Filter auf großen Zeiträumen sind langsamer. |
| 24 | Ein Test erzwingt, dass Aggregate und Rohdaten dieselben Zahlen liefern. | Beide Pfade dürfen nie auseinanderlaufen. |
| 25 | "Aktive Besucher" = Sitzungen mit Aufruf in den letzten 5 Minuten. | Üblicher Wert, im Tooltip genannt. |
| 26 | Die Oberfläche nutzt dieselben JS-Module wie der Prototyp, nur die Datenquelle wechselt (`quelle.js`). | Eine Codebasis, der Prototyp bleibt testbar. |
| 27 | Ziele (Phase 7) fehlen noch: Tabelle "Ziele" ist leer, Filter `ziel` wird ignoriert. | Gehört zu Phase 7. |
| 28 | Anmeldung ist minimal (E-Mail, Passwort, Sperre nach 10 Fehlversuchen je 15 Minuten, Abmelden per POST). 2FA, Passwort-Reset, Benutzerverwaltung folgen in Phase 6. | Phasenplan. |
| 29 | Startseite `/` ist das Dashboard (erste Site des Benutzers); ohne Site eine Hinweisseite. Sites anlegen per Oberfläche folgt in Phase 6. | Phasenplan. |

## Phase 6: Verwaltung

| # | Entscheidung | Begründung |
|---|---|---|
| 30 | Einstellungen unter `/einstellungen`: Websites (anlegen, ändern, Tracking-Code, Ausschlussliste, Löschen mit Domain-Bestätigung), Benutzer (anlegen, Rolle, Websites freigeben, sperren, löschen), Mein Konto (Passwort). Betrachter sehen nur Mein Konto. | Lastenheft Phase 6. |
| 31 | Der letzte aktive Administrator lässt sich nicht herabstufen, sperren oder löschen; niemand löscht sich selbst. | Aussperren verhindern. |
| 32 | Rollen bleiben bei zwei (Administrator, Betrachter) wie im Schema. | Schema freigegeben. |
| 33 | Passwort-Reset per E-Mail und der SMTP-Client folgen mit Phase 7, weil beides E-Mail-Einstellungen braucht. Bis dahin setzt ein Administrator Passwörter über die Benutzerverwaltung neu. | Gemeinsame Grundlage mit den Berichten. |
| 34 | 2FA per TOTP (RFC 6238), eigene Umsetzung ohne Abhängigkeit, geprüft mit den RFC-Testvektoren. Das Geheimnis liegt mit AES-256-GCM (Schlüssel aus `app_key`) verschlüsselt in der Datenbank, ein Code gilt nur einmal. Einrichtung per Schlüssel und `otpauth://`-Link, kein QR-Code (dafür bräuchte es eine Bibliothek). Keine Wiederherstellungscodes: Ein Administrator setzt 2FA eines Benutzers zurück. | Keine neue Abhängigkeit; Wiederherstellung über den Administrator ist einfach und nachvollziehbar. Hinweis: Verliert der einzige Administrator sein Gerät, hilft nur ein Eingriff in der Datenbank (`totp_enabled_at` auf NULL). |

## Phase 7: E-Mail, Ziele

| # | Entscheidung | Begründung |
|---|---|---|
| 35 | Ziele sind Seiten (Pfad) oder Ereignisse (Name), gespeichert in `goals`. Die Auswertung nutzt die vorhandenen Seiten- und Ereignis-Aggregate (kein eigenes Aggregat) und gilt deshalb rückwirkend. Besucher über mehrere Tage sind die Summe der Tageswerte. | Weniger Tabellen, gleiche Genauigkeit wie der Rest. |
| 36 | Eigener SMTP-Client ohne Abhängigkeit (STARTTLS, SSL, PLAIN/LOGIN, Zertifikatsprüfung, UTF-8). Zugangsdaten in `settings`, das Passwort mit dem `app_key` verschlüsselt. | Keine Abhängigkeit, läuft auf jedem Hoster mit Socket-Zugriff (`TODO(prüfen)`: Hoster, die ausgehende SMTP-Verbindungen sperren). |
| 37 | Links in E-Mails nutzen die in den E-Mail-Einstellungen gespeicherte Adresse, nie den `Host`-Header der Anfrage. | Schutz vor manipulierten Links in Passwort-E-Mails. |
| 38 | Passwort-Reset: Einmal-Link, eine Stunde gültig, nur der Hash des Tokens in der Datenbank, immer dieselbe Antwort, begrenzt auf 5 Anfragen pro Adresse und 3 pro E-Mail-Adresse und Stunde. 2FA bleibt davon unberührt. Ohne eingerichteten E-Mail-Versand gibt es den Weg nicht. | Standardvorgehen ohne Hinweis darauf, welche Adressen existieren. |
| 39 | REST-API Version 1, nur lesend: `GET /api/v1/sites` und `GET /api/v1/sites/{id}/stats`, Anmeldung per `Authorization: Bearer`. Schlüssel gehören einem Benutzer, werden als SHA-256-Hash gespeichert, einmal angezeigt und sehen genau die Websites ihres Benutzers. 120 Anfragen pro Minute und Schlüssel. Die Antwort ist dasselbe JSON wie das des Dashboards (deutsche Feldnamen). | Wenig Oberfläche, kein zweites Rechtemodell. |
| 40 | CSV-Export: UTF-8 mit BOM, Semikolon, Dezimalkomma, Formel-Einschleusung entschärft. Er enthält höchstens die 50 stärksten Zeilen je Tabelle. | Für deutsches Excel gedacht. |
| 41 | Öffentliches Dashboard: geheimer Link `/oeffentlich/{Token}` (128 Bit), ein- und ausschaltbar, jederzeit durch einen neuen Link ersetzbar. Schreibgeschützt, ohne Verwaltung und Export, mit `Referrer-Policy: no-referrer`, höchstens 60 Anfragen pro Minute und Adresse. Filter bleiben erlaubt. | Wer den Link kennt, sieht alles im Dashboard; die Einstellungsseite sagt das ausdrücklich. |
