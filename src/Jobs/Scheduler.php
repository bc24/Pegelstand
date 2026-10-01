<?php

declare(strict_types=1);

namespace Pegelstand\Jobs;

use Closure;
use DateTimeImmutable;
use Pegelstand\Core\ErrorLog;
use Throwable;

/** Führt fällige Jobs aus. Fehler eines Jobs stoppen die anderen nicht. */
final class Scheduler
{
    /**
     * @param list<Job> $jobs
     * @param (Closure(): DateTimeImmutable)|null $clock
     */
    public function __construct(
        private readonly JobRunner $runner,
        private readonly array $jobs,
        private readonly ?ErrorLog $log = null,
        private readonly ?Closure $clock = null,
    ) {}

    /**
     * @return array<string, string> Jobname => Ergebnis (Meldung, "Fehler: …"); nicht gelaufene Jobs fehlen
     */
    public function runDue(bool $force = false): array
    {
        $ergebnis = [];
        foreach ($this->jobs as $job) {
            $jetzt = $this->now();
            if (!$force && !$this->runner->isDue($job->name(), $job->interval(), $jetzt)) {
                continue;
            }
            if (!$this->runner->tryStart($job->name(), $job->timeout(), $jetzt)) {
                continue;
            }
            try {
                $meldung = $job->run($jetzt);
                $this->runner->finish($job->name(), 'ok', $meldung, $this->now());
                $ergebnis[$job->name()] = $meldung;
            } catch (Throwable $fehler) {
                $this->log?->write($fehler);
                $this->runner->finish($job->name(), 'error', $fehler->getMessage(), $this->now());
                $ergebnis[$job->name()] = 'Fehler: ' . $fehler->getMessage();
            }
        }

        return $ergebnis;
    }

    private function now(): DateTimeImmutable
    {
        return $this->clock !== null ? ($this->clock)() : new DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}
