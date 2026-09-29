<?php
declare(strict_types=1);
/**
 * Diagnose der Installation (nur Kommandozeile):
 *   php bin/doctor.php
 * Am aussagekräftigsten als Webserver-Benutzer, z. B.:  sudo -u www-data php bin/doctor.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
define('FP_ROOT', dirname(__DIR__));
$problems = 0;
$ok = static function (string $m): void { echo "  [ OK ]  $m\n"; };
$warn = static function (string $m) use (&$problems): void { echo "  [FEHLER] $m\n"; $problems++; };
$info = static function (string $m): void { echo "          $m\n"; };
$who = function_exists('posix_getpwuid') ? (posix_getpwuid(posix_geteuid())['name'] ?? '?') : get_current_user();

echo "\nFrank Panzer — Installations-Diagnose (läuft als Benutzer: $who)\n\n";

echo "PHP\n";
version_compare(PHP_VERSION, '8.1.0', '>=') ? $ok('PHP ' . PHP_VERSION) : $warn('PHP ' . PHP_VERSION . ' ist zu alt (benötigt >= 8.1)');
foreach (['pdo_mysql', 'mbstring', 'fileinfo', 'dom'] as $ext) {
    extension_loaded($ext) ? $ok("Erweiterung $ext") : $warn("Erweiterung $ext fehlt");
}
foreach (['gd', 'zip', 'iconv'] as $ext) {
    extension_loaded($ext) ? $ok("Erweiterung $ext") : $info("Hinweis: Erweiterung $ext fehlt (optional/empfohlen)");
}

echo "\nDateien\n";
$cfg = FP_ROOT . '/config/config.php';
if (!is_file($cfg)) {
    $warn('config/config.php fehlt — Installation (/install/ oder php install/cli.php) noch nicht durchgeführt?');
} else {
    $perm = substr(sprintf('%o', fileperms($cfg)), -4);
    $owner = function_exists('posix_getpwuid') ? (posix_getpwuid((int)fileowner($cfg))['name'] ?? (string)fileowner($cfg)) : (string)fileowner($cfg);
    $group = function_exists('posix_getgrgid') ? (posix_getgrgid((int)filegroup($cfg))['name'] ?? (string)filegroup($cfg)) : (string)filegroup($cfg);
    $info("config/config.php: Rechte $perm, Besitzer $owner:$group");
    if (!is_readable($cfg)) {
        $warn("config/config.php ist für diesen Benutzer ($who) nicht lesbar");
    } else {
        $ok('config/config.php ist lesbar');
        if ((fileperms($cfg) & 0004) === 0) {
            $info('Achtung: Die Datei ist nicht für "andere" lesbar. Der Webserver-Benutzer muss Besitzer oder in der Gruppe sein.');
            $info("Typische Lösung:  chown www-data:www-data config/config.php   (Benutzer/Gruppe deines Servers einsetzen)");
        }
    }
}
foreach (['storage', 'storage/logs', 'uploads', 'config'] as $dir) {
    $path = FP_ROOT . '/' . $dir;
    if (!is_dir($path)) {
        $warn("Ordner $dir fehlt");
    } elseif (!is_writable($path)) {
        $warn("Ordner $dir ist für $who nicht beschreibbar");
    } else {
        $ok("Ordner $dir ist beschreibbar");
    }
}
$info('Wenn der Webserver unter einem anderen Benutzer läuft, gehören storage/, uploads/ und config/ diesem Benutzer:');
$info('  chown -R www-data:www-data storage uploads config');

echo "\nDatenbank\n";
$conf = null;
if (is_file($cfg) && is_readable($cfg)) {
    try {
        $conf = require $cfg;
    } catch (Throwable $e) {
        $warn('config/config.php enthält einen Fehler: ' . $e->getMessage());
    }
}
if (is_array($conf) && isset($conf['db'])) {
    $d = $conf['db'];
    $info("Verbindung: {$d['user']}@{$d['host']}:{$d['port']} / {$d['name']}");
    try {
        $pdo = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $d['host'], $d['port'], $d['name']), $d['user'], $d['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $ok('Verbindung erfolgreich (Server ' . $pdo->query('SELECT VERSION()')->fetchColumn() . ')');
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        foreach (['users', 'settings', 'sections', 'projects', 'posts', 'pages'] as $t) {
            in_array($t, $tables, true) ? $ok("Tabelle $t vorhanden (" . $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn() . ' Zeilen)') : $warn("Tabelle $t fehlt");
        }
        $info('Standard-Socket der PHP-Kommandozeile: ' . (ini_get('pdo_mysql.default_socket') ?: '(nicht gesetzt)'));
    } catch (Throwable $e) {
        $warn('Datenbankverbindung fehlgeschlagen: ' . $e->getMessage());
        $info('Prüfe Zugangsdaten in config/config.php, ob der Datenbankdienst läuft und ob der Benutzer Rechte auf die Datenbank hat.');
        if ($d['host'] === 'localhost') {
            $info('Tipp: Bei "localhost" nutzt PHP den Unix-Socket. Hilft es, in config.php host auf 127.0.0.1 zu setzen?');
        }
    }
}

echo "\nSeitenaufruf (simuliert, mit sichtbaren Fehlern)\n";
if ($conf) {
    foreach (['/', '/admin/', '/blog/'] as $uri) {
        $env = ['REQUEST_URI' => $uri, 'REQUEST_METHOD' => 'GET', 'HTTP_HOST' => 'localhost', 'SERVER_NAME' => 'localhost', 'SCRIPT_NAME' => '/index.php', 'PATH' => getenv('PATH') ?: '/usr/bin:/bin'];
        $script = str_starts_with($uri, '/admin') ? FP_ROOT . '/admin/index.php' : FP_ROOT . '/index.php';
        $cmd = escapeshellarg(PHP_BINARY) . ' -d display_errors=1 -d error_reporting=-1 -d variables_order=EGPCS ' . escapeshellarg($script) . ' 2>&1';
        $proc = proc_open($cmd, [1 => ['pipe', 'w']], $pipes, FP_ROOT, $env);
        $out = is_resource($proc) ? stream_get_contents($pipes[1]) : '';
        if (is_resource($proc)) {
            proc_close($proc);
        }
        if (preg_match('/(Fatal error|Parse error|Uncaught|Warning|Notice|Deprecated)[^\n]*/i', $out, $m)) {
            $warn("$uri liefert einen PHP-Fehler:");
            $info(trim(mb_substr($m[0], 0, 400)));
        } elseif (str_contains($out, '[fp-error]')) {
            $warn("$uri zeigt die Fehlerseite:");
            $info(trim(mb_substr($out, 0, 500)));
        } elseif (stripos($out, '<title>') !== false || stripos($out, 'Location:') !== false || strlen($out) > 200) {
            $t = preg_match('~<title>([^<]*)~i', $out, $tm) ? trim($tm[1]) : '';
            if (str_contains($out, '[fp-error]') || str_contains($out, '<title>Fehler</title>')) {
                $warn("$uri zeigt die Fehlerseite: $t");
            } else {
                $ok("$uri wird ausgeliefert (" . strlen($out) . " Bytes, Titel: $t)");
            }
        } else {
            $warn("$uri liefert keine Ausgabe");
        }
    }
} else {
    $info('übersprungen (keine lesbare Konfiguration)');
}

echo "\n" . ($problems === 0 ? "Alles in Ordnung.\n" : "$problems Problem(e) gefunden — siehe oben.\n") . "\n";
exit($problems === 0 ? 0 : 1);
