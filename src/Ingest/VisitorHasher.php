<?php

declare(strict_types=1);

namespace Pegelstand\Ingest;

/**
 * Anonyme Besucherkennung: HMAC-SHA-256 über Site, Adresse und User-Agent mit dem Tages-Salt, auf 16 Byte gekürzt.
 * Ohne das (nach Tagesende gelöschte) Salt lässt sich daraus weder Adresse noch Gerät rekonstruieren.
 */
final class VisitorHasher
{
    public static function visitor(string $salt, int $siteId, string $normalizedIp, string $userAgent): string
    {
        return substr(hash_hmac('sha256', $siteId . '|' . $normalizedIp . '|' . $userAgent, $salt, true), 0, 16);
    }

    /** Schlüssel für die Ratenbegrenzung, getrennt vom Besucher-Hash. */
    public static function rateKey(string $salt, string $normalizedIp): string
    {
        return substr(hash_hmac('sha256', 'rate|' . $normalizedIp, $salt, true), 0, 16);
    }
}
