<?php

declare(strict_types=1);

namespace Pegelstand\Ingest;

use Pegelstand\Database\Database;

/**
 * Wörterbücher: Jeder Text steht nur einmal in der Datenbank, die Datentabellen enthalten Zahlen.
 * Schlüssel ist UNHEX(MD5(wert)). Das hält den Index auch bei langen Pfaden klein.
 */
final class Dictionary
{
    public const TABLES = ['path', 'referrer', 'utm', 'browser', 'os', 'event_name', 'prop_key', 'prop_value'];

    /** @var array<string, int> */
    private array $cache = [];

    public function __construct(private readonly Database $db) {}

    public function id(string $table, string $value): int
    {
        if (!in_array($table, self::TABLES, true)) {
            throw new \InvalidArgumentException('Unbekanntes Wörterbuch: ' . $table);
        }
        $schluessel = $table . "\0" . $value;
        if (isset($this->cache[$schluessel])) {
            return $this->cache[$schluessel];
        }

        $name = $this->db->table('dict_' . $table);
        $suche = 'SELECT id FROM ' . $name . ' WHERE value_hash = UNHEX(MD5(?)) AND value = ?';
        $id = $this->db->fetchInt($suche, [$value, $value]);
        if ($id === 0) {
            $this->db->run('INSERT IGNORE INTO ' . $name . ' (value_hash, value) VALUES (UNHEX(MD5(?)), ?)', [$value, $value]);
            $id = $this->db->fetchInt($suche, [$value, $value]);
            if ($id === 0) {
                // Hash-Kollision mit einem anderen Text: den vorhandenen Eintrag verwenden.
                $id = $this->db->fetchInt('SELECT id FROM ' . $name . ' WHERE value_hash = UNHEX(MD5(?))', [$value]);
            }
        }

        return $this->cache[$schluessel] = $id;
    }
}
