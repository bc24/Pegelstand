<?php

declare(strict_types=1);

use Pegelstand\Database\Migration;

defined('PEGELSTAND_ROOT') || exit;

/** Ziele: eine Seite oder ein Ereignis, dessen Erreichen als Erfolg zählt. */
return new class implements Migration {
    public function name(): string
    {
        return 'Ziele';
    }

    public function statements(string $prefix): array
    {
        $ende = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        return [
            'CREATE TABLE IF NOT EXISTS `' . $prefix . "goals` (
                id      INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                site_id INT UNSIGNED NOT NULL,
                name    VARCHAR(120) NOT NULL,
                kind    ENUM('page','event') NOT NULL,
                target  VARCHAR(1024) NOT NULL,
                KEY ix_goals_site (site_id),
                FOREIGN KEY (site_id) REFERENCES `" . $prefix . "sites` (id) ON DELETE CASCADE
            ) $ende",
        ];
    }
};
