<?php

declare(strict_types=1);

namespace Pegelstand\Jobs;

use DateTimeImmutable;
use Pegelstand\Database\Database;

/** Sperre und Status der Hintergrundjobs in der Tabelle `job_runs`. */
final class JobRunner
{
    public function __construct(private readonly Database $db) {}

    /** Versucht, den Job zu sperren. false, wenn gerade ein anderer Lauf aktiv ist. */
    public function tryStart(string $job, int $timeout, DateTimeImmutable $now): bool
    {
        $tabelle = $this->db->table('job_runs');
        $this->db->run('INSERT IGNORE INTO ' . $tabelle . ' (job) VALUES (?)', [$job]);
        $jetzt = gmdate('Y-m-d H:i:s', $now->getTimestamp());
        $stmt = $this->db->run(
            'UPDATE ' . $tabelle . " SET locked_until = ?, started_at = ?, status = 'running', message = NULL"
            . ' WHERE job = ? AND (locked_until IS NULL OR locked_until < ?)',
            [gmdate('Y-m-d H:i:s', $now->getTimestamp() + $timeout), $jetzt, $job, $jetzt],
        );

        return $stmt->rowCount() === 1;
    }

    public function finish(string $job, string $status, string $message, DateTimeImmutable $now): void
    {
        $this->db->run(
            'UPDATE ' . $this->db->table('job_runs') . ' SET locked_until = NULL, finished_at = ?, status = ?, message = ? WHERE job = ?',
            [gmdate('Y-m-d H:i:s', $now->getTimestamp()), $status, mb_substr($message, 0, 255), $job],
        );
    }

    /** Ist der Mindestabstand seit dem letzten abgeschlossenen Lauf verstrichen? */
    public function isDue(string $job, int $interval, DateTimeImmutable $now): bool
    {
        $fertig = $this->db->fetchValue('SELECT finished_at FROM ' . $this->db->table('job_runs') . ' WHERE job = ?', [$job]);
        if (!is_string($fertig)) {
            return true;
        }
        $zeit = strtotime($fertig . ' UTC');

        return $zeit === false || $now->getTimestamp() - $zeit >= $interval;
    }

    /**
     * @return list<array{job: string, status: string, message: string, finished_at: ?string}>
     */
    public function status(): array
    {
        $zeilen = $this->db->fetchAll('SELECT job, status, message, finished_at FROM ' . $this->db->table('job_runs') . ' ORDER BY job');

        return array_map(static fn(array $z): array => [
            'job' => is_string($z['job']) ? $z['job'] : '',
            'status' => is_string($z['status']) ? $z['status'] : '',
            'message' => is_string($z['message']) ? $z['message'] : '',
            'finished_at' => is_string($z['finished_at']) ? $z['finished_at'] : null,
        ], $zeilen);
    }
}
