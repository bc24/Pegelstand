# Häufige Fragen

**Brauche ich einen Cookie-Hinweis?**
Das Tracking-Script setzt keine Cookies und nutzt keinen Browser-Speicher. Ob du trotzdem einen Hinweis oder eine Einwilligung brauchst, ist eine rechtliche Frage, die ich hier nicht beantworte. Sieh dir [[Datenschutz]] an und kläre es für deinen Fall.

**Warum sind „Besucher“ über mehrere Tage mehr als verschiedene Menschen?**
Die anonyme Kennung ändert sich täglich. Ein Mensch, der an drei Tagen kommt, zählt dreimal. Das ist Absicht und Teil des Datenschutzkonzepts.

**Warum sehe ich keine Länder?**
Pegelstand lädt keine GeoIP-Datenbank selbst herunter. Lege eine MMDB-Datei als `storage/geoip/country.mmdb` ab, siehe [[Tracking-Code-und-Ereignisse]].

**Mein Besuch wird nicht gezählt.**
Gehe die Liste unter [[Fehlerbehebung]] durch (Domain, localhost, Do Not Track, Ausschlussliste, Werbeblocker).

**Läuft Pegelstand auch ohne Cronjob?**
Ja. Ein Pseudo-Cron führt fällige Jobs nach Antworten an Besucher aus. Für viel Verkehr ist ein echter Cronjob besser, siehe [[Hintergrundjobs]].

**Kann ich Pegelstand in einem Unterverzeichnis betreiben?**
Ja, Pfade sind relativ zum Installationsverzeichnis. Am einfachsten ist trotzdem eine eigene (Sub-)Domain.

**Funktioniert es mit nginx?**
Es gibt eine Beispielkonfiguration, die aber noch nicht in der Praxis erprobt ist: [[nginx]].

**Gibt es einen Docker-Container?**
Es gibt ein Setup im Quellcode (`docker/`), ebenfalls noch nicht in der Praxis erprobt: [[Docker]].

**Wie viele Daten kann Pegelstand verarbeiten?**
In einer Messung mit rund 9 Millionen Ereignissen blieben Erfassung und Dashboard schnell (Zahlen und Grenzen unter [[Leistung]]). Andere Hoster und Datenbanken liefern andere Werte.

**Kann ich die Daten exportieren?**
Ja: Alle Tabellen als CSV (Menü **Exportieren**) und lesend über die [[REST-API]].

**Wie sichere ich meine Daten?**
[[Datensicherung]]: die Datenbank und `config/config.php` (wegen des `app_key`).

**Ich habe mein Zwei-Faktor-Gerät verloren.**
Ein anderer Administrator setzt die Zwei-Faktor-Anmeldung zurück. Ist es der einzige Administrator, hilft nur ein Eingriff in der Datenbank, siehe [[Fehlerbehebung]].

**Ist Pegelstand kostenlos?**
Der Quellcode steht unter der [MIT-Lizenz](https://github.com/bc24/Pegelstand/blob/main/LICENSE). Du darfst ihn nutzen und ändern.

**Wann kommt Version 1.0?**
Das steht noch nicht fest. Offen sind vor allem ein erster CI-Lauf, Praxistests von Docker und nginx und Tests auf echten Hostern. Den Stand zeigt die [README](https://github.com/bc24/Pegelstand#fahrplan).
