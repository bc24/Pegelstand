<?php

declare(strict_types=1);

namespace Pegelstand\Stats;

/** Schlüssel der Aufschlüsselungen in `agg_daily_dim` (siehe docs/schema-entwurf.md). */
final class Dimension
{
    public const PAGE = 1;
    public const ENTRY_PAGE = 2;
    public const EXIT_PAGE = 3;
    public const REFERRER = 4;
    public const UTM_SOURCE = 5;
    public const UTM_MEDIUM = 6;
    public const UTM_CAMPAIGN = 7;
    public const UTM_TERM = 8;
    public const UTM_CONTENT = 9;
    public const COUNTRY = 10;
    public const DEVICE = 11;
    public const BROWSER = 12;
    public const OS = 13;
    public const EVENT_NAME = 14;

    /** Ländercode als Zahl, z. B. "DE" => 17477. */
    public static function countryId(string $code): int
    {
        return ord($code[0]) * 256 + ord($code[1]);
    }

    public static function countryCode(int $id): string
    {
        return chr(intdiv($id, 256)) . chr($id % 256);
    }
}
