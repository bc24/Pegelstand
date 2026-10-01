<?php

declare(strict_types=1);

namespace Pegelstand\Ingest;

/** Ergebnis einer Erfassung. Gefilterte Anfragen sehen von außen genauso aus wie gespeicherte. */
enum IngestResult
{
    case Accepted;
    case Ignored;
    case BadRequest;
    case TooLarge;
    case UnknownSite;
    case RateLimited;

    public function status(): int
    {
        return match ($this) {
            self::Accepted, self::Ignored => 202,
            self::BadRequest => 400,
            self::UnknownSite => 404,
            self::TooLarge => 413,
            self::RateLimited => 429,
        };
    }
}
