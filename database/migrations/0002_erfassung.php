<?php

declare(strict_types=1);

use Pegelstand\Database\Migration;

defined('PEGELSTAND_ROOT') || exit;

/**
 * Erfassung: Tages-Salt, Ratenbegrenzung, Wörterbücher, Sitzungen, Ereignisse, ausgeschlossene Adressen.
 */
return new class implements Migration {
    public function name(): string
    {
        return 'Erfassung: Salt, Wörterbücher, Sitzungen, Ereignisse';
    }

    public function statements(string $prefix): array
    {
        $tabelle = static fn(string $name): string => '`' . $prefix . $name . '`';
        $ende = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        $sql = [
            'CREATE TABLE IF NOT EXISTS ' . $tabelle('daily_salts') . " (
                salt_date  DATE       NOT NULL PRIMARY KEY,
                salt       BINARY(32) NOT NULL,
                created_at DATETIME   NOT NULL
            ) $ende",

            'CREATE TABLE IF NOT EXISTS ' . $tabelle('rate_limits') . " (
                bucket       VARCHAR(30)  NOT NULL,
                key_hash     BINARY(16)   NOT NULL,
                window_start DATETIME     NOT NULL,
                hits         INT UNSIGNED NOT NULL,
                PRIMARY KEY (bucket, key_hash),
                KEY ix_rate_limits_window (window_start)
            ) $ende",
        ];

        $woerterbuecher = [
            'dict_path' => 1024,
            'dict_referrer' => 255,
            'dict_utm' => 255,
            'dict_browser' => 64,
            'dict_os' => 64,
            'dict_event_name' => 120,
            'dict_prop_key' => 64,
            'dict_prop_value' => 255,
        ];
        foreach ($woerterbuecher as $name => $laenge) {
            $sql[] = 'CREATE TABLE IF NOT EXISTS ' . $tabelle($name) . " (
                id         INT UNSIGNED  NOT NULL AUTO_INCREMENT PRIMARY KEY,
                value_hash BINARY(16)    NOT NULL,
                value      VARCHAR($laenge) NOT NULL,
                UNIQUE KEY uq_{$name} (value_hash)
            ) $ende";
        }

        $sql[] = 'CREATE TABLE IF NOT EXISTS ' . $tabelle('sessions') . " (
                id              BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT PRIMARY KEY,
                site_id         INT UNSIGNED      NOT NULL,
                visitor_hash    BINARY(16)        NOT NULL,
                started_at      DATETIME          NOT NULL,
                last_seen_at    DATETIME          NOT NULL,
                entry_path_id   INT UNSIGNED      NOT NULL,
                exit_path_id    INT UNSIGNED      NOT NULL,
                pageviews       SMALLINT UNSIGNED NOT NULL DEFAULT 1,
                custom_events   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                referrer_id     INT UNSIGNED      NULL,
                utm_source_id   INT UNSIGNED      NULL,
                utm_medium_id   INT UNSIGNED      NULL,
                utm_campaign_id INT UNSIGNED      NULL,
                utm_term_id     INT UNSIGNED      NULL,
                utm_content_id  INT UNSIGNED      NULL,
                country         CHAR(2)           NULL,
                device          TINYINT UNSIGNED  NOT NULL DEFAULT 0,
                browser_id      SMALLINT UNSIGNED NULL,
                os_id           SMALLINT UNSIGNED NULL,
                KEY ix_sessions_lookup (site_id, visitor_hash, last_seen_at),
                KEY ix_sessions_time (site_id, started_at),
                KEY ix_sessions_live (site_id, last_seen_at)
            ) $ende";

        $sql[] = 'CREATE TABLE IF NOT EXISTS ' . $tabelle('events') . " (
                id          BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT PRIMARY KEY,
                site_id     INT UNSIGNED     NOT NULL,
                session_id  BIGINT UNSIGNED  NOT NULL,
                occurred_at DATETIME         NOT NULL,
                kind        TINYINT UNSIGNED NOT NULL,
                path_id     INT UNSIGNED     NOT NULL,
                name_id     INT UNSIGNED     NULL,
                KEY ix_events_time (site_id, occurred_at),
                KEY ix_events_path (site_id, path_id, occurred_at),
                KEY ix_events_name (site_id, name_id, occurred_at),
                KEY ix_events_session (session_id)
            ) $ende";

        $sql[] = 'CREATE TABLE IF NOT EXISTS ' . $tabelle('event_props') . " (
                event_id BIGINT UNSIGNED NOT NULL,
                key_id   INT UNSIGNED    NOT NULL,
                value_id INT UNSIGNED    NOT NULL,
                PRIMARY KEY (event_id, key_id)
            ) $ende";

        $sql[] = 'CREATE TABLE IF NOT EXISTS ' . $tabelle('site_ip_exclusions') . " (
                id       INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                site_id  INT UNSIGNED NOT NULL,
                ip_range VARCHAR(64)  NOT NULL,
                label    VARCHAR(120) NULL,
                KEY ix_exclusions_site (site_id),
                FOREIGN KEY (site_id) REFERENCES " . $tabelle('sites') . " (id) ON DELETE CASCADE
            ) $ende";

        return $sql;
    }
};
