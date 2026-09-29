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

/**
 * Sichtbare Fehlerseite statt leerem "500": Protokolliert den Fehler und zeigt Besuchern
 * eine neutrale Meldung. Technische Details erscheinen nur bei 'debug' => true in config.php.
 */
function fp_fail(string $title, string $hint, ?Throwable $e = null, int $status = 500): never
{
    if ($e) {
        error_log('[fp] ' . $title . ': ' . $e::class . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    }
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, '[fp-error] ' . $title . "\n" . $hint . "\n" . ($e ? $e::class . ': ' . $e->getMessage() . "\n" : ''));
        exit(1);
    }
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store');
        header('Retry-After: 300');
    }
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    $detail = '';
    if ($e && ($GLOBALS['fp_config']['debug'] ?? false)) {
        $detail = '<pre style="white-space:pre-wrap;background:#182030;padding:1rem;border-radius:10px;font-size:.85rem">'
            . htmlspecialchars($e::class . ': ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine(), ENT_QUOTES) . '</pre>';
    }
    echo '<!doctype html><html lang="de"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex">'
        . '<title>' . htmlspecialchars($title, ENT_QUOTES) . '</title>'
        . '<body style="margin:0;min-height:100vh;display:grid;place-items:center;background:#0b0f15;color:#e9eef6;font:16px/1.6 system-ui,sans-serif;padding:1.5rem">'
        . '<main style="max-width:560px"><h1 style="margin:0 0 .5rem">' . htmlspecialchars($title, ENT_QUOTES) . '</h1>'
        . '<p style="color:#8b97aa">' . htmlspecialchars($hint, ENT_QUOTES) . '</p>' . $detail . '</main></body></html>';
    exit;
}

set_exception_handler(static function (Throwable $e): void {
    fp_fail('Da ist etwas schiefgegangen', 'Bitte versuche es in ein paar Minuten noch einmal. Betreiber: Details stehen im Fehlerprotokoll (storage/logs/php-error.log bzw. Fehlerlog des Webservers); "php bin/doctor.php" prüft die Installation.', $e);
});
register_shutdown_function(static function (): void {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true) && PHP_SAPI !== 'cli') {
        error_log('[fp] Fatal: ' . $err['message'] . ' @ ' . $err['file'] . ':' . $err['line']);
        if (!headers_sent()) {
            fp_fail('Da ist etwas schiefgegangen', 'Ein schwerwiegender Fehler ist aufgetreten. Betreiber: Details stehen im Fehlerlog des Webservers; "php bin/doctor.php" prüft die Installation.', null);
        }
    }
});

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
if (!is_readable($__cfgFile)) {
    fp_fail('Konfiguration nicht lesbar', 'Die Datei config/config.php ist für den Webserver-Benutzer nicht lesbar. Betreiber: Dateirechte prüfen, z. B. "chown www-data:www-data config/config.php && chmod 640 config/config.php" (Benutzer/Gruppe an den Server anpassen) oder "php bin/doctor.php" ausführen.');
}
$__cfg = require $__cfgFile;
if (!is_array($__cfg) || !isset($__cfg['db'])) {
    fp_fail('Konfiguration ungültig', 'Die Datei config/config.php ist beschädigt oder unvollständig. Betreiber: Installation wiederholen oder Datei anhand von config/config.sample.php korrigieren.');
}
$GLOBALS['fp_config'] = $__cfg;
unset($__cfg);
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

try {
    Db::connect(cfg('db'));
} catch (Throwable $e) {
    $code = $e instanceof PDOException && isset($e->errorInfo[1]) ? ' (MySQL-Fehler ' . (int)$e->errorInfo[1] . ')' : '';
    fp_fail('Datenbank nicht erreichbar', 'Die Verbindung zur Datenbank ist fehlgeschlagen' . $code . '. Betreiber: Zugangsdaten in config/config.php und den Datenbank-Dienst prüfen oder "php bin/doctor.php" ausführen.', $e, 503);
}
