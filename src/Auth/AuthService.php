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

    private const PENDING = 'auth.pending';
    private const SETUP = 'auth.totp_setup';
    private const PENDING_SECONDS = 600;

    public function __construct(
        private readonly Database $db,
        private readonly Session $session,
        private readonly Crypto $crypto,
    ) {}

    /**
     * Prüft die Zugangsdaten und meldet an. Der Zeitaufwand ist auch bei unbekannter E-Mail-Adresse gleich,
     * damit sich nicht erraten lässt, welche Adressen es gibt.
     */
    public function attempt(string $email, string $password, DateTimeImmutable $now): string
    {
        $zeilen = $this->db->fetchAll(
            'SELECT id, name, email, role, password_hash, totp_enabled_at FROM ' . $this->db->table('users') . ' WHERE email = ? AND disabled_at IS NULL',
            [mb_strtolower(trim($email))],
        );
        $zeile = $zeilen[0] ?? null;
        $hash = $zeile !== null && is_string($zeile['password_hash']) ? $zeile['password_hash'] : self::dummyHash();
        $stimmt = password_verify($password, $hash);
        if ($zeile === null || !$stimmt) {
            return 'fail';
        }

        $this->session->regenerate();
        $id = is_numeric($zeile['id']) ? (int) $zeile['id'] : 0;
        if ($zeile['totp_enabled_at'] !== null) {
            $this->session->set(self::PENDING, ['id' => $id, 'bis' => $now->getTimestamp() + self::PENDING_SECONDS]);

            return 'totp';
        }
        $this->anmelden($id, $now);

        return 'ok';
    }

    /** Wartet die Anmeldung auf den zweiten Faktor? */
    public function hatAusstehendenCode(DateTimeImmutable $now): bool
    {
        return $this->pendingId($now) !== null;
    }

    /** @return bool true, wenn der Code stimmt und der Benutzer jetzt angemeldet ist */
    public function completeTotp(string $code, DateTimeImmutable $now): bool
    {
        $id = $this->pendingId($now);
        if ($id === null || !$this->pruefeCode($id, $code, $now)) {
            return false;
        }
        $this->session->remove(self::PENDING);
        $this->anmelden($id, $now);

        return true;
    }

    public function totpActive(int $userId): bool
    {
        return $this->db->fetchValue('SELECT totp_enabled_at FROM ' . $this->db->table('users') . ' WHERE id = ?', [$userId]) !== null;
    }

    /** Beginnt die Einrichtung und liefert das Geheimnis (bis zur Bestätigung nur in der Sitzung). */
    public function beginTotpSetup(): string
    {
        $secret = $this->session->get(self::SETUP);
        if (!is_string($secret)) {
            $secret = Totp::generateSecret();
            $this->session->set(self::SETUP, $secret);
        }

        return $secret;
    }

    public function pendingTotpSecret(): ?string
    {
        $s = $this->session->get(self::SETUP);

        return is_string($s) ? $s : null;
    }

    public function confirmTotpSetup(int $userId, string $code, DateTimeImmutable $now): bool
    {
        $secret = $this->pendingTotpSecret();
        $fenster = $secret === null ? null : Totp::verify($secret, $code, $now->getTimestamp());
        if ($secret === null || $fenster === null) {
            return false;
        }
        $this->db->run(
            'UPDATE ' . $this->db->table('users') . ' SET totp_secret = ?, totp_enabled_at = ? WHERE id = ?',
            [$this->crypto->encrypt($secret), gmdate('Y-m-d H:i:s', $now->getTimestamp()), $userId],
        );
        $this->merkeFenster($userId, $fenster);
        $this->session->remove(self::SETUP);

        return true;
    }

    public function cancelTotpSetup(): void
    {
        $this->session->remove(self::SETUP);
    }

    public function disableTotp(int $userId): void
    {
        $this->db->run('UPDATE ' . $this->db->table('users') . ' SET totp_secret = NULL, totp_enabled_at = NULL WHERE id = ?', [$userId]);
        $this->db->run('DELETE FROM ' . $this->db->table('settings') . ' WHERE name = ?', ['totp_last_' . $userId]);
    }

    private function anmelden(int $id, DateTimeImmutable $now): void
    {
        $this->session->set(self::KEY, $id);
        $this->db->run('UPDATE ' . $this->db->table('users') . ' SET last_login_at = ? WHERE id = ?', [gmdate('Y-m-d H:i:s', $now->getTimestamp()), $id]);
        $this->cache = null;
    }

    private function pendingId(DateTimeImmutable $now): ?int
    {
        $p = $this->session->get(self::PENDING);
        if (!is_array($p) || !is_int($p['id'] ?? null) || !is_int($p['bis'] ?? null) || $p['bis'] < $now->getTimestamp()) {
            return null;
        }

        return $p['id'];
    }

    private function pruefeCode(int $userId, string $code, DateTimeImmutable $now): bool
    {
        $roh = $this->db->fetchValue('SELECT totp_secret FROM ' . $this->db->table('users') . ' WHERE id = ? AND disabled_at IS NULL', [$userId]);
        $secret = is_string($roh) ? $this->crypto->decrypt($roh) : null;
        if ($secret === null) {
            return false;
        }
        $zuletzt = $this->db->fetchInt('SELECT value FROM ' . $this->db->table('settings') . ' WHERE name = ?', ['totp_last_' . $userId]);
        $fenster = Totp::verify($secret, $code, $now->getTimestamp(), $zuletzt);
        if ($fenster === null) {
            return false;
        }
        $this->merkeFenster($userId, $fenster);

        return true;
    }

    private function merkeFenster(int $userId, int $fenster): void
    {
        $this->db->run(
            'INSERT INTO ' . $this->db->table('settings') . ' (name, value, updated_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = ?, updated_at = ?',
            ['totp_last_' . $userId, (string) $fenster, gmdate('Y-m-d H:i:s'), (string) $fenster, gmdate('Y-m-d H:i:s')],
        );
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

    /** Der aktive Benutzer mit dieser ID, etwa für die API. */
    public function userById(int $id): ?AuthUser
    {
        $z = $this->db->fetchAll('SELECT id, name, email, role FROM ' . $this->db->table('users') . ' WHERE id = ? AND disabled_at IS NULL', [$id])[0] ?? null;
        if ($z === null) {
            return null;
        }

        return new AuthUser($id, is_string($z['name']) ? $z['name'] : '', is_string($z['email']) ? $z['email'] : '', is_string($z['role']) ? $z['role'] : 'viewer');
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
