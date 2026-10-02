<?php

declare(strict_types=1);

namespace Pegelstand\Reports;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Wann ist ein Bericht fällig und welchen Zeitraum deckt er ab? Wochenberichte erscheinen montags ab 7 Uhr
 * (Ortszeit der Website) für die Vorwoche, Monatsberichte am Ersten ab 7 Uhr für den Vormonat.
 */
final class ReportSchedule
{
    public const HOUR = 7;

    /**
     * Der jüngste Zeitpunkt, an dem ein Bericht dieser Art hätte erscheinen sollen.
     *
     * @return array{faellig: DateTimeImmutable, von: string, bis: string}
     */
    public static function latest(string $frequency, DateTimeImmutable $now, DateTimeZone $zone): array
    {
        $lokal = $now->setTimezone($zone);
        if ($frequency === 'monthly') {
            $erster = $lokal->modify('first day of this month')->setTime(self::HOUR, 0);
            if ($erster > $lokal) {
                $erster = $erster->modify('first day of last month')->setTime(self::HOUR, 0);
            }
            $von = $erster->modify('first day of last month')->format('Y-m-d');
            $bis = $erster->modify('-1 day')->format('Y-m-d');

            return ['faellig' => $erster->setTimezone(new DateTimeZone('UTC')), 'von' => $von, 'bis' => $bis];
        }
        $montag = $lokal->modify('monday this week')->setTime(self::HOUR, 0);
        if ($montag > $lokal) {
            $montag = $montag->modify('-7 days');
        }

        return [
            'faellig' => $montag->setTimezone(new DateTimeZone('UTC')),
            'von' => $montag->modify('-7 days')->format('Y-m-d'),
            'bis' => $montag->modify('-1 day')->format('Y-m-d'),
        ];
    }
}
