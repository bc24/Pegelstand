<?php
declare(strict_types=1);

/** Zustandsloses Formular-Token (Zeitstempel + HMAC) gegen Bots; ohne Session/Cookie. */
final class FormToken
{
    public static function issue(string $scope): string
    {
        $ts = (string)time();
        return $ts . '.' . hash_hmac('sha256', $scope . '|' . $ts, (string)cfg('secret', ''));
    }

    public static function verify(string $token, string $scope, int $minAge = 3, int $maxAge = 7200): bool
    {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2 || !ctype_digit($parts[0])) {
            return false;
        }
        $expected = hash_hmac('sha256', $scope . '|' . $parts[0], (string)cfg('secret', ''));
        if (!hash_equals($expected, $parts[1])) {
            return false;
        }
        $age = time() - (int)$parts[0];
        return $age >= $minAge && $age <= $maxAge;
    }
}
