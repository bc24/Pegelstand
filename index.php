<?php

declare(strict_types=1);

// Front Controller. Alle Anfragen, die keine vorhandene Datei treffen, landen hier (siehe .htaccess).
define('PEGELSTAND_ROOT', __DIR__);

if (version_compare(PHP_VERSION, '8.2.0', '<')) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Pegelstand braucht PHP 8.2 oder neuer. Dein Server nutzt PHP ' . PHP_VERSION . ".\n"
        . 'Stelle in der Verwaltung deines Hosters die PHP-Version um.';
    exit;
}

if (!is_file(__DIR__ . '/vendor/autoload.php')) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Der Ordner vendor/ fehlt. Lade das vollständige Release-Zip von Pegelstand hoch, es enthält alles Nötige.\n";
    exit;
}

require __DIR__ . '/vendor/autoload.php';

use Pegelstand\Core\Application;
use Pegelstand\Core\Paths;
use Pegelstand\Core\Request;

$app = new Application(Paths::fromEnvironment(__DIR__));
// Der Body wird nur für den Erfassungs-Endpunkt gebraucht und auf 16 KB begrenzt.
$body = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && empty($_POST) ? (string) file_get_contents('php://input', false, null, 0, 16384) : '';
$app->handle(Request::fromGlobals($_SERVER, $_GET, $_POST, $body))->send();

// Hintergrundjobs (Pseudo-Cron) laufen erst, wenn der Besucher seine Antwort schon hat.
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}
$app->afterResponse();
