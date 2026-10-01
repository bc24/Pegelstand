<?php

declare(strict_types=1);

// Misst Erfassung, Aggregation und typische Abfragen gegen die konfigurierte Datenbank.
// ACHTUNG: legt eine eigene Site "Benchmark" mit Beispieldaten an und entfernt sie danach wieder.
//   php bin/benchmark.php --sessions=200000 --days=30 --ingest=500
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('PEGELSTAND_ROOT', dirname(__DIR__));
require PEGELSTAND_ROOT . '/vendor/autoload.php';

use Pegelstand\Core\Application;
use Pegelstand\Core\Paths;
use Pegelstand\Core\Request;
use Pegelstand\Demo\DemoDataGenerator;
use Pegelstand\Geo\NullCountryLookup;
use Pegelstand\Ingest\BotFilter;
use Pegelstand\Ingest\ClientIp;
use Pegelstand\Ingest\Collector;
use Pegelstand\Ingest\Dictionary;
use Pegelstand\Ingest\RateLimiter;
use Pegelstand\Ingest\SaltService;
use Pegelstand\Ingest\UrlParser;
use Pegelstand\Ingest\UserAgentParser;
use Pegelstand\Jobs\AggregationJob;
use Pegelstand\Stats\Aggregator;

$optionen = [];
foreach (array_slice($argv, 1) as $argument) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/', $argument, $treffer) === 1) {
        $optionen[$treffer[1]] = $treffer[2] ?? '1';
    }
}
$sitzungen = max(1, (int) ($optionen['sessions'] ?? 50000));
$tage = max(1, (int) ($optionen['days'] ?? 30));
$aufrufe = max(1, (int) ($optionen['ingest'] ?? 500));

$app = new Application(Paths::fromEnvironment(PEGELSTAND_ROOT));
$db = $app->database();
if ($db === null) {
    fwrite(STDERR, "Pegelstand ist noch nicht installiert.\n");
    exit(1);
}
$app->migrate($db);
$ms = static fn(float $start): string => sprintf('%.1f ms', (microtime(true) - $start) * 1000);

$publicId = bin2hex(random_bytes(8));
$db->run('INSERT INTO ' . $db->table('sites') . ' (public_id, name, domain, created_at) VALUES (?, ?, ?, ?)', [$publicId, 'Benchmark', 'benchmark.example', gmdate('Y-m-d H:i:s')]);
$siteId = (int) $db->pdo->lastInsertId();

