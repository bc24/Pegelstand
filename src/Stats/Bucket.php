<?php

declare(strict_types=1);

namespace Pegelstand\Stats;

/** Ein Punkt der Zeitreihe: eine Stunde oder ein Tag in der Zeitzone der Site. */
final class Bucket
{
    public function __construct(
        public readonly string $startUtc,
        public readonly string $endUtc,
        public readonly string $day,
        public readonly ?int $hour,
    ) {}
}
