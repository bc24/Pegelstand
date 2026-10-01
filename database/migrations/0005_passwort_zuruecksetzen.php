<?php

declare(strict_types=1);

use Pegelstand\Database\Migration;

defined('PEGELSTAND_ROOT') || exit;

/** Einmal-Links zum Zurücksetzen von Passwörtern. Gespeichert wird nur der Hash des Tokens. */
return new class implements Migration {
    public function name(): string
    {
        return 'Passwort zurücksetzen';
    }

    public function statements(string $prefix): array
    {
        $ende = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        return [
            'CREATE TABLE IF NOT EXISTS `' . $prefix . "password_resets` (
                id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id    INT UNSIGNED NOT NULL,
                token_hash CHAR(64)     NOT NULL,
                expires_at DATETIME     NOT NULL,
                used_at    DATETIME     NULL,
                UNIQUE KEY uq_resets_token (token_hash),
                FOREIGN KEY (user_id) REFERENCES `" . $prefix . "users` (id) ON DELETE CASCADE
            ) $ende",
        ];
    }
};
