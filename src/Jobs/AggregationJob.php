<?php

declare(strict_types=1);

namespace Pegelstand\Jobs;

use DateTimeImmutable;
use DateTimeZone;
use Pegelstand\Database\Database;
use Pegelstand\Stats\Aggregator;

/**
 * Berechnet Tages- und Stundenwerte neu. Pro Site merkt sich der Job, ab welchem Tag alles endgültig ist
 * (`agg_cursor_<id>` in den Einstellungen). Ab dort bis heute wird neu berechnet, höchstens 31 Tage pro Lauf.
 */
final class AggregationJob implements Job
{
    public const MAX_DAYS_PER_RUN = 31;

    public function __construct(
        private readonly Database $db,
        private readonly Aggregator $aggregator,
    ) {}

    public function name(): string
    {
        return 'aggregate';
    }

    public function interval(): int
    {
        return 300;
    }

    public function timeout(): int
    {
        return 600;
    }

    public function run(DateTimeImmutable $now): string
    {
        $sites = $this->db->fetchAll('SELECT id, timezone FROM ' . $this->db->table('sites') . ' ORDER BY id');
        $tage = 0;
        foreach ($sites as $site) {
            $id = is_numeric($site['id']) ? (int) $site['id'] : 0;
            $zeitzone = is_string($site['timezone']) ? $site['timezone'] : 'UTC';
            $tage += $this->site($id, $zeitzone, $now);
        }

        return count($sites) . ' Sites, ' . $tage . ' Tage';
    }

    private function site(int $id, string $zeitzone, DateTimeImmutable $now): int
    {
        $zone = new DateTimeZone($zeitzone);
        $heute = $now->setTimezone($zone)->format('Y-m-d');
        $gestern = (new DateTimeImmutable($heute, $zone))->modify('-1 day')->format('Y-m-d');
        $schluessel = self::cursorKey($id);

        $cursor = $this->db->fetchValue('SELECT value FROM ' . $this->db->table('settings') . ' WHERE name = ?', [$schluessel]);
        if (!is_string($cursor) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $cursor) !== 1) {
            $roh = $this->db->fetchValue('SELECT MIN(started_at) FROM ' . $this->db->table('sessions') . ' WHERE site_id = ?', [$id]);
            if (!is_string($roh)) {
                return 0; // Noch keine Daten, nichts zu tun.
            }
            $cursor = (new DateTimeImmutable($roh, new DateTimeZone('UTC')))->setTimezone($zone)->format('Y-m-d');
        }
        if ($cursor > $gestern) {
            $cursor = $gestern;
        }

        $tag = $cursor;
        $anzahl = 0;
        $naechster = $cursor;
        while ($tag <= $heute && $anzahl < self::MAX_DAYS_PER_RUN) {
            $this->aggregator->aggregateDay($id, $zeitzone, $tag);
            ++$anzahl;
            $tag = (new DateTimeImmutable($tag, $zone))->modify('+1 day')->format('Y-m-d');
            // Tage vor "gestern" sind abgeschlossen und werden nicht noch einmal berechnet.
            $naechster = min($tag, $gestern);
        }
        $this->db->run(
            'INSERT INTO ' . $this->db->table('settings') . ' (name, value, updated_at) VALUES (?, ?, ?)'
            . ' ON DUPLICATE KEY UPDATE value = ?, updated_at = ?',
            [$schluessel, $naechster, gmdate('Y-m-d H:i:s', $now->getTimestamp()), $naechster, gmdate('Y-m-d H:i:s', $now->getTimestamp())],
        );

        return $anzahl;
    }

    public static function cursorKey(int $siteId): string
    {
        return 'agg_cursor_' . $siteId;
    }
}
