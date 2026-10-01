<?php

declare(strict_types=1);

namespace Pegelstand\Stats;

use Pegelstand\Database\Database;

/**
 * Zahlen direkt aus den Rohdaten. Nötig für Filter und für kurze Zeiträume (Heute, Gestern), die in Echtzeit stimmen sollen.
 *
 * @phpstan-import-type Totals from StatsSource
 * @phpstan-import-type Row from StatsSource
 */
final class RawStats implements StatsSource
{
    private const SESSION_DIMS = [
        Dimension::ENTRY_PAGE => 's.entry_path_id',
        Dimension::EXIT_PAGE => 's.exit_path_id',
        Dimension::REFERRER => 'IF(s.referrer_id IS NOT NULL, s.referrer_id, IF(s.utm_source_id IS NULL, 0, NULL))',
        Dimension::UTM_SOURCE => 's.utm_source_id',
        Dimension::UTM_MEDIUM => 's.utm_medium_id',
        Dimension::UTM_CAMPAIGN => 's.utm_campaign_id',
        Dimension::UTM_TERM => 's.utm_term_id',
        Dimension::UTM_CONTENT => 's.utm_content_id',
        Dimension::COUNTRY => '(ASCII(SUBSTRING(s.country, 1, 1)) * 256 + ASCII(SUBSTRING(s.country, 2, 1)))',
        Dimension::DEVICE => 'NULLIF(s.device, 0)',
        Dimension::BROWSER => 's.browser_id',
        Dimension::OS => 's.os_id',
    ];

    public function __construct(
        private readonly Database $db,
        private readonly SessionFilter $filter,
    ) {}

    public function totals(int $siteId, Period $period): array
    {
        $sitzungen = $this->db->fetchAll(
            'SELECT COUNT(DISTINCT s.visitor_hash) AS besucher, COUNT(*) AS besuche, COALESCE(SUM(s.pageviews = 1 AND s.custom_events = 0), 0) AS bounces,'
            . ' COALESCE(SUM(TIMESTAMPDIFF(SECOND, s.started_at, s.last_seen_at)), 0) AS dauer FROM ' . $this->db->table('sessions')
            . ' s WHERE s.site_id = ? AND s.started_at >= ? AND s.started_at < ?' . $this->filter->sql,
            [$siteId, $period->startUtc, $period->endUtc, ...$this->filter->params],
        )[0];
        $aufrufe = $this->db->fetchInt(
            'SELECT COUNT(*) FROM ' . $this->db->table('events') . ' e JOIN ' . $this->db->table('sessions')
            . ' s ON s.id = e.session_id WHERE e.site_id = ? AND e.kind = 1 AND e.occurred_at >= ? AND e.occurred_at < ?' . $this->filter->sql,
            [$siteId, $period->startUtc, $period->endUtc, ...$this->filter->params],
        );

        return [
            'besucher' => self::zahl($sitzungen['besucher']),
            'aufrufe' => $aufrufe,
            'besuche' => self::zahl($sitzungen['besuche']),
            'bounces' => self::zahl($sitzungen['bounces']),
            'dauer' => self::zahl($sitzungen['dauer']),
        ];
    }

    public function series(int $siteId, array $buckets): array
    {
        if ($buckets === []) {
            return [];
        }
        $grenzen = array_map(static fn(Bucket $b): int => (int) strtotime($b->startUtc . ' UTC'), $buckets);
        $platzhalter = implode(',', array_fill(0, count($grenzen), '?'));
        $ende = $buckets[count($buckets) - 1]->endUtc;
        $start = $buckets[0]->startUtc;

        $leer = ['besucher' => 0, 'aufrufe' => 0, 'besuche' => 0, 'bounces' => 0, 'dauer' => 0];
        $reihe = array_fill(0, count($buckets), $leer);

        $zeilen = $this->db->fetchAll(
            'SELECT INTERVAL(UNIX_TIMESTAMP(s.started_at), ' . $platzhalter . ') AS b, COUNT(DISTINCT s.visitor_hash) AS besucher, COUNT(*) AS besuche,'
            . ' SUM(s.pageviews = 1 AND s.custom_events = 0) AS bounces, SUM(TIMESTAMPDIFF(SECOND, s.started_at, s.last_seen_at)) AS dauer FROM '
            . $this->db->table('sessions') . ' s WHERE s.site_id = ? AND s.started_at >= ? AND s.started_at < ?' . $this->filter->sql . ' GROUP BY b',
            [...$grenzen, $siteId, $start, $ende, ...$this->filter->params],
        );
        foreach ($zeilen as $z) {
            $i = self::zahl($z['b']) - 1;
            if (isset($reihe[$i])) {
                $reihe[$i]['besucher'] = self::zahl($z['besucher']);
                $reihe[$i]['besuche'] = self::zahl($z['besuche']);
                $reihe[$i]['bounces'] = self::zahl($z['bounces']);
                $reihe[$i]['dauer'] = self::zahl($z['dauer']);
            }
        }
        $zeilen = $this->db->fetchAll(
            'SELECT INTERVAL(UNIX_TIMESTAMP(e.occurred_at), ' . $platzhalter . ') AS b, COUNT(*) AS aufrufe FROM ' . $this->db->table('events') . ' e JOIN '
            . $this->db->table('sessions') . ' s ON s.id = e.session_id WHERE e.site_id = ? AND e.kind = 1 AND e.occurred_at >= ? AND e.occurred_at < ?'
            . $this->filter->sql . ' GROUP BY b',
            [...$grenzen, $siteId, $start, $ende, ...$this->filter->params],
        );
        foreach ($zeilen as $z) {
            $i = self::zahl($z['b']) - 1;
            if (isset($reihe[$i])) {
                $reihe[$i]['aufrufe'] = self::zahl($z['aufrufe']);
            }
        }

        return $reihe;
    }

