<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Support;

/** Auskunft über die mitgelieferten Migrationen, damit Tests nicht bei jeder neuen Migration angepasst werden müssen. */
final class Migrationen
{
    /**
     * @return list<string> Versionen wie "0001", aufsteigend
     */
    public static function versionen(): array
    {
        $dateien = glob(PEGELSTAND_ROOT . '/database/migrations/[0-9][0-9][0-9][0-9]_*.php') ?: [];
        sort($dateien);

        return array_map(static fn(string $datei): string => substr(basename($datei), 0, 4), $dateien);
    }

    public static function anzahl(): int
    {
        return count(self::versionen());
    }

    public static function letzte(): string
    {
        $versionen = self::versionen();

        return $versionen[count($versionen) - 1];
    }
}
