<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Unit\Install;

use Pegelstand\Install\Check;
use Pegelstand\Install\Requirements;
use PHPUnit\Framework\TestCase;

final class RequirementsTest extends TestCase
{
    /**
     * @param list<string> $fehlend
     * @return array<string, Check>
     */
    private function pruefe(string $php = '8.3.0', array $fehlend = [], ?string $configDir = null, bool $argon2 = true): array
    {
        $ordner = sys_get_temp_dir();
        $checks = (new Requirements(
            $php,
            static fn(string $name): bool => !in_array($name, $fehlend, true),
            $configDir ?? $ordner,
            $ordner,
            $argon2,
        ))->run();
        $nachId = [];
        foreach ($checks as $check) {
            $nachId[$check->id] = $check;
        }

        return $nachId;
    }

    public function testAllesErfuellt(): void
    {
        $checks = $this->pruefe();

        foreach ($checks as $check) {
            self::assertSame(Check::OK, $check->status, $check->id);
        }
        self::assertFalse(Requirements::isBlocked(array_values($checks)));
    }

    public function testZuAltesPhpBlockiert(): void
    {
        $checks = $this->pruefe('8.1.27');

        self::assertSame(Check::FAIL, $checks['php']->status);
        self::assertSame('8.1.27', $checks['php']->params['version']);
        self::assertTrue(Requirements::isBlocked(array_values($checks)));
    }

    public function testGenauDieMindestversionReicht(): void
    {
        self::assertSame(Check::OK, $this->pruefe('8.2.0')['php']->status);
    }

    public function testFehlendeErweiterungBlockiert(): void
    {
        foreach (Requirements::REQUIRED_EXTENSIONS as $erweiterung) {
            $checks = $this->pruefe('8.3.0', [$erweiterung]);

            self::assertSame(Check::FAIL, $checks['ext_' . $erweiterung]->status, $erweiterung);
            self::assertTrue(Requirements::isBlocked(array_values($checks)));
        }
    }

    public function testNichtBeschreibbarerOrdnerBlockiert(): void
    {
        $checks = $this->pruefe('8.3.0', [], '/gibt/es/nicht/config');

        self::assertSame(Check::FAIL, $checks['config_schreibbar']->status);
        self::assertSame(Check::OK, $checks['storage_schreibbar']->status);
    }

    public function testFehlenderArgon2IstNurEinHinweis(): void
    {
        $checks = $this->pruefe('8.3.0', [], null, false);

        self::assertSame(Check::WARN, $checks['argon2']->status);
        self::assertFalse(Requirements::isBlocked(array_values($checks)));
    }

    public function testDieseUmgebungErfuelltDieAnforderungen(): void
    {
        $checks = Requirements::forThisServer(sys_get_temp_dir(), sys_get_temp_dir())->run();

        self::assertFalse(Requirements::isBlocked($checks));
    }
}