    public function dimension(int $siteId, int $dim, Period $period, int $limit, ?array $keys = null): array
    {
        if ($keys !== null && $keys === []) {
            return [];
        }
        $in = $keys === null ? '' : ' IN (' . implode(',', array_map('intval', $keys)) . ')';
        $basis = [$siteId, $period->startUtc, $period->endUtc, ...$this->filter->params];
        if ($dim === Dimension::PAGE) {
            $sql = 'SELECT e.path_id AS k, COUNT(DISTINCT s.visitor_hash) AS besucher, COUNT(*) AS aufrufe, COUNT(DISTINCT e.session_id) AS besuche, 0 AS bounces FROM '
                . $this->db->table('events') . ' e JOIN ' . $this->db->table('sessions') . ' s ON s.id = e.session_id'
                . ' WHERE e.site_id = ? AND e.kind = 1 AND e.occurred_at >= ? AND e.occurred_at < ?' . $this->filter->sql . ($in !== '' ? ' AND e.path_id' . $in : '') . ' GROUP BY e.path_id';
        } elseif ($dim === Dimension::EVENT_NAME) {
            $sql = 'SELECT e.name_id AS k, COUNT(DISTINCT s.visitor_hash) AS besucher, COUNT(*) AS aufrufe, COUNT(DISTINCT e.session_id) AS besuche, 0 AS bounces FROM '
                . $this->db->table('events') . ' e JOIN ' . $this->db->table('sessions') . ' s ON s.id = e.session_id'
                . ' WHERE e.site_id = ? AND e.kind = 2 AND e.name_id IS NOT NULL AND e.occurred_at >= ? AND e.occurred_at < ?' . $this->filter->sql . ($in !== '' ? ' AND e.name_id' . $in : '') . ' GROUP BY e.name_id';
        } elseif (isset(self::SESSION_DIMS[$dim])) {
            $ausdruck = self::SESSION_DIMS[$dim];
            $sql = 'SELECT ' . $ausdruck . ' AS k, COUNT(DISTINCT s.visitor_hash) AS besucher, SUM(s.pageviews) AS aufrufe, COUNT(*) AS besuche,'
                . ' SUM(s.pageviews = 1 AND s.custom_events = 0) AS bounces FROM ' . $this->db->table('sessions') . ' s'
                . ' WHERE s.site_id = ? AND s.started_at >= ? AND s.started_at < ?' . $this->filter->sql . ' AND ' . $ausdruck . ' IS NOT NULL GROUP BY k';
        } else {
            return [];
        }

        return self::zeilen($this->db->fetchAll($sql . ' ORDER BY besucher DESC, k LIMIT ' . $limit, $basis));
    }

    public function pageViews(int $siteId, Period $period, array $pathIds): array
    {
        if ($pathIds === []) {
            return [];
        }
        $zeilen = $this->db->fetchAll(
            'SELECT e.path_id AS k, COUNT(*) AS n FROM ' . $this->db->table('events') . ' e JOIN ' . $this->db->table('sessions')
            . ' s ON s.id = e.session_id WHERE e.site_id = ? AND e.kind = 1 AND e.occurred_at >= ? AND e.occurred_at < ?' . $this->filter->sql
            . ' AND e.path_id IN (' . implode(',', array_map('intval', $pathIds)) . ') GROUP BY e.path_id',
            [$siteId, $period->startUtc, $period->endUtc, ...$this->filter->params],
        );
        $ergebnis = [];
        foreach ($zeilen as $z) {
            $ergebnis[self::zahl($z['k'])] = self::zahl($z['n']);
        }

        return $ergebnis;
    }

    public function eventProps(int $siteId, Period $period): array
    {
        $zeilen = $this->db->fetchAll(
            'SELECT e.name_id AS n, p.key_id AS k, p.value_id AS v, COUNT(*) AS anzahl FROM ' . $this->db->table('events') . ' e JOIN '
            . $this->db->table('event_props') . ' p ON p.event_id = e.id JOIN ' . $this->db->table('sessions') . ' s ON s.id = e.session_id'
            . ' WHERE e.site_id = ? AND e.kind = 2 AND e.name_id IS NOT NULL AND e.occurred_at >= ? AND e.occurred_at < ?' . $this->filter->sql
            . ' GROUP BY e.name_id, p.key_id, p.value_id',
            [$siteId, $period->startUtc, $period->endUtc, ...$this->filter->params],
        );
        $ergebnis = [];
        foreach ($zeilen as $z) {
            $ergebnis[self::zahl($z['n'])][] = ['key_id' => self::zahl($z['k']), 'value_id' => self::zahl($z['v']), 'anzahl' => self::zahl($z['anzahl'])];
        }

        return $ergebnis;
    }

    /**
     * @param list<array<string, mixed>> $zeilen
     * @return list<Row>
     */
    public static function zeilen(array $zeilen): array
    {
        return array_map(static fn(array $z): array => [
            'key' => self::zahl($z['k']),
            'besucher' => self::zahl($z['besucher']),
            'aufrufe' => self::zahl($z['aufrufe']),
            'besuche' => self::zahl($z['besuche']),
            'bounces' => self::zahl($z['bounces']),
        ], $zeilen);
    }

    private static function zahl(mixed $wert): int
    {
        return is_numeric($wert) ? (int) $wert : 0;
    }
}
