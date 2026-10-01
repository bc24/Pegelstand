# Pegelstand

Webanalyse ohne Cookies, die auf jedem PHP-Webspace läuft. Hochladen, installieren, fertig.

> **Status:** in Entwicklung (Phase 0, Grundgerüst). Es gibt noch keine lauffähige Version.
> Dieses README wird in Phase 8 vollständig ausgearbeitet.

## Entwicklung

Voraussetzungen: PHP 8.2 oder neuer, Composer, Node.js (nur für Entwicklung und E2E-Tests).

```bash
composer install
npm install

composer check        # Code-Style, statische Analyse und PHPUnit
npm run test:e2e      # Playwright (startet den PHP-Entwicklungsserver selbst)
```

Integrationstests laufen gegen eine echte MySQL- oder MariaDB-Instanz, wenn
`PEGELSTAND_TEST_DB_DSN`, `PEGELSTAND_TEST_DB_USER` und `PEGELSTAND_TEST_DB_PASSWORD`
gesetzt sind. Ohne diese Variablen werden sie übersprungen.

## Lizenz

[MIT](LICENSE). © 2026 [Frank Panzer](https://frank-panzer.de) · Entwickelt von [Panzer IT](https://panzerit.de)
