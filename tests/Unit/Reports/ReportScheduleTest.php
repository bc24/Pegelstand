<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Unit\Reports;

use DateTimeImmutable;
use DateTimeZone;
use Pegelstand\Reports\ReportSchedule;
use PHPUnit\Framework\TestCase;

final class ReportScheduleTest extends TestCase
{
    private function jetzt(string $utc): DateTimeImmutable
    {
        return new DateTimeImmutable($utc, new DateTimeZone('UTC'));
    }

    public function testWochenberichtAmMontagNachSiebenUhr(): void
    {
        // Montag, 05.10.2026, 08:30 Uhr in Berlin (06:30 UTC) -> Bericht für 28.09. bis 04.10.
        $t = ReportSchedule::latest('weekly', $this->jetzt('2026-10-05 06:30:00'), new DateTimeZone('Europe/Berlin'));

        self::assertSame('2026-10-05 05:00:00', $t['faellig']->format('Y-m-d H:i:s'), '07:00 Uhr in Berlin (MESZ) ist 05:00 UTC.');
        self::assertSame(['2026-09-28', '2026-10-04'], [$t['von'], $t['bis']]);
    }

    public function testVorSiebenUhrGiltNochDieVorwoche(): void
    {
        // Montag 06:00 Uhr in Berlin: der Termin dieser Woche ist noch nicht erreicht.
        $t = ReportSchedule::latest('weekly', $this->jetzt('2026-10-05 04:00:00'), new DateTimeZone('Europe/Berlin'));

        self::assertSame(['2026-09-21', '2026-09-27'], [$t['von'], $t['bis']]);
        self::assertSame('2026-09-28 05:00:00', $t['faellig']->format('Y-m-d H:i:s'));
    }

    public function testMittwochGiltDerMontagDavor(): void
    {
        $t = ReportSchedule::latest('weekly', $this->jetzt('2026-10-07 12:00:00'), new DateTimeZone('Europe/Berlin'));

        self::assertSame(['2026-09-28', '2026-10-04'], [$t['von'], $t['bis']]);
    }

    public function testMonatsbericht(): void
    {
        $t = ReportSchedule::latest('monthly', $this->jetzt('2026-10-01 09:00:00'), new DateTimeZone('Europe/Berlin'));
        self::assertSame(['2026-09-01', '2026-09-30'], [$t['von'], $t['bis']]);
        self::assertSame('2026-10-01 05:00:00', $t['faellig']->format('Y-m-d H:i:s'));

        // Am Ersten um 06:00 Uhr Ortszeit ist noch der Termin des Vormonats der jüngste.
        $frueh = ReportSchedule::latest('monthly', $this->jetzt('2026-10-01 04:00:00'), new DateTimeZone('Europe/Berlin'));
        self::assertSame(['2026-08-01', '2026-08-31'], [$frueh['von'], $frueh['bis']]);

        $mitte = ReportSchedule::latest('monthly', $this->jetzt('2026-10-20 12:00:00'), new DateTimeZone('Europe/Berlin'));
        self::assertSame(['2026-09-01', '2026-09-30'], [$mitte['von'], $mitte['bis']]);
    }

    public function testJahreswechsel(): void
    {
        $t = ReportSchedule::latest('monthly', $this->jetzt('2027-01-03 12:00:00'), new DateTimeZone('Europe/Berlin'));
        self::assertSame(['2026-12-01', '2026-12-31'], [$t['von'], $t['bis']]);

        $w = ReportSchedule::latest('weekly', $this->jetzt('2027-01-04 12:00:00'), new DateTimeZone('Europe/Berlin'));
        self::assertSame(['2026-12-28', '2027-01-03'], [$w['von'], $w['bis']]);
    }
}
