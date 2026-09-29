<?php
// Wird vom Installer (/install/) als config.php erzeugt. Diese Datei ist nur eine Vorlage.
return [
    'db' => [
        'host'    => 'localhost',
        'port'    => 3306,
        'name'    => 'frankpanzer',
        'user'    => 'frankpanzer',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],
    'base_path'  => '',                         // z. B. '/frank' bei Installation im Unterordner
    'site_url'   => 'https://frank-panzer.de',  // ohne abschließenden Slash
    'secret'     => 'ZUFALLSWERT',              // wird vom Installer erzeugt
    'debug'      => false,
    'timezone'   => 'Europe/Berlin',
    'session_timeout' => 7200,                  // Admin-Inaktivität in Sekunden
    'trust_proxy' => false,                     // true, wenn die Seite hinter Cloudflare/Reverse-Proxy läuft (echte Besucher-IP)
];
