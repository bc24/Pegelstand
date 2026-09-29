<?php
declare(strict_types=1);
/**
 * Automatisches Backup (Datenbank als .sql.gz, Uploads als .zip) nach storage/backups.
 *
 *   php bin/backup.php [--keep=14] [--no-uploads]
 *
 * Täglich per Cron, als Webserver-Benutzer (damit die Dateien diesem gehören):
 *   17 3 * * *  cd /var/www/frank-panzer.de && sudo -u www-data php bin/backup.php >/dev/null 2>&1
 * Pro Art bleiben die neuesten --keep Dateien erhalten (Standard 14). Die Uploads werden nur neu gesichert, wenn sie sich geändert haben.
 * Für echten Schutz die Dateien zusätzlich auf einen anderen Rechner/Speicher kopieren (rsync, Cloud-Speicher).
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/bootstrap.php';

$keep = Backup::DEFAULT_KEEP;
$uploads = true;
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--keep=(\d+)$/', $arg, $m)) {
        $keep = max(1, (int)$m[1]);
    } elseif ($arg === '--no-uploads') {
        $uploads = false;
    } else {
        fwrite(STDERR, "Unbekannte Option: $arg\nAufruf: php bin/backup.php [--keep=14] [--no-uploads]\n");
        exit(2);
    }
}
try {
    $res = Backup::run($keep, $uploads);
} catch (Throwable $e) {
    fwrite(STDERR, 'Backup fehlgeschlagen: ' . $e->getMessage() . "\n");
    exit(1);
}
foreach ($res['files'] as $f) {
    echo 'Angelegt:   storage/backups/' . $f['name'] . ' (' . Media::humanSize($f['size']) . ")\n";
}
foreach ($res['skipped'] as $m) {
    echo "Übersprungen: $m\n";
}
foreach ($res['removed'] as $n) {
    echo "Gelöscht (älter als $keep Sicherungen): $n\n";
}
try {
    Db::insert('activity_log', ['user_id' => null, 'username' => 'cron', 'action' => 'backup', 'entity' => 'system', 'entity_id' => '', 'detail' => 'Automatisches Backup', 'ip_hash' => '', 'created_at' => now()]);
} catch (Throwable) {
}
