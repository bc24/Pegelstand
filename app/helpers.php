<?php
declare(strict_types=1);

/* ------------------------------------------------------------------ *
 *  Allgemeine Helfer (Front + Admin)
 * ------------------------------------------------------------------ */

function e(mixed $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function cfg(string $key, mixed $default = null): mixed
{
    $c = $GLOBALS['fp_config'] ?? [];
    return $c[$key] ?? $default;
}

/** Rohwert einer Einstellung (Tabelle settings), gecacht pro Request. */
function setting(string $key, string $default = ''): string
{
    return Settings::get($key, $default);
}

/** Einstellung in aktueller Sprache; fällt auf DE bzw. den Basisschlüssel zurück. */
function s(string $key, string $default = ''): string
{
    $lang = Lang::$code;
    $v = Settings::get($key . '_' . $lang, '');
    if ($v === '') {
        $v = Settings::get($key . '_de', '');
    }
    if ($v === '') {
        $v = Settings::get($key, $default);
    }
    return $v;
}

function sb(string $key, bool $default = false): bool
{
    $v = Settings::get($key, $default ? '1' : '0');
    return $v === '1' || $v === 'true' || $v === 'on';
}

/** Lokalisiertes Feld einer Datenbankzeile (title_de / title_en). */
function t(array $row, string $field): string
{
    $lang = Lang::$code;
    $v = (string)($row[$field . '_' . $lang] ?? '');
    if ($v === '' && $lang !== 'de') {
        $v = (string)($row[$field . '_de'] ?? '');
    }
    if ($v === '' && isset($row[$field])) {
        $v = (string)$row[$field];
    }
    return $v;
}

/** UI-Übersetzung aus app/lang.php */
function tr(string $key, array $vars = []): string
{
    $s = Lang::get($key);
    foreach ($vars as $k => $v) {
        $s = str_replace('{' . $k . '}', (string)$v, $s);
    }
    return $s;
}

/** Interne URL mit Basispfad und Sprachpräfix. */
function u(string $path = '/', ?string $lang = null): string
{
    $lang ??= Lang::$code;
    $base = rtrim((string)cfg('base_path', ''), '/');
    $prefix = $lang === 'en' ? '/en' : '';
    $path = '/' . ltrim($path, '/');
    if ($path === '/' && $prefix !== '') {
        return $base . $prefix . '/';
    }
    return $base . $prefix . $path;
}

/** Absolute URL zu einem App-Pfad (ohne Sprachpräfix); site_url enthält bei Unterordner-Installation den Basispfad. */
function abs_url(string $path): string
{
    $base = rtrim((string)(Settings::get('site_url', '') ?: cfg('site_url', '')), '/');
    return $base . '/' . ltrim($path, '/');
}

/** Absolute Seiten-URL inkl. Sprachpräfix (für Canonical, hreflang, Sitemap). */
function page_url(string $path = '/', ?string $lang = null): string
{
    $lang ??= Lang::$code;
    $base = rtrim((string)(Settings::get('site_url', '') ?: cfg('site_url', '')), '/');
    $path = '/' . ltrim($path, '/');
    return $base . ($lang === 'en' ? '/en' : '') . $path;
}

function asset(string $path): string
{
    $file = FP_ROOT . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? substr((string)filemtime($file), -6) : '0';
    return rtrim((string)cfg('base_path', ''), '/') . '/assets/' . ltrim($path, '/') . '?v=' . $v;
}

/** URL eines Bildes: Uploads (/uploads/...), Assets (assets/...) oder externe URL. */
function media_url(?string $path): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^(https?:)?//#', $path) || str_starts_with($path, 'data:')) {
        return $path;
    }
    return rtrim((string)cfg('base_path', ''), '/') . '/' . ltrim($path, '/');
}

/** Inline-SVG-Icon aus dem Sprite. Unbekannte Namen (z. B. Emoji) werden als Text ausgegeben. */
function icon(string $name, string $class = ''): string
{
    static $known = null;
    if ($known === null) {
        $known = array_flip(Icons::names());
    }
    $name = trim($name);
    if ($name === '') {
        return '';
    }
    if (!isset($known[$name])) {
        return '<span class="i i-text ' . e($class) . '" aria-hidden="true">' . e($name) . '</span>';
    }
    $brand = str_starts_with($name, 'brand-') ? ' i-brand' : '';
    return '<svg class="i' . $brand . ($class ? ' ' . e($class) : '') . '" aria-hidden="true" focusable="false"><use href="'
        . e(rtrim((string)cfg('base_path', ''), '/') . '/assets/img/icons.svg?v=' . Icons::version() . '#' . $name) . '"/></svg>';
}

function redirect(string $url, int $code = 302): never
{
    header('Location: ' . $url, true, $code);
    exit;
}

function json_out(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function client_ip(): string
{
    // Standard: nur REMOTE_ADDR (Proxy-Header sind fälschbar). Hinter Cloudflare/Reverse-Proxy
    // 'trust_proxy' => true in config.php setzen, dann wird der weitergereichte Header genutzt.
    if (cfg('trust_proxy', false)) {
        $h = (string)($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
        $ip = trim(explode(',', $h)[0]);
        if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }
    return (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

function ip_hash(string $salt = ''): string
{
    return hash('sha256', client_ip() . '|' . $salt . '|' . cfg('secret', ''));
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');
}

function slugify(string $text): string
{
    $map = ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue', 'ß' => 'ss'];
    $text = strtr($text, $map);
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
    $text = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $text) ?? '');
    return trim($text, '-') ?: 'eintrag';
}

function excerpt(string $html, int $len = 160): string
{
    $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8')) ?? '');
    if (mb_strlen($text) <= $len) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $len - 1), " ,.;:-–—") . '…';
}

function reading_minutes(string $html): int
{
    $words = str_word_count(strip_tags($html), 0, 'äöüÄÖÜß');
    return max(1, (int)ceil($words / 200));
}

function format_date(string|int|null $ts, ?string $lang = null, bool $short = false): string
{
    if ($ts === null || $ts === '') {
        return '';
    }
    $time = is_int($ts) ? $ts : strtotime($ts);
    if (!$time) {
        return '';
    }
    $lang ??= Lang::$code;
    $de = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
    $en = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    $m = (int)date('n', $time) - 1;
    if ($lang === 'en') {
        return $short ? date('M j, Y', $time) : $en[$m] . ' ' . date('j, Y', $time);
    }
    return $short ? date('d.m.Y', $time) : date('j', $time) . '. ' . $de[$m] . ' ' . date('Y', $time);
}

function random_token(int $bytes = 16): string
{
    return bin2hex(random_bytes($bytes));
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

/** Zeilenweise Liste aus einem Textfeld. */
function lines(string $text): array
{
    $out = [];
    foreach (preg_split('/\R/u', $text) ?: [] as $l) {
        $l = trim($l);
        if ($l !== '') {
            $out[] = $l;
        }
    }
    return $out;
}

/** Kleines Zufalls-Passwort für den Installer. */
function random_password(int $len = 16): string
{
    $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $out = '';
    for ($i = 0; $i < $len; $i++) {
        $out .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $out;
}

function hex_to_rgb(string $hex): array
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
        return [249, 115, 22];
    }
    return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
}

function valid_hex(string $hex): bool
{
    return (bool)preg_match('/^#[0-9a-fA-F]{6}$/', $hex);
}
