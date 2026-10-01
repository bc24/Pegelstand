<?php

declare(strict_types=1);

namespace Pegelstand\Core;

use RuntimeException;

/**
 * Liest die Konfigurationsdatei (config/config.php), die der Installer erzeugt.
 */
final class Config
{
    /**
     * @param array<string, mixed> $values
     */
    private function __construct(private readonly array $values) {}

    /**
     * @param array<string, mixed> $values
     */
    public static function fromArray(array $values): self
    {
        return new self($values);
    }

    public static function load(string $file): ?self
    {
        if (!is_file($file)) {
            return null;
        }
        $werte = (static fn(): mixed => require $file)();
        if (!is_array($werte)) {
            throw new RuntimeException('Die Konfigurationsdatei muss ein Array zurückgeben.');
        }

        /** @var array<string, mixed> $werte */
        return new self($werte);
    }

    /**
     * Zugriff mit Punktschreibweise, z. B. "db.host".
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $aktuell = $this->values;
        foreach (explode('.', $key) as $teil) {
            if (!is_array($aktuell) || !array_key_exists($teil, $aktuell)) {
                return $default;
            }
            $aktuell = $aktuell[$teil];
        }

        return $aktuell;
    }

    public function string(string $key, string $default = ''): string
    {
        $wert = $this->get($key, $default);

        return is_scalar($wert) ? (string) $wert : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $wert = $this->get($key, $default);

        return is_numeric($wert) ? (int) $wert : $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        return (bool) $this->get($key, $default);
    }
}
