# Frank Panzer — Website mit Admin-Bereich

Persönliche Website von Frank Panzer (Bremen) auf Basis von **PHP 8 + MySQL/MariaDB**, ohne Framework und ohne Build-Schritt.
Alle Inhalte, Texte (Deutsch/Englisch), Bilder, Reihenfolgen und das Design lassen sich im Admin-Bereich unter `/admin/` bearbeiten.

## Highlights

**Öffentliche Seite**
- Zweisprachig (DE/EN) mit eigenen URLs (`/…` und `/en/…`), `hreflang`, Sitemap, RSS-Feed, Schema.org (Person, WebSite, BlogPosting), Open Graph
- Animationen: Preloader, Partikel-Netz, morphendes Porträt mit schwebenden Chips, Typewriter, Text-Reveal (Zeichen/Wörter), Zahlen-Counter, Scroll-Timeline, 3D-Tilt und Spotlight auf Karten, magnetische Buttons, Cursor-Glow, Laufband, Filter mit Übergängen, animierte Skill-Balken, FAQ-Akkordeon, Vinyl/Equalizer, Konami-Easter-Egg
- Hell/Dunkel-Modus, Akzentfarbe im Admin einstellbar, `prefers-reduced-motion` wird respektiert
- Blog mit Inhaltsverzeichnis, Likes, moderierten Kommentaren, Teilen; Projekt-Übersicht und Detailseiten
- Kontaktformular (Honeypot, Zeit-Token, Ratenbegrenzung) → Datenbank + E-Mail
- Keine externen Ressourcen: Schriften, Icons und Skripte liegen lokal; Besuchszähler ohne Cookies und ohne IP

**Admin-Bereich (`/admin/`)**
- Dashboard mit Kennzahlen, Aufrufe-Diagramm, System-Check
- Alle Bereiche editierbar: Bereiche & Reihenfolge (Drag & Drop), Hero, Über mich, Zahlen, Interessen, Fun Facts, Zeitstrahl, Projekte, Ehemalige Webseiten, Maker, Shop, Skills, Lebenslauf, Musik/Tracks, Tools, FAQ, Partner, Social Links, Blog, Rechtstexte
- Zweisprachige Formulare (DE/EN-Umschalter), Rich-Text-Editor, Icon-Auswahl, Medienbibliothek mit Upload (Bilder werden neu kodiert, EXIF/GPS entfernt, Vorschaubilder)
- Nachrichten-Postfach, Kommentar-Moderation, Benutzer & Rollen (Administrator/Redakteur)
- Zwei-Faktor-Anmeldung (TOTP), Login-Sperre, CSRF-Schutz, Aktivitätsprotokoll
- Backup (SQL-Dump, Uploads als ZIP) und Wiederherstellung

## Voraussetzungen

- PHP ≥ 8.1 mit `pdo_mysql`, `mbstring`, `fileinfo`, `dom` (empfohlen: `gd`, `zip`)
- MySQL 5.7+ oder MariaDB 10.3+ (leere Datenbank)
- Apache mit `mod_rewrite` (Konfiguration liegt in `.htaccess`) oder nginx (siehe unten)

## Installation

1. Dateien auf den Webspace laden (Document-Root = Projektordner).
2. Leere Datenbank samt Benutzer anlegen (Plesk/cPanel).
3. `https://deine-domain.de/install/` aufrufen, Formular ausfüllen, absenden.
4. Den Installer über den angezeigten Button löschen (oder Ordner `install/` per FTP entfernen).
5. Unter `/admin/` anmelden.

Per Kommandozeile (auf einem VPS mit `--web-user` den Webserver-Benutzer als Besitzer von `config/`, `storage/` und `uploads/` setzen):

```bash
sudo php install/cli.php --db=frankpanzer --user=dbuser --pass=geheim \
    --admin=frank --email=frank@panzerit.de --url=https://frank-panzer.de --web-user=www-data
```

Der Installer legt die Tabellen an, spielt die Startinhalte ein (aus frank-panzer.de und panzerit.de übernommen) und schreibt `config/config.php` (nicht im Git, siehe `.gitignore`). Schreibrechte werden für `config/`, `storage/` und `uploads/` benötigt.

