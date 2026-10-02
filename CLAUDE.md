# CLAUDE.md – Pegelstand

Datenschutzfreundliche Webanalyse in PHP und MySQL/MariaDB. Läuft auf günstigem Shared Hosting (kein Docker, kein Node, kein Root, keine Kommandozeile beim Endnutzer). Open Source (MIT), öffentlich auf GitHub.

## Arbeitsweise

- Strikt nach Phasen 0 bis 9. Jede Phase beginnt mit einem kurzen Plan (Dateien, Tabellen, offene Fragen); implementiert wird erst nach Freigabe durch Frank.
- Keine stillen Annahmen bei Laufzeit-Abhängigkeiten, Datenbankschema, Lizenzen externer Daten und Schriften: nachfragen. Schema-Entwurf vor den Migrationen zur Freigabe vorlegen.
- Nichts bauen, was nicht im Lastenheft steht, ohne vorher zu fragen. Erst nach Version 1.0: WordPress-Plugin, englische Oberfläche, Importe, Webhooks.
- Keine erfundenen Fakten: keine Zahlen über Konkurrenzprodukte, keine Rechtsaussagen, keine hosterspezifischen Details. Ungeprüftes mit `TODO(prüfen)` markieren.
- Nach jedem Schritt: `composer check` (Code-Style, PHPStan, PHPUnit) und bei Oberfläche `npm run test:e2e`. Erst bei Grün committen.
- Commits: Conventional Commits, Beschreibung auf Deutsch, ein Commit pro logischem Schritt. Entwicklung nur auf dem zugewiesenen Branch, kein Pull Request ohne ausdrücklichen Wunsch.
- Nach jedem Schritt mit Oberfläche: Playwright-Screenshots (Desktop und Smartphone 360 px, hell und dunkel), selbst gegen die Design-Checkliste prüfen, Mängel beheben, dann berichten.
- Jede Phase endet mit grünen Tests, geprüften Screenshots und kurzem Bericht (gebaut, offen, bekannte Schwächen).

## Sprache

Deutsch: Oberfläche, Texte, README, Doku, Code-Kommentare, Commit-Beschreibungen, Kommunikation. Ansprache der Nutzer mit "du". Englisch: Klassen-, Funktions-, Variablen- und Tabellennamen. Begriffe verbindlich in `docs/glossar.md` (ab Phase 1). Zahlen und Daten deutsch (1.234,5 und 29.09.2026), Standard-Zeitzone Europe/Berlin.

## Architektur-Entscheidungen

