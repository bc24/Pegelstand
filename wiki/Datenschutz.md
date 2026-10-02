# Datenschutz: Was gespeichert wird und was nicht

Pegelstand ist so gebaut, dass möglichst wenig anfällt. Das ist eine **technische Beschreibung, keine Rechtsberatung**. Ob du für deine Website eine Einwilligung, einen Hinweis in der Datenschutzerklärung oder einen Auftragsverarbeitungsvertrag brauchst, klärst du selbst, gegebenenfalls mit einer Fachperson.

## Gespeichert wird pro Aufruf

- Zeitpunkt (UTC)
- Pfad der Seite **ohne** Query-String und Anker (Ausnahme: UTM-Parameter)
- Referrer nur als Domain
- Land (nur mit hinterlegter GeoIP-Datei), Gerätetyp, Browser- und Betriebssystemname **ohne Versionsnummer**
- ein anonymer Besucher-Hash

## Nicht gespeichert und nicht protokolliert

IP-Adressen, vollständiger User-Agent, Cookies, Bildschirmgröße, Sprache, Query-Strings.

## Der Besucher-Hash

Er entsteht aus Website, IP-Adresse und User-Agent mit einem zufälligen **Tages-Salt** (`HMAC-SHA-256`, auf 16 Byte gekürzt). Das Salt wechselt um Mitternacht (Standard: Europe/Berlin), das alte wird gelöscht. Danach lässt sich aus einem Hash keine Adresse mehr nachrechnen, und derselbe Mensch erscheint am nächsten Tag als neuer Besucher. IPv6-Adressen werden vorher auf das /64-Netz gekürzt.

Die IP-Adresse wird nur im Arbeitsspeicher benutzt: für den Hash, für die Ratenbegrenzung (über einen gesalzenen Hash) und für die Länderbestimmung.

## Weitere Punkte

- **Keine Cookies, kein Browser-Speicher** durch das Tracking-Script. Das Dashboard setzt für angemeldete Benutzer ein Sitzungs-Cookie, Besucher deiner Websites nie.
- **Keine Anfragen an Dritte:** Schrift, Diagramme und Icons liegen auf deinem Server. Auch die Länderbestimmung nutzt eine Datei auf deinem Server, nichts wird nachgeladen.
- **Aufbewahrung:** Rohdaten löscht ein Hintergrundjob nach der pro Website eingestellten Frist (Standard 730 Tage). Zusammenfassungen (Zahlen ohne Personenbezug zu einzelnen Aufrufen) bleiben.
- **Do Not Track / Global Privacy Control:** pro Website einschaltbar (Standard: aus).
- **Opt-out für Besucher:** `localStorage.pegelstand_ignore = 'true'` im Browser. Details unter [[Tracking-Code-und-Ereignisse]].
- **Eigene Eigenschaften** bei Ereignissen: Schicke keine personenbezogenen Daten als Eigenschaft.

Wenn du die Datenschutzerklärung deiner Website anpasst, kannst du dich an dieser Beschreibung orientieren. Die rechtliche Bewertung bleibt bei dir.
