<?php
declare(strict_types=1);

/** Key/Value-Einstellungen (Tabelle settings) mit Request-Cache. */
final class Settings
{
    private static ?array $all = null;

    public static function all(): array
    {
        if (self::$all === null) {
            self::$all = [];
            try {
                foreach (Db::all('SELECT skey, svalue FROM settings') as $r) {
                    self::$all[$r['skey']] = (string)$r['svalue'];
                }
            } catch (Throwable) {
                self::$all = [];
            }
        }
        return self::$all;
    }

    public static function get(string $key, string $default = ''): string
    {
        $all = self::all();
        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public static function set(string $key, string $value): void
    {
        Db::upsert('settings', ['skey' => $key, 'svalue' => $value], ['svalue']);
        if (self::$all !== null) {
            self::$all[$key] = $value;
        }
    }

    public static function reset(): void
    {
        self::$all = null;
    }

    /** Definition aller Einstellungen (Gruppen → Felder). */
    public static function schema(): array
    {
        static $schema = null;
        return $schema ??= require FP_ROOT . '/app/settings_schema.php';
    }

    /** Standardwerte aller Einstellungen als flache Liste. */
    public static function defaults(): array
    {
        $out = [];
        foreach (self::schema() as $group) {
            foreach ($group['fields'] as $f) {
                if ($f['bilingual'] ?? false) {
                    $out[$f['key'] . '_de'] = (string)($f['default_de'] ?? $f['default'] ?? '');
                    $out[$f['key'] . '_en'] = (string)($f['default_en'] ?? '');
                } else {
                    $out[$f['key']] = (string)($f['default'] ?? '');
                }
            }
        }
        return $out;
    }
}
