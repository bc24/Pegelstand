<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Integration;

use Pegelstand\Database\Database;
use PHPUnit\Framework\TestCase;

/**
 * Grundlage für Tests gegen eine echte MySQL- oder MariaDB-Instanz. Ohne PEGELSTAND_TEST_DB_DSN werden sie übersprungen.
 * Jeder Test arbeitet mit einem eigenen Tabellenpräfix und räumt nach sich auf.
 */
abstract class DatenbankTestCase extends TestCase
{
    /** @var list<string> */
    private array $praefixe = [];

    protected function tearDown(): void
    {
        foreach ($this->praefixe as $praefix) {
            $this->raeumeAuf($praefix);
        }
        $this->praefixe = [];
    }

    /**
     * @return array{host: string, port: int, name: string, user: string, password: string}
     */
    protected function zugang(): array
    {
        $dsn = getenv('PEGELSTAND_TEST_DB_DSN');
        if ($dsn === false || $dsn === '') {
            self::markTestSkipped('PEGELSTAND_TEST_DB_DSN ist nicht gesetzt.');
        }
        preg_match('/host=([^;]+)/', $dsn, $host);
        preg_match('/port=(\d+)/', $dsn, $port);
        preg_match('/dbname=([^;]+)/', $dsn, $name);

        return [
            'host' => $host[1] ?? '127.0.0.1',
            'port' => (int) ($port[1] ?? 3306),
            'name' => $name[1] ?? 'pegelstand_test',
            'user' => getenv('PEGELSTAND_TEST_DB_USER') ?: '',
            'password' => getenv('PEGELSTAND_TEST_DB_PASSWORD') ?: '',
        ];
    }

    /**
     * Verbindung mit eigenem, eindeutigem Präfix (wird nach dem Test gelöscht).
     */
    protected function datenbank(?string $praefix = null): Database
    {
        $praefix ??= 't' . bin2hex(random_bytes(3)) . '_';
        $this->praefixe[] = $praefix;
        $db = Database::connect([...$this->zugang(), 'prefix' => $praefix]);
        $this->raeumeAuf($praefix);

        return $db;
    }

    protected function neuerPraefix(): string
    {
        $praefix = 't' . bin2hex(random_bytes(3)) . '_';
        $this->praefixe[] = $praefix;

        return $praefix;
    }

    private function raeumeAuf(string $praefix): void
    {
        $db = Database::connect([...$this->zugang(), 'prefix' => $praefix]);
        $tabellen = $db->fetchAll(
            'SELECT table_name AS n FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE ?',
            [str_replace('_', '\\_', $praefix) . '%'],
        );
        $db->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($tabellen as $zeile) {
            $name = $zeile['n'];
            if (is_string($name)) {
                $db->pdo->exec('DROP TABLE IF EXISTS `' . $name . '`');
            }
        }
        $db->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
}
