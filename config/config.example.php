<?php

declare(strict_types=1);

// Beispiel für config/config.php. Der Installer erzeugt die echte Datei selbst, du musst sie nicht von Hand anlegen.
defined('PEGELSTAND_ROOT') || exit;

return [
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'pegelstand',
        'user' => 'pegelstand',
        'password' => 'geheim',
        'prefix' => 'ps_',
    ],
    // Zufälliger Schlüssel für Verschlüsselungen (z. B. SMTP-Passwort, 2FA-Geheimnisse). Niemals weitergeben.
    'app_key' => 'base64:...',
    'timezone' => 'Europe/Berlin',
    // Zeitzone, in der das Tages-Salt der Besucherkennung wechselt.
    'rotation_timezone' => 'Europe/Berlin',
    'debug' => false,
    // Optional: Namen von Script und Endpunkt ändern (Blocker-Listen kennen die Standardnamen).
    // 'tracker' => ['script_path' => '/stats.js', 'endpoint_path' => '/stats/senden'],
    // Optional: Anfragen pro Minute und Besucher-Adresse, danach Antwort 429.
    // 'ingest' => ['rate_limit' => 300],
    // Optional: Anmeldeversuche je Adresse und je E-Mail-Adresse innerhalb von 15 Minuten, danach Sperre.
    // 'login' => ['rate_limit' => 10],
    // Optional: Läuft Pegelstand hinter einem Reverse-Proxy, steht die Besucher-Adresse in einer Kopfzeile.
    // Sie wird nur beachtet, wenn die Verbindung von einer der vertrauten Adressen kommt.
    // 'proxy' => ['header' => 'X-Forwarded-For', 'trusted' => ['127.0.0.1', '10.0.0.0/8']],
];
