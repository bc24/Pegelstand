<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Integration;

use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

/**
 * Prüft, dass die Testumgebung eine echte MySQL- oder MariaDB-Instanz erreicht.
 * Ohne PEGELSTAND_TEST_DB_DSN wird der Test übersprungen (lokal ohne Datenbank).
 */
final class DatabaseConnectionTest extends TestCase
{
    public function testVerbindungUndUtf8mb4(): void
    {
        $dsn = getenv('PEGELSTAND_TEST_DB_DSN');
        if ($dsn === false || $dsn === '') {
            self::markTestSkipped('PEGELSTAND_TEST_DB_DSN ist nicht gesetzt.');
        }

        $pdo = new PDO(
            $dsn,
            getenv('PEGELSTAND_TEST_DB_USER') ?: null,
            getenv('PEGELSTAND_TEST_DB_PASSWORD') ?: null,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );

        $version = $this->einzelwert($pdo, 'SELECT VERSION()');
        self::assertIsString($version);
        self::assertNotSame('', $version);

        self::assertSame('utf8mb4', $this->einzelwert($pdo, 'SELECT @@character_set_connection'));
    }

    private function einzelwert(PDO $pdo, string $sql): mixed
    {
        $ergebnis = $pdo->query($sql);
        self::assertInstanceOf(PDOStatement::class, $ergebnis);

        return $ergebnis->fetchColumn();
    }
}
