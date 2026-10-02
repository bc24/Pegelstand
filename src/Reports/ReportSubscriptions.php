<?php

declare(strict_types=1);

namespace Pegelstand\Reports;

use Pegelstand\Database\Database;

/** Abonnements verwalten. Wer neu abonniert, bekommt den ersten Bericht zum nächsten regulären Termin. */
final class ReportSubscriptions
{
    public const FREQUENCIES = ['weekly', 'monthly'];

    public function __construct(private readonly Database $db) {}

    /**
     * @return list<string> Einträge der Form "siteId:frequency"
     */
    public function forUser(int $userId): array
    {
        return array_map(
            static fn(array $z): string => (is_numeric($z['site_id']) ? (int) $z['site_id'] : 0) . ':' . (is_string($z['frequency']) ? $z['frequency'] : ''),
            $this->db->fetchAll('SELECT site_id, frequency FROM ' . $this->db->table('report_subscriptions') . ' WHERE user_id = ?', [$userId]),
        );
    }

    /**
     * Setzt die Abonnements eines Benutzers für die erlaubten Websites auf genau die gewählten.
     *
     * @param list<int> $erlaubteSites
     * @param list<string> $gewaehlt Einträge der Form "siteId:frequency"
     */
    public function replace(int $userId, array $erlaubteSites, array $gewaehlt, ?\DateTimeImmutable $jetzt = null): void
    {
        $zeit = gmdate('Y-m-d H:i:s', ($jetzt ?? new \DateTimeImmutable())->getTimestamp());
        $vorher = $this->forUser($userId);
        foreach ($erlaubteSites as $siteId) {
            foreach (self::FREQUENCIES as $f) {
                $schluessel = $siteId . ':' . $f;
                $will = in_array($schluessel, $gewaehlt, true);
                $hat = in_array($schluessel, $vorher, true);
                if ($will && !$hat) {
                    $this->db->run(
                        'INSERT INTO ' . $this->db->table('report_subscriptions') . ' (user_id, site_id, frequency, last_sent_at) VALUES (?, ?, ?, ?)',
                        [$userId, $siteId, $f, $zeit],
                    );
                } elseif (!$will && $hat) {
                    $this->db->run('DELETE FROM ' . $this->db->table('report_subscriptions') . ' WHERE user_id = ? AND site_id = ? AND frequency = ?', [$userId, $siteId, $f]);
                }
            }
        }
    }

    /**
     * Alle Abonnements aktiver Benutzer, die ihre Website noch sehen dürfen.
     *
     * @return list<array{id: int, email: string, name: string, role: string, site_id: int, frequency: string, last_sent_at: ?string, user_id: int}>
     */
    public function all(): array
    {
        $zeilen = $this->db->fetchAll(
            'SELECT r.id, r.user_id, r.site_id, r.frequency, r.last_sent_at, u.email, u.name, u.role FROM ' . $this->db->table('report_subscriptions') . ' r'
            . ' JOIN ' . $this->db->table('users') . ' u ON u.id = r.user_id AND u.disabled_at IS NULL'
            . ' LEFT JOIN ' . $this->db->table('site_users') . ' su ON su.user_id = u.id AND su.site_id = r.site_id'
            . " WHERE u.role = 'admin' OR su.user_id IS NOT NULL ORDER BY r.id",
        );
        $ergebnis = [];
        foreach ($zeilen as $z) {
            $ergebnis[] = [
                'id' => is_numeric($z['id']) ? (int) $z['id'] : 0,
                'user_id' => is_numeric($z['user_id']) ? (int) $z['user_id'] : 0,
                'site_id' => is_numeric($z['site_id']) ? (int) $z['site_id'] : 0,
                'frequency' => is_string($z['frequency']) ? $z['frequency'] : 'weekly',
                'last_sent_at' => is_string($z['last_sent_at']) ? $z['last_sent_at'] : null,
                'email' => is_string($z['email']) ? $z['email'] : '',
                'name' => is_string($z['name']) ? $z['name'] : '',
                'role' => is_string($z['role']) ? $z['role'] : 'viewer',
            ];
        }

        return $ergebnis;
    }

    public function markSent(int $id, \DateTimeImmutable $zeit): void
    {
        $this->db->run('UPDATE ' . $this->db->table('report_subscriptions') . ' SET last_sent_at = ? WHERE id = ?', [gmdate('Y-m-d H:i:s', $zeit->getTimestamp()), $id]);
    }
}
