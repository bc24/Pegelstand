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
];
