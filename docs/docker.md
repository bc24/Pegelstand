# Docker

Ein Container-Setup mit Apache, PHP 8.3 und MariaDB liegt in `docker/`.

> **Ungetestet** (`TODO(prüfen)`): In der Entwicklungsumgebung stand kein Docker zur Verfügung. Die Dateien folgen dem Standardvorgehen, sind aber nie gebaut worden. Melde Fehler gern.

```bash
export DB_PASSWORT='ein-langes-passwort'
docker compose -f docker/docker-compose.yml up -d --build
```

Öffne dann `http://localhost:8080`. Im Installer trägst du ein:

| Feld | Wert |
|---|---|
| Server | `db` |
| Datenbankname | `pegelstand` |
| Benutzer | `pegelstand` |
| Passwort | der Wert von `DB_PASSWORT` |

Konfiguration und Daten liegen in den Volumes `pegelstand_config` und `pegelstand_storage`, die Datenbank in `pegelstand_db`. Sie bleiben beim Neubauen erhalten.

- **Aktualisieren:** neue Version holen, `docker compose … up -d --build`. Die Migrationen laufen beim ersten Aufruf.
- **Cronjob:** Der Pseudo-Cron reicht. Für einen echten: `docker compose -f docker/docker-compose.yml exec -u www-data app php bin/cron.php` per Host-Cron.
- **HTTPS:** Stelle einen Reverse-Proxy (Caddy, Traefik, nginx) davor und setze `proxy` in der Konfiguration, siehe [docs/tracking.md](tracking.md#hinter-einem-reverse-proxy).
- **Sicherung:** Datenbank per `docker compose exec db mariadb-dump …` und das Volume `pegelstand_config`, siehe [docs/datensicherung.md](datensicherung.md).
