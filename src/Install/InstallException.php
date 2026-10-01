<?php

declare(strict_types=1);

namespace Pegelstand\Install;

use RuntimeException;

/**
 * Fehler während der Installation. Der Schlüssel verweist auf einen Text der Sprachdatei, der den Lösungsweg nennt.
 */
final class InstallException extends RuntimeException
{
    /**
     * @param array<string, scalar> $params
     */
    public function __construct(
        public readonly string $messageKey,
        public readonly array $params = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($messageKey, 0, $previous);
    }
}
