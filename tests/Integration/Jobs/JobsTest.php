<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Integration\Jobs;

use DateTimeImmutable;
use DateTimeZone;
use Pegelstand\Jobs\AggregationJob;
use Pegelstand\Jobs\CleanupJob;
use Pegelstand\Jobs\Job;
use Pegelstand\Jobs\JobRunner;
use Pegelstand\Jobs\Scheduler;
use Pegelstand\Stats\Aggregator;
use Pegelstand\Tests\Integration\SiteTestCase;
use RuntimeException;

final class JobsTest extends SiteTestCase
{
    private function zeit(string $utc): DateTimeImmutable
    {
        return new DateTimeImmutable($utc, new DateTimeZone('UTC'));
    }

    public function testSperreVerhindertDoppelstart(): void
    {
        $runner = new JobRunner($this->db);
        $jetzt = $this->zeit('2026-10-01 12:00:00');

        self::assertTrue($runner->tryStart('x', 60, $jetzt));
        self::assertFalse($runner->tryStart('x', 60, $jetzt->modify('+30 seconds')), 'Sperre ist noch aktiv.');
        self::assertTrue($runner->tryStart('x', 60, $jetzt->modify('+61 seconds')), 'Abgelaufene Sperre (abgestürzter Lauf) ist frei.');
        $runner->finish('x', 'ok', 'fertig', $jetzt->modify('+70 seconds'));
        self::assertTrue($runner->tryStart('x', 60, $jetzt->modify('+71 seconds')), 'Nach dem Ende ist sie sofort frei.');
    }

    public function testFaelligkeit(): void
    {
        $runner = new JobRunner($this->db);
        $jetzt = $this->zeit('2026-10-01 12:00:00');

        self::assertTrue($runner->isDue('x', 300, $jetzt), 'Noch nie gelaufen.');
        $runner->tryStart('x', 60, $jetzt);
        $runner->finish('x', 'ok', '', $jetzt);
        self::assertFalse($runner->isDue('x', 300, $jetzt->modify('+299 seconds')));
        self::assertTrue($runner->isDue('x', 300, $jetzt->modify('+300 seconds')));
    }

    public function testSchedulerFuehrtNurFaelligeJobsAusUndMeldetFehler(): void
    {
        $jetzt = $this->zeit('2026-10-01 12:00:00');
        $gut = new class implements Job {
            public int $aufrufe = 0;

            public function name(): string
            {
                return 'gut';
            }

            public function interval(): int
            {
                return 600;
            }

            public function timeout(): int
            {
                return 60;
            }

            public function run(DateTimeImmutable $now): string
            {
                ++$this->aufrufe;

                return 'erledigt';
            }
        };
        $kaputt = new class implements Job {
            public function name(): string
            {
                return 'kaputt';
            }

            public function interval(): int
            {
                return 600;
            }

            public function timeout(): int
            {
                return 60;
            }

            public function run(DateTimeImmutable $now): string
            {
                throw new RuntimeException('Boom');
            }
        };
        $uhr = $jetzt;
        $scheduler = new Scheduler(new JobRunner($this->db), [$kaputt, $gut], null, static function () use (&$uhr): DateTimeImmutable {
            return $uhr;
        });

        $erste = $scheduler->runDue();
        self::assertSame(['kaputt' => 'Fehler: Boom', 'gut' => 'erledigt'], $erste, 'Der Fehler stoppt den zweiten Job nicht.');
        self::assertSame([], $scheduler->runDue(), 'Direkt danach ist nichts fällig.');

        $uhr = $jetzt->modify('+601 seconds');
        self::assertSame(['kaputt' => 'Fehler: Boom', 'gut' => 'erledigt'], $scheduler->runDue());
        self::assertSame(2, $gut->aufrufe);
        $status = (new JobRunner($this->db))->status();
        self::assertSame('error', $status[1]['status'] === 'error' ? 'error' : $status[0]['status']);
    }

    public function testErzwungenerLaufIgnoriertDenMindestabstand(): void
    {
        $jetzt = $this->zeit('2026-10-01 12:00:00');
        $scheduler = new Scheduler(new JobRunner($this->db), [new CleanupJob($this->db)], null, static fn(): DateTimeImmutable => $jetzt);

        self::assertArrayHasKey('cleanup', $scheduler->runDue());
        self::assertSame([], $scheduler->runDue());
        self::assertArrayHasKey('cleanup', $scheduler->runDue(true));
    }

