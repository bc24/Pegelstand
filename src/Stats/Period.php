<?php

declare(strict_types=1);

namespace Pegelstand\Stats;

/** Ein Zeitfenster in UTC (Ende ausgeschlossen) mit den zugehörigen Kalendertagen der Site. */
final class Period
{
    public function __construct(
        public readonly string $startUtc,
        public readonly string $endUtc,
        public readonly string $firstDay,
        public readonly string $lastDay,
    ) {}
}
