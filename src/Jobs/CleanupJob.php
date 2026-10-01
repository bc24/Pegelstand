<?php

declare(strict_types=1);

namespace Pegelstand\Jobs;

use DateTimeImmutable;
use DateTimeZone;
use Pegelstand\Database\Database;

/**
 * Löscht Rohdaten, die älter sind als die Aufbewahrungsfrist der Site, in kleinen Blöcken.
 * Nur Tage, die bereits aggregiert sind (Cursor des Aggregationsjobs), werden angefasst.
 */
final class CleanupJob implements Job
{
    private const BLOCK = 2000;
    private const MAX_BLOCKS = 25;

    public function __construct(private readonly Database $db) {}

    public function name(): string
    {
        return 'cleanup';
    }

    public function interval(): int
    {
        return 3600;
    }

    public function timeout(): int
    {
        return 900;
    }

    public function run(DateTimeImmutable $now): string
    {
        $utc = new DateTimeZone('UTC');
        $geloescht = 0;
        $sites = $this->db->fetchAll('SELECT id, timezone, retention_days FROM ' . $this->db->table('sites'));
        foreach ($sites as $site) {
            $id = is_numeric($site['id']) ? (int) $site['id'] : 0;
            $cursor = $this->db->fetchValue('SELECT value FROM ' . $this->db->table('settings') . ' WHERE name = ?', [AggregationJob::cursorKey($id)]);
            if (!is_string($cursor) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $cursor) !== 1) {
                continue;
            }
            $tage = is_numeric($site['retention_days']) ? max(1, (int) $site['retention_days']) : 730;
            $zeitzone = new DateTimeZone(is_string($site['timezone']) ? $site['timezone'] : 'UTC');
            $aufbewahrung = $now->setTimezone($utc)->modify('-' . $tage . ' days')->format('Y-m-d H:i:s');
            $aggregiertBis = (new DateTimeImmutable($cursor . ' 00:00:00', $zeitzone))->setTimezone($utc)->format('Y-m-d H:i:s');
            $grenze = min($aufbewahrung, $aggregiertBis);
            $geloescht += $this->bereinige($id, $grenze);
        }
        $this->db->run('DELETE FROM ' . $this->db->table('rate_limits') . ' WHERE window_start < ?', [gmdate('Y-m-d H:i:s', $now->getTimestamp() - 7200)]);

        return $geloescht . ' Rohdaten-Zeilen gelöscht';
    }

    private function bereinige(int $siteId, string $grenze): int
    {
        $ereignisse = $this->db->table('events');
        $eigenschaften = $this->db->table('event_props');
        $summe = 0;
        for ($i = 0; $i < self::MAX_BLOCKS; ++$i) {
            $ids = array_map(
                static fn(array $z): int => is_numeric($z['id']) ? (int) $z['id'] : 0,
                $this->db->fetchAll('SELECT id FROM ' . $ereignisse . ' WHERE site_id = ? AND occurred_at < ? LIMIT ' . self::BLOCK, [$siteId, $grenze]),
            );
            if ($ids === []) {
                break;
            }
            $platzhalter = implode(',', array_fill(0, count($ids), '?'));
            $this->db->run('DELETE FROM ' . $eigenschaften . ' WHERE event_id IN (' . $platzhalter . ')', $ids);
            $summe += $this->db->run('DELETE FROM ' . $ereignisse . ' WHERE id IN (' . $platzhalter . ')', $ids)->rowCount();
        }
        for ($i = 0; $i < self::MAX_BLOCKS; ++$i) {
            $anzahl = $this->db->run(
                'DELETE FROM ' . $this->db->table('sessions') . ' WHERE site_id = ? AND last_seen_at < ? LIMIT ' . self::BLOCK,
                [$siteId, $grenze],
            )->rowCount();
            $summe += $anzahl;
            if ($anzahl < self::BLOCK) {
                break;
            }
        }

        return $summe;
    }
}
