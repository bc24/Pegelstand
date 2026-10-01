<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Unit\Core;

use Pegelstand\Core\Config;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ConfigTest extends TestCase
{
    private string $datei;

    protected function setUp(): void
    {
        $this->datei = tempnam(sys_get_temp_dir(), 'ps-config-') ?: '';
    }

    protected function tearDown(): void
    {
        @unlink($this->datei);
    }

    public function testFehlendeDateiLiefertNull(): void
    {
        self::assertNull(Config::load($this->datei . '.gibt-es-nicht'));
    }

    public function testLiestWerteMitPunktschreibweise(): void
    {
        file_put_contents($this->datei, "<?php return ['db' => ['host' => 'h', 'port' => '3307'], 'debug' => 1];");
        $config = Config::load($this->datei);

        self::assertNotNull($config);
        self::assertSame('h', $config->string('db.host'));
        self::assertSame(3307, $config->int('db.port'));
        self::assertTrue($config->bool('debug'));
        self::assertSame('standard', $config->string('db.fehlt', 'standard'));
        self::assertNull($config->get('a.b.c'));
    }

    public function testKeinArrayWirdAbgelehnt(): void
    {
        file_put_contents($this->datei, '<?php return 5;');

        $this->expectException(RuntimeException::class);
        Config::load($this->datei);
    }
}
