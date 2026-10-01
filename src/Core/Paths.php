<?php

declare(strict_types=1);

namespace Pegelstand\Core;

/**
 * Zentrale Pfade der Installation. Für Tests und besondere Betriebsarten lässt sich das
 * Konfigurationsverzeichnis über PEGELSTAND_CONFIG_DIR umlenken.
 */
final class Paths
{
    public function __construct(
        public readonly string $root,
        private readonly ?string $configOverride = null,
        private readonly ?string $storageOverride = null,
    ) {}

    public static function fromEnvironment(string $root): self
    {
        $config = getenv('PEGELSTAND_CONFIG_DIR');
        $storage = getenv('PEGELSTAND_STORAGE_DIR');

        return new self(
            $root,
            $config === false || $config === '' ? null : $config,
            $storage === false || $storage === '' ? null : $storage,
        );
    }

    public function configDir(): string
    {
        return $this->configOverride ?? $this->root . '/config';
    }

    public function configFile(): string
    {
        return $this->configDir() . '/config.php';
    }

    public function lockFile(): string
    {
        return $this->configDir() . '/installed.lock';
    }

    public function storageDir(): string
    {
        return $this->storageOverride ?? $this->root . '/storage';
    }

    public function resourcesDir(): string
    {
        return $this->root . '/resources';
    }

    public function migrationsDir(): string
    {
        return $this->root . '/database/migrations';
    }
}
