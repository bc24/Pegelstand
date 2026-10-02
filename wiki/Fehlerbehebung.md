<!-- Automatisch aus docs/fehlerbehebung.md erzeugt (php bin/build-wiki.php). Änderungen bitte dort vornehmen. -->
# Fehlerbehebung

Zuerst: `storage/logs/error.log` enthält die technischen Meldungen (ohne Besucherdaten).

## Installer und Betrieb

| Problem | Ursache und Lösung |
|---|---|
| Der Installer meldet „nicht beschreibbar“ | Gib dem Webserver Schreibrechte auf `config/` und `storage/` (Rechte 755 oder 775, je nach Hoster). |
| Weißer Bildschirm oder Fehler 500 | PHP-Version zu alt (mindestens 8.2) oder Erweiterung fehlt. `storage/logs/error.log` und die PHP-Fehlerprotokolle deines Hosters ansehen. |
| „Der Ordner vendor/ fehlt“ | Du hast den Quellcode statt des Release-Zips hochgeladen. Nimm das Zip oder führe `composer install --no-dev` aus. |
| Seite zeigt „Pegelstand wird aktualisiert“ und bleibt so | Eine Migration läuft oder ist abgebrochen. Nach einer Minute neu laden, sonst `error.log` lesen. Siehe [Aktualisieren](Aktualisieren). |
| Alle Unterseiten ergeben 404 | `mod_rewrite` oder `.htaccess` ist nicht aktiv (`AllowOverride All` fehlt). Bitte deinen Hoster darum. |
| Datenbank nicht erreichbar | Zugangsdaten in `config/config.php` prüfen. Der Datenbankserver ist bei manchen Hostern nicht `localhost`. |
| Anmeldung gesperrt („Zu viele Anmeldeversuche“) | 15 Minuten warten. Der Wert ist in der Konfiguration (`login.rate_limit`) änderbar. |
| Zwei-Faktor-Gerät verloren | Ein anderer Administrator setzt sie unter Benutzer zurück. Ist es der einzige Administrator: in der Datenbank `totp_enabled_at` und `totp_secret` des Benutzers auf `NULL` setzen. |

## Es werden keine Besucher gezählt

Gehe der Reihe nach durch:

1. Ist der Tracking-Code im `<head>` und stimmt `data-site`? Im Browser unter Netzwerk sollte ein `POST` an `…/api/event` mit Antwort `202` erscheinen.
2. Gehört der Hostname der Seite zur Website? Die Domain (mit und ohne `www.`) und die „Weiteren erlaubten Domains“ zählen, alles andere wird ignoriert.
3. Bist du auf `localhost`? Dort bleibt das Script still, außer mit `data-local`.
4. Hast du „Do Not Track“ oder „Global Privacy Control“ für die Website eingeschaltet und sendet dein Browser das Signal?
5. Steht deine Adresse in der Ausschlussliste, oder hast du im Browser `localStorage.pegelstand_ignore = 'true'` gesetzt?
6. Ein Werbeblocker oder Datenschutz-Add-on blockiert das Script. Probiere ein privates Fenster ohne Erweiterungen.
7. Antwortet der Endpunkt mit `404`? Dann stimmt `data-site` nicht. Mit `429`: zu viele Anfragen (Ratenbegrenzung).
8. Die Zahlen für mehrere Tage kommen aus Zusammenfassungen, die alle 5 Minuten entstehen. „Heute“ ist sofort aktuell. Läuft der Hintergrundjob nicht, siehe [Hintergrundjobs](Hintergrundjobs).

## Länder fehlen

Es liegt keine GeoIP-Datei unter `storage/geoip/country.mmdb`, siehe [Tracking Code und Ereignisse](Tracking-Code-und-Ereignisse#länder-geoip). Hinter einem Proxy fehlt zudem die echte Besucheradresse, dann zählt der Standort des Proxys.

## E-Mails kommen nicht an

Unter Einstellungen, E-Mail die Testnachricht senden. Die Fehlermeldung nennt die Ursache (Verbindung, Anmeldung, Ablehnung). Manche Hoster sperren ausgehende SMTP-Verbindungen zu fremden Servern; nutze dann den SMTP-Server des Hosters. Schau auch im Spam-Ordner.

## Das Dashboard wirkt langsam

Gefilterte Ansichten über lange Zeiträume lesen die einzelnen Aufrufe und brauchen bei vielen Millionen Zeilen länger. Ungefilterte Ansichten sind schnell. Zahlen dazu in [Leistung](Leistung).
