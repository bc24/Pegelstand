<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Unit\Core;

use Pegelstand\Core\Paths;
use PHPUnit\Framework\TestCase;

final class PathsTest extends TestCase
{
    public function testStandardUndUmlenkung(): void
    {
        $standard = new Paths('/srv/ps');
        self::assertSame('/srv/ps/config/config.php', $standard->configFile());
        self::assertSame('/srv/ps/config/installed.lock', $standard->lockFile());
        self::assertSame('/srv/ps/storage', $standard->storageDir());
        self::assertSame('/srv/ps/database/migrations', $standard->migrationsDir());
        self::assertSame('/srv/ps/resources', $standard->resourcesDir());

        $umgelenkt = new Paths('/srv/ps', '/tmp/c', '/tmp/s');
        self::assertSame('/tmp/c/config.php', $umgelenkt->configFile());
        self::assertSame('/tmp/s', $umgelenkt->storageDir());
    }
}
