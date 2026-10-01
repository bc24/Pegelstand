<?php

declare(strict_types=1);

namespace Pegelstand\Database;

use InvalidArgumentException;
use PDO;
use PDOStatement;

/**
 * Dünne Hülle um PDO. Alle Abfragen laufen als Prepared Statements, Tabellennamen tragen den Präfix.
 */
final class Database
{
    public function __construct(
        public readonly PDO $pdo,
        public readonly string $prefix = 'ps_',
        public readonly string $name = '',
    ) {
        if (preg_match('/^[A-Za-z0-9_]{0,32}$/', $prefix) !== 1) {
            throw new InvalidArgumentException('Der Tabellenpräfix darf nur Buchstaben, Ziffern und Unterstriche enthalten (höchstens 32 Zeichen).');
        }
    }

    /**
     * @param array{host: string, port: int, name: string, user: string, password: string, prefix?: string} $zugang
     */
    public static function connect(array $zugang): self
    {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $zugang['host'], $zugang['port'], $zugang['name']);
        $pdo = new PDO($dsn, $zugang['user'], $zugang['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5,
            // Gepufferte Abfragen verhindern Fehler 2014, wenn ein Befehl Zeilen liefert, die nicht gelesen werden.
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ]);
        $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '+00:00'");

        return new self($pdo, $zugang['prefix'] ?? 'ps_', $zugang['name']);
    }

    /** Tabellenname mit Präfix, in Backticks. */
    public function table(string $name): string
    {
        return '`' . $this->prefix . $name . '`';
    }

    /**
     * @param array<int|string, scalar|null> $params
     */
    public function run(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement;
    }

    /**
     * @param array<int|string, scalar|null> $params
     * @return list<array<string, mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        /** @var list<array<string, mixed>> $zeilen */
        $zeilen = $this->run($sql, $params)->fetchAll();

        return $zeilen;
    }

    /**
     * @param array<int|string, scalar|null> $params
     */
    public function fetchValue(string $sql, array $params = []): mixed
    {
        return $this->run($sql, $params)->fetchColumn();
    }

    /**
     * @param array<int|string, scalar|null> $params
     */
    public function fetchInt(string $sql, array $params = []): int
    {
        $wert = $this->fetchValue($sql, $params);

        return is_numeric($wert) ? (int) $wert : 0;
    }

    public function tableExists(string $name): bool
    {
        return $this->fetchInt(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            [$this->prefix . $name],
        ) > 0;
    }

    /** Versionstext des Servers, z. B. "8.0.36" oder "10.11.14-MariaDB-0ubuntu0.24.04.1". */
    public function serverVersion(): string
    {
        $version = $this->fetchValue('SELECT VERSION()');

        return is_scalar($version) ? (string) $version : '';
    }
}
