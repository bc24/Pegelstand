<?php
declare(strict_types=1);

/** Liest die Symbol-IDs aus dem SVG-Sprite (für icon() und die Icon-Auswahl im Admin). */
final class Icons
{
    private static ?array $names = null;

    public static function file(): string
    {
        return FP_ROOT . '/assets/img/icons.svg';
    }

    public static function version(): string
    {
        return is_file(self::file()) ? substr((string)filemtime(self::file()), -6) : '0';
    }

    /** @return string[] */
    public static function names(): array
    {
        if (self::$names === null) {
            $svg = is_file(self::file()) ? (string)file_get_contents(self::file()) : '';
            preg_match_all('/<symbol id="([a-z0-9-]+)"/', $svg, $m);
            self::$names = $m[1] ?? [];
        }
        return self::$names;
    }
}
