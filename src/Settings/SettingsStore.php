<?php

declare(strict_types=1);

namespace Pegelstand\Settings;

use Pegelstand\Auth\Crypto;
use Pegelstand\Database\Database;

/** Zur Laufzeit änderbare Einstellungen in der Tabelle `settings`. Geheimnisse werden verschlüsselt abgelegt. */
final class SettingsStore
{
    public function __construct(
        private readonly Database $db,
        private readonly Crypto $crypto,
    ) {}

    public function get(string $name, string $default = ''): string
    {
        $v = $this->db->fetchValue('SELECT value FROM ' . $this->db->table('settings') . ' WHERE name = ?', [$name]);

        return is_string($v) ? $v : $default;
    }

    public function set(string $name, string $value): void
    {
        $jetzt = gmdate('Y-m-d H:i:s');
        $this->db->run(
            'INSERT INTO ' . $this->db->table('settings') . ' (name, value, updated_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = ?, updated_at = ?',
            [$name, $value, $jetzt, $value, $jetzt],
        );
    }

    public function getSecret(string $name): string
    {
        $roh = base64_decode($this->get($name), true);

        return $roh === false || $roh === '' ? '' : ($this->crypto->decrypt($roh) ?? '');
    }

    public function setSecret(string $name, string $klartext): void
    {
        $this->set($name, $klartext === '' ? '' : base64_encode($this->crypto->encrypt($klartext)));
    }
}
