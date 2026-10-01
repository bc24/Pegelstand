<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Integration;

use Pegelstand\Database\Database;
use Pegelstand\Database\Migrator;
use Pegelstand\Ingest\Dictionary;

/**
 * Migrierte Datenbank mit einer Site (ID 1, Europe/Berlin) und Hilfen, um Rohdaten gezielt anzulegen.
 */
abstract class SiteTestCase extends DatenbankTestCase
{
    protected Database $db;
    protected Dictionary $dict;
    private int $sitzungId = 0;
    private int $ereignisId = 0;

    protected function setUp(): void
    {
        $this->db = $this->datenbank();
        (new Migrator($this->db, PEGELSTAND_ROOT . '/database/migrations'))->migrate();
        $this->db->run(
            'INSERT INTO ' . $this->db->table('sites') . ' (public_id, name, domain, timezone, retention_days, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            ['abcd1234abcd1234', 'Test', 'beispiel.de', 'Europe/Berlin', 30, '2026-01-01 00:00:00'],
        );
        $this->dict = new Dictionary($this->db);
    }

    /**
     * Legt eine Sitzung mit Seitenaufrufen (und optional einem Ereignis) an.
     *
     * @param list<string> $pfade Pfade der Seitenaufrufe, je 30 Sekunden Abstand
     * @param array{besucher?: string, land?: ?string, geraet?: int, browser?: ?string, os?: ?string, referrer?: ?string, utm_source?: ?string, ereignis?: ?string, props?: array<string, string>, site?: int} $optionen
     */
    protected function sitzung(string $beginnUtc, array $pfade, array $optionen = []): int
    {
        $site = $optionen['site'] ?? 1;
        $besucher = hash('sha256', $optionen['besucher'] ?? ('b' . ++$this->sitzungId), true);
        $beginn = (int) strtotime($beginnUtc . ' UTC');
        $ende = $beginn + 30 * (count($pfade) - 1);
        $ereignis = $optionen['ereignis'] ?? null;
        if ($ereignis !== null) {
            $ende += 5;
        }
        $id = ++$this->sitzungId + 1000;
        $this->db->run(
            'INSERT INTO ' . $this->db->table('sessions')
            . ' (id, site_id, visitor_hash, started_at, last_seen_at, entry_path_id, exit_path_id, pageviews, custom_events, referrer_id, utm_source_id, country, device, browser_id, os_id)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $id, $site, substr($besucher, 0, 16), gmdate('Y-m-d H:i:s', $beginn), gmdate('Y-m-d H:i:s', $ende),
                $this->dict->id('path', $pfade[0]), $this->dict->id('path', $pfade[count($pfade) - 1]), count($pfade), $ereignis !== null ? 1 : 0,
                isset($optionen['referrer']) ? $this->dict->id('referrer', $optionen['referrer']) : null,
                isset($optionen['utm_source']) ? $this->dict->id('utm', $optionen['utm_source']) : null,
                $optionen['land'] ?? null, $optionen['geraet'] ?? 1,
                isset($optionen['browser']) ? $this->dict->id('browser', $optionen['browser']) : null,
                isset($optionen['os']) ? $this->dict->id('os', $optionen['os']) : null,
            ],
        );
        foreach ($pfade as $i => $pfad) {
            $this->db->run(
                'INSERT INTO ' . $this->db->table('events') . ' (id, site_id, session_id, occurred_at, kind, path_id) VALUES (?, ?, ?, ?, 1, ?)',
                [++$this->ereignisId, $site, $id, gmdate('Y-m-d H:i:s', $beginn + 30 * $i), $this->dict->id('path', $pfad)],
            );
        }
        if ($ereignis !== null) {
            $this->db->run(
                'INSERT INTO ' . $this->db->table('events') . ' (id, site_id, session_id, occurred_at, kind, path_id, name_id) VALUES (?, ?, ?, ?, 2, ?, ?)',
                [++$this->ereignisId, $site, $id, gmdate('Y-m-d H:i:s', $ende), $this->dict->id('path', $pfade[count($pfade) - 1]), $this->dict->id('event_name', $ereignis)],
            );
            foreach ($optionen['props'] ?? [] as $schluessel => $wert) {
                $this->db->run(
                    'INSERT INTO ' . $this->db->table('event_props') . ' (event_id, key_id, value_id) VALUES (?, ?, ?)',
                    [$this->ereignisId, $this->dict->id('prop_key', $schluessel), $this->dict->id('prop_value', $wert)],
                );
            }
        }

        return $id;
    }

    protected function zaehle(string $tabelle, string $where = '1=1'): int
    {
        return $this->db->fetchInt('SELECT COUNT(*) FROM ' . $this->db->table($tabelle) . ' WHERE ' . $where);
    }
}
