<?php

declare(strict_types=1);

// Erzeugt die aus docs/ abgeleiteten Seiten des GitHub-Wikis in wiki/. Nur für Entwickler: php bin/build-wiki.php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('PEGELSTAND_ROOT', dirname(__DIR__));
require PEGELSTAND_ROOT . '/vendor/autoload.php';

use Pegelstand\Tests\Support\WikiBuilder;

foreach ((new WikiBuilder(PEGELSTAND_ROOT))->build() as $datei => $inhalt) {
    file_put_contents(PEGELSTAND_ROOT . '/wiki/' . $datei, $inhalt);
    echo "wiki/$datei\n";
}
