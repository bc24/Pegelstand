<?php

declare(strict_types=1);

use Pegelstand\Database\Migration;

defined('PEGELSTAND_ROOT') || exit;

/** Abonnements für E-Mail-Berichte (wöchentlich oder monatlich je Benutzer und Website). */
return new class implements Migration {
    public function name(): string
    {
        return 'E-Mail-Berichte';
    }

    public function statements(string $prefix): array
    {
        $ende = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        return [
            'CREATE TABLE IF NOT EXISTS `' . $prefix . "report_subscriptions` (
                id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id      INT UNSIGNED NOT NULL,
                site_id      INT UNSIGNED NOT NULL,
                frequency    ENUM('weekly','monthly') NOT NULL,
                last_sent_at DATETIME NULL,
                UNIQUE KEY uq_report (user_id, site_id, frequency),
                FOREIGN KEY (user_id) REFERENCES `" . $prefix . "users` (id) ON DELETE CASCADE,
                FOREIGN KEY (site_id) REFERENCES `" . $prefix . "sites` (id) ON DELETE CASCADE
            ) $ende",
        ];
    }
};
