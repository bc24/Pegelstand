<?php
declare(strict_types=1);

final class Lang
{
    public static string $code = 'de';
    private static array $strings = [];

    public const SUPPORTED = ['de', 'en'];

    public static function init(string $code): void
    {
        self::$code = in_array($code, self::SUPPORTED, true) ? $code : 'de';
        self::$strings = require FP_ROOT . '/app/lang.php';
    }

    public static function get(string $key): string
    {
        return self::$strings[self::$code][$key] ?? self::$strings['de'][$key] ?? $key;
    }

    public static function locale(): string
    {
        return self::$code === 'en' ? 'en_US' : 'de_DE';
    }
}
