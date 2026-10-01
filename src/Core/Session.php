<?php

declare(strict_types=1);

namespace Pegelstand\Core;

/**
 * Sitzungsspeicher für angemeldete Bereiche und den Installer. Besucher der gemessenen Websites
 * bekommen nie eine Sitzung oder ein Cookie.
 */
interface Session
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;

    public function remove(string $key): void;

    /** Erneuert die Sitzungs-ID, z. B. nach einer Anmeldung. */
    public function regenerate(): void;

    public function destroy(): void;
}
