<?php
declare(strict_types=1);

final class Security
{
    private static ?string $nonce = null;

    public static function nonce(): string
    {
        return self::$nonce ??= rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
    }

    /** Sicherheits-Header inkl. Content-Security-Policy (Nonce für Inline-Skripte). */
    public static function headers(): void
    {
        if (headers_sent()) {
            return;
        }
        $csp = "default-src 'self'; "
             . "script-src 'self' 'nonce-" . self::nonce() . "'; "
             . "style-src 'self' 'unsafe-inline'; "
             . "img-src 'self' data: https:; "
             . "font-src 'self'; "
             . "connect-src 'self'; "
             . "media-src 'self' https:; "
             . "frame-src https://www.tiktok.com https://www.youtube-nocookie.com https://open.spotify.com https://w.soundcloud.com; "
             . "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'";
        header('Content-Security-Policy: ' . $csp);
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
        if (is_https()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    /** Strengere Header für den Admin-Bereich (kein Framing, keine Indexierung, kein Caching). */
    public static function adminHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-" . self::nonce() . "'; style-src 'self' 'unsafe-inline'; "
            . "img-src 'self' data: blob: https:; font-src 'self'; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: same-origin');
        header('X-Robots-Tag: noindex, nofollow');
        header('Cache-Control: no-store');
        if (is_https()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}
