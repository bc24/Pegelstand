<?php

declare(strict_types=1);

namespace Pegelstand\Database;

/**
 * Prüft, ob der Datenbankserver zu den Anforderungen passt: MySQL ab 8.0 oder MariaDB ab 10.6.
 */
final class ServerVersion
{
    public static function isMariaDb(string $version): bool
    {
        return stripos($version, 'mariadb') !== false;
    }

    /** Gibt die reine Versionsnummer zurück, z. B. "10.11.14" oder "8.0.36". */
    public static function number(string $version): string
    {
        // MariaDB kann hinter einem Replikations-Präfix "5.5.5-" melden.
        $bereinigt = preg_replace('/^5\.5\.5-/', '', $version) ?? $version;
        preg_match('/^\d+(?:\.\d+){0,2}/', $bereinigt, $treffer);

        return $treffer[0] ?? '0';
    }

    public static function isSupported(string $version): bool
    {
        $nummer = self::number($version);

        return version_compare($nummer, self::isMariaDb($version) ? '10.6' : '8.0', '>=');
    }

    public static function label(string $version): string
    {
        return (self::isMariaDb($version) ? 'MariaDB ' : 'MySQL ') . self::number($version);
    }
}
