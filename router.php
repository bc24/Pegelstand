<?php
// Nur für die lokale Entwicklung: php -S localhost:8080 router.php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;
if ($path !== '/' && is_file($file) && !preg_match('#^/(app|config|database|views|storage)/#', $path)) {
    return false;
}
if (preg_match('#^/(admin|install)(/|$)#', $path) && is_dir(__DIR__ . rtrim($path, '/')) ) {
    $_SERVER['SCRIPT_NAME'] = rtrim($path, '/') . '/index.php';
    require __DIR__ . rtrim($path, '/') . '/index.php';
    return true;
}
require __DIR__ . '/index.php';
