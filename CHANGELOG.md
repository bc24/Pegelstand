# Changelog

Alle nennenswerten Änderungen an diesem Projekt werden in dieser Datei festgehalten.

Das Format orientiert sich an [Keep a Changelog](https://keepachangelog.com/de/1.1.0/),
die Versionierung folgt [Semantic Versioning](https://semver.org/lang/de/).

## [Unveröffentlicht]

### Hinzugefügt

- Installer in vier Schritten (Systemprüfung, Datenbank, Administrator, Fertig) mit verständlichen Fehlermeldungen, Selbstsperre und Konfigurationsdatei (Phase 2).
- Anwendungskern (Router, Container, Sitzung, CSRF, Templates, Übersetzer) und Migrationssystem mit Migration `0001` (Einstellungen, Benutzer, Sites).
- Komponenten Hinweis, Fortschrittsanzeige und Chip im Designsystem.
- Dashboard-Prototyp mit Demo-Daten (Phase 1, Schritt 2): Kennzahlen, Diagramm, Filter, Zeiträume, Befehlspalette, Tastaturkürzel und alle Zustände.
- Designsystem (Phase 1, Schritt 1): Tokens für hell und dunkel, Typografie mit Inter, Lucide-Icons, Komponenten und interne Komponentenseite.
- Projektgerüst: Verzeichnisstruktur, Composer, PHPUnit, PHPStan, PHP-CS-Fixer, Playwright, CI, MIT-Lizenz.
