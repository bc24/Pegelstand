<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Unit;

use Pegelstand\Version;
use PHPUnit\Framework\TestCase;

final class VersionTest extends TestCase
{
    public function testVersionFolgtSemanticVersioning(): void
    {
        $muster = '/^\d+\.\d+\.\d+(-[0-9A-Za-z.-]+)?(\+[0-9A-Za-z.-]+)?$/';

        self::assertMatchesRegularExpression($muster, Version::CURRENT);
    }
}
