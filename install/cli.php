<?php
declare(strict_types=1);
/**
 * Kommandozeilen-Installer:
 *   php install/cli.php --host=localhost --db=frankpanzer --user=fp --pass=geheim \
 *       --admin=frank --email=frank@panzerit.de [--password=...] [--url=https://frank-panzer.de] [--base=/]
 */
if (PHP_SAPI !== 'cli') {
    exit('Nur per CLI.');
}
define('FP_ROOT', dirname(__DIR__));
spl_autoload_register(static function (string $c): void {
    $f = FP_ROOT . '/app/' . $c . '.php';
    if (is_file($f)) {
        require $f;
    }
});
require FP_ROOT . '/app/helpers.php';

$o = getopt('', ['host:', 'port:', 'db:', 'user:', 'pass:', 'admin:', 'email:', 'password:', 'url:', 'base:', 'force', 'web-user:']);
foreach (['db', 'user', 'admin', 'email'] as $req) {
    if (empty($o[$req])) {
        fwrite(STDERR, "Fehlender Parameter --$req\n");
        exit(1);
    }
}
if (Installer::isInstalled() && !isset($o['force'])) {
    fwrite(STDERR, "Bereits installiert (config/installed.lock). Mit --force überschreiben.\n");
    exit(1);
}
$password = $o['password'] ?? random_password(16);
try {
    Installer::run(
        ['host' => $o['host'] ?? 'localhost', 'port' => (int)($o['port'] ?? 3306), 'name' => $o['db'], 'user' => $o['user'], 'pass' => $o['pass'] ?? ''],
        ['username' => $o['admin'], 'email' => $o['email'], 'password' => $password],
        ['site_url' => rtrim($o['url'] ?? 'https://frank-panzer.de', '/'), 'base_path' => rtrim($o['base'] ?? '', '/')]
    );
} catch (Throwable $e) {
    fwrite(STDERR, 'Fehler: ' . $e->getMessage() . "\n");
    exit(1);
}
if (!empty($o['web-user'])) {
    // Besitz für den Webserver-Benutzer setzen (config/storage/uploads), z. B. --web-user=www-data
    $chown = static function (string $path) use (&$chown, $o): void {
        @chown($path, $o['web-user']);
        @chgrp($path, $o['web-user']);
        if (is_dir($path)) {
            foreach (scandir($path) ?: [] as $f) {
                if ($f !== '.' && $f !== '..') {
                    $chown($path . '/' . $f);
                }
            }
        }
    };
    foreach (['config', 'storage', 'uploads'] as $d) {
        $chown(FP_ROOT . '/' . $d);
    }
    echo "Besitzer von config/, storage/ und uploads/ auf {$o['web-user']} gesetzt.\n";
}
echo "Installation abgeschlossen.\nAdmin-Login: {$o['admin']} / $password\n";
echo "Hinweis: config/config.php wurde mit Rechten 0644 angelegt. Ist der Webserver-Benutzer nicht der Besitzer, ggf. Gruppe setzen und auf 0640 verschärfen.\n";