- PHP >= 8.2, MySQL 8.0+ und MariaDB 10.6+, Zugriff ausschließlich über PDO mit Prepared Statements.
- Kein Full-Stack-Framework: Front Controller, Router, PSR-4 (`Pegelstand\` nach `src/`), einfacher DI-Container, Templates in plain PHP mit konsequentem Escaping.
- **Das Projektverzeichnis ist das Document Root, es gibt kein `public/`** (Apache). Sensible Pfade sperren die Root-`.htaccess` (404) und je eine `.htaccess` mit `Require all denied` (403; mit Apache 2.4 und mod_php geprüft) in `config`, `src`, `storage`, `bin`, `tests`, `resources`; `vendor/` erhält sie beim Build. Zusätzlich Direktzugriffsschutz in PHP-Dateien mit Nebenwirkungen. Öffentlich sind nur `index.php` und `assets/`. Beispielkonfiguration für nginx folgt in Phase 8.
- Kern (`src/Core`): `Application` (Installer ohne Konfiguration, sonst Betrieb), `Request` (Pfade relativ zum Unterverzeichnis), `Response` (Sicherheits-Header auf jeder Antwort, strenge CSP ohne Inline-Skripte), `Router`, `Container`, `View` (plain PHP, `e()` und `t()` maskieren), `Translator`, `Csrf`, `Session` (nur für Installer und angemeldete Bereiche, nie für Besucher), `ErrorLog` (keine Besucherdaten). Datenbank (`src/Database`): `Database` (PDO, Prepared Statements, gepufferte Abfragen), `Migrator`. Installer (`src/Install`). Texte in `resources/lang/de.php`, Templates in `resources/views`.
- Migrationen: `database/migrations/NNNN_name.php`, wiederholbar (`CREATE TABLE IF NOT EXISTS`), Prüfsumme wird gespeichert, Schema-Entwurf und Entscheidungen in `docs/schema-entwurf.md`. Migrationen laufen bei Updates automatisch.
- Konfiguration: `config/config.php` (vom Installer erzeugt, mit Direktzugriffsschutz), `config/installed.lock`. Für Tests lassen sich `PEGELSTAND_CONFIG_DIR` und `PEGELSTAND_STORAGE_DIR` umlenken.
- Laufzeit-Abhängigkeiten: derzeit keine. Jede neue vorher mit Frank abstimmen, Lizenz muss MIT-kompatibel sein. Pflicht-Erweiterungen: pdo_mysql, json, mbstring, openssl.
- Das Release-Zip enthält `vendor/` und gebaute Assets (`bin/build-release.sh`, Allowlist). Endnutzer brauchen weder Composer noch Node.
- Erfassung (`src/Ingest`, `src/Geo`): `Collector` verarbeitet `POST /api/event` (Parser, Bot-Filter, Hostprüfung, Ratenbegrenzung, Salt/Hash, Wörterbücher, Sitzungen), `IngestController` liefert Endpunkt und `/p.js` (Pfade per `tracker.*` änderbar). Eigener MMDB-Leser ohne Abhängigkeit, Länder-Datei manuell unter `storage/geoip/country.mmdb`. Tracker-Quelle `resources/js/tracker/p.js` (esbuild nach `assets/p.js`). Doku `docs/tracking.md`, Entscheidungen `docs/entscheidungen.md` (Frank hat Entscheidungen delegiert: selbst entscheiden, dort festhalten, nur bei Irreversiblem oder Außenwirksamem fragen).
- Aggregation (`src/Stats`, `src/Jobs`, `src/Demo`): `Aggregator` rechnet Tage neu, `Scheduler` führt `AggregationJob` und `CleanupJob` aus (Sperre über `job_runs`), Pseudo-Cron in `Application::afterResponse()` (Marker `storage/cache/cron-last`, abschaltbar mit `cron.mode = external`), `bin/cron.php`, `bin/demo-data.php`, `bin/benchmark.php`. Integrationstests nutzen `SiteTestCase` (Site mit Rohdaten-Helfer) und `InstalliertTestCase` (echte Installation im Temp-Verzeichnis). Achtung: Test mit leerem Tabellenpräfix räumt die ganze Test-Datenbank leer, Benchmarks deshalb in einer eigenen Datenbank laufen lassen.
- Frontend: Vanilla JS oder Alpine.js (Entscheidung in Phase 1), lokale Diagramm-Bibliothek, keine CDNs, keine externen Schriften, keine Requests an Dritte. Eigene CSS-Design-Tokens statt Tailwind. Build-Werkzeuge (esbuild) nur für Entwickler.
- Datenschutz: keine Cookies, kein Fingerprinting, IP-Adressen nie speichern oder loggen, Besucher-Hash mit täglich rotierendem Salt. Keine Rechtsgarantien in Texten (siehe Lastenheft).
- Frontend-Quellen in `resources/css` (Tokens, Basis, Komponenten) und `resources/js` (Vanilla-ES-Module, Event-Delegation, Texte nur per `textContent`). `npm run build` (esbuild, `bin/build-assets.mjs`) erzeugt `assets/` (nicht im Repository). Keine Inline-Skripte und keine Inline-Event-Handler (spätere CSP). Farben nur über `--ps-*`-Tokens (`light-dark()`), keine Sonderfarben. Neue Icons in `ICONS` im Build-Skript eintragen. Details in `docs/designsystem.md`, Begriffe in `docs/glossar.md`.
- Dashboard-Prototyp: `prototype/index.html`, Logik in `resources/js/dashboard/` (Demo-Daten in `demo-daten.js` als Platzhalter der API). Zustand steckt in der Adresszeile (siehe `docs/designsystem.md`). Größenbudget Dashboard-JS < 100 KB gzip (`npm run check:groesse`).
- Interne Komponentenseite: `prototype/komponenten.html` (nur Entwicklung, per `.htaccess` gesperrt, nicht im Zip). Prototypen liegen in `prototype/`.
- Projekt-Website: `website/*.html` (statisch, ohne JavaScript), CSS aus `resources/css/website.css`, `npm run build:website` (nach `npm run build`) erzeugt `website/assets/` (nicht im Repository). Impressum und Hoster-Angaben bleiben `TODO(prüfen)`, nie Angaben erfinden. Release-Zip: `bin/build-release.sh` (Allowlist), Demo-Modus: Konfiguration `demo`, Docker/nginx-Beispiele sind ungetestet.
- Tracking-Script unter 2 KB gzip, geprüft von `bin/check-script-size.sh` (CI).

## Qualität und Werkzeuge

- `composer cs` / `composer cs:fix` (PHP-CS-Fixer, `@PER-CS`, strict_types), `composer analyse` (PHPStan Level max, keine Baseline), `composer test` (PHPUnit: Suites Unit und Integration), `composer check` (alles).
- Integrationstests nutzen `PEGELSTAND_TEST_DB_DSN`, `PEGELSTAND_TEST_DB_USER`, `PEGELSTAND_TEST_DB_PASSWORD`; ohne diese werden sie übersprungen.
- E2E: Playwright mit axe-core, vier Projekte (desktop-hell, desktop-dunkel, smartphone-hell, smartphone-dunkel), jedes mit eigenem PHP-Server (Ports 8091 bis 8094), eigener temporärer Konfiguration und eigenem Tabellenpräfix (`e2e1_` bis `e2e4_`). `npm run test:e2e` baut vorher die Assets. Installer-Tests brauchen `PEGELSTAND_TEST_DB_*`, ohne sie werden sie übersprungen. `PEGELSTAND_CHROMIUM` kann den Browserpfad vorgeben.
- CI (`.github/workflows`): Stil und Analyse, Test-Matrix PHP 8.2 bis 8.5 gegen MySQL 8.0/8.4 und MariaDB 10.6/11.4 (`TODO(prüfen)` gegen offizielle Quellen), Script-Größe, E2E; `release.yml` baut bei Tags das Zip.

## Hinweise für Cloud-Sessions

- Kein `pkill -f` mit einem Muster, das im eigenen Befehl vorkommt: Die Shell beendet sich sonst selbst.
- Das lokale `vendor/` ist durch die Quell-Installation riesig (alle Git-Historien). Das Release-Zip entsteht in der CI mit `composer install --no-dev`.

- `composer` braucht `COMPOSER_ALLOW_SUPERUSER=1`.
- Zip-Downloads von `api.github.com` und `codeload.github.com` sind gesperrt (403), `git clone` über github.com funktioniert. Lokale Installation daher mit temporär angepasstem Lockfile (ohne `dist`-Einträge, `--prefer-source`; PHPStan ist dist-only und wird aus einem flachen Clone eingebunden). Das committete `composer.lock` bleibt unverändert.
- Lokal nur PHP 8.3 und MariaDB 10.11 (`service mariadb start`); Docker-Daemon ist nicht erreichbar. Alles darüber hinaus prüft nur die CI.
