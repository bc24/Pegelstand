<?php

declare(strict_types=1);

namespace Pegelstand\Core;

use Throwable;

/**
 * Schreibt Fehler in storage/logs/error.log. Es werden weder Besucherdaten noch IP-Adressen protokolliert.
 */
final class ErrorLog
{
    public function __construct(private readonly string $file) {}

    public function write(Throwable $fehler): void
    {
        $ordner = dirname($this->file);
        if (!is_dir($ordner) && !@mkdir($ordner, 0750, true) && !is_dir($ordner)) {
            return;
        }
        $zeile = sprintf(
            "[%s] %s: %s in %s:%d\n",
            gmdate('Y-m-d H:i:s') . ' UTC',
            $fehler::class,
            str_replace(["\r", "\n"], ' ', $fehler->getMessage()),
            basename($fehler->getFile()),
            $fehler->getLine(),
        );
        @file_put_contents($this->file, $zeile, FILE_APPEND | LOCK_EX);
    }
}
