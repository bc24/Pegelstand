<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Unit\Database;

use Pegelstand\Database\Migration;
use PHPUnit\Framework\TestCase;

/**
 * Schützt die Regeln für alle Migrationen: wiederholbar, präfixiert, InnoDB und utf8mb4.
 */
final class MigrationDateienTest extends TestCase
{
    /**
     * @return list<array{string, Migration}>
     */
    private function migrationen(): array
    {
        $dateien = glob(PEGELSTAND_ROOT . '/database/migrations/[0-9][0-9][0-9][0-9]_*.php') ?: [];
        self::assertNotEmpty($dateien);
        $ergebnis = [];
        foreach ($dateien as $datei) {
            $migration = (static fn(): mixed => require $datei)();
            self::assertInstanceOf(Migration::class, $migration, basename($datei));
            $ergebnis[] = [basename($datei), $migration];
        }

        return $ergebnis;
    }

    public function testDateinamenSindLueckenlosNummeriert(): void
    {
        $nummern = array_map(static fn(array $m): int => (int) substr($m[0], 0, 4), $this->migrationen());

        self::assertSame(range(1, count($nummern)), $nummern);
    }

    public function testBefehleSindWiederholbarUndPraefixiert(): void
    {
        foreach ($this->migrationen() as [$datei, $migration]) {
            self::assertNotSame('', $migration->name(), $datei);
            foreach ($migration->statements('x1_') as $sql) {
                self::assertMatchesRegularExpression('/^CREATE TABLE IF NOT EXISTS `x1_[a-z_]+`/', $sql, $datei . ': ' . substr($sql, 0, 60));
                self::assertStringContainsString('ENGINE=InnoDB', $sql, $datei);
                self::assertStringContainsString('utf8mb4_unicode_ci', $sql, $datei);
            }
        }
    }

    public function testOhnePraefixFunktioniertDasSchemaAuch(): void
    {
        foreach ($this->migrationen() as [, $migration]) {
            foreach ($migration->statements('') as $sql) {
                self::assertMatchesRegularExpression('/^CREATE TABLE IF NOT EXISTS `[a-z_]+`/', $sql);
            }
        }
    }
}
