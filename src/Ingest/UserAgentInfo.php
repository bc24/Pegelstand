<?php

declare(strict_types=1);

namespace Pegelstand\Ingest;

/** Grobe Geräteangaben ohne Versionsnummern. */
final class UserAgentInfo
{
    public const DEVICE_UNKNOWN = 0;
    public const DEVICE_DESKTOP = 1;
    public const DEVICE_MOBILE = 2;
    public const DEVICE_TABLET = 3;

    public function __construct(
        public readonly ?string $browser,
        public readonly ?string $os,
        public readonly int $device,
    ) {}
}
