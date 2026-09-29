<?php
declare(strict_types=1);

/** Front-Controller der öffentlichen Seite. */
require __DIR__ . '/app/bootstrap.php';

$uri = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$base = rtrim((string)cfg('base_path', ''), '/');
if ($base !== '' && str_starts_with($uri, $base)) {
    $uri = substr($uri, strlen($base));
}
$uri = '/' . trim(rawurldecode($uri), '/');

$lang = 'de';
if (preg_match('#^/en(/|$)#', $uri)) {
    $lang = 'en';
    $uri = '/' . trim(substr($uri, 3), '/');
}
Lang::init($lang);

$path = $uri === '' ? '/' : $uri;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'HEAD') {
    $method = 'GET';
}

// Seiten mit abschließendem Slash kanonisieren (GET, HTML-Routen)
if ($method === 'GET' && $path !== '/' && !preg_match('#\.(xml|txt)$#', $path)) {
    $raw = (string)parse_url((string)$_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (!str_ends_with($raw, '/')) {
        $qs = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : '';
        redirect($raw . '/' . $qs, 301);
    }
}

try {
    Front::dispatch($path, $method);
} catch (Throwable $e) {
    error_log('[fp] ' . $e::class . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (cfg('debug', false)) {
        throw $e;
    }
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>Fehler</title><body style="font-family:system-ui;padding:3rem;background:#0a0d12;color:#eaf0f7"><h1>Ups.</h1><p>Da ist etwas schiefgegangen. Bitte versuche es gleich noch einmal.</p>';
}
