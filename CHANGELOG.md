# Changelog

Alle nennenswerten Änderungen an diesem Projekt werden in dieser Datei festgehalten.

Das Format orientiert sich an [Keep a Changelog](https://keepachangelog.com/de/1.1.0/),
die Versionierung folgt [Semantic Versioning](https://semver.org/lang/de/).

## [Unveröffentlicht]

### Hinzugefügt

- Aggregation und Hintergrundjobs (Phase 4): Migration `0003` (Tages-, Stunden-, Aufschlüsselungs- und Eigenschafts-Aggregate, Jobsperren), Aggregationsjob mit Nachholen, Aufräumjob nach Aufbewahrungsfrist, Pseudo-Cron nach der Antwort, `bin/cron.php` für echten Cron, Demo-Daten-Generator (`bin/demo-data.php`) und Benchmark (`bin/benchmark.php`). Doku in `docs/hintergrundjobs.md`.
- Erfassung (Phase 3): Endpunkt `POST /api/event`, Tracking-Script `p.js` (unter 2 KB gzip), Migration `0002` (Salt, Wörterbücher, Sitzungen, Ereignisse), Besucher-Hash mit Tages-Salt, Sitzungszuordnung, Bot-Filter, Hostprüfung, Ratenbegrenzung, UTM und Referrer, eigener MMDB-Leser für Länder, Proxy-Unterstützung. Doku in `docs/tracking.md`, Entscheidungen in `docs/entscheidungen.md`.
- Installer in vier Schritten (Systemprüfung, Datenbank, Administrator, Fertig) mit verständlichen Fehlermeldungen, Selbstsperre und Konfigurationsdatei (Phase 2).
- Anwendungskern (Router, Container, Sitzung, CSRF, Templates, Übersetzer) und Migrationssystem mit Migration `0001` (Einstellungen, Benutzer, Sites).
- Komponenten Hinweis, Fortschrittsanzeige und Chip im Designsystem.
- Dashboard-Prototyp mit Demo-Daten (Phase 1, Schritt 2): Kennzahlen, Diagramm, Filter, Zeiträume, Befehlspalette, Tastaturkürzel und alle Zustände.
- Designsystem (Phase 1, Schritt 1): Tokens für hell und dunkel, Typografie mit Inter, Lucide-Icons, Komponenten und interne Komponentenseite.
- Projektgerüst: Verzeichnisstruktur, Composer, PHPUnit, PHPStan, PHP-CS-Fixer, Playwright, CI, MIT-Lizenz.
