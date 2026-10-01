<?php

declare(strict_types=1);

use Pegelstand\Database\Migration;

defined('PEGELSTAND_ROOT') || exit;

/** API-Schlüssel für die lesende REST-Schnittstelle. Gespeichert wird nur der Hash. */
return new class implements Migration {
    public function name(): string
    {
        return 'API-Schlüssel';
    }

    public function statements(string $prefix): array
    {
        $ende = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        return [
            'CREATE TABLE IF NOT EXISTS `' . $prefix . "api_keys` (
                id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id      INT UNSIGNED NOT NULL,
                name         VARCHAR(120) NOT NULL,
                key_prefix   CHAR(8)      NOT NULL,
                key_hash     CHAR(64)     NOT NULL,
                created_at   DATETIME     NOT NULL,
                last_used_at DATETIME     NULL,
                UNIQUE KEY uq_api_key_hash (key_hash),
                FOREIGN KEY (user_id) REFERENCES `" . $prefix . "users` (id) ON DELETE CASCADE
            ) $ende",
        ];
    }
};
