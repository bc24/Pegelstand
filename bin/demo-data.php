<?php

declare(strict_types=1);

// Erzeugt frei erfundene Beispieldaten für Demo, Tests und Lasttests.
//   php bin/demo-data.php --create-site --days=90 --sessions=300
//   php bin/demo-data.php --site=1 --days=30 --sessions=2000 --seed=5
// Optionen: --site=ID oder --create-site (legt die Site "Demo" an), --days (Standard 90),
// --sessions (mittlere Sitzungen pro Tag, Standard 300), --seed (Standard 1), --no-aggregate.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('PEGELSTAND_ROOT', dirname(__DIR__));
require PEGELSTAND_ROOT . '/vendor/autoload.php';

use Pegelstand\Core\Application;
use Pegelstand\Core\Paths;
use Pegelstand\Demo\DemoDataGenerator;

$optionen = [];
foreach (array_slice($argv, 1) as $argument) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/', $argument, $treffer) === 1) {
        $optionen[$treffer[1]] = $treffer[2] ?? '1';
    }
}

$app = new Application(Paths::fromEnvironment(PEGELSTAND_ROOT));
$db = $app->database();
if ($db === null) {
    fwrite(STDERR, "Pegelstand ist noch nicht installiert.\n");
    exit(1);
}
$app->migrate($db);

if (isset($optionen['create-site'])) {
    $publicId = bin2hex(random_bytes(8));
    $db->run(
        'INSERT INTO ' . $db->table('sites') . ' (public_id, name, domain, created_at) VALUES (?, ?, ?, ?)',
        [$publicId, 'Demo', 'demo.example', gmdate('Y-m-d H:i:s')],
    );
    $siteId = (int) $db->pdo->lastInsertId();
    echo "Site \"Demo\" angelegt (ID $siteId, öffentliche ID $publicId).\n";
} else {
    $siteId = (int) ($optionen['site'] ?? 0);
    if ($siteId < 1 || $db->fetchInt('SELECT COUNT(*) FROM ' . $db->table('sites') . ' WHERE id = ?', [$siteId]) === 0) {
        fwrite(STDERR, "Gib --site=ID einer vorhandenen Site an oder nutze --create-site.\n");
        exit(1);
    }
}

$tage = max(1, (int) ($optionen['days'] ?? 90));
$sitzungen = max(1, (int) ($optionen['sessions'] ?? 300));
$seed = (int) ($optionen['seed'] ?? 1);
$utc = new DateTimeZone('UTC');
$heute = new DateTimeImmutable('today', $utc);

$start = microtime(true);
$summe = (new DemoDataGenerator($db))->generate($siteId, $heute->modify('-' . ($tage - 1) . ' days'), $heute, $sitzungen, $seed);
printf("%d Sitzungen und %d Ereignisse in %.1f s erzeugt.\n", $summe['sessions'], $summe['events'], microtime(true) - $start);

if (!isset($optionen['no-aggregate'])) {
    $start = microtime(true);
    $ergebnis = $app->scheduler($db)->runDue(true);
    printf("Aggregation in %.1f s: %s\n", microtime(true) - $start, $ergebnis['aggregate'] ?? '-');
}