    public function testAggregationHolteVergangeneTageNachUndMerktSichDenStand(): void
    {
        $this->sitzung('2026-09-27 08:00:00', ['/'], ['besucher' => 'A']);
        $this->sitzung('2026-09-29 08:00:00', ['/', '/b'], ['besucher' => 'B']);
        $this->sitzung('2026-10-01 08:00:00', ['/'], ['besucher' => 'C']);
        $job = new AggregationJob($this->db, new Aggregator($this->db));

        $meldung = $job->run($this->zeit('2026-10-01 10:00:00'));

        self::assertSame('1 Sites, 5 Tage', $meldung, '27.09. bis 01.10.');
        self::assertSame(3, $this->zaehle('agg_daily'));
        self::assertSame(
            '2026-09-30',
            $this->db->fetchValue('SELECT value FROM ' . $this->db->table('settings') . ' WHERE name = ?', [AggregationJob::cursorKey(1)]),
            'Ab gestern wird noch einmal neu berechnet, ältere Tage sind endgültig.',
        );

        // Nachzügler am gestrigen und heutigen Tag fließen beim nächsten Lauf ein, alte Tage bleiben unangetastet.
        $this->sitzung('2026-09-30 20:00:00', ['/'], ['besucher' => 'D']);
        $this->sitzung('2026-10-01 09:00:00', ['/'], ['besucher' => 'E']);
        $meldung = $job->run($this->zeit('2026-10-01 11:00:00'));

        self::assertSame('1 Sites, 2 Tage', $meldung);
        self::assertSame(4, $this->zaehle('agg_daily'));
        self::assertEquals(2, $this->db->fetchValue('SELECT sessions FROM ' . $this->db->table('agg_daily') . " WHERE day = '2026-10-01'"));
    }

    public function testAggregationOhneDatenTutNichts(): void
    {
        $meldung = (new AggregationJob($this->db, new Aggregator($this->db)))->run($this->zeit('2026-10-01 10:00:00'));

        self::assertSame('1 Sites, 0 Tage', $meldung);
        self::assertSame(0, $this->zaehle('settings', "name LIKE 'agg_cursor_%'"));
    }

    public function testAggregationBegrenztTageProLauf(): void
    {
        $this->sitzung('2026-06-01 08:00:00', ['/']);
        $job = new AggregationJob($this->db, new Aggregator($this->db));

        $meldung = $job->run($this->zeit('2026-10-01 10:00:00'));

        self::assertSame('1 Sites, ' . AggregationJob::MAX_DAYS_PER_RUN . ' Tage', $meldung);
        self::assertSame('2026-07-02', $this->db->fetchValue('SELECT value FROM ' . $this->db->table('settings') . ' WHERE name = ?', [AggregationJob::cursorKey(1)]));
    }

    public function testCleanupLoeschtNurAggregierteUndAbgelaufeneRohdaten(): void
    {
        // Aufbewahrung 30 Tage, "jetzt" ist der 01.10.2026: Grenze 01.09.2026.
        $alt = $this->sitzung('2026-08-15 08:00:00', ['/', '/b'], ['ereignis' => 'Signup', 'props' => ['plan' => 'pro']]);
        $this->sitzung('2026-09-20 08:00:00', ['/']);
        $jetzt = $this->zeit('2026-10-01 10:00:00');
        $cleanup = new CleanupJob($this->db);

        self::assertSame('0 Rohdaten-Zeilen gelöscht', $cleanup->run($jetzt), 'Ohne Aggregation (kein Cursor) wird nichts gelöscht.');
        self::assertSame(2, $this->zaehle('sessions'));

        (new AggregationJob($this->db, new Aggregator($this->db)))->run($jetzt);
        $meldung = $cleanup->run($jetzt);

        self::assertSame(1, $this->zaehle('sessions'));
        self::assertSame(0, $this->zaehle('events', 'session_id = ' . $alt));
        self::assertSame(0, $this->zaehle('event_props'));
        self::assertStringStartsWith('4 ', $meldung, '1 Sitzung, 3 Ereignisse.');
        self::assertGreaterThan(0, $this->zaehle('agg_daily', "day = '2026-08-15'"), 'Aggregate bleiben.');
    }

    public function testCleanupLoeschtAbgelaufeneRatenbegrenzungen(): void
    {
        $this->db->run('INSERT INTO ' . $this->db->table('rate_limits') . " VALUES ('ingest', ?, '2026-10-01 07:00:00', 5), ('ingest', ?, '2026-10-01 09:59:00', 5)", [str_repeat('a', 16), str_repeat('b', 16)]);

        (new CleanupJob($this->db))->run($this->zeit('2026-10-01 10:00:00'));

        self::assertSame(1, $this->zaehle('rate_limits'));
    }
}
