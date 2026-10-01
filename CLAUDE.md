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
- Laufzeit-Abhängigkeiten: derzeit keine. Jede neue vorher mit Frank abstimmen, Lizenz muss MIT-kompatibel sein. Pflicht-Erweiterungen: pdo_mysql, json, mbstring, openssl.
- Das Release-Zip enthält `vendor/` und gebaute Assets (`bin/build-release.sh`, Allowlist). Endnutzer brauchen weder Composer noch Node.
- Frontend: Vanilla JS oder Alpine.js (Entscheidung in Phase 1), lokale Diagramm-Bibliothek, keine CDNs, keine externen Schriften, keine Requests an Dritte. Eigene CSS-Design-Tokens statt Tailwind. Build-Werkzeuge (esbuild) nur für Entwickler.
- Datenschutz: keine Cookies, kein Fingerprinting, IP-Adressen nie speichern oder loggen, Besucher-Hash mit täglich rotierendem Salt. Keine Rechtsgarantien in Texten (siehe Lastenheft).
- Tracking-Script unter 2 KB gzip, geprüft von `bin/check-script-size.sh` (CI).

## Qualität und Werkzeuge

- `composer cs` / `composer cs:fix` (PHP-CS-Fixer, `@PER-CS`, strict_types), `composer analyse` (PHPStan Level max, keine Baseline), `composer test` (PHPUnit: Suites Unit und Integration), `composer check` (alles).
- Integrationstests nutzen `PEGELSTAND_TEST_DB_DSN`, `PEGELSTAND_TEST_DB_USER`, `PEGELSTAND_TEST_DB_PASSWORD`; ohne diese werden sie übersprungen.
- E2E: Playwright mit axe-core, vier Projekte (desktop-hell, desktop-dunkel, smartphone-hell, smartphone-dunkel). `PEGELSTAND_CHROMIUM` kann den Browserpfad vorgeben.
- CI (`.github/workflows`): Stil und Analyse, Test-Matrix PHP 8.2 bis 8.5 gegen MySQL 8.0/8.4 und MariaDB 10.6/11.4 (`TODO(prüfen)` gegen offizielle Quellen), Script-Größe, E2E; `release.yml` baut bei Tags das Zip.

## Hinweise für Cloud-Sessions

- `composer` braucht `COMPOSER_ALLOW_SUPERUSER=1`.
- Zip-Downloads von `api.github.com` und `codeload.github.com` sind gesperrt (403), `git clone` über github.com funktioniert. Lokale Installation daher mit temporär angepasstem Lockfile (ohne `dist`-Einträge, `--prefer-source`; PHPStan ist dist-only und wird aus einem flachen Clone eingebunden). Das committete `composer.lock` bleibt unverändert.
- Lokal nur PHP 8.3 und MariaDB 10.11 (`service mariadb start`); Docker-Daemon ist nicht erreichbar. Alles darüber hinaus prüft nur die CI.
