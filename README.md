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

Per Kommandozeile:

```bash
php install/cli.php --db=frankpanzer --user=dbuser --pass=geheim \
    --admin=frank --email=frank@panzerit.de --url=https://frank-panzer.de
```

Der Installer legt die Tabellen an, spielt die Startinhalte ein (aus frank-panzer.de und panzerit.de übernommen) und schreibt `config/config.php` (nicht im Git, siehe `.gitignore`). Schreibrechte werden für `config/`, `storage/` und `uploads/` benötigt.

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
app/                 PHP-Klassen (Db, Auth, Crud, Front, Media, Mailer, Html, …)
app/entities.php     Definition aller editierbaren Inhaltstypen (Formulare entstehen daraus)
app/settings_schema.php  Definition aller Einstellungen
app/lang.php         UI-Texte DE/EN
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
- **Projekte:** Zusätzlich zu den 13 bisherigen Projekten stehen die Apps aus `/projekte/` auf der Seite `/projekte/`. „Panzer IT Cockpit“ und „Social Media Manager“ sind Login-Tools und standardmäßig unsichtbar.
- **Blog:** Der Artikel „frank-panzer.de — komplett neu gebaut“ beschreibt noch die statische Vorgängerseite.
- **Mail-Versand:** Unter Einstellungen → Kontakt & E-Mail ggf. SMTP eintragen und „Testmail senden“ nutzen.
