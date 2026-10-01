<?php

declare(strict_types=1);

namespace Pegelstand\Core;

use Closure;
use RuntimeException;

/**
 * Minimaler DI-Container: Fabriken werden beim ersten Zugriff aufgerufen, das Ergebnis bleibt erhalten.
 */
final class Container
{
    /** @var array<string, Closure(self): mixed> */
    private array $factories = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    /**
     * @param Closure(self): mixed $factory
     */
    public function set(string $id, Closure $factory): void
    {
        $this->factories[$id] = $factory;
        unset($this->instances[$id]);
    }

    public function has(string $id): bool
    {
        return isset($this->factories[$id]) || array_key_exists($id, $this->instances);
    }

    /**
     * @template T of object
     * @param class-string<T> $typ
     * @return T
     */
    public function get(string $id, string $typ): object
    {
        if (!array_key_exists($id, $this->instances)) {
            if (!isset($this->factories[$id])) {
                throw new RuntimeException(sprintf('Dienst "%s" ist nicht registriert.', $id));
            }
            $this->instances[$id] = ($this->factories[$id])($this);
        }
        $dienst = $this->instances[$id];
        if (!$dienst instanceof $typ) {
            throw new RuntimeException(sprintf('Dienst "%s" ist kein %s.', $id, $typ));
        }

        return $dienst;
    }
}
