<?php

declare(strict_types=1);

namespace Pegelstand\Core;

use RuntimeException;

/**
 * PHP-Sitzung mit gehärteter Konfiguration, die Daten liegen in storage/sessions.
 */
final class NativeSession implements Session
{
    private const NAME = 'pegelstand_session';

    private bool $started = false;

    public function __construct(
        private readonly string $savePath,
        private readonly bool $secure,
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        // Ohne Sitzungs-Cookie gibt es nichts zu lesen. So legt ein anonymer Aufruf keine Sitzungsdatei an.
        if (!$this->started && !isset($_COOKIE[self::NAME])) {
            return $default;
        }
        $this->start();

        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    public function remove(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    public function regenerate(): void
    {
        $this->start();
        session_regenerate_id(true);
    }

    public function destroy(): void
    {
        $this->start();
        $_SESSION = [];
        session_destroy();
        $this->started = false;
    }

    private function start(): void
    {
        if ($this->started) {
            return;
        }
        if (!is_dir($this->savePath) && !@mkdir($this->savePath, 0750, true) && !is_dir($this->savePath)) {
            throw new RuntimeException('Der Sitzungsordner storage/sessions kann nicht angelegt werden.');
        }
        session_save_path($this->savePath);
        session_name(self::NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $this->secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_start();
        $this->started = true;
    }
}
