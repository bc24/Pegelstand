<!-- Automatisch aus docs/installation.md erzeugt (php bin/build-wiki.php). Änderungen bitte dort vornehmen. -->
# Installation

Pegelstand läuft auf normalem PHP-Webspace. Du brauchst keine Kommandozeile.

> Das fertige Release-Zip erscheint mit Version 1.0. Bis dahin baust du es selbst (`bin/build-release.sh`, siehe [README](https://github.com/bc24/Pegelstand/blob/main/README.md#entwicklung)) oder installierst aus dem Quellcode.

## Voraussetzungen

- PHP 8.2 oder neuer mit `pdo_mysql`, `json`, `mbstring`, `openssl`, `session` (die PHP-Version stellst du bei den meisten Hostern in der Verwaltung um)
- MySQL 8.0+ oder MariaDB 10.6+ mit einer leeren Datenbank
- Apache mit `mod_rewrite` und erlaubter `.htaccess` (`AllowOverride All`). Für nginx siehe [nginx](nginx).
- Besser auf einer eigenen (Sub-)Domain wie `stats.beispiel.de`. Ein Unterverzeichnis geht auch.
- HTTPS. Das Tracking-Script wird auf deinen Websites eingebunden, die selbst meist HTTPS nutzen.

## Schritt für Schritt

1. **Datenbank anlegen.** In der Verwaltung deines Hosters eine neue Datenbank mit Benutzer anlegen. Notiere Server (oft `localhost`), Name, Benutzer und Passwort.
2. **Dateien hochladen.** Entpacke das Zip und lade den **Inhalt** des Ordners `pegelstand` per FTP/SFTP in das Verzeichnis, das als Webseite ausgeliefert wird. Das Verzeichnis ist zugleich das Document Root, es gibt keinen `public/`-Ordner.
3. **Schreibrechte.** Die Ordner `config/` und `storage/` müssen für den Webserver beschreibbar sein (meist Rechte 755 oder 775, je nach Hoster). Der Installer prüft das und sagt dir, was fehlt.
4. **Installer öffnen.** Rufe die Adresse im Browser auf. Der Installer führt dich durch vier Schritte (Prüfung, Datenbank, Administrator, Fertig).
5. **Website anlegen.** Melde dich an, öffne Einstellungen, Websites und lege deine erste Website an. Du bekommst den Tracking-Code.
6. **Code einbinden.** Setze die Zeile in den Kopfbereich (`<head>`) deiner Seiten, siehe [Tracking Code und Ereignisse](Tracking-Code-und-Ereignisse). Rufe die Seite einmal auf, nach kurzer Zeit erscheint der Besuch im Dashboard.

Der Installer sperrt sich nach dem Abschluss selbst. `config/config.php` enthält deine Zugangsdaten und den `app_key`. Sichere sie (siehe [Datensicherung](Datensicherung)), gib sie aber nie weiter.

## Danach (alles optional)

- **E-Mail-Versand** unter Einstellungen, E-Mail einrichten. Das brauchen Berichte und „Passwort vergessen“.
- **Länder** anzeigen: GeoIP-Datei ablegen, siehe [Tracking Code und Ereignisse](Tracking-Code-und-Ereignisse#länder-geoip).
- **Echter Cronjob** statt Pseudo-Cron: siehe [Hintergrundjobs](Hintergrundjobs).
- **Hinter einem Proxy oder Cloudflare:** `proxy` in der Konfiguration setzen, siehe [Tracking Code und Ereignisse](Tracking-Code-und-Ereignisse#hinter-einem-reverse-proxy).
- **Zwei-Faktor-Anmeldung** unter Mein Konto einschalten.

## Prüfen, ob die interne Ordner geschützt sind

Rufe `https://deine-domain/config/config.example.php` und `https://deine-domain/storage/` im Browser auf. Beides muss **nicht gefunden** (404) oder **verboten** (403) ergeben. Der Installer prüft das ebenfalls. Siehst du dort Inhalte, ist die `.htaccess` wirkungslos (`AllowOverride` fehlt). Dann liegen deine Zugangsdaten offen, behebe das, bevor du weitermachst.

Hilfe bei Problemen: [Fehlerbehebung](Fehlerbehebung).
