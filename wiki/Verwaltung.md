# Verwaltung

Alle Einstellungen findest du über das Konto-Menü oben rechts im Dashboard, Punkt **Einstellungen**. Websites, Benutzer und E-Mail sieht nur, wer **Administrator** ist. Betrachter sehen nur „Mein Konto“.

![Einstellungen einer Website](https://raw.githubusercontent.com/bc24/Pegelstand/main/docs/bilder/einstellungen-website.png)

## Websites

Unter **Websites** legst du Websites an und bearbeitest sie.

| Einstellung | Bedeutung |
|---|---|
| Name, Domain | Aufrufe von anderen Domains werden ignoriert. Die Domain mit und ohne `www.` ist immer erlaubt. |
| Weitere erlaubte Domains | Eine pro Zeile, mit `*.beispiel.de` alle Subdomains. |
| Zeitzone | Bestimmt, wann ein Tag beginnt. |
| Aufbewahrung der Rohdaten | 30 bis 3650 Tage (Standard 730). Danach löscht Pegelstand die einzelnen Aufrufe, die Zusammenfassungen bleiben. |
| Do Not Track, Global Privacy Control | Wenn eingeschaltet, werden Besucher mit diesem Signal nicht gezählt. |

Weitere Bereiche auf der Seite einer Website:

- **Tracking-Code** zum Kopieren
- **Ziele:** eine Seite (Pfad, z. B. `/danke`) oder ein Ereignis (Name) als Erfolg. Das Dashboard zeigt Besucher, Anzahl und Conversion-Rate und filtert darauf. Ziele gelten rückwirkend.
- **Öffentliches Dashboard:** ein geheimer Link, über den jeder (auch ohne Konto) die Zahlen ansehen kann, ohne Einstellungen und Export. Du kannst ihn abschalten oder durch einen neuen ersetzen. Wer den Link kennt, sieht alles, was im Dashboard steht.
- **Eigene Besuche ausschließen:** IP-Adresse oder Bereich (z. B. `192.0.2.0/24`). Die Adressen werden nur dafür gespeichert.
- **Website löschen:** entfernt die Website mit allen Messdaten. Zur Sicherheit musst du die Domain eintippen.

## Benutzer

![Benutzerverwaltung](https://raw.githubusercontent.com/bc24/Pegelstand/main/docs/bilder/einstellungen-benutzer.png)

- **Administrator:** verwaltet alles, sieht alle Websites.
- **Betrachter:** sieht nur Websites, die du freigibst.
- Benutzer lassen sich sperren, löschen und mit neuem Passwort versehen. Der letzte aktive Administrator lässt sich nicht herabstufen, sperren oder löschen, und niemand löscht sich selbst.
- Passwörter brauchen mindestens 12 Zeichen.

## E-Mail

Unter **E-Mail** trägst du den SMTP-Zugang deines Hosters oder E-Mail-Anbieters ein (Server, Port, Verschlüsselung, Benutzer, Passwort, Absender) und prüfst ihn mit einer Testnachricht. Das SMTP-Passwort liegt verschlüsselt in der Datenbank. Die **Adresse von Pegelstand** steht in Links in E-Mails; sie wird bewusst nicht aus der Anfrage übernommen.

Ohne eingerichteten Versand gibt es weder Berichte noch „Passwort vergessen“.

## Mein Konto

- **Passwort ändern**
- **Zwei-Faktor-Anmeldung** mit einer Authenticator-App (zeitbasierte Codes nach RFC 6238): Schlüssel in der App eintragen, Code bestätigen. Einen QR-Code gibt es nicht. Hast du dein Gerät verloren, setzt ein Administrator die Zwei-Faktor-Anmeldung für dich zurück.
- **E-Mail-Berichte** abonnieren: wöchentlich (Montag ab 7 Uhr, für die Vorwoche) oder monatlich (am Ersten ab 7 Uhr, für den Vormonat), nach Ortszeit der Website. Der erste Bericht kommt zum nächsten Termin.
- **API-Schlüssel** für die [[REST-API]]. Der Schlüssel wird nur einmal angezeigt.

![Zwei-Faktor-Anmeldung einrichten](https://raw.githubusercontent.com/bc24/Pegelstand/main/docs/bilder/einstellungen-zwei-faktor.png)

## Anmeldung

Nach 10 Fehlversuchen innerhalb von 15 Minuten (je Adresse und je E-Mail-Adresse) wird die Anmeldung vorübergehend gesperrt. „Passwort vergessen“ schickt einen Einmal-Link, der eine Stunde gilt.
