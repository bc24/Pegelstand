<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Unit\Database;

use InvalidArgumentException;
use PDO;
use Pegelstand\Database\Database;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class DatabaseTest extends TestCase
{
    private function pdo(): PDO
    {
        return (new ReflectionClass(PDO::class))->newInstanceWithoutConstructor();
    }

    public function testTabellennameTraegtPraefix(): void
    {
        self::assertSame('`ps_users`', (new Database($this->pdo(), 'ps_'))->table('users'));
        self::assertSame('`users`', (new Database($this->pdo(), ''))->table('users'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function ungueltigePraefixe(): iterable
    {
        yield 'Backtick' => ['ps`_'];
        yield 'Leerzeichen' => ['ps _'];
        yield 'Semikolon' => ['ps;'];
        yield 'Bindestrich' => ['ps-'];
        yield 'Zu lang' => [str_repeat('a', 33)];
    }

    #[DataProvider('ungueltigePraefixe')]
    public function testUngueltigerPraefixWirdAbgelehnt(string $praefix): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Database($this->pdo(), $praefix);
    }
}
