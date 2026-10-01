<?php

declare(strict_types=1);

// Führt fällige Hintergrundjobs aus (Aggregation, Aufräumen). Für einen echten Cronjob, z. B. alle 5 Minuten:
//   */5 * * * * php /pfad/zu/pegelstand/bin/cron.php
// Mit --force laufen alle Jobs sofort. Setze dann in config/config.php 'cron' => ['mode' => 'external'],
// damit nicht zusätzlich der Pseudo-Cron bei Seitenaufrufen läuft.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('PEGELSTAND_ROOT', dirname(__DIR__));
require PEGELSTAND_ROOT . '/vendor/autoload.php';

use Pegelstand\Core\Application;
use Pegelstand\Core\Paths;

$app = new Application(Paths::fromEnvironment(PEGELSTAND_ROOT));
$db = $app->database();
if ($db === null) {
    fwrite(STDERR, "Pegelstand ist noch nicht installiert.\n");
    exit(1);
}
$app->migrate($db);
$ergebnis = $app->scheduler($db)->runDue(in_array('--force', $argv, true));
if ($ergebnis === []) {
    echo "Nichts fällig.\n";
}
$fehler = 0;
foreach ($ergebnis as $job => $meldung) {
    echo $job . ': ' . $meldung . "\n";
    if (str_starts_with($meldung, 'Fehler')) {
        ++$fehler;
    }
}
exit($fehler > 0 ? 1 : 0);
