<?php
declare(strict_types=1);

/** Sitzungsbasiertes CSRF-Token für den Admin-Bereich. */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(24));
        }
        return $_SESSION['csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    public static function valid(): bool
    {
        $sent = (string)($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        return $sent !== '' && hash_equals(self::token(), $sent);
    }

    public static function check(): void
    {
        if (!self::valid()) {
            http_response_code(419);
            exit('Sitzung abgelaufen oder ungültiges Formular. Bitte Seite neu laden.');
        }
    }
}
