<?php

declare(strict_types=1);

defined('PEGELSTAND_ROOT') || exit;

// Deutsche Texte. Ansprache mit „du“, Begriffe laut docs/glossar.md.
return [
    'app' => ['name' => 'Pegelstand'],

    'layout' => [
        'zum_inhalt' => 'Zum Inhalt springen',
        'darstellung' => 'Darstellung',
        'hell' => 'Hell',
        'dunkel' => 'Dunkel',
        'system' => 'System',
        'entwickelt_von' => 'Entwickelt von',
    ],

    'install' => [
        'titel' => 'Installation',
        'fortschritt' => 'Fortschritt der Installation',
        'schritt_aktuell' => 'aktueller Schritt',
        'schritt_fertig' => 'erledigt',
        'schritte' => [
            'pruefung' => 'Prüfung',
            'datenbank' => 'Datenbank',
            'admin' => 'Administrator',
            'fertig' => 'Fertig',
        ],

        'pruefung' => [
            'titel' => 'Willkommen bei Pegelstand',
            'einleitung' => 'Webanalyse ohne Cookies, die auf jedem PHP-Webspace läuft. In wenigen Minuten bist du startklar. Zuerst prüfen wir, ob dein Webspace alles mitbringt.',
            'punkte_titel' => 'Systemprüfung',
            'weiter' => 'Weiter zur Datenbank',
            'erneut' => 'Erneut prüfen',
            'blockiert_titel' => 'Es fehlt noch etwas',
            'blockiert' => 'Behebe die mit „Fehler“ markierten Punkte und prüfe dann erneut.',
            'status' => ['ok' => 'Erfüllt', 'warn' => 'Hinweis', 'fail' => 'Fehler', 'pruefe' => 'Wird geprüft'],
            'punkte' => [
                'php' => [
                    'label' => 'PHP 8.2 oder neuer',
                    'detail' => 'Gefunden: PHP {version}',
                    'hinweis' => 'Stelle in der Verwaltung deines Hosters die PHP-Version auf 8.2 oder neuer um.',
                ],
                'ext_pdo_mysql' => [
                    'label' => 'PHP-Erweiterung pdo_mysql',
                    'detail' => 'Für den Zugriff auf die Datenbank',
                    'hinweis' => 'Aktiviere die Erweiterung pdo_mysql in den PHP-Einstellungen deines Hosters.',
                ],
                'ext_json' => [
                    'label' => 'PHP-Erweiterung json',
                    'detail' => 'Für den Datenaustausch mit dem Browser',
                    'hinweis' => 'Aktiviere die Erweiterung json in den PHP-Einstellungen deines Hosters.',
                ],
                'ext_mbstring' => [
                    'label' => 'PHP-Erweiterung mbstring',
                    'detail' => 'Für Umlaute und Sonderzeichen',
                    'hinweis' => 'Aktiviere die Erweiterung mbstring in den PHP-Einstellungen deines Hosters.',
                ],
                'ext_openssl' => [
                    'label' => 'PHP-Erweiterung openssl',
                    'detail' => 'Für Verschlüsselung und sichere Zufallswerte',
                    'hinweis' => 'Aktiviere die Erweiterung openssl in den PHP-Einstellungen deines Hosters.',
                ],
                'ext_session' => [
                    'label' => 'PHP-Erweiterung session',
                    'detail' => 'Für die Anmeldung und den Installer',
                    'hinweis' => 'Aktiviere die Erweiterung session in den PHP-Einstellungen deines Hosters.',
                ],
                'config_schreibbar' => [
                    'label' => 'Ordner config/ ist beschreibbar',
                    'detail' => 'Hier speichert die Installation deine Zugangsdaten',
                    'hinweis' => 'Gib dem Webserver Schreibrechte auf den Ordner config/. Das geht im FTP-Programm oder in der Verwaltung deines Hosters unter „Dateirechte“.',
                ],
                'storage_schreibbar' => [
                    'label' => 'Ordner storage/ ist beschreibbar',
                    'detail' => 'Für Sitzungen, Zwischenspeicher und Protokolle',
                    'hinweis' => 'Gib dem Webserver Schreibrechte auf den Ordner storage/. Das geht im FTP-Programm oder in der Verwaltung deines Hosters unter „Dateirechte“.',
                ],
                'argon2' => [
                    'label' => 'Passwort-Verfahren Argon2id',
                    'detail_ok' => 'Verfügbar',
                    'detail_warn' => 'Nicht verfügbar, Pegelstand nutzt stattdessen bcrypt',
                    'hinweis' => 'Das ist unkritisch, die Installation funktioniert trotzdem.',
                ],
                'zugriff' => [
                    'label' => 'Interne Ordner sind von außen gesperrt',
                    'detail_pruefe' => 'Wird im Browser geprüft …',
                    'detail_ok' => 'config/, src/ und storage/ sind aus dem Internet nicht abrufbar',
                    'detail_warn' => 'Mindestens ein interner Ordner ist von außen erreichbar',
                    'detail_ohne_js' => 'Die Prüfung braucht JavaScript',
                    'hinweis' => 'Prüfe, ob die Datei .htaccess hochgeladen wurde und dein Hoster .htaccess-Dateien zulässt (bei Apache „AllowOverride All“). Bei nginx richtest du die Sperren selbst ein.',
                ],
            ],
        ],

        'datenbank' => [
            'titel' => 'Datenbank verbinden',
            'einleitung' => 'Lege vorher in der Verwaltung deines Hosters eine leere MySQL- oder MariaDB-Datenbank samt Benutzer an. Die Zugangsdaten findest du dort.',
            'host' => 'Server',
            'host_hinweis' => 'Oft localhost.',
            'port' => 'Port',
            'port_hinweis' => 'Üblich ist 3306.',
            'name' => 'Datenbankname',
            'user' => 'Benutzername',
            'password' => 'Passwort',
            'prefix' => 'Tabellenpräfix',
            'passwort_erneut' => 'Aus Sicherheitsgründen gibst du das Passwort bei jedem Versuch neu ein.',
            'prefix_hinweis' => 'Wird jedem Tabellennamen vorangestellt. Ändere ihn nur, wenn die Datenbank noch andere Programme enthält.',
            'weiter' => 'Verbindung testen und weiter',
            'zurueck' => 'Zurück',
        ],

        'admin' => [
            'titel' => 'Administrator anlegen',
            'einleitung' => 'Mit diesem Zugang verwaltest du Pegelstand.',
            'name' => 'Name',
            'email' => 'E-Mail-Adresse',
            'email_hinweis' => 'Du brauchst sie für die Anmeldung und um dein Passwort zurückzusetzen.',
            'password' => 'Passwort',
            'password_hinweis' => 'Mindestens 12 Zeichen. Ein Satz aus mehreren Wörtern ist ein gutes Passwort.',
            'password_repeat' => 'Passwort wiederholen',
            'anzeigen' => 'Anzeigen',
            'verbergen' => 'Verbergen',
            'installieren' => 'Pegelstand installieren',
            'zurueck' => 'Zurück',
        ],

        'fertig' => [
            'titel' => 'Pegelstand ist installiert',
            'einleitung' => 'Datenbank, Konfiguration und dein Administrator sind eingerichtet. Aus Sicherheitsgründen hat sich der Installer selbst gesperrt.',
            'naechste_titel' => 'Das ist jetzt wichtig',
            'sichern' => 'Bewahre die Datei config/config.php sicher auf. Sie enthält deine Datenbank-Zugangsdaten und ist nicht für den Zugriff aus dem Internet gedacht.',
            'backup' => 'Lege vor jedem Update ein Backup von Datenbank und Dateien an.',
            'weiter' => 'Zu Pegelstand',
        ],

        'fehler' => [
            'zusammenfassung' => 'Bitte prüfe deine Angaben',
            'zusammenfassung_eins' => 'Das hat nicht geklappt',
            'csrf' => 'Die Seite war zu lange offen oder wurde aus einem anderen Tab gesendet. Lade sie neu und versuche es noch einmal.',
            'feld' => [
                'host' => 'Der Servername ist ungültig. Trage ihn so ein, wie dein Hoster ihn angibt, z. B. localhost.',
                'port' => 'Der Port muss eine Zahl zwischen 1 und 65535 sein. Üblich ist 3306.',
                'dbname' => 'Der Datenbankname darf nur Buchstaben, Ziffern, Unterstrich, Bindestrich, Punkt und Dollarzeichen enthalten und höchstens 64 Zeichen lang sein.',
                'dbuser' => 'Trage den Benutzernamen der Datenbank ein (höchstens 80 Zeichen).',
                'dbpasswort' => 'Das Passwort ist zu lang (höchstens 200 Zeichen).',
                'praefix' => 'Der Präfix darf nur Buchstaben, Ziffern und Unterstriche enthalten (höchstens 32 Zeichen), z. B. ps_.',
                'name' => 'Trage einen Namen ein (höchstens 120 Zeichen).',
                'email' => 'Diese E-Mail-Adresse ist ungültig. Prüfe sie auf Tippfehler, z. B. name@beispiel.de.',
                'passwort_kurz' => 'Das Passwort ist zu kurz. Wähle mindestens 12 Zeichen.',
                'passwort_lang' => 'Das Passwort ist zu lang (höchstens 200 Zeichen).',
                'passwort_gleich' => 'Die beiden Passwörter stimmen nicht überein. Gib sie noch einmal ein.',
            ],
            'db_zugang' => 'Benutzername oder Passwort stimmen nicht. Prüfe beides in der Verwaltung deines Hosters und achte auf Groß- und Kleinschreibung.',
            'db_unbekannt' => 'Diese Datenbank gibt es nicht. Lege sie in der Verwaltung deines Hosters an oder prüfe den Namen.',
            'db_rechte' => 'Der Datenbankbenutzer darf auf diese Datenbank nicht zugreifen, oder es fehlen ihm Rechte. Prüfe zuerst den Datenbanknamen. Dann braucht der Benutzer mindestens CREATE, ALTER, INDEX, REFERENCES, SELECT, INSERT, UPDATE und DELETE, die du in der Verwaltung deines Hosters vergibst.',
            'db_server' => 'Der Datenbankserver antwortet nicht. Prüfe Servername und Port. Beides steht in der Verwaltung deines Hosters, der Servername ist oft localhost.',
            'db_allgemein' => 'Die Verbindung zur Datenbank ist fehlgeschlagen (Fehlercode {code}). Prüfe alle Angaben und versuche es noch einmal.',
            'dbversion' => 'Dein Datenbankserver ({version}) ist zu alt. Pegelstand braucht MySQL 8.0 oder neuer beziehungsweise MariaDB 10.6 oder neuer. Stelle bei deinem Hoster auf eine neuere Version um.',
            'vorhanden' => 'In dieser Datenbank gibt es mit dem Präfix „{praefix}“ bereits eine Pegelstand-Installation mit Benutzern. Wähle einen anderen Präfix oder eine leere Datenbank.',
            'migration' => 'Beim Anlegen der Tabellen ist ein Fehler aufgetreten: {detail}',
            'config_schreiben' => 'Die Konfigurationsdatei konnte nicht geschrieben werden. Gib dem Webserver Schreibrechte auf den Ordner config/ und versuche es noch einmal.',
            'allgemein' => 'Die Installation ist fehlgeschlagen. Genaueres steht in storage/logs/error.log.',
        ],
    ],

    'login' => [
        'titel' => 'Anmelden',
        'einleitung' => 'Melde dich mit deiner E-Mail-Adresse und deinem Passwort an.',
        'email' => 'E-Mail-Adresse',
        'passwort' => 'Passwort',
        'anmelden' => 'Anmelden',
        'abmelden' => 'Abmelden',
        'konto' => 'Konto und Darstellung ({name})',
        'fehler_zugang' => 'E-Mail-Adresse oder Passwort stimmt nicht. Prüfe deine Eingabe und versuche es noch einmal.',
        'fehler_zuviele' => 'Zu viele Anmeldeversuche. Warte etwa 15 Minuten und versuche es dann noch einmal.',
        'fehler_sitzung' => 'Die Seite war zu lange offen. Versuche die Anmeldung noch einmal.',
    ],

    'dashboard' => ['titel' => 'Dashboard'],

    'keine_site' => [
        'titel' => 'Noch keine Website',
        'text_admin' => 'Du hast noch keine Website angelegt. Das Anlegen von Websites in der Oberfläche folgt in einer späteren Version. Bis dahin kannst du eine Website mit dem Skript bin/demo-data.php anlegen.',
        'text_viewer' => 'Dir ist noch keine Website zugeordnet. Bitte einen Administrator, dir eine Website freizugeben.',
        'abmelden' => 'Abmelden',
    ],

    'fehlerseite' => [
        'zur_startseite' => 'Zur Startseite',
        '404' => [
            'titel' => 'Seite nicht gefunden',
            'text' => 'Diese Adresse gibt es nicht (mehr). Prüfe sie auf Tippfehler oder gehe zurück zur Startseite.',
        ],
        '405' => [
            'titel' => 'Aktion nicht erlaubt',
            'text' => 'Diese Seite lässt die gewählte Aktion nicht zu. Gehe zurück zur Startseite.',
        ],
        '419' => [
            'titel' => 'Sitzung abgelaufen',
            'text' => 'Die Seite war zu lange offen oder wurde aus einem anderen Tab gesendet. Gehe zur Startseite und versuche es noch einmal.',
        ],
        '500' => [
            'titel' => 'Hier ist etwas schiefgelaufen',
            'text' => 'Der Fehler wurde in storage/logs/error.log festgehalten. Versuche es gleich noch einmal. Bleibt es dabei, schau in diese Datei oder frage den Support deines Hosters.',
        ],
        'datenbank' => [
            'titel' => 'Die Datenbank ist nicht erreichbar',
            'text' => 'Pegelstand kann sich nicht mit der Datenbank verbinden. Prüfe in der Verwaltung deines Hosters, ob der Datenbankserver läuft, und vergleiche die Zugangsdaten in config/config.php.',
        ],
        'wartung' => [
            'titel' => 'Pegelstand wird aktualisiert',
            'text' => 'Die Datenbank wird gerade auf die neue Version gebracht. Das dauert nur einen Moment, diese Seite lädt sich gleich neu.',
        ],
    ],
];
