<?php
declare(strict_types=1);

/** SQL-Dump und Uploads-ZIP, wahlweise als Download (Admin) oder automatisch in storage/backups (Cron, bin/backup.php). */
final class Backup
{
    public const DEFAULT_KEEP = 14;
    private const NAME = '~^(db|uploads)-\d{4}-\d{2}-\d{2}-\d{6}\.(sql\.gz|sql|zip)$~';

    public static function dir(): string
    {
        return FP_ROOT . '/storage/backups';
    }

    /** Ordner anlegen und vor Webzugriff schützen. */
    public static function ensureDir(): bool
    {
        $dir = self::dir();
        if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
            return false;
        }
        if (!is_file($dir . '/.htaccess')) {
            @file_put_contents($dir . '/.htaccess', "Require all denied\n<IfModule !mod_authz_core.c>\n  Order allow,deny\n  Deny from all\n</IfModule>\n");
        }
        if (!is_file($dir . '/index.html')) {
            @file_put_contents($dir . '/index.html', '');
        }
        return is_writable($dir);
    }

    public static function validName(string $name): bool
    {
        return (bool)preg_match(self::NAME, $name);
    }

    /** SQL-Dump über eine Ausgabefunktion schreiben (Datei, gzip-Stream oder Download). */
    public static function writeSql(callable $out): void
    {
        $pdo = Db::pdo();
        $out("-- Frank Panzer Backup\n-- Erstellt: " . date('c') . "\n-- Wiederherstellung: Admin → Backup → Wiederherstellen oder per phpMyAdmin\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n");
        foreach (Db::col('SHOW TABLES') as $table) {
            if ($table === 'rate_limits') {
                continue;
            }
            $create = (string)Db::one("SHOW CREATE TABLE `$table`")['Create Table'];
            $out("DROP TABLE IF EXISTS `$table`;\n" . preg_replace('/\s*\R\s*/', ' ', $create) . ";\n");
            $offset = 0;
            do {
                $rows = Db::all("SELECT * FROM `$table` LIMIT 400 OFFSET $offset");
                if (!$rows) {
                    break;
                }
                $cols = '`' . implode('`,`', array_keys($rows[0])) . '`';
                $vals = [];
                foreach ($rows as $r) {
                    $vals[] = '(' . implode(',', array_map(static fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), array_values($r))) . ')';
                }
                $out("INSERT INTO `$table` ($cols) VALUES\n" . implode(",\n", $vals) . ";\n");
                $offset += 400;
            } while (count($rows) === 400);
        }
        $out("SET FOREIGN_KEY_CHECKS=1;\n");
    }

    /** Alle Dateien in uploads/ (ohne .htaccess/.gitkeep) als [Pfad, Größe, Änderungszeit]. */
    private static function uploadFiles(): array
    {
        $base = FP_ROOT . '/uploads';
        $files = [];
        if (!is_dir($base)) {
            return $files;
        }
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)) as $f) {
            if ($f->isFile() && !in_array($f->getFilename(), ['.htaccess', '.gitkeep'], true)) {
                $files[] = [$f->getPathname(), $f->getSize(), $f->getMTime()];
            }
        }
        return $files;
    }

    public static function zipUploads(string $target): int
    {
        $zip = new ZipArchive();
        if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('ZIP-Datei konnte nicht angelegt werden.');
        }
        $base = FP_ROOT . '/uploads';
        $n = 0;
        foreach (self::uploadFiles() as [$path]) {
            $zip->addFile($path, 'uploads/' . ltrim(substr($path, strlen($base)), '/'));
            $n++;
        }
        $zip->close();
        return $n;
    }

    /** Vorhandene Server-Backups, neueste zuerst: [name, type, size, time]. */
    public static function list(): array
    {
        $out = [];
        foreach (@scandir(self::dir()) ?: [] as $n) {
            if (self::validName($n)) {
                $p = self::dir() . '/' . $n;
                $out[] = ['name' => $n, 'type' => str_starts_with($n, 'db-') ? 'db' : 'uploads', 'size' => (int)@filesize($p), 'time' => (int)@filemtime($p)];
            }
        }
        usort($out, static fn($a, $b) => $b['name'] <=> $a['name']);
        return $out;
    }

    public static function newest(): ?int
    {
        $l = self::list();
        return $l ? max(array_column($l, 'time')) : null;
    }

    /** Pro Art nur die neuesten $keep Dateien behalten. @return string[] gelöschte Dateinamen */
    public static function rotate(int $keep): array
    {
        $removed = [];
        foreach (['db', 'uploads'] as $type) {
            $files = array_values(array_filter(self::list(), static fn($f) => $f['type'] === $type));
            foreach (array_slice($files, max(1, $keep)) as $f) {
                if (@unlink(self::dir() . '/' . $f['name'])) {
                    $removed[] = $f['name'];
                }
            }
        }
        return $removed;
    }

    /**
     * Sicherung in storage/backups anlegen.
     * @return array{files: array<int, array{name: string, size: int}>, skipped: string[], removed: string[]}
     */
    public static function run(int $keep = self::DEFAULT_KEEP, bool $uploads = true): array
    {
        @set_time_limit(600);
        if (!self::ensureDir()) {
            throw new RuntimeException('Der Ordner storage/backups ist nicht beschreibbar.');
        }
        $stamp = date('Y-m-d-His');
        $res = ['files' => [], 'skipped' => [], 'removed' => []];

        // Datenbank
        $gz = function_exists('gzopen');
        $name = 'db-' . $stamp . ($gz ? '.sql.gz' : '.sql');
        $path = self::dir() . '/' . $name;
        $tmp = $path . '.part';
        $h = $gz ? @gzopen($tmp, 'wb6') : @fopen($tmp, 'wb');
        if (!$h) {
            throw new RuntimeException('Backup-Datei konnte nicht geschrieben werden.');
        }
        try {
            self::writeSql($gz ? static function (string $s) use ($h): void { gzwrite($h, $s); } : static function (string $s) use ($h): void { fwrite($h, $s); });
        } catch (Throwable $e) {
            $gz ? gzclose($h) : fclose($h);
            @unlink($tmp);
            throw $e;
        }
        $gz ? gzclose($h) : fclose($h);
        rename($tmp, $path);
        $res['files'][] = ['name' => $name, 'size' => (int)filesize($path)];

        // Uploads nur, wenn sich seit der letzten Sicherung etwas geändert hat
        if ($uploads) {
            if (!class_exists('ZipArchive')) {
                $res['skipped'][] = 'Uploads: PHP-Erweiterung zip fehlt';
            } else {
                $files = self::uploadFiles();
                $lastZip = 0;
                foreach (self::list() as $f) {
                    if ($f['type'] === 'uploads') {
                        $lastZip = max($lastZip, $f['time']);
                    }
                }
                $newest = $files ? max(array_column($files, 2)) : 0;
                if (!$files) {
                    $res['skipped'][] = 'Uploads: keine Dateien vorhanden';
                } elseif ($lastZip > 0 && $newest <= $lastZip && count($files) === self::lastUploadCount()) {
                    $res['skipped'][] = 'Uploads: unverändert seit der letzten Sicherung';
                } else {
                    $zname = 'uploads-' . $stamp . '.zip';
                    $zpath = self::dir() . '/' . $zname;
                    try {
                        $n = self::zipUploads($zpath . '.part');
                    } catch (Throwable $e) {
                        @unlink($zpath . '.part');
                        throw $e;
                    }
                    rename($zpath . '.part', $zpath);
                    self::lastUploadCount($n);
                    $res['files'][] = ['name' => $zname, 'size' => (int)filesize($zpath)];
                }
            }
        }
        $res['removed'] = self::rotate($keep);
        return $res;
    }

    /** Anzahl der Dateien der letzten Uploads-Sicherung (erkennt auch gelöschte Dateien). */
    private static function lastUploadCount(?int $set = null): int
    {
        $f = self::dir() . '/.uploads-count';
        if ($set !== null) {
            @file_put_contents($f, (string)$set);
            return $set;
        }
        return is_file($f) ? (int)file_get_contents($f) : -1;
    }
}
