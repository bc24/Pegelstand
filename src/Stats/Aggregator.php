<?php

declare(strict_types=1);

namespace Pegelstand\Stats;

use DateTimeImmutable;
use DateTimeZone;
use Pegelstand\Database\Database;

/**
 * Verdichtet Rohdaten (Sitzungen, Ereignisse) zu Tages- und Stundenwerten. Jeder Lauf berechnet einen Tag
 * komplett neu und ersetzt die alten Zeilen. Das ist wiederholbar und fängt Nachzügler ab.
 *
 * Zuordnung: Sitzungswerte (Besucher, Sitzungen, Absprünge, Dauer, Aufschlüsselungen nach Herkunft und Gerät)
 * zählen an dem Tag, an dem die Sitzung begann. Seitenaufrufe und Ereignisse zählen an dem Tag, an dem sie geschahen.
 */
final class Aggregator
{
    public function __construct(private readonly Database $db) {}

    /**
     * @param string $localDay Tag in der Zeitzone der Site, Format Y-m-d
     */
    public function aggregateDay(int $siteId, string $timezone, string $localDay): void
    {
        $zone = new DateTimeZone($timezone);
        $utc = new DateTimeZone('UTC');
        $anfang = new DateTimeImmutable($localDay . ' 00:00:00', $zone);
        $ende = $anfang->modify('+1 day');
        $von = $anfang->setTimezone($utc)->format('Y-m-d H:i:s');
        $bis = $ende->setTimezone($utc)->format('Y-m-d H:i:s');

        $this->db->pdo->beginTransaction();
        try {
            $this->tag($siteId, $localDay, $von, $bis);
            $this->stunden($siteId, $zone, $anfang->format('Y-m-d H:i:s'), $ende->format('Y-m-d H:i:s'), $von, $bis);
            $this->aufschluesselungen($siteId, $localDay, $von, $bis);
            $this->eigenschaften($siteId, $localDay, $von, $bis);
            $this->db->pdo->commit();
        } catch (\Throwable $fehler) {
            if ($this->db->pdo->inTransaction()) {
                $this->db->pdo->rollBack();
            }
            throw $fehler;
        }
    }

    private function tag(int $siteId, string $tag, string $von, string $bis): void
    {
        $this->db->run('DELETE FROM ' . $this->db->table('agg_daily') . ' WHERE site_id = ? AND day = ?', [$siteId, $tag]);
        $s = $this->db->fetchAll(
            'SELECT COUNT(DISTINCT visitor_hash) AS visitors, COUNT(*) AS sessions,'
            . ' COALESCE(SUM(pageviews = 1 AND custom_events = 0), 0) AS bounces,'
            . ' COALESCE(SUM(TIMESTAMPDIFF(SECOND, started_at, last_seen_at)), 0) AS duration'
            . ' FROM ' . $this->db->table('sessions') . ' WHERE site_id = ? AND started_at >= ? AND started_at < ?',
            [$siteId, $von, $bis],
        )[0];
        $e = $this->db->fetchAll(
            'SELECT COALESCE(SUM(kind = 1), 0) AS pageviews, COALESCE(SUM(kind = 2), 0) AS events FROM '
            . $this->db->table('events') . ' WHERE site_id = ? AND occurred_at >= ? AND occurred_at < ?',
            [$siteId, $von, $bis],
        )[0];
        if (self::zahl($s['sessions']) === 0 && self::zahl($e['pageviews']) === 0 && self::zahl($e['events']) === 0) {
            return;
        }
        $this->db->run(
            'INSERT INTO ' . $this->db->table('agg_daily') . ' (site_id, day, visitors, sessions, pageviews, bounces, duration_sum, events)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$siteId, $tag, self::zahl($s['visitors']), self::zahl($s['sessions']), self::zahl($e['pageviews']), self::zahl($s['bounces']), self::zahl($s['duration']), self::zahl($e['events'])],
        );
    }

