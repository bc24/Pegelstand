<?php
declare(strict_types=1);

/** Anonymer Aufrufzähler: nur (Tag, Pfad) → Anzahl. Keine IP, keine Cookies, keine Wiedererkennung. */
final class Visits
{
    /** Zählt der Aufruf? (kein Bot, kein Do-Not-Track, kein Admin-Besuch) */
    public static function countable(): bool
    {
        if (!sb('count_visits', true) || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET' || Auth::hasSessionCookie()) {
            return false;
        }
        $ua = strtolower((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if ($ua === '' || preg_match('/bot|crawl|spider|slurp|curl|wget|python|monitor|preview|facebookexternalhit|headless|lighthouse|uptime|check/', $ua)) {
            return false;
        }
        return ($_SERVER['HTTP_DNT'] ?? '') !== '1' && ($_SERVER['HTTP_SEC_GPC'] ?? '') !== '1';
    }

    public static function track(string $path): void
    {
        if (!self::countable()) {
            return;
        }
        try {
            Db::q('INSERT INTO visits (day, path, hits) VALUES (CURDATE(), ?, 1) ON DUPLICATE KEY UPDATE hits = hits + 1', [substr($path, 0, 190)]);
        } catch (Throwable) {
            // Zähler darf die Seite nie stören
        }
    }

    /** @return array<string,int> Tag → Aufrufe für die letzten $days Tage */
    public static function perDay(int $days = 14): array
    {
        $rows = Db::all('SELECT day, SUM(hits) AS h FROM visits WHERE day >= DATE_SUB(CURDATE(), INTERVAL ? DAY) GROUP BY day', [$days - 1]);
        $map = [];
        foreach ($rows as $r) {
            $map[$r['day']] = (int)$r['h'];
        }
        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i day"));
            $out[$d] = $map[$d] ?? 0;
        }
        return $out;
    }

    public static function topPages(int $days = 30, int $limit = 6): array
    {
        return Db::all(
            'SELECT path, SUM(hits) AS h FROM visits WHERE day >= DATE_SUB(CURDATE(), INTERVAL ? DAY) GROUP BY path ORDER BY h DESC LIMIT ' . (int)$limit,
            [$days - 1]
        );
    }
}
