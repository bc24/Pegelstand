<?php

declare(strict_types=1);

// Räumt Tabellen früherer E2E-Läufe (Präfix "e2e") aus der Test-Datenbank. Nur für Entwicklung und CI.
$dsn = getenv('PEGELSTAND_TEST_DB_DSN');
if ($dsn === false || $dsn === '') {
    exit(0);
}

$pdo = new PDO($dsn, getenv('PEGELSTAND_TEST_DB_USER') ?: null, getenv('PEGELSTAND_TEST_DB_PASSWORD') ?: null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
$tabellen = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE 'e2e%'");
foreach ($tabellen === false ? [] : $tabellen->fetchAll(PDO::FETCH_COLUMN) as $name) {
    if (is_string($name)) {
        $pdo->exec('DROP TABLE IF EXISTS `' . $name . '`');
    }
}
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

// Optional: eine fertig installierte Instanz mit erfundenen Daten für die Dashboard-Tests (Argument: Arbeitsverzeichnis).
$verzeichnis = $argv[1] ?? null;
if ($verzeichnis === null) {
    exit(0);
}
define('PEGELSTAND_ROOT', dirname(__DIR__));
require PEGELSTAND_ROOT . '/vendor/autoload.php';

use Pegelstand\Core\Application;
use Pegelstand\Core\Paths;
use Pegelstand\Database\Database;
use Pegelstand\Demo\DemoDataGenerator;
use Pegelstand\Install\AdminInput;
use Pegelstand\Install\DatabaseInput;
use Pegelstand\Install\Installer;

preg_match('/host=([^;]+)/', $dsn, $host);
preg_match('/port=(\d+)/', $dsn, $port);
preg_match('/dbname=([^;]+)/', $dsn, $name);
$zugang = [
    'host' => $host[1] ?? '127.0.0.1',
    'port' => (int) ($port[1] ?? 3306),
    'name' => $name[1] ?? 'pegelstand_test',
    'user' => getenv('PEGELSTAND_TEST_DB_USER') ?: '',
    'password' => getenv('PEGELSTAND_TEST_DB_PASSWORD') ?: '',
];
$paths = new Paths(PEGELSTAND_ROOT, $verzeichnis . '/config', $verzeichnis . '/storage');
(new Installer($paths))->install(
    new DatabaseInput($zugang['host'], $zugang['port'], $zugang['name'], $zugang['user'], $zugang['password'], 'e2elive_'),
    new AdminInput('Frank E2E', 'e2e@beispiel.de', 'ein sehr langer satz', 'ein sehr langer satz'),
);
// Viele parallele Testanmeldungen von derselben Adresse: Sperre für diese Testinstanz lockern.
$konfig = (string) file_get_contents($paths->configFile());
file_put_contents($paths->configFile(), str_replace('return array (', "return array (\n  'login' => ['rate_limit' => 10000],", $konfig));
$db = Database::connect([...$zugang, 'prefix' => 'e2elive_']);
foreach ([['livedemo00000001', 'beispiel.de', 120], ['liveleer00000002', 'neue-seite.de', 0]] as [$publicId, $domain, $sitzungen]) {
    $db->run(
        'INSERT INTO ' . $db->table('sites') . ' (public_id, name, domain, created_at) VALUES (?, ?, ?, ?)',
        [$publicId, $domain, $domain, gmdate('Y-m-d H:i:s')],
    );
    if ($sitzungen > 0) {
        $heute = new DateTimeImmutable('today', new DateTimeZone('UTC'));
        $siteNr = (int) $db->pdo->lastInsertId();
        (new DemoDataGenerator($db))->generate($siteNr, $heute->modify('-59 days'), $heute, $sitzungen, 7);
        $db->run('UPDATE ' . $db->table('sites') . " SET public_token = 'abcdef0123456789abcdef0123456789' WHERE id = ?", [$siteNr]);
        $db->run('INSERT INTO ' . $db->table('goals') . " (site_id, name, kind, target) VALUES (?, 'Preise angesehen', 'page', '/preise'), (?, 'Anmeldung', 'event', 'Signup')", [$siteNr, $siteNr]);
    }
}
$app = new Application($paths);
for ($i = 0; $i < 4; ++$i) {
    $app->scheduler($db)->runDue(true); // holt je Lauf bis zu 31 Tage nach
}
echo "Live-Instanz bereit.\n";