    private function stunden(int $siteId, DateTimeZone $zone, string $lokalVon, string $lokalBis, string $von, string $bis): void
    {
        $tabelle = $this->db->table('agg_hourly');
        $this->db->run('DELETE FROM ' . $tabelle . ' WHERE site_id = ? AND hour >= ? AND hour < ?', [$siteId, $lokalVon, $lokalBis]);

        /** @var array<string, array{visitors: int, sessions: int, pageviews: int, bounces: int, duration: int, events: int}> $stunden */
        $stunden = [];
        $leer = ['visitors' => 0, 'sessions' => 0, 'pageviews' => 0, 'bounces' => 0, 'duration' => 0, 'events' => 0];
        $lokal = static fn(mixed $utcStunde): string => (new DateTimeImmutable(is_string($utcStunde) ? $utcStunde : '', new DateTimeZone('UTC')))
            ->setTimezone($zone)->format('Y-m-d H:i:00');

        $sitzungen = $this->db->fetchAll(
            "SELECT DATE_FORMAT(started_at, '%Y-%m-%d %H:00:00') AS h, COUNT(DISTINCT visitor_hash) AS visitors, COUNT(*) AS sessions,"
            . ' SUM(pageviews = 1 AND custom_events = 0) AS bounces, SUM(TIMESTAMPDIFF(SECOND, started_at, last_seen_at)) AS duration'
            . ' FROM ' . $this->db->table('sessions') . ' WHERE site_id = ? AND started_at >= ? AND started_at < ? GROUP BY h',
            [$siteId, $von, $bis],
        );
        foreach ($sitzungen as $z) {
            $schluessel = $lokal($z['h']);
            $stunden[$schluessel] ??= $leer;
            $stunden[$schluessel]['visitors'] += self::zahl($z['visitors']);
            $stunden[$schluessel]['sessions'] += self::zahl($z['sessions']);
            $stunden[$schluessel]['bounces'] += self::zahl($z['bounces']);
            $stunden[$schluessel]['duration'] += self::zahl($z['duration']);
        }
        $ereignisse = $this->db->fetchAll(
            "SELECT DATE_FORMAT(occurred_at, '%Y-%m-%d %H:00:00') AS h, SUM(kind = 1) AS pageviews, SUM(kind = 2) AS events"
            . ' FROM ' . $this->db->table('events') . ' WHERE site_id = ? AND occurred_at >= ? AND occurred_at < ? GROUP BY h',
            [$siteId, $von, $bis],
        );
        foreach ($ereignisse as $z) {
            $schluessel = $lokal($z['h']);
            $stunden[$schluessel] ??= $leer;
            $stunden[$schluessel]['pageviews'] += self::zahl($z['pageviews']);
            $stunden[$schluessel]['events'] += self::zahl($z['events']);
        }
        foreach ($stunden as $stunde => $w) {
            $this->db->run(
                'INSERT INTO ' . $tabelle . ' (site_id, hour, visitors, sessions, pageviews, bounces, duration_sum, events) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$siteId, $stunde, $w['visitors'], $w['sessions'], $w['pageviews'], $w['bounces'], $w['duration'], $w['events']],
            );
        }
    }

