<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Integration\Demo;

use DateTimeImmutable;
use DateTimeZone;
use Pegelstand\Demo\DemoDataGenerator;
use Pegelstand\Jobs\AggregationJob;
use Pegelstand\Stats\Aggregator;
use Pegelstand\Tests\Integration\SiteTestCase;

final class DemoDataGeneratorTest extends SiteTestCase
{
    /**
     * @return array{sessions: int, events: int}
     */
    private function erzeuge(int $seed = 1): array
    {
        $utc = new DateTimeZone('UTC');

        return (new DemoDataGenerator($this->db))->generate(1, new DateTimeImmutable('2026-09-20', $utc), new DateTimeImmutable('2026-09-30', $utc), 60, $seed);
    }

    public function testErzeugtKonsistenteDaten(): void
    {
        $summe = $this->erzeuge();

        self::assertGreaterThan(300, $summe['sessions']);
        self::assertSame($summe['sessions'], $this->zaehle('sessions'));
        self::assertSame($summe['events'], $this->zaehle('events'));
        self::assertSame(0, $this->zaehle('events', 'session_id NOT IN (SELECT id FROM ' . $this->db->table('sessions') . ')'));
        self::assertSame(0, $this->zaehle('sessions', 'pageviews < 1'));
        self::assertSame(0, $this->zaehle('sessions', 'last_seen_at < started_at'));
        self::assertSame(
            $this->zaehle('events', 'kind = 1'),
            $this->db->fetchInt('SELECT SUM(pageviews) FROM ' . $this->db->table('sessions')),
            'Die Seitenaufrufe der Sitzungen passen zu den Ereignissen.',
        );
        self::assertGreaterThan(0, $this->zaehle('event_props'));
    }

    public function testGleicherStartwertGleicheDaten(): void
    {
        $a = $this->erzeuge(7);
        $this->db->pdo->exec('DELETE FROM ' . $this->db->table('sessions'));
        $this->db->pdo->exec('DELETE FROM ' . $this->db->table('events'));
        $this->db->pdo->exec('DELETE FROM ' . $this->db->table('event_props'));
        $b = $this->erzeuge(7);

        self::assertSame($a, $b);
    }

    public function testAggregationStimmtMitRohdatenUeberein(): void
    {
        $this->erzeuge();
        (new AggregationJob($this->db, new Aggregator($this->db)))->run(new DateTimeImmutable('2026-10-01 10:00:00', new DateTimeZone('UTC')));

        $agg = $this->db->fetchAll('SELECT SUM(sessions) AS s, SUM(pageviews) AS p, SUM(events) AS e FROM ' . $this->db->table('agg_daily'))[0];
        self::assertEquals($this->zaehle('sessions'), $agg['s']);
        self::assertEquals($this->zaehle('events', 'kind = 1'), $agg['p']);
        self::assertEquals($this->zaehle('events', 'kind = 2'), $agg['e']);
        $stunden = $this->db->fetchAll('SELECT SUM(sessions) AS s, SUM(pageviews) AS p FROM ' . $this->db->table('agg_hourly'))[0];
        self::assertEquals($agg['s'], $stunden['s']);
        self::assertEquals($agg['p'], $stunden['p']);
    }

    public function testNochmaligesErzeugenHaengtAnStattZuUeberschreiben(): void
    {
        $a = $this->erzeuge(1);
        $b = $this->erzeuge(2);

        self::assertSame($a['sessions'] + $b['sessions'], $this->zaehle('sessions'));
    }
}
