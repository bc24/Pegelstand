# Entwicklung

Pegelstand ist Open Source (MIT). Der Quellcode: https://github.com/bc24/Pegelstand

## Einrichten

Voraussetzungen: PHP 8.2 oder neuer, Composer, Node.js (nur für Entwicklung und Tests), eine MySQL- oder MariaDB-Datenbank.

```bash
composer install
npm install
npm run build          # CSS, JavaScript, Schrift, Icons, Tracking-Script nach assets/
php -S 127.0.0.1:8080  # Installer im Browser öffnen
```

Mit `php bin/demo-data.php --site=1 --days=90 --sessions=300` füllst du eine Website mit erfundenen Daten.

## Prüfen

```bash
composer check         # Code-Style (PHP-CS-Fixer), PHPStan Level max, PHPUnit
npm run test:e2e       # Playwright mit axe-core, vier Darstellungen
```

Integrationstests laufen gegen eine echte Datenbank, wenn `PEGELSTAND_TEST_DB_DSN`, `PEGELSTAND_TEST_DB_USER` und `PEGELSTAND_TEST_DB_PASSWORD` gesetzt sind, sonst werden sie übersprungen. Die E2E-Tests starten pro Darstellung (Desktop und Smartphone, hell und dunkel) einen eigenen PHP-Server.

## Aufbau

| Ordner | Inhalt |
|---|---|
| `src/Core` | Front Controller, Router, Container, Sitzung, Templates |
| `src/Ingest`, `src/Geo` | Erfassung: Endpunkt, Filter, Hashing, Sitzungen, GeoIP-Leser |
| `src/Stats`, `src/Jobs` | Aggregation, Dashboard-Daten, Hintergrundjobs |
| `src/Auth`, `src/Settings`, `src/Api`, `src/Mail`, `src/Reports` | Anmeldung, Verwaltung, REST-API, SMTP, Berichte |
| `database/migrations` | Datenbankmigrationen (wiederholbar, mit Prüfsumme) |
| `resources` | Texte, Templates, CSS und JavaScript (Quellen) |
| `website` | Statische Projekt-Website |
| `docs` | Dokumentation (Quelle dieses Wikis) |

Es gibt kein Framework. Regeln, die gelten: PDO mit Prepared Statements, Templates mit Escaping, keine Inline-Skripte, keine Laufzeit-Abhängigkeiten ohne Absprache, deutsche Oberfläche und Dokumentation, englische Bezeichner im Code. Die Entscheidungen stehen im [Entscheidungslog](https://github.com/bc24/Pegelstand/blob/main/docs/entscheidungen.md), das Datenmodell im [Schema-Entwurf](https://github.com/bc24/Pegelstand/blob/main/docs/schema-entwurf.md).

## Release

`bin/build-release.sh <Version>` baut das Zip (Allowlist, frisches `vendor/` ohne Entwicklungspakete, Prüfsumme). Die CI baut und prüft es bei jedem Lauf, ein Tag `vX.Y.Z` veröffentlicht es als GitHub-Release.

## Dieses Wiki

Die Seiten liegen im Ordner `wiki/` des Repositories. Die Seiten aus `docs/` erzeugt `php bin/build-wiki.php`, ändere also dort die Quelle. Von Hand geschrieben sind Home, Dashboard, Verwaltung, Datenschutz, Häufige Fragen und diese Seite.
