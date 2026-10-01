<?php

declare(strict_types=1);

// Dateien mit Direktzugriffsschutz prüfen diese Konstante.
if (!defined('PEGELSTAND_ROOT')) {
    define('PEGELSTAND_ROOT', dirname(__DIR__));
}

require dirname(__DIR__) . '/vendor/autoload.php';
