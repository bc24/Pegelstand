# Datensicherung

Sichere regelmäßig, mindestens vor jedem Update.

## Was sichern?

| Was | Wo | Warum |
|---|---|---|
| **Datenbank** | Verwaltung deines Hosters (Export als SQL) oder `mysqldump` | Alle Messdaten, Benutzer, Websites, Einstellungen |
| **`config/config.php`** | Server | Datenbankzugang und `app_key` |
| `storage/geoip/country.mmdb` | Server | Nur wenn du Länder auswertest (sonst neu herunterladbar) |

Der `app_key` in `config/config.php` verschlüsselt das SMTP-Passwort und die Zwei-Faktor-Geheimnisse. **Ohne ihn lassen sich diese nach einer Wiederherstellung nicht mehr lesen.** Dann müssen alle ihre Zwei-Faktor-Anmeldung neu einrichten (ein Administrator kann sie zurücksetzen) und du musst das SMTP-Passwort neu eintragen. Alles andere funktioniert weiter.

Die Programmdateien brauchst du nicht zu sichern, du hast sie aus dem Release.

## Beispiel mit `mysqldump`

```bash
mysqldump --single-transaction -u BENUTZER -p DATENBANK > pegelstand-$(date +%F).sql
```

Das Tages-Salt der Besucherkennung steckt in der Datenbank (`daily_salts`). Es ist nur der aktuelle Tag enthalten. Eine Sicherung enthält keine IP-Adressen, weil Pegelstand keine speichert.

## Wiederherstellen

1. Neue, leere Datenbank anlegen und die Sicherung einspielen.
2. Programmdateien des passenden Release hochladen.
3. `config/config.php` zurückkopieren und bei neuer Datenbank die Zugangsdaten darin anpassen.

## Aufbewahrung und Löschen

Rohdaten löscht Pegelstand nach der pro Website eingestellten Frist (Standard 730 Tage). Zusammenfassungen bleiben. Eine ältere Sicherung kann also Rohdaten enthalten, die in der laufenden Installation schon gelöscht sind. Bedenke das, wenn du Löschfristen einhalten willst.
