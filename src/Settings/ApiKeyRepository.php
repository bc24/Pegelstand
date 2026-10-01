<?php

declare(strict_types=1);

namespace Pegelstand\Settings;

use Pegelstand\Database\Database;

/**
 * API-Schlüssel eines Benutzers. Der Klartext ist nur beim Anlegen sichtbar, in der Datenbank liegt der SHA-256-Hash.
 *
 * @phpstan-type KeyRow array{id: int, name: string, key_prefix: string, created_at: string, last_used_at: string}
 */
final class ApiKeyRepository
{
    public const PREFIX = 'psk_';

    public function __construct(private readonly Database $db) {}

    /** @return string Der neue Schlüssel im Klartext */
    public function create(int $userId, string $name): string
    {
        $schluessel = self::PREFIX . bin2hex(random_bytes(20));
        $this->db->run(
            'INSERT INTO ' . $this->db->table('api_keys') . ' (user_id, name, key_prefix, key_hash, created_at) VALUES (?, ?, ?, ?, ?)',
            [$userId, $name, substr($schluessel, 0, 8), hash('sha256', $schluessel), gmdate('Y-m-d H:i:s')],
        );

        return $schluessel;
    }

    /**
     * @return list<KeyRow>
     */
    public function forUser(int $userId): array
    {
        return array_map(static fn(array $z): array => [
            'id' => is_numeric($z['id']) ? (int) $z['id'] : 0,
            'name' => is_string($z['name']) ? $z['name'] : '',
            'key_prefix' => is_string($z['key_prefix']) ? $z['key_prefix'] : '',
            'created_at' => is_string($z['created_at']) ? $z['created_at'] : '',
            'last_used_at' => is_string($z['last_used_at']) ? $z['last_used_at'] : '',
        ], $this->db->fetchAll('SELECT id, name, key_prefix, created_at, last_used_at FROM ' . $this->db->table('api_keys') . ' WHERE user_id = ? ORDER BY id', [$userId]));
    }

    public function delete(int $userId, int $id): void
    {
        $this->db->run('DELETE FROM ' . $this->db->table('api_keys') . ' WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    /**
     * Sucht den Benutzer zu einem Schlüssel (nur aktive Benutzer) und merkt sich die Nutzung.
     *
     * @return array{user_id: int, key_id: int}|null
     */
    public function authenticate(string $schluessel): ?array
    {
        if (!str_starts_with($schluessel, self::PREFIX) || strlen($schluessel) > 100) {
            return null;
        }
        $z = $this->db->fetchAll(
            'SELECT k.id, k.user_id FROM ' . $this->db->table('api_keys') . ' k JOIN ' . $this->db->table('users') . ' u ON u.id = k.user_id'
            . ' WHERE k.key_hash = ? AND u.disabled_at IS NULL',
            [hash('sha256', $schluessel)],
        )[0] ?? null;
        if ($z === null || !is_numeric($z['id']) || !is_numeric($z['user_id'])) {
            return null;
        }
        $this->db->run('UPDATE ' . $this->db->table('api_keys') . ' SET last_used_at = ? WHERE id = ?', [gmdate('Y-m-d H:i:s'), (int) $z['id']]);

        return ['user_id' => (int) $z['user_id'], 'key_id' => (int) $z['id']];
    }
}