    private function aufschluesselungen(int $siteId, string $tag, string $von, string $bis): void
    {
        $tabelle = $this->db->table('agg_daily_dim');
        $this->db->run('DELETE FROM ' . $tabelle . ' WHERE site_id = ? AND day = ?', [$siteId, $tag]);
        $sitzungen = $this->db->table('sessions');
        $ereignisse = $this->db->table('events');
        $kopf = 'INSERT INTO ' . $tabelle . ' (site_id, day, dim, value_id, visitors, sessions, hits, bounces, duration_sum) ';

        $sitzungsDimensionen = [
            Dimension::ENTRY_PAGE => 'entry_path_id',
            Dimension::EXIT_PAGE => 'exit_path_id',
            Dimension::REFERRER => 'referrer_id',
            Dimension::UTM_SOURCE => 'utm_source_id',
            Dimension::UTM_MEDIUM => 'utm_medium_id',
            Dimension::UTM_CAMPAIGN => 'utm_campaign_id',
            Dimension::UTM_TERM => 'utm_term_id',
            Dimension::UTM_CONTENT => 'utm_content_id',
            Dimension::COUNTRY => '(ASCII(SUBSTRING(country, 1, 1)) * 256 + ASCII(SUBSTRING(country, 2, 1)))',
            Dimension::DEVICE => 'NULLIF(device, 0)',
            Dimension::BROWSER => 'browser_id',
            Dimension::OS => 'os_id',
        ];
        foreach ($sitzungsDimensionen as $dim => $ausdruck) {
            $this->db->run(
                $kopf . 'SELECT site_id, ?, ?, ' . $ausdruck . ', COUNT(DISTINCT visitor_hash), COUNT(*), SUM(pageviews),'
                . ' SUM(pageviews = 1 AND custom_events = 0), SUM(TIMESTAMPDIFF(SECOND, started_at, last_seen_at)) FROM ' . $sitzungen
                . ' WHERE site_id = ? AND started_at >= ? AND started_at < ? AND ' . $ausdruck . ' IS NOT NULL GROUP BY ' . $ausdruck,
                [$tag, $dim, $siteId, $von, $bis],
            );
        }

        $this->db->run(
            $kopf . 'SELECT e.site_id, ?, ?, e.path_id, COUNT(DISTINCT s.visitor_hash), COUNT(DISTINCT e.session_id), COUNT(*), 0, 0 FROM ' . $ereignisse
            . ' e JOIN ' . $sitzungen . ' s ON s.id = e.session_id WHERE e.site_id = ? AND e.occurred_at >= ? AND e.occurred_at < ? AND e.kind = 1 GROUP BY e.path_id',
            [$tag, Dimension::PAGE, $siteId, $von, $bis],
        );
        $this->db->run(
            $kopf . 'SELECT e.site_id, ?, ?, e.name_id, COUNT(DISTINCT s.visitor_hash), COUNT(DISTINCT e.session_id), COUNT(*), 0, 0 FROM ' . $ereignisse
            . ' e JOIN ' . $sitzungen . ' s ON s.id = e.session_id WHERE e.site_id = ? AND e.occurred_at >= ? AND e.occurred_at < ? AND e.kind = 2 AND e.name_id IS NOT NULL GROUP BY e.name_id',
            [$tag, Dimension::EVENT_NAME, $siteId, $von, $bis],
        );
    }

    private function eigenschaften(int $siteId, string $tag, string $von, string $bis): void
    {
        $tabelle = $this->db->table('agg_daily_prop');
        $this->db->run('DELETE FROM ' . $tabelle . ' WHERE site_id = ? AND day = ?', [$siteId, $tag]);
        $this->db->run(
            'INSERT INTO ' . $tabelle . ' (site_id, day, name_id, key_id, value_id, visitors, events)'
            . ' SELECT e.site_id, ?, e.name_id, p.key_id, p.value_id, COUNT(DISTINCT s.visitor_hash), COUNT(*) FROM ' . $this->db->table('events') . ' e'
            . ' JOIN ' . $this->db->table('event_props') . ' p ON p.event_id = e.id'
            . ' JOIN ' . $this->db->table('sessions') . ' s ON s.id = e.session_id'
            . ' WHERE e.site_id = ? AND e.occurred_at >= ? AND e.occurred_at < ? AND e.kind = 2 AND e.name_id IS NOT NULL GROUP BY e.name_id, p.key_id, p.value_id',
            [$tag, $siteId, $von, $bis],
        );
    }

    private static function zahl(mixed $wert): int
    {
        return is_numeric($wert) ? (int) $wert : 0;
    }
}
