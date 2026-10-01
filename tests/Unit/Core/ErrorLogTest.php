<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Unit\Core;

use Pegelstand\Core\ErrorLog;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ErrorLogTest extends TestCase
{
    public function testSchreibtEineZeileOhneZeilenumbruchEinschleusung(): void
    {
        $ordner = sys_get_temp_dir() . '/ps-log-' . bin2hex(random_bytes(4));
        $datei = $ordner . '/logs/error.log';
        (new ErrorLog($datei))->write(new RuntimeException("erste\nzweite Zeile"));

        $zeilen = file($datei, FILE_IGNORE_NEW_LINES) ?: [];
        self::assertCount(1, $zeilen);
        self::assertStringContainsString('RuntimeException: erste zweite Zeile', $zeilen[0]);
        self::assertStringContainsString('ErrorLogTest.php', $zeilen[0]);

        unlink($datei);
        rmdir($ordner . '/logs');
        rmdir($ordner);
    }
}
