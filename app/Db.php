<?php
declare(strict_types=1);

/** Dünne PDO-Schicht. Alle Werte laufen über Prepared Statements. */
final class Db
{
    private static ?PDO $pdo = null;

    public static function connect(array $c): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $c['host'] ?? 'localhost',
            (int)($c['port'] ?? 3306),
            $c['name'] ?? '',
            $c['charset'] ?? 'utf8mb4'
        );
        self::$pdo = new PDO($dsn, $c['user'] ?? '', $c['pass'] ?? '', [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '" . date('P') . "'",
        ]);
        return self::$pdo;
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo) {
            throw new RuntimeException('Keine Datenbankverbindung.');
        }
        return self::$pdo;
    }

    public static function q(string $sql, array $params = []): PDOStatement
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::q($sql, $params)->fetchAll();
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $r = self::q($sql, $params)->fetch();
        return $r === false ? null : $r;
    }

    public static function val(string $sql, array $params = []): mixed
    {
        $r = self::q($sql, $params)->fetchColumn();
        return $r === false ? null : $r;
    }

    public static function col(string $sql, array $params = []): array
    {
        return self::q($sql, $params)->fetchAll(PDO::FETCH_COLUMN);
    }

    private static function ident(string $name): string
    {
        if (!preg_match('/^[a-z0-9_]+$/i', $name)) {
            throw new InvalidArgumentException('Ungültiger Bezeichner: ' . $name);
        }
        return '`' . $name . '`';
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = 'INSERT INTO ' . self::ident($table) . ' (' . implode(',', array_map([self::class, 'ident'], $cols)) . ')'
             . ' VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')';
        self::q($sql, array_values($data));
        return (int)self::pdo()->lastInsertId();
    }

    public static function update(string $table, int|string $id, array $data, string $pk = 'id'): void
    {
        if (!$data) {
            return;
        }
        $set = implode(',', array_map(fn($c) => self::ident($c) . '=?', array_keys($data)));
        self::q('UPDATE ' . self::ident($table) . ' SET ' . $set . ' WHERE ' . self::ident($pk) . '=?', [...array_values($data), $id]);
    }

    public static function delete(string $table, int|string $id, string $pk = 'id'): void
    {
        self::q('DELETE FROM ' . self::ident($table) . ' WHERE ' . self::ident($pk) . '=?', [$id]);
    }

    /** INSERT ... ON DUPLICATE KEY UPDATE für Key/Value-Tabellen. */
    public static function upsert(string $table, array $data, array $updateCols): void
    {
        $cols = array_keys($data);
        $sql = 'INSERT INTO ' . self::ident($table) . ' (' . implode(',', array_map([self::class, 'ident'], $cols)) . ')'
             . ' VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ') ON DUPLICATE KEY UPDATE '
             . implode(',', array_map(fn($c) => self::ident($c) . '=VALUES(' . self::ident($c) . ')', $updateCols));
        self::q($sql, array_values($data));
    }

    public static function tableExists(string $table): bool
    {
        return (bool)self::val('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?', [$table]);
    }
}
