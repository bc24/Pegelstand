<?php

declare(strict_types=1);

namespace Pegelstand\Stats;

use Pegelstand\Database\Database;

/**
 * Zahlen aus den fertig verdichteten Tageswerten. Sehr schnell, aber nur ungefiltert und mit Tagesauflösung.
 *
 * @phpstan-import-type Totals from StatsSource
 * @phpstan-import-type Row from StatsSource
 */
final class AggregateStats implements StatsSource
{
    public function __construct(private readonly Database $db) {}

    public function totals(int $siteId, Period $period): array
    {
        $z = $this->db->fetchAll(
            'SELECT COALESCE(SUM(visitors), 0) AS besucher, COALESCE(SUM(pageviews), 0) AS aufrufe, COALESCE(SUM(sessions), 0) AS besuche,'
            . ' COALESCE(SUM(bounces), 0) AS bounces, COALESCE(SUM(duration_sum), 0) AS dauer FROM ' . $this->db->table('agg_daily')
            . ' WHERE site_id = ? AND day BETWEEN ? AND ?',
            [$siteId, $period->firstDay, $period->lastDay],
        )[0];

        return self::summe($z);
    }

    public function series(int $siteId, array $buckets): array
    {
        if ($buckets === []) {
            return [];
        }
        $zeilen = $this->db->fetchAll(
            'SELECT day, visitors AS besucher, pageviews AS aufrufe, sessions AS besuche, bounces, duration_sum AS dauer FROM ' . $this->db->table('agg_daily')
            . ' WHERE site_id = ? AND day BETWEEN ? AND ?',
            [$siteId, $buckets[0]->day, $buckets[count($buckets) - 1]->day],
        );
        $nachTag = [];
        foreach ($zeilen as $z) {
            if (is_string($z['day'])) {
                $nachTag[$z['day']] = self::summe($z);
            }
        }

        return array_map(static fn(Bucket $b): array => $nachTag[$b->day] ?? ['besucher' => 0, 'aufrufe' => 0, 'besuche' => 0, 'bounces' => 0, 'dauer' => 0], $buckets);
    }

    public function dimension(int $siteId, int $dim, Period $period, int $limit, ?array $keys = null): array
    {
        if ($keys !== null && $keys === []) {
            return [];
        }
        $in = $keys === null ? '' : ' AND value_id IN (' . implode(',', array_map('intval', $keys)) . ')';
        return RawStats::zeilen($this->db->fetchAll(
            'SELECT value_id AS k, SUM(visitors) AS besucher, SUM(hits) AS aufrufe, SUM(sessions) AS besuche, SUM(bounces) AS bounces FROM '
            . $this->db->table('agg_daily_dim') . ' WHERE site_id = ? AND dim = ? AND day BETWEEN ? AND ?' . $in . ' GROUP BY value_id ORDER BY besucher DESC, k LIMIT ' . $limit,
            [$siteId, $dim, $period->firstDay, $period->lastDay],
        ));
    }

    public function pageViews(int $siteId, Period $period, array $pathIds): array
    {
        if ($pathIds === []) {
            return [];
        }
        $zeilen = $this->db->fetchAll(
            'SELECT value_id AS k, SUM(hits) AS n FROM ' . $this->db->table('agg_daily_dim') . ' WHERE site_id = ? AND dim = ? AND day BETWEEN ? AND ?'
            . ' AND value_id IN (' . implode(',', array_map('intval', $pathIds)) . ') GROUP BY value_id',
            [$siteId, Dimension::PAGE, $period->firstDay, $period->lastDay],
        );
        $ergebnis = [];
        foreach ($zeilen as $z) {
            $ergebnis[is_numeric($z['k']) ? (int) $z['k'] : 0] = is_numeric($z['n']) ? (int) $z['n'] : 0;
        }

        return $ergebnis;
    }

    public function eventProps(int $siteId, Period $period): array
    {
        $zeilen = $this->db->fetchAll(
            'SELECT name_id AS n, key_id AS k, value_id AS v, SUM(events) AS anzahl FROM ' . $this->db->table('agg_daily_prop')
            . ' WHERE site_id = ? AND day BETWEEN ? AND ? GROUP BY name_id, key_id, value_id',
            [$siteId, $period->firstDay, $period->lastDay],
        );
        $ergebnis = [];
        foreach ($zeilen as $z) {
            $ergebnis[is_numeric($z['n']) ? (int) $z['n'] : 0][] = [
                'key_id' => is_numeric($z['k']) ? (int) $z['k'] : 0,
                'value_id' => is_numeric($z['v']) ? (int) $z['v'] : 0,
                'anzahl' => is_numeric($z['anzahl']) ? (int) $z['anzahl'] : 0,
            ];
        }

        return $ergebnis;
    }

    /**
     * @param array<string, mixed> $z
     * @return Totals
     */
    private static function summe(array $z): array
    {
        $n = static fn(mixed $w): int => is_numeric($w) ? (int) $w : 0;

        return ['besucher' => $n($z['besucher']), 'aufrufe' => $n($z['aufrufe']), 'besuche' => $n($z['besuche']), 'bounces' => $n($z['bounces']), 'dauer' => $n($z['dauer'])];
    }
}
