<?php

declare(strict_types=1);

use Pegelstand\Database\Migration;

defined('PEGELSTAND_ROOT') || exit;

/**
 * Aggregate (Stunde, Tag, Aufschlüsselungen, Eigenschaften) und Hintergrundjobs.
 */
return new class implements Migration {
    public function name(): string
    {
        return 'Aggregate und Hintergrundjobs';
    }

    public function statements(string $prefix): array
    {
        $tabelle = static fn(string $name): string => '`' . $prefix . $name . '`';
        $ende = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        $kennzahlen = 'visitors INT UNSIGNED NOT NULL, sessions INT UNSIGNED NOT NULL, pageviews INT UNSIGNED NOT NULL,
                bounces INT UNSIGNED NOT NULL, duration_sum BIGINT UNSIGNED NOT NULL, events INT UNSIGNED NOT NULL';

        return [
            'CREATE TABLE IF NOT EXISTS ' . $tabelle('job_runs') . " (
                job          VARCHAR(60)  NOT NULL PRIMARY KEY,
                locked_until DATETIME     NULL,
                started_at   DATETIME     NULL,
                finished_at  DATETIME     NULL,
                status       VARCHAR(20)  NOT NULL DEFAULT 'idle',
                message      VARCHAR(255) NULL
            ) $ende",

            'CREATE TABLE IF NOT EXISTS ' . $tabelle('agg_hourly') . " (
                site_id INT UNSIGNED NOT NULL,
                hour    DATETIME     NOT NULL,
                $kennzahlen,
                PRIMARY KEY (site_id, hour)
            ) $ende",

            'CREATE TABLE IF NOT EXISTS ' . $tabelle('agg_daily') . " (
                site_id INT UNSIGNED NOT NULL,
                day     DATE         NOT NULL,
                $kennzahlen,
                PRIMARY KEY (site_id, day)
            ) $ende",

            'CREATE TABLE IF NOT EXISTS ' . $tabelle('agg_daily_dim') . " (
                site_id      INT UNSIGNED     NOT NULL,
                day          DATE             NOT NULL,
                dim          TINYINT UNSIGNED NOT NULL,
                value_id     INT UNSIGNED     NOT NULL,
                visitors     INT UNSIGNED     NOT NULL,
                sessions     INT UNSIGNED     NOT NULL,
                hits         INT UNSIGNED     NOT NULL,
                bounces      INT UNSIGNED     NOT NULL,
                duration_sum BIGINT UNSIGNED  NOT NULL,
                PRIMARY KEY (site_id, day, dim, value_id),
                KEY ix_agg_dim_lookup (site_id, dim, day)
            ) $ende",

            'CREATE TABLE IF NOT EXISTS ' . $tabelle('agg_daily_prop') . " (
                site_id  INT UNSIGNED NOT NULL,
                day      DATE         NOT NULL,
                name_id  INT UNSIGNED NOT NULL,
                key_id   INT UNSIGNED NOT NULL,
                value_id INT UNSIGNED NOT NULL,
                visitors INT UNSIGNED NOT NULL,
                events   INT UNSIGNED NOT NULL,
                PRIMARY KEY (site_id, day, name_id, key_id, value_id)
            ) $ende",
        ];
    }
};
