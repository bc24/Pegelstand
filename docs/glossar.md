# Glossar

Dieses Glossar legt die Begriffe verbindlich fest. Oberfläche, Dokumentation und Code-Kommentare verwenden sie einheitlich. Ansprache durchgehend mit „du“.

## Kennzahlen

| Begriff | Bedeutung |
|---|---|
| **Besucher** | Anzahl unterschiedlicher Besucher an einem Tag. Pegelstand erkennt Besucher nur innerhalb eines Tages wieder. Über mehrere Tage ist die Zahl die Summe der Tageswerte, ein Mensch, der an drei Tagen kommt, zählt also dreimal. |
| **Seitenaufrufe** | Anzahl der aufgerufenen Seiten, auch wiederholte Aufrufe derselben Seite. |
| **Seiten pro Besuch** | Seitenaufrufe geteilt durch Besuche. |
| **Besuch** | Zusammenhängende Aktivität eines Besuchers. Er endet nach 30 Minuten ohne Aktivität. Technisch heißt das `session`. |
| **Absprungrate** | Anteil der Besuche, die nur eine Seite umfassten. |
| **Besuchsdauer** | Durchschnittliche Zeit zwischen erstem und letztem Aufruf eines Besuchs. |
| **Veränderung** | Abweichung der Kennzahl gegenüber der Vorperiode in Prozent. |
| **Vorperiode** | Der gleich lange Zeitraum direkt vor dem gewählten Zeitraum. |
| **Aktive Besucher** | Besucher mit mindestens einem Aufruf in den letzten 30 Minuten (Echtzeit). |
| **Conversion-Rate** | Anteil der Besucher, die ein Ziel erreicht haben. |

## Seiten und Herkunft

| Begriff | Bedeutung |
|---|---|
| **Top-Seiten** | Die am häufigsten aufgerufenen Seiten. |
| **Einstiegsseite** | Erste Seite eines Besuchs. |
| **Ausstiegsseite** | Letzte Seite eines Besuchs. |
| **Quellen** | Woher Besucher kommen. Sie umfassen Referrer, Suchmaschinen, soziale Netzwerke und Kampagnen. |
| **Referrer** | Website, von der ein Besucher über einen Link kam. |
| **Direkt** | Kein Referrer vorhanden, z. B. bei eingetippter Adresse oder Lesezeichen. |
| **Kampagne** | Besuche mit UTM-Parametern (`utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content`). |
| **Länder** | Herkunftsland des Besuchs, bestimmt über eine lokale GeoIP-Datenbank. |
| **Geräte, Browser, Betriebssysteme** | Aus dem User-Agent abgeleitet. Der User-Agent selbst wird verworfen. |

## Ziele und Ereignisse

| Begriff | Bedeutung |
|---|---|
| **Ereignis** | Eine vom Betreiber definierte Aktion, z. B. „Anmeldung“. Wird per JavaScript-API ausgelöst. |
| **Eigenschaften** | Zusatzangaben zu einem Ereignis, z. B. `tarif: pro`. |
| **Ziel** | Ein Ereignis oder eine Seite, deren Erreichen als Erfolg zählt. |
| **Ausgehende Links, Downloads, 404-Seiten** | Optional automatisch erfasste Ereignisse. |

## Bedienung

| Begriff | Bedeutung |
|---|---|
| **Dashboard** | Die Übersichtsseite mit allen Kennzahlen einer Site. |
| **Site** | Eine Website, die Pegelstand misst. |
| **Zeitraum** | Der betrachtete Datumsbereich. |
| **Filter** | Einschränkung der gesamten Ansicht, z. B. auf eine Seite oder ein Land. Er erscheint als entfernbarer Chip. |
| **Befehlspalette** | Suchfeld für Aktionen, geöffnet mit Strg+K. |
| **Tracking-Code** | Die Zeile `<script>`, die du in deine Seite einbindest. |
| **Tracking** | Das Erfassen von Seitenaufrufen und Ereignissen. |
| **Verbindung prüfen** | Wartet live auf den ersten Aufruf nach dem Einbinden. |
| **Admin, Betrachter** | Rollen: Admins verwalten, Betrachter lesen nur. |
| **Öffentlicher Link** | Schreibgeschützte Ansicht eines Dashboards, erreichbar über einen geheimen Token. |

## Betrieb

| Begriff | Bedeutung |
|---|---|
| **Hintergrundjobs** | Aggregation, Salt-Rotation, Aufräumen und E-Mail-Berichte. |
| **Pseudo-Cron** | Stößt fällige Hintergrundjobs bei Dashboard-Aufrufen an, wenn der Hoster keinen Cron bietet. |
| **Salt** | Täglich neu erzeugter Zufallswert für die Besucherkennung. Das alte Salt wird gelöscht. |
| **Aufbewahrungsdauer** | Wie lange Rohdaten gespeichert bleiben. Aggregierte Werte bleiben dauerhaft. |

Dies ist keine Rechtsberatung. Aussagen zum Datenschutz beschreiben nur, was technisch passiert und was nicht gespeichert wird.