### Umzug von der bisherigen statischen Seite

Liegen im Zielordner noch die alten statischen Dateien (`index.html`, `css/`, `js/`, `blog/…/index.html`, `impressum/`, `datenschutz/`), werden diese von Apache **vor** der neuen Seite ausgeliefert. Vor dem Upload also entfernen bzw. überschreiben. Die Ordner der Einzel-Apps unter `projekte/<name>/` bleiben unverändert erhalten; die alten URLs (`/blog/<artikel>/`, `/impressum/`, `/datenschutz/`, `/assets/img/frank-panzer.jpg`) funktionieren weiter.

Läuft die Installation per CLI unter einem anderen Benutzer als der Webserver, muss `config/config.php` für den Webserver lesbar sein (der Installer setzt 0644; bei Bedarf Gruppe setzen und auf 0640 verschärfen).

### Betrieb hinter Cloudflare / Reverse-Proxy

In `config/config.php` `'trust_proxy' => true` setzen, damit Ratenbegrenzungen die echte Besucher-IP nutzen.

### nginx

```nginx
location / { try_files $uri $uri/ /index.php?$query_string; }
location ~ ^/(app|config|database|views|storage|admin/views)/ { deny all; }
location ^~ /uploads/ { location ~ \.php$ { deny all; } }
location ~ \.php$ { include fastcgi_params; fastcgi_pass unix:/run/php/php8.3-fpm.sock; fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; }
```

Hinweis: Auf dem bestehenden Server liegen unter `/projekte/<name>/` die Einzel-Apps. Die `.htaccess` liefert nur `/projekte/` (Übersicht) über diese Seite aus; die Unterordner bleiben unverändert erreichbar. In nginx entsprechend `location = /projekte/ { try_files /index.php$is_args$args =404; }` ergänzen.

## Songs von TikTok

Im Musik-Bereich erscheinen die letzten 10 Videos von `@dj.frankus` als Karussell. Die Cover liegen lokal auf dem Server; erst beim Klick auf einen Song wird der TikTok-Player in einem Fenster geladen (Datenschutz: vorher keine Verbindung zu TikTok).

- **Aktualisieren im Admin:** Musik: Tracks → „Von TikTok aktualisieren“. Neue Videos werden ergänzt, Aufrufzahlen aktualisiert, ältere automatisch importierte Videos entfernt. Titel, die du im Admin geändert hast, bleiben erhalten.
- **Automatisch per Cron** (täglich, als Webserver-Benutzer): `17 6 * * * cd /var/www/frank-panzer.de && sudo -u www-data php bin/sync-tiktok.php`
- **Einzelne Songs:** Beim Anlegen eines Tracks reicht der TikTok-Video-Link; Titel, Cover und Datum werden automatisch ergänzt. Auch Links zu anderen Plattformen sind möglich (öffnen in neuem Tab).
- Profil und Anzahl: Einstellungen → Musik. Die Daten stammen aus der öffentlichen Profil-Einbettung von TikTok; ändert TikTok deren Format, meldet der Abgleich einen Fehler und Songs lassen sich weiter manuell pflegen.
- Bestehende Installationen werden beim ersten Aufruf nach dem Update automatisch migriert (neue Spalten, Einstellungen, Hinweis „TikTok-Einbettung“ in der Datenschutzerklärung). Danach im Admin einmal „Von TikTok aktualisieren“ klicken.

## Fehlersuche (leere Seite / Fehler 500)

```bash
sudo -u www-data php bin/doctor.php     # als Webserver-Benutzer ausführen
```

Das Skript prüft PHP-Erweiterungen, Dateirechte (config/, storage/, uploads/), die Datenbankverbindung und ruft Startseite, Admin und Blog mit sichtbaren Fehlern auf. Die Anwendung zeigt bei Konfigurations- oder Datenbankproblemen eine Fehlerseite mit Hinweis; Details stehen im Fehlerlog (`storage/logs/php-error.log` bzw. das Log des Webservers, z. B. `/var/log/apache2/error.log`). Für die Fehlersuche kann in `config/config.php` vorübergehend `'debug' => true` gesetzt werden.

## Lokale Entwicklung

