<?php
declare(strict_types=1);

/** Anmeldung, Sitzung und Rollen des Admin-Bereichs. */
final class Auth
{
    public const COOKIE = 'fp_admin';
    private static ?array $user = null;

    public static function hasSessionCookie(): bool
    {
        return isset($_COOKIE[self::COOKIE]);
    }

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name(self::COOKIE);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        // Eigener Sitzungsordner (auf Shared Hosting sonst für andere Konten lesbar)
        $dir = FP_ROOT . '/storage/sessions';
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        if (is_dir($dir) && is_writable($dir)) {
            session_save_path($dir);
            ini_set('session.gc_probability', '1');
            ini_set('session.gc_divisor', '50');
            ini_set('session.gc_maxlifetime', (string)max(43200, (int)cfg('session_timeout', 7200)));
        }
        session_start();

        $now = time();
        $ua = hash('sha256', (string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if (isset($_SESSION['uid'])) {
            $expired = $now - (int)($_SESSION['last'] ?? 0) > (int)cfg('session_timeout', 7200)
                || $now - (int)($_SESSION['started'] ?? 0) > 43200
                || !hash_equals((string)($_SESSION['ua'] ?? ''), $ua);
            if ($expired) {
                self::destroy();
                session_start();
            }
        }
        $_SESSION['last'] = $now;
        $_SESSION['ua'] ??= $ua;
    }

    private static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(self::COOKIE, '', time() - 3600, $p['path'], $p['domain'], (bool)$p['secure'], (bool)$p['httponly']);
        }
        session_destroy();
        self::$user = null;
    }

    public static function user(): ?array
    {
        if (self::$user === null && !empty($_SESSION['uid'])) {
            self::$user = Db::one('SELECT id, username, email, role, totp_enabled, last_login FROM users WHERE id = ?', [$_SESSION['uid']]);
            if (!self::$user) {
                unset($_SESSION['uid']);
            }
        }
        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? '') === 'admin';
    }

    /**
     * @return string 'ok' | '2fa' | 'invalid' | 'locked'
     */
    public static function attempt(string $username, string $password): string
    {
        $ipKey = 'login:ip:' . substr(ip_hash('login'), 0, 32);
        $userKey = 'login:user:' . substr(hash('sha256', strtolower($username)), 0, 32);
        if (RateLimit::blocked($ipKey, 10) || RateLimit::blocked($userKey, 8)) {
            return 'locked';
        }
        $user = Db::one('SELECT * FROM users WHERE username = ?', [$username]);
        // Immer einen Hash prüfen, damit die Antwortzeit nichts über die Existenz des Benutzers verrät
        $hash = $user['password_hash'] ?? '$2y$12$V5b/xeVwYTdC2CIpYCregOwBPjNtgu9T8V9LJNL8VNtBdmQJJ69Bm';
        $valid = password_verify($password, $hash) && $user;
        if (!$valid) {
            RateLimit::hit($ipKey, 10, 900);
            RateLimit::hit($userKey, 8, 900);
            self::log('login_failed', 'user', '', $username, null, $username);
            return 'invalid';
        }
        if ((int)$user['totp_enabled'] === 1) {
            session_regenerate_id(true);
            $_SESSION['pending_2fa'] = (int)$user['id'];
            $_SESSION['pending_at'] = time();
            return '2fa';
        }
        self::login($user);
        RateLimit::clear($ipKey);
        RateLimit::clear($userKey);
        return 'ok';
    }

    public static function verify2fa(string $code): bool
    {
        $uid = (int)($_SESSION['pending_2fa'] ?? 0);
        if ($uid === 0 || time() - (int)($_SESSION['pending_at'] ?? 0) > 300) {
            unset($_SESSION['pending_2fa']);
            return false;
        }
        $ipKey = 'login:2fa:' . substr(ip_hash('login'), 0, 32);
        if (RateLimit::blocked($ipKey, 6)) {
            return false;
        }
        $user = Db::one('SELECT * FROM users WHERE id = ?', [$uid]);
        if ($user && $user['totp_secret'] && Totp::verify((string)$user['totp_secret'], $code)) {
            unset($_SESSION['pending_2fa'], $_SESSION['pending_at']);
            self::login($user);
            RateLimit::clear($ipKey);
            return true;
        }
        RateLimit::hit($ipKey, 6, 900);
        return false;
    }

    public static function pending2fa(): bool
    {
        return !empty($_SESSION['pending_2fa']) && time() - (int)($_SESSION['pending_at'] ?? 0) <= 300;
    }

    private static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$user['id'];
        $_SESSION['started'] = time();
        $_SESSION['csrf'] = bin2hex(random_bytes(24));
        Db::update('users', (int)$user['id'], ['last_login' => now()]);
        self::$user = null;
        self::log('login', 'user', (string)$user['id'], '', (int)$user['id'], (string)$user['username']);
    }

    public static function logout(): void
    {
        self::log('logout', 'user', (string)($_SESSION['uid'] ?? ''), '');
        self::destroy();
    }

    /** Aktivität protokollieren (ohne IP im Klartext). */
    public static function log(string $action, string $entity = '', string $id = '', string $detail = '', ?int $userId = null, ?string $username = null): void
    {
        try {
            $u = self::user();
            Db::insert('activity_log', [
                'user_id'   => $userId ?? ($u['id'] ?? null),
                'username'  => $username ?? ($u['username'] ?? ''),
                'action'    => substr($action, 0, 40),
                'entity'    => substr($entity, 0, 60),
                'entity_id' => substr($id, 0, 40),
                'detail'    => substr($detail, 0, 255),
                'ip_hash'   => substr(ip_hash('log'), 0, 16),
                'created_at' => now(),
            ]);
        } catch (Throwable) {
        }
    }
}
