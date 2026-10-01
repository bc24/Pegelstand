<?php

declare(strict_types=1);

namespace Pegelstand\Core;

/**
 * Sitzung im Arbeitsspeicher, vor allem für Tests.
 */
final class ArraySession implements Session
{
    /** @var array<string, mixed> */
    private array $data = [];

    public int $regenerated = 0;

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    public function regenerate(): void
    {
        ++$this->regenerated;
    }

    public function destroy(): void
    {
        $this->data = [];
    }
}