try {
    echo "Server: " . $db->serverVersion() . ", PHP " . PHP_VERSION . "\n";

    // 1. Erfassung: einzelne Aufrufe nacheinander durch den Collector (ohne HTTP, ohne GeoIP).
    $collector = new Collector(
        $db,
        new SaltService($db, new DateTimeZone('Europe/Berlin')),
        new Dictionary($db),
        new UrlParser(),
        new UserAgentParser(),
        BotFilter::fromFile(PEGELSTAND_ROOT . '/resources/data/bots.php'),
        new NullCountryLookup(),
        new RateLimiter($db),
        new ClientIp(),
        1_000_000,
    );
    $zeiten = [];
    for ($i = 0; $i < $aufrufe; ++$i) {
        $anfrage = new Request('POST', '/api/event', headers: ['user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 b' . ($i % 400)], ip: '198.51.100.' . ($i % 250), body: json_encode(['s' => $publicId, 'u' => 'https://benchmark.example/seite' . ($i % 40)], JSON_THROW_ON_ERROR));
        $start = microtime(true);
        $collector->collect($anfrage);
        $zeiten[] = (microtime(true) - $start) * 1000;
    }
    sort($zeiten);
    printf("Erfassung (%d Aufrufe, leere Tabellen): Median %.2f ms, 95. Perzentil %.2f ms, Maximum %.2f ms\n", $aufrufe, $zeiten[intdiv(count($zeiten), 2)], $zeiten[(int) (count($zeiten) * 0.95)], $zeiten[count($zeiten) - 1]);

    // 2. Datenmenge erzeugen.
    $heute = new DateTimeImmutable('today', new DateTimeZone('UTC'));
    $start = microtime(true);
    $summe = (new DemoDataGenerator($db))->generate($siteId, $heute->modify('-' . ($tage - 1) . ' days'), $heute, intdiv($sitzungen, $tage), 42);
    printf("Erzeugt: %d Sitzungen, %d Ereignisse in %.1f s\n", $summe['sessions'], $summe['events'], microtime(true) - $start);

    // 3. Erfassung gegen gefüllte Tabellen.
    $zeiten = [];
    for ($i = 0; $i < $aufrufe; ++$i) {
        $anfrage = new Request('POST', '/api/event', headers: ['user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 c' . ($i % 400)], ip: '203.0.113.' . ($i % 250), body: json_encode(['s' => $publicId, 'u' => 'https://benchmark.example/seite' . ($i % 40)], JSON_THROW_ON_ERROR));
        $start = microtime(true);
        $collector->collect($anfrage);
        $zeiten[] = (microtime(true) - $start) * 1000;
    }
    sort($zeiten);
    printf("Erfassung (%d Aufrufe, %d Ereignisse in der Tabelle): Median %.2f ms, 95. Perzentil %.2f ms, Maximum %.2f ms\n", $aufrufe, $summe['events'], $zeiten[intdiv(count($zeiten), 2)], $zeiten[(int) (count($zeiten) * 0.95)], $zeiten[count($zeiten) - 1]);

    // 4. Aggregation.
    $start = microtime(true);
    $meldung = (new AggregationJob($db, new Aggregator($db)))->run(new DateTimeImmutable('now', new DateTimeZone('UTC')));
    printf("Aggregation (%s): %.1f s\n", $meldung, microtime(true) - $start);

    // 5. Abfragen, wie das Dashboard sie braucht: aus Aggregaten und zum Vergleich aus den Rohdaten.
    $von = $heute->modify('-' . ($tage - 1) . ' days')->format('Y-m-d');
    $bis = $heute->format('Y-m-d');
    $abfragen = [
        'Kennzahlen aus Tagesaggregaten' => ['SELECT SUM(visitors), SUM(sessions), SUM(pageviews), SUM(bounces), SUM(duration_sum) FROM ' . $db->table('agg_daily') . ' WHERE site_id = ? AND day BETWEEN ? AND ?', [$siteId, $von, $bis]],
        'Diagramm Tage aus Aggregaten' => ['SELECT day, visitors, pageviews FROM ' . $db->table('agg_daily') . ' WHERE site_id = ? AND day BETWEEN ? AND ? ORDER BY day', [$siteId, $von, $bis]],
        'Top-Seiten aus Aggregaten' => ['SELECT d.value, SUM(a.hits) h FROM ' . $db->table('agg_daily_dim') . ' a JOIN ' . $db->table('dict_path') . ' d ON d.id = a.value_id WHERE a.site_id = ? AND a.dim = 1 AND a.day BETWEEN ? AND ? GROUP BY a.value_id ORDER BY h DESC LIMIT 10', [$siteId, $von, $bis]],
        'Kennzahlen aus Rohdaten (Vergleich)' => ['SELECT COUNT(DISTINCT visitor_hash), COUNT(*), SUM(pageviews) FROM ' . $db->table('sessions') . ' WHERE site_id = ? AND started_at >= ? AND started_at < ?', [$siteId, $von . ' 00:00:00', $bis . ' 23:59:59']],
        'Aktive Besucher (letzte 5 Minuten)' => ['SELECT COUNT(DISTINCT visitor_hash) FROM ' . $db->table('sessions') . ' WHERE site_id = ? AND last_seen_at >= ?', [$siteId, gmdate('Y-m-d H:i:s', time() - 300)]],
    ];
    foreach ($abfragen as $name => [$sql, $parameter]) {
        $start = microtime(true);
        $db->fetchAll($sql, $parameter);
        printf("%-42s %s\n", $name . ':', $ms($start));
    }

    $groesse = $db->fetchAll(
        'SELECT table_name AS t, ROUND((data_length + index_length) / 1048576, 1) AS mb FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE ? AND table_name IN (?, ?, ?, ?)',
        [str_replace('_', '\\_', $db->prefix) . '%', $db->prefix . 'sessions', $db->prefix . 'events', $db->prefix . 'agg_daily_dim', $db->prefix . 'dict_path'],
    );
    foreach ($groesse as $zeile) {
        $name = is_string($zeile['t']) ? $zeile['t'] : '';
        $mb = is_numeric($zeile['mb']) ? (float) $zeile['mb'] : 0.0;
        printf("Größe %-24s %8.1f MB\n", $name, $mb);
    }
} finally {
    $db->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach (['events' => 'site_id', 'sessions' => 'site_id', 'agg_hourly' => 'site_id', 'agg_daily' => 'site_id', 'agg_daily_dim' => 'site_id', 'agg_daily_prop' => 'site_id'] as $tabelle => $spalte) {
        do {
            $anzahl = $db->run('DELETE FROM ' . $db->table($tabelle) . ' WHERE ' . $spalte . ' = ? LIMIT 50000', [$siteId])->rowCount();
        } while ($anzahl > 0);
    }
    $db->run('DELETE FROM ' . $db->table('event_props') . ' WHERE event_id NOT IN (SELECT id FROM ' . $db->table('events') . ')');
    $db->run('DELETE FROM ' . $db->table('settings') . ' WHERE name = ?', [AggregationJob::cursorKey($siteId)]);
    $db->run('DELETE FROM ' . $db->table('sites') . ' WHERE id = ?', [$siteId]);
    $db->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}
