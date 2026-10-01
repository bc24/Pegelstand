<?php

declare(strict_types=1);

namespace Pegelstand\Settings;

use Pegelstand\Database\Database;
use Pegelstand\Install\PasswordHasher;

/**
 * Benutzer verwalten. Der letzte aktive Administrator lässt sich weder herabstufen noch sperren.
 *
 * @phpstan-type UserRow array{id: int, name: string, email: string, role: string, disabled: bool, last_login_at: string, sites: list<int>}
 */
final class UserRepository
{
    public function __construct(private readonly Database $db) {}

    /**
     * @return list<UserRow>
     */
    public function all(): array
    {
        $zuordnung = [];
        foreach ($this->db->fetchAll('SELECT user_id, site_id FROM ' . $this->db->table('site_users')) as $z) {
            $zuordnung[is_numeric($z['user_id']) ? (int) $z['user_id'] : 0][] = is_numeric($z['site_id']) ? (int) $z['site_id'] : 0;
        }

        return array_map(fn(array $z): array => $this->row($z, $zuordnung), $this->db->fetchAll(
            'SELECT id, name, email, role, disabled_at, last_login_at FROM ' . $this->db->table('users') . ' ORDER BY name, id',
        ));
    }

    /**
     * @return UserRow|null
     */
    public function find(int $id): ?array
    {
        foreach ($this->all() as $u) {
            if ($u['id'] === $id) {
                return $u;
            }
        }

        return null;
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        return $this->db->fetchInt(
            'SELECT COUNT(*) FROM ' . $this->db->table('users') . ' WHERE email = ? AND id <> ?',
            [mb_strtolower($email), $exceptId ?? 0],
        ) > 0;
    }

    /**
     * @param list<int> $siteIds
     */
    public function create(string $name, string $email, string $password, string $role, array $siteIds): int
    {
        $this->db->run(
            'INSERT INTO ' . $this->db->table('users') . ' (email, name, password_hash, role, created_at) VALUES (?, ?, ?, ?, ?)',
            [mb_strtolower($email), $name, PasswordHasher::hash($password), $role === 'admin' ? 'admin' : 'viewer', gmdate('Y-m-d H:i:s')],
        );
        $id = (int) $this->db->pdo->lastInsertId();
        $this->setSites($id, $siteIds);

        return $id;
    }

    /**
     * @param list<int> $siteIds
     * @return bool false, wenn damit der letzte aktive Administrator verloren ginge
     */
    public function update(int $id, string $name, string $role, bool $disabled, array $siteIds): bool
    {
        $role = $role === 'admin' ? 'admin' : 'viewer';
        if (($role !== 'admin' || $disabled) && $this->isLastAdmin($id)) {
            return false;
        }
        $this->db->run(
            'UPDATE ' . $this->db->table('users') . ' SET name = ?, role = ?, disabled_at = ? WHERE id = ?',
            [$name, $role, $disabled ? gmdate('Y-m-d H:i:s') : null, $id],
        );
        $this->setSites($id, $siteIds);

        return true;
    }

    public function setPassword(int $id, string $password): void
    {
        $this->db->run('UPDATE ' . $this->db->table('users') . ' SET password_hash = ? WHERE id = ?', [PasswordHasher::hash($password), $id]);
    }

    public function passwordHash(int $id): string
    {
        $h = $this->db->fetchValue('SELECT password_hash FROM ' . $this->db->table('users') . ' WHERE id = ?', [$id]);

        return is_string($h) ? $h : '';
    }

    /** @return bool false beim letzten aktiven Administrator */
    public function delete(int $id): bool
    {
        if ($this->isLastAdmin($id)) {
            return false;
        }
        $this->db->run('DELETE FROM ' . $this->db->table('users') . ' WHERE id = ?', [$id]);

        return true;
    }

    private function isLastAdmin(int $id): bool
    {
        $admins = $this->db->fetchAll("SELECT id FROM " . $this->db->table('users') . " WHERE role = 'admin' AND disabled_at IS NULL");
        $ids = array_map(static fn(array $z): int => is_numeric($z['id']) ? (int) $z['id'] : 0, $admins);

        return in_array($id, $ids, true) && count($ids) === 1;
    }

    /**
     * @param list<int> $siteIds
     */
    private function setSites(int $userId, array $siteIds): void
    {
        $this->db->run('DELETE FROM ' . $this->db->table('site_users') . ' WHERE user_id = ?', [$userId]);
        foreach (array_unique($siteIds) as $siteId) {
            $this->db->run(
                'INSERT INTO ' . $this->db->table('site_users') . ' (site_id, user_id) SELECT id, ? FROM ' . $this->db->table('sites') . ' WHERE id = ?',
                [$userId, $siteId],
            );
        }
    }

    /**
     * @param array<string, mixed> $z
     * @param array<int, list<int>> $zuordnung
     * @return UserRow
     */
    private function row(array $z, array $zuordnung): array
    {
        $id = is_numeric($z['id']) ? (int) $z['id'] : 0;

        return [
            'id' => $id,
            'name' => is_string($z['name']) ? $z['name'] : '',
            'email' => is_string($z['email']) ? $z['email'] : '',
            'role' => is_string($z['role']) ? $z['role'] : 'viewer',
            'disabled' => $z['disabled_at'] !== null,
            'last_login_at' => is_string($z['last_login_at']) ? $z['last_login_at'] : '',
            'sites' => $zuordnung[$id] ?? [],
        ];
    }
}
