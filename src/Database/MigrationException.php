<?php

declare(strict_types=1);

namespace Pegelstand\Database;

use RuntimeException;

final class MigrationException extends RuntimeException
{
    public const LOCKED = 1;
    public const CHANGED = 2;
    public const FAILED = 3;
}
