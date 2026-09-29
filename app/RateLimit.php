<?php
declare(strict_types=1);

/** Häufigkeitsbegrenzung in der Datenbank (Schlüssel enthalten nur Hashes, keine Klartext-IPs). */
final class RateLimit
{
    /** Zählt einen Versuch; true = noch erlaubt. */
    public static function hit(string $key, int $max, int $windowSeconds): bool
    {
        $key = substr($key, 0, 140);
        Db::q('DELETE FROM rate_limits WHERE rkey = ? AND expires_at < NOW()', [$key]);
        Db::q(
            'INSERT INTO rate_limits (rkey, hits, expires_at) VALUES (?, 1, DATE_ADD(NOW(), INTERVAL ? SECOND)) ON DUPLICATE KEY UPDATE hits = hits + 1',
            [$key, $windowSeconds]
        );
        if (random_int(1, 100) === 1) {
            Db::q('DELETE FROM rate_limits WHERE expires_at < NOW()');
        }
        return (int)Db::val('SELECT hits FROM rate_limits WHERE rkey = ?', [$key]) <= $max;
    }

    /** Ohne zu zählen prüfen, ob das Limit schon erreicht ist. */
    public static function blocked(string $key, int $max): bool
    {
        return (int)Db::val('SELECT hits FROM rate_limits WHERE rkey = ? AND expires_at >= NOW()', [substr($key, 0, 140)]) >= $max;
    }

    public static function clear(string $key): void
    {
        Db::q('DELETE FROM rate_limits WHERE rkey = ?', [substr($key, 0, 140)]);
    }
}
