<?php

declare(strict_types=1);

namespace Pegelstand\Install;

use Closure;

/**
 * Prüft, ob der Webspace die Anforderungen erfüllt. Die Umgebung wird übergeben, damit sich jeder Fall testen lässt.
 */
final class Requirements
{
    public const MIN_PHP = '8.2.0';
    public const REQUIRED_EXTENSIONS = ['pdo_mysql', 'json', 'mbstring', 'openssl', 'session'];

    /**
     * @param Closure(string): bool $extensionLoaded
     */
    public function __construct(
        private readonly string $phpVersion,
        private readonly Closure $extensionLoaded,
        private readonly string $configDir,
        private readonly string $storageDir,
        private readonly bool $argon2Available,
    ) {}

    public static function forThisServer(string $configDir, string $storageDir): self
    {
        return new self(
            PHP_VERSION,
            static fn(string $name): bool => extension_loaded($name),
            $configDir,
            $storageDir,
            defined('PASSWORD_ARGON2ID'),
        );
    }

    /**
     * @return list<Check>
     */
    public function run(): array
    {
        $checks = [];
        $checks[] = new Check(
            'php',
            version_compare($this->phpVersion, self::MIN_PHP, '>=') ? Check::OK : Check::FAIL,
            ['version' => $this->phpVersion],
        );
        foreach (self::REQUIRED_EXTENSIONS as $erweiterung) {
            $checks[] = new Check(
                'ext_' . $erweiterung,
                ($this->extensionLoaded)($erweiterung) ? Check::OK : Check::FAIL,
                ['name' => $erweiterung],
            );
        }
        $checks[] = new Check('config_schreibbar', $this->istSchreibbar($this->configDir) ? Check::OK : Check::FAIL);
        $checks[] = new Check('storage_schreibbar', $this->istSchreibbar($this->storageDir) ? Check::OK : Check::FAIL);
        $checks[] = new Check('argon2', $this->argon2Available ? Check::OK : Check::WARN);

        return $checks;
    }

    /**
     * @param list<Check> $checks
     */
    public static function isBlocked(array $checks): bool
    {
        foreach ($checks as $check) {
            if ($check->status === Check::FAIL) {
                return true;
            }
        }

        return false;
    }

    private function istSchreibbar(string $ordner): bool
    {
        if (is_dir($ordner)) {
            return is_writable($ordner);
        }

        // Fehlende Ordner kann die Installation anlegen, wenn der übergeordnete Ordner beschreibbar ist.
        return is_writable(dirname($ordner));
    }
}
