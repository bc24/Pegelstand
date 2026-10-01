<?php

declare(strict_types=1);

use Pegelstand\Database\Migration;

defined('PEGELSTAND_ROOT') || exit;

/**
 * Verwaltungstabellen: Einstellungen, Benutzer, Sites und ihre Zuordnung.
 * Die Tabelle "migrations" legt der Migrator selbst an.
 */
return new class implements Migration {
    public function name(): string
    {
        return 'Verwaltung: Einstellungen, Benutzer, Sites';
    }

    public function statements(string $prefix): array
    {
        $tabelle = static fn(string $name): string => '`' . $prefix . $name . '`';
        $ende = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        return [
            'CREATE TABLE IF NOT EXISTS ' . $tabelle('settings') . " (
                name       VARCHAR(100) NOT NULL PRIMARY KEY,
                value      MEDIUMTEXT   NOT NULL,
                updated_at DATETIME     NOT NULL
            ) $ende",

            'CREATE TABLE IF NOT EXISTS ' . $tabelle('users') . " (
                id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                email           VARCHAR(190) NOT NULL,
                name            VARCHAR(120) NOT NULL,
                password_hash   VARCHAR(255) NOT NULL,
                role            ENUM('admin','viewer') NOT NULL DEFAULT 'viewer',
                totp_secret     VARBINARY(255) NULL,
                totp_enabled_at DATETIME NULL,
                created_at      DATETIME NOT NULL,
                last_login_at   DATETIME NULL,
                disabled_at     DATETIME NULL,
                UNIQUE KEY uq_users_email (email)
            ) $ende",

            'CREATE TABLE IF NOT EXISTS ' . $tabelle('sites') . " (
                id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                public_id      CHAR(16)     NOT NULL,
                name           VARCHAR(120) NOT NULL,
                domain         VARCHAR(190) NOT NULL,
                allowed_hosts  TEXT NULL,
                timezone       VARCHAR(64)  NOT NULL DEFAULT 'Europe/Berlin',
                retention_days SMALLINT UNSIGNED NOT NULL DEFAULT 730,
                respect_dnt    TINYINT(1) NOT NULL DEFAULT 0,
                respect_gpc    TINYINT(1) NOT NULL DEFAULT 0,
                public_token   CHAR(32) NULL,
                created_at     DATETIME NOT NULL,
                UNIQUE KEY uq_sites_public_id (public_id),
                UNIQUE KEY uq_sites_public_token (public_token)
            ) $ende",

            'CREATE TABLE IF NOT EXISTS ' . $tabelle('site_users') . " (
                site_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                PRIMARY KEY (site_id, user_id),
                CONSTRAINT `{$prefix}fk_site_users_site` FOREIGN KEY (site_id) REFERENCES " . $tabelle('sites') . " (id) ON DELETE CASCADE,
                CONSTRAINT `{$prefix}fk_site_users_user` FOREIGN KEY (user_id) REFERENCES " . $tabelle('users') . " (id) ON DELETE CASCADE
            ) $ende",
        ];
    }
};
