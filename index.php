<?php

declare(strict_types=1);

use Pegelstand\Version;

// Platzhalter-Front-Controller (Phase 0). Router, DI-Container und Templates folgen.
require __DIR__ . '/vendor/autoload.php';

header('Content-Type: text/html; charset=utf-8');

$version = htmlspecialchars(Version::CURRENT, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pegelstand</title>
    <style>
        body { margin: 0; font-family: system-ui, sans-serif; background: #ffffff; color: #0b1f2a; }
        main { max-width: 40rem; margin: 0 auto; padding: 4rem 1rem; }
        @media (prefers-color-scheme: dark) { body { background: #0b1f2a; color: #e6eef2; } }
    </style>
</head>
<body>
    <main>
        <h1>Pegelstand</h1>
        <p>Dieses Gerüst ist noch ohne Funktion. Version <?= $version ?>.</p>
    </main>
</body>
</html>
