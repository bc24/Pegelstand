# Aktualisieren

1. **Sicherung machen** (Datenbank und `config/`, siehe [docs/datensicherung.md](datensicherung.md)). Lies vorher das [CHANGELOG](../CHANGELOG.md) der neuen Version.
2. **Alte Programmdateien ersetzen.** Lösche auf dem Server diese Ordner und Dateien und lade die der neuen Version hoch:
   `src/`, `vendor/`, `resources/`, `database/`, `assets/`, `docs/`, `bin/`, `licenses/`, `index.php`, `.htaccess` sowie `README.md`, `CHANGELOG.md`, `LICENSE`, `THIRD-PARTY.md`.
3. **Nicht anfassen** (bleiben, wie sie sind): `config/config.php`, `config/installed.lock` und der Inhalt von `storage/` (Logs, GeoIP-Datei, Sitzungen).
4. **Seite öffnen.** Beim ersten Aufruf führt Pegelstand die Datenbankmigrationen selbst aus. Währenddessen sehen Besucher kurz eine Wartungsseite, die sich neu lädt. Gemessen wird weiter, sobald die Migration fertig ist.

Warum die alten Ordner löschen? Dateien, die in der neuen Version nicht mehr existieren, würden sonst liegen bleiben und Verwirrung stiften.

## Wenn etwas schiefgeht

- Fehlerseite oder weißer Bildschirm: Schau in `storage/logs/error.log`.
- Eine Migration meldet einen Fehler: Spiele die Sicherung der Datenbank ein, behebe die Ursache (die Meldung nennt sie) und starte erneut. Migrationen lassen sich wiederholen.
- Zurück zur alten Version: Datenbank-Sicherung einspielen **und** die alten Programmdateien zurückkopieren. Die Datenbank allein zurückzusetzen reicht nicht, wenn neue Dateien liegen.
