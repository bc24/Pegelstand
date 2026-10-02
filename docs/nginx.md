# nginx

Pegelstand ist für Apache gebaut (die mitgelieferten `.htaccess`-Dateien sperren interne Ordner). Mit nginx musst du das selbst nachbilden. Eine Beispielkonfiguration liegt in [docker/nginx.conf.example](../docker/nginx.conf.example).

> Diese Konfiguration ist **ungetestet** (`TODO(prüfen)`), weil in der Entwicklungsumgebung kein nginx lief. Prüfe sie vor dem Einsatz.

Wichtig dabei:

1. **Interne Ordner sperren:** `config`, `src`, `storage`, `vendor`, `bin`, `database`, `resources` und alle Dotdateien müssen mit 404 antworten. Sonst liegen Zugangsdaten offen.
2. **Alles Unbekannte an `index.php`** weiterreichen. Das gilt auch für `/p.js` (Tracking-Script) und `/api/event` (Endpunkt).
3. **Nur `index.php` ausführen**, keine andere PHP-Datei.
4. **Statische Dateien** nur aus `/assets/`.

Nach dem Einrichten prüfst du wie in der [Installationsanleitung](installation.md#prüfen-ob-die-interne-ordner-geschützt-sind), ob `/config/config.example.php` und `/storage/` nicht erreichbar sind.

Sitzt nginx als Reverse-Proxy vor Apache oder PHP-FPM in einem anderen Container, muss Pegelstand die echte Besucheradresse bekommen, siehe [docs/tracking.md](tracking.md#hinter-einem-reverse-proxy).
