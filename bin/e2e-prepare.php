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
