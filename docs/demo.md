# Demo-Modus

Mit dem Demo-Modus zeigst du Pegelstand öffentlich, ohne dass Besucher etwas verändern können. Alle Zahlen sind erfunden.

1. Normal installieren (siehe [Installation](installation.md)), dazu einen Benutzer für die Demo anlegen (am besten einen **Betrachter**).
2. Erfundene Daten erzeugen und der Demo-Website zuordnen:
   ```bash
   php bin/demo-data.php --create-site --days=90 --sessions=300
   ```
   Gib dem Demo-Benutzer in der Benutzerverwaltung die Demo-Website frei.
3. In `config/config.php` einschalten:
   ```php
   'demo' => ['enabled' => true, 'user' => 'demo@beispiel.de', 'password' => 'demo-passwort'],
   ```
   Die Anmeldeseite zeigt dann die Zugangsdaten zum Ausprobieren.

**Was der Demo-Modus tut:** Auf allen Seiten steht ein Hinweisband. Alles unter `/einstellungen` und „Passwort vergessen“ lässt sich nur ansehen, abschicken geht nicht (Antwort 403 mit Erklärung). Dashboard, Filter, Export und Abmelden funktionieren.

**Was er nicht tut:** Er verhindert nicht, dass jemand Messpunkte an den Erfassungs-Endpunkt schickt, und er setzt keine Daten zurück. Frische die Beispieldaten gelegentlich per Skript auf. Verwende für die Demo eine eigene Installation mit eigener Datenbank, nie deine echten Daten.
