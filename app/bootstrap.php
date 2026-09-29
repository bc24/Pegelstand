<?php
declare(strict_types=1);

define('FP_ROOT', dirname(__DIR__));
define('FP_VERSION', '1.0.0');

mb_internal_encoding('UTF-8');

spl_autoload_register(static function (string $class): void {
    $file = FP_ROOT . '/app/' . $class . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require FP_ROOT . '/app/helpers.php';

$__cfgFile = FP_ROOT . '/config/config.php';
if (!is_file($__cfgFile)) {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Keine config/config.php gefunden. Bitte /install/ ausführen.\n");
        exit(1);
    }
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
    $base = preg_replace('#/(admin|install)$#', '', $base);
    header('Location: ' . $base . '/install/');
    exit;
}
$GLOBALS['fp_config'] = require $__cfgFile;
unset($__cfgFile);

date_default_timezone_set(cfg('timezone', 'Europe/Berlin'));

if (cfg('debug', false)) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED);
}
ini_set('log_errors', '1');
ini_set('error_log', FP_ROOT . '/storage/logs/php-error.log');

Db::connect(cfg('db'));
