<?php

declare(strict_types=1);

namespace Pegelstand\Install;

/**
 * Ergebnis einer Systemprüfung. Texte stehen in der Sprachdatei unter install.pruefung.punkte.<id>.
 */
final class Check
{
    public const OK = 'ok';
    public const WARN = 'warn';
    public const FAIL = 'fail';

    /**
     * @param self::OK|self::WARN|self::FAIL $status
     * @param array<string, scalar> $params Werte für den Detailtext
     */
    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly array $params = [],
    ) {}
}
