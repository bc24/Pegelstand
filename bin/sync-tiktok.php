<?php
declare(strict_types=1);
/**
 * Lädt die neuesten TikTok-Videos des Profils (Einstellungen → Musik) in die Song-Liste.
 * Für den täglichen Abgleich per Cron, z. B.:
 *   17 6 * * *  cd /var/www/frank-panzer.de && sudo -u www-data php bin/sync-tiktok.php >/dev/null 2>&1
 * Als Webserver-Benutzer ausführen, damit uploads/tiktok/ diesem gehört.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/bootstrap.php';
$user = trim(setting('tiktok_user', 'dj.frankus'));
$res = TikTok::sync($user, max(1, (int)setting('tiktok_limit', '10')));
if ($res['error'] !== '') {
    fwrite(STDERR, "TikTok-Abgleich fehlgeschlagen: {$res['error']}\n");
    exit(1);
}
echo "TikTok @$user: {$res['added']} neu, {$res['updated']} aktualisiert, {$res['removed']} entfernt.\n";
