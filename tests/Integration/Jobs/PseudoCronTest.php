<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Integration\Jobs;

use Pegelstand\Tests\Integration\InstalliertTestCase;

final class PseudoCronTest extends InstalliertTestCase
{
    private function sitzung(): void
    {
        $this->db->run(
            'INSERT INTO ' . $this->db->table('sessions') . ' (site_id, visitor_hash, started_at, last_seen_at, entry_path_id, exit_path_id) VALUES (1, ?, ?, ?, 1, 1)',
            [str_repeat('a', 16), gmdate('Y-m-d H:i:s'), gmdate('Y-m-d H:i:s')],
        );
    }

    private function laeufe(): int
    {
        return $this->db->fetchInt('SELECT COUNT(*) FROM ' . $this->db->table('job_runs') . " WHERE status = 'ok'");
    }

    public function testNachDerAntwortLaufenFaelligeJobs(): void
    {
        $this->sitzung();

        $this->app()->afterResponse();

        self::assertSame(3, $this->laeufe(), 'Aggregation, Aufräumen und Berichte sind gelaufen.');
        self::assertSame(1, $this->db->fetchInt('SELECT COUNT(*) FROM ' . $this->db->table('agg_daily')));
    }

    public function testMarkerDateiBremstDieNaechsteAnfrage(): void
    {
        $this->app()->afterResponse();
        $this->db->run('DELETE FROM ' . $this->db->table('job_runs'));

        $this->app()->afterResponse();

        self::assertSame(0, $this->laeufe(), 'Innerhalb einer Minute wird nicht erneut geprüft.');

        touch($this->temp . '/storage/cache/cron-last', time() - 120);
        $this->app()->afterResponse();
        self::assertSame(3, $this->laeufe());
    }

    public function testExternerModusSchaltetPseudoCronAus(): void
    {
        $this->konfiguration("    'cron' => ['mode' => 'external'],\n");

        $this->app()->afterResponse();

        self::assertSame(0, $this->laeufe());
        self::assertFileDoesNotExist($this->temp . '/storage/cache/cron-last');
    }

    public function testOhneInstallationPassiertNichts(): void
    {
        unlink($this->temp . '/config/config.php');

        $this->app()->afterResponse();

        self::assertSame(0, $this->laeufe());
    }

    public function testKommandozeileFuehrtJobsAus(): void
    {
        $this->sitzung();

        $ausgabe = shell_exec(sprintf(
            'PEGELSTAND_CONFIG_DIR=%s PEGELSTAND_STORAGE_DIR=%s php %s --force 2>&1',
            escapeshellarg($this->temp . '/config'),
            escapeshellarg($this->temp . '/storage'),
            escapeshellarg(PEGELSTAND_ROOT . '/bin/cron.php'),
        ));

        self::assertStringContainsString('aggregate: 1 Sites, 2 Tage', (string) $ausgabe);
        self::assertStringContainsString('cleanup: ', (string) $ausgabe);
        self::assertSame(3, $this->laeufe());
    }

    public function testDemoDatenSkript(): void
    {
        $ausgabe = shell_exec(sprintf(
            'PEGELSTAND_CONFIG_DIR=%s PEGELSTAND_STORAGE_DIR=%s php %s --site=1 --days=5 --sessions=40 2>&1',
            escapeshellarg($this->temp . '/config'),
            escapeshellarg($this->temp . '/storage'),
            escapeshellarg(PEGELSTAND_ROOT . '/bin/demo-data.php'),
        ));

        self::assertStringContainsString('Sitzungen und', (string) $ausgabe);
        self::assertStringContainsString('Aggregation in', (string) $ausgabe);
        self::assertGreaterThan(50, $this->db->fetchInt('SELECT COUNT(*) FROM ' . $this->db->table('sessions')));
        self::assertGreaterThan(0, $this->db->fetchInt('SELECT COUNT(*) FROM ' . $this->db->table('agg_daily')));
    }
}
