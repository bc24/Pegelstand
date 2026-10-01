<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Unit\Install;

use Pegelstand\Install\ConfigWriter;
use Pegelstand\Install\InstallException;
use Pegelstand\Install\PasswordHasher;
use PHPUnit\Framework\TestCase;

final class KleinteileTest extends TestCase
{
    private string $ordner;

    protected function setUp(): void
    {
        $this->ordner = sys_get_temp_dir() . '/ps-cw-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->ordner . '/*') ?: [] as $datei) {
            @unlink($datei);
        }
        @rmdir($this->ordner);
    }

    public function testKonfigurationWirdAtomarGeschriebenUndIstEinlesbar(): void
    {
        (new ConfigWriter())->write($this->ordner, ['db' => ['host' => "lo'cal\\host", 'port' => 3306], 'debug' => false]);

        $werte = (static fn(string $datei): mixed => require $datei)($this->ordner . '/config.php');
        self::assertSame(['db' => ['host' => "lo'cal\\host", 'port' => 3306], 'debug' => false], $werte);
        self::assertFileExists($this->ordner . '/installed.lock');
        self::assertSame([], glob($this->ordner . '/*.tmp'));
        self::assertSame('0640', substr(sprintf('%o', fileperms($this->ordner . '/config.php')), -4));
    }

    public function testKonfigurationsdateiHatDirektzugriffsschutz(): void
    {
        (new ConfigWriter())->write($this->ordner, ['a' => 1]);

        self::assertStringContainsString("defined('PEGELSTAND_ROOT') || exit;", (string) file_get_contents($this->ordner . '/config.php'));
    }

    public function testUnbeschreibbarerOrdnerLiefertVerstaendlichenFehler(): void
    {
        try {
            (new ConfigWriter())->write('/proc/gibt/es/nicht', ['a' => 1]);
            self::fail('Ausnahme erwartet');
        } catch (InstallException $fehler) {
            self::assertSame('install.fehler.config_schreiben', $fehler->messageKey);
        }
    }

    public function testPasswortHashIstPruefbar(): void
    {
        $hash = PasswordHasher::hash('ein sehr langer satz');

        self::assertTrue(password_verify('ein sehr langer satz', $hash));
        self::assertFalse(password_verify('falsch', $hash));
        self::assertMatchesRegularExpression('/^\$(argon2id|2y)\$/', $hash);
        self::assertNotSame($hash, PasswordHasher::hash('ein sehr langer satz'), 'Jeder Hash hat ein eigenes Salz.');
    }
}
