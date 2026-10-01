<?php

declare(strict_types=1);

namespace Pegelstand\Auth;

use DateTimeImmutable;
use Pegelstand\Core\Session;
use Pegelstand\Database\Database;

/** Anmeldung per E-Mail und Passwort, getragen von der Sitzung. */
final class AuthService
{
    private const KEY = 'auth.user_id';

    private ?AuthUser $cache = null;

    public function __construct(
        private readonly Database $db,
        private readonly Session $session,
    ) {}

    /**
     * Prüft die Zugangsdaten und meldet an. Der Zeitaufwand ist auch bei unbekannter E-Mail-Adresse gleich,
     * damit sich nicht erraten lässt, welche Adressen es gibt.
     */
    public function attempt(string $email, string $password, DateTimeImmutable $now): ?AuthUser
    {
        $zeilen = $this->db->fetchAll(
            'SELECT id, name, email, role, password_hash FROM ' . $this->db->table('users') . ' WHERE email = ? AND disabled_at IS NULL',
            [mb_strtolower(trim($email))],
        );
        $zeile = $zeilen[0] ?? null;
        $hash = $zeile !== null && is_string($zeile['password_hash']) ? $zeile['password_hash'] : self::dummyHash();
        $stimmt = password_verify($password, $hash);
        if ($zeile === null || !$stimmt) {
            return null;
        }

        $this->session->regenerate();
        $id = is_numeric($zeile['id']) ? (int) $zeile['id'] : 0;
        $this->session->set(self::KEY, $id);
        $this->db->run('UPDATE ' . $this->db->table('users') . ' SET last_login_at = ? WHERE id = ?', [gmdate('Y-m-d H:i:s', $now->getTimestamp()), $id]);
        $this->cache = null;

        return $this->user();
    }

    public function user(): ?AuthUser
    {
        if ($this->cache !== null) {
            return $this->cache;
        }
        $id = $this->session->get(self::KEY);
        if (!is_int($id)) {
            return null;
        }
        $zeilen = $this->db->fetchAll(
            'SELECT id, name, email, role FROM ' . $this->db->table('users') . ' WHERE id = ? AND disabled_at IS NULL',
            [$id],
        );
        $zeile = $zeilen[0] ?? null;
        if ($zeile === null) {
            $this->session->remove(self::KEY);

            return null;
        }

        return $this->cache = new AuthUser(
            $id,
            is_string($zeile['name']) ? $zeile['name'] : '',
            is_string($zeile['email']) ? $zeile['email'] : '',
            is_string($zeile['role']) ? $zeile['role'] : 'viewer',
        );
    }

    public function logout(): void
    {
        $this->cache = null;
        $this->session->destroy();
    }

    /**
     * Sites, die der Benutzer sehen darf: Administratoren alle, andere die ihnen zugeordneten.
     *
     * @return list<array{id: int, public_id: string, name: string, domain: string, timezone: string, created_at: string}>
     */
    public function sites(AuthUser $user): array
    {
        $sql = 'SELECT s.id, s.public_id, s.name, s.domain, s.timezone, s.created_at FROM ' . $this->db->table('sites') . ' s';
        $params = [];
        if (!$user->isAdmin()) {
            $sql .= ' JOIN ' . $this->db->table('site_users') . ' su ON su.site_id = s.id AND su.user_id = ?';
            $params[] = $user->id;
        }
        $ergebnis = [];
        foreach ($this->db->fetchAll($sql . ' ORDER BY s.name, s.id', $params) as $z) {
            $ergebnis[] = [
                'id' => is_numeric($z['id']) ? (int) $z['id'] : 0,
                'public_id' => is_string($z['public_id']) ? $z['public_id'] : '',
                'name' => is_string($z['name']) ? $z['name'] : '',
                'domain' => is_string($z['domain']) ? $z['domain'] : '',
                'timezone' => is_string($z['timezone']) ? $z['timezone'] : 'UTC',
                'created_at' => is_string($z['created_at']) ? $z['created_at'] : '',
            ];
        }

        return $ergebnis;
    }

    /**
     * Fester Hash eines nicht verwendbaren Passworts. Er sorgt dafür, dass die Prüfung bei unbekannter
     * E-Mail-Adresse etwa so lange dauert wie bei einer bekannten (bcrypt, Kostenfaktor 12).
     */
    private static function dummyHash(): string
    {
        return '$2y$12$VAQVeBCXM5Ll3iSlU7lvoOxAbXNRd8eN1vDMB4aqznWfm4cEhnRkm';
    }
}
