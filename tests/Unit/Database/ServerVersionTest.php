<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Unit\Database;

use Pegelstand\Database\ServerVersion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ServerVersionTest extends TestCase
{
    /**
     * @return iterable<string, array{string, bool, string}>
     */
    public static function versionen(): iterable
    {
        yield 'MySQL 8.0' => ['8.0.36', true, 'MySQL 8.0.36'];
        yield 'MySQL 8.4' => ['8.4.0', true, 'MySQL 8.4.0'];
        yield 'MySQL 9' => ['9.1.0', true, 'MySQL 9.1.0'];
        yield 'MySQL 5.7' => ['5.7.44', false, 'MySQL 5.7.44'];
        yield 'MySQL mit Zusatz' => ['8.0.35-0ubuntu0.22.04.1', true, 'MySQL 8.0.35'];
        yield 'MariaDB 10.6' => ['10.6.12-MariaDB', true, 'MariaDB 10.6.12'];
        yield 'MariaDB 10.11 Ubuntu' => ['10.11.14-MariaDB-0ubuntu0.24.04.1', true, 'MariaDB 10.11.14'];
        yield 'MariaDB 11.4' => ['11.4.2-MariaDB', true, 'MariaDB 11.4.2'];
        yield 'MariaDB 10.5' => ['10.5.23-MariaDB', false, 'MariaDB 10.5.23'];
        yield 'MariaDB mit Replikationspräfix' => ['5.5.5-10.6.12-MariaDB', true, 'MariaDB 10.6.12'];
        yield 'Unlesbar' => ['unbekannt', false, 'MySQL 0'];
    }

    #[DataProvider('versionen')]
    public function testErkennungUndMindestversion(string $roh, bool $unterstuetzt, string $bezeichnung): void
    {
        self::assertSame($unterstuetzt, ServerVersion::isSupported($roh));
        self::assertSame($bezeichnung, ServerVersion::label($roh));
    }
}
