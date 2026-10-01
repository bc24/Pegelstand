<?php

declare(strict_types=1);

namespace Pegelstand\Install;

/**
 * Passwort-Hashing: Argon2id, falls verfügbar, sonst bcrypt (der PHP-Standard).
 */
final class PasswordHasher
{
    public static function hash(string $passwort): string
    {
        return password_hash($passwort, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT);
    }
}