```bash
php install/cli.php --db=… --user=… --pass=… --admin=admin --email=you@example.com --url=http://localhost:8080
php -S localhost:8080 router.php
```

## Aufbau

```
index.php            Front-Controller der öffentlichen Seite
router.php           nur für den PHP-Entwicklungsserver
admin/               Admin-Einstieg und Templates (admin/views)
bin/doctor.php       Diagnose der Installation (nur CLI)
app/                 PHP-Klassen (Db, Auth, Crud, Front, Media, Mailer, Html, TikTok, Migrations, …)
app/entities.php     Definition aller editierbaren Inhaltstypen (Formulare entstehen daraus)
app/settings_schema.php  Definition aller Einstellungen
app/strings.php         UI-Texte DE/EN
database/            schema.sql und seed.php (Startinhalte)
views/               Templates der öffentlichen Seite (views/sections/* = Startseiten-Bereiche)
assets/              CSS, JS, Schriften, Icons, Bilder
uploads/             Medien-Uploads (Ausführung von Skripten gesperrt)
storage/             Logs
```

### Erweitern

- **Neuer Inhaltstyp:** Tabelle in `database/schema.sql`, Definition in `app/entities.php` → Verwaltung im Admin entsteht automatisch (Liste, Formular, Sortierung, Sichtbarkeit).
- **Neuer Startseiten-Bereich:** Datei `views/sections/<schlüssel>.php` anlegen und eine Zeile in der Tabelle `sections` eintragen (Reihenfolge, Menü und Überschriften sind dann im Admin steuerbar).
- **Neue Einstellung:** Eintrag in `app/settings_schema.php`, Lesen im Template mit `setting('key')` bzw. `s('key')` (sprachabhängig).
- **Icons:** Sprite `assets/img/icons.svg` (Lucide + Marken-Icons); Auswahl im Admin. Eigene Emojis sind ebenfalls möglich.

## Sicherheit

Prepared Statements überall, Ausgabe-Escaping, Whitelist-Bereinigung von Rich-Text, CSRF-Token in allen Admin-Formularen, gehashte Passwörter (`password_hash`), Login-Sperre, optionale 2FA, Sitzungs-Cookies (`HttpOnly`, `SameSite`, `Secure` bei HTTPS), Content-Security-Policy mit Nonce, Upload-Prüfung per `fileinfo` mit Neu-Kodierung, gesperrte interne Ordner. Empfehlungen: HTTPS erzwingen, `install/` löschen, 2FA aktivieren, regelmäßig Backup ziehen.

## Vor dem Livegang prüfen

- **Rechtstexte:** Impressum und Datenschutz wurden von der bisherigen Seite übernommen und an die neue Technik angepasst (Formspree und Google Fonts entfallen, Kontaktformular/Kommentare/Besuchszähler ergänzt, Verweise auf DDG statt TMG). Bitte inhaltlich prüfen.
- **Instagram:** Als Profil ist `@frankpanzer82` eingetragen (Link aus dem Auftrag); die alte Seite verlinkte `@frank__panzer`. Im Admin unter „Social Links“ anpassbar.
- **Projekte:** Die Apps aus `/projekte/<ordner>/` (BewerbungsPilot, TechDeals24, Trockenheld, Weltenentdecker, Spielearena, Poesiealbum sowie die bisherigen Apps) stehen im Projekt-Raster der Startseite, auf `/projekte/` und mit eigener Detailseite unter `/projekt/<name>/`. Die Links zeigen relativ auf den jeweiligen App-Ordner (`/projekte/<ordner>/`). „PanzerIT Territoriumskrieg“ liegt nur auf panzerit.de/spiel. „Panzer IT Cockpit“ und „Social Media Manager“ sind private Login-Tools und standardmäßig unsichtbar (Admin → Projekte → Sichtbar). Projektdaten liegen in `database/seed/projects.php`; bestehende Installationen werden beim ersten Aufruf automatisch angepasst (nur unveränderte Standardtexte werden ersetzt).
- **Blog:** Der Artikel „frank-panzer.de — komplett neu gebaut“ beschreibt noch die statische Vorgängerseite.
- **Mail-Versand:** Unter Einstellungen → Kontakt & E-Mail ggf. SMTP eintragen und „Testmail senden“ nutzen.
