<?php

declare(strict_types=1);

namespace Pegelstand\Ingest;

use DateTimeImmutable;
use Pegelstand\Database\Database;

/**
 * Begrenzt Anfragen pro Minute über gesalzene Hashes statt Klartext-Adressen.
 * Die Zeilen leben nur kurz und werden nebenbei gelöscht.
 */
final class RateLimiter
{
    public function __construct(private readonly Database $db) {}

    /** Zählt einen Treffer und sagt, ob das Limit überschritten ist. */
    public function exceeded(string $bucket, string $keyHash, int $limit, DateTimeImmutable $now): bool
    {
        $fenster = gmdate('Y-m-d H:i:00', $now->getTimestamp());
        $tabelle = $this->db->table('rate_limits');
        $this->db->run(
            'INSERT INTO ' . $tabelle . ' (bucket, key_hash, window_start, hits) VALUES (?, ?, ?, 1) '
            . 'ON DUPLICATE KEY UPDATE hits = IF(window_start = ?, hits + 1, 1), window_start = ?',
            [$bucket, $keyHash, $fenster, $fenster, $fenster],
        );
        $treffer = $this->db->fetchInt('SELECT hits FROM ' . $tabelle . ' WHERE bucket = ? AND key_hash = ?', [$bucket, $keyHash]);

        if (random_int(1, 100) === 1) {
            $this->db->run('DELETE FROM ' . $tabelle . ' WHERE window_start < ?', [gmdate('Y-m-d H:i:s', $now->getTimestamp() - 600)]);
        }

        return $treffer > $limit;
    }
}
