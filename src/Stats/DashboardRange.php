<?php

declare(strict_types=1);

namespace Pegelstand\Stats;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Der angefragte Zeitraum samt Vorperiode (gleich lang, direkt davor) und den Punkten der Zeitreihe.
 * Alles in der Zeitzone der Site: Tage beginnen dort um Mitternacht, Rohdaten liegen in UTC.
 */
final class DashboardRange
{
    public const MAX_DAYS = 1100;
    public const MIN_DAY = '2000-01-01';

    /**
     * @param list<Bucket> $buckets
     * @param list<Bucket> $previousBuckets
     */
    private function __construct(
        public readonly string $from,
        public readonly string $to,
        public readonly string $previousFrom,
        public readonly string $previousTo,
        public readonly bool $hourly,
        public readonly Period $current,
        public readonly Period $previous,
        public readonly array $buckets,
        public readonly array $previousBuckets,
        public readonly string $today,
        public readonly int $currentHour,
        public readonly string $nowLabel,
    ) {}

    /**
     * Rechnet den Zeitraum aus und begrenzt ihn auf sinnvolle Werte (nicht in der Zukunft, höchstens MAX_DAYS Tage).
     */
    public static function create(DateTimeZone $zone, ?string $from, ?string $to, DateTimeImmutable $now): self
    {
        $utc = new DateTimeZone('UTC');
        $lokalJetzt = $now->setTimezone($zone);
        $heute = $lokalJetzt->format('Y-m-d');

        $bis = self::gueltigerTag($to) ?? $heute;
        if ($bis > $heute) {
            $bis = $heute;
        }
        $von = self::gueltigerTag($from) ?? self::addDays($bis, -29);
        if ($von > $bis) {
            [$von, $bis] = [$bis, $von];
        }
        if ($von < self::MIN_DAY) {
            $von = self::MIN_DAY;
        }
        if (self::daysBetween($von, $bis) > self::MAX_DAYS) {
            $von = self::addDays($bis, -(self::MAX_DAYS - 1));
        }
        $tage = self::daysBetween($von, $bis);
        $vorBis = self::addDays($von, -1);
        $vorVon = self::addDays($vorBis, -($tage - 1));
        $stuendlich = $tage <= 2;

        $start = static fn(string $tag): DateTimeImmutable => (new DateTimeImmutable($tag . ' 00:00:00', $zone));
        $utcText = static fn(DateTimeImmutable $t): string => $t->setTimezone($utc)->format('Y-m-d H:i:s');
        $aktuellEnde = $start(self::addDays($bis, 1));
        $vorEnde = $start(self::addDays($vorBis, 1));
        if ($stuendlich && $bis === $heute) {
            // Vergleich mit gleich viel vergangener Tageszeit statt mit dem vollen Vortag.
            $vergangen = $now->getTimestamp() - $start($heute)->getTimestamp();
            $vorEnde = $start($vorBis)->modify('+' . max(0, $vergangen) . ' seconds');
        }

        return new self(
            $von,
            $bis,
            $vorVon,
            $vorBis,
            $stuendlich,
            new Period($utcText($start($von)), $utcText($aktuellEnde), $von, $bis),
            new Period($utcText($start($vorVon)), $utcText($vorEnde), $vorVon, $vorBis),
            self::buckets($zone, $von, $bis, $stuendlich, $now),
            self::buckets($zone, $vorVon, $vorBis, $stuendlich, null),
            $heute,
            (int) $lokalJetzt->format('G'),
            $lokalJetzt->format('H:i'),
        );
    }

    /**
     * @return list<Bucket>
     */
    private static function buckets(DateTimeZone $zone, string $von, string $bis, bool $stuendlich, ?DateTimeImmutable $bisJetzt): array
    {
        $utc = new DateTimeZone('UTC');
        $liste = [];
        if ($stuendlich) {
            $t = (new DateTimeImmutable($von . ' 00:00:00', $zone))->setTimezone($utc);
            $ende = (new DateTimeImmutable(self::addDays($bis, 1) . ' 00:00:00', $zone))->setTimezone($utc);
            while ($t < $ende) {
                if ($bisJetzt !== null && $t > $bisJetzt) {
                    break;
                }
                $naechste = $t->modify('+1 hour');
                $lokal = $t->setTimezone($zone);
                $liste[] = new Bucket($t->format('Y-m-d H:i:s'), $naechste->format('Y-m-d H:i:s'), $lokal->format('Y-m-d'), (int) $lokal->format('G'));
                $t = $naechste;
            }

            return $liste;
        }
        for ($tag = $von; $tag <= $bis; $tag = self::addDays($tag, 1)) {
            $anfang = (new DateTimeImmutable($tag . ' 00:00:00', $zone))->setTimezone($utc);
            $ende = (new DateTimeImmutable(self::addDays($tag, 1) . ' 00:00:00', $zone))->setTimezone($utc);
            $liste[] = new Bucket($anfang->format('Y-m-d H:i:s'), $ende->format('Y-m-d H:i:s'), $tag, null);
        }

        return $liste;
    }

    public static function gueltigerTag(?string $tag): ?string
    {
        if ($tag === null || preg_match('/^\d{4}-\d{2}-\d{2}$/', $tag) !== 1) {
            return null;
        }
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $tag, new DateTimeZone('UTC'));

        return $d !== false && $d->format('Y-m-d') === $tag ? $tag : null;
    }

    public static function addDays(string $tag, int $n): string
    {
        return (new DateTimeImmutable($tag, new DateTimeZone('UTC')))->modify(($n >= 0 ? '+' : '') . $n . ' days')->format('Y-m-d');
    }

    /** Anzahl der Tage von $von bis $bis, beide eingeschlossen. */
    public static function daysBetween(string $von, string $bis): int
    {
        $utc = new DateTimeZone('UTC');

        return (int) (new DateTimeImmutable($von, $utc))->diff(new DateTimeImmutable($bis, $utc))->days + 1;
    }
}
