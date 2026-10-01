<?php

declare(strict_types=1);

namespace Pegelstand\Settings;

use DateTimeZone;
use Pegelstand\Database\Database;
use Pegelstand\Ingest\IpAddress;

/**
 * Websites verwalten: anlegen, ändern, löschen samt Rohdaten, Ausschlussliste.
 *
 * @phpstan-type SiteRow array{id: int, public_id: string, name: string, domain: string, allowed_hosts: string, timezone: string, retention_days: int, respect_dnt: bool, respect_gpc: bool}
 */
final class SiteRepository
{
    public function __construct(private readonly Database $db) {}

    /**
     * @return list<SiteRow>
     */
    public function all(): array
    {
        return array_map(self::row(...), $this->db->fetchAll($this->select() . ' ORDER BY name, id'));
    }

    /**
     * @return SiteRow|null
     */
    public function find(string $publicId): ?array
    {
        $z = $this->db->fetchAll($this->select() . ' WHERE public_id = ?', [$publicId])[0] ?? null;

        return $z === null ? null : self::row($z);
    }

    /**
     * Prüft die Eingaben. Gibt Feld => Textschlüssel der Fehlermeldung zurück.
     *
     * @param array<string, string> $werte name, domain, allowed_hosts, timezone, retention_days
     * @return array<string, string>
     */
    public function validate(array $werte, ?int $ownId = null): array
    {
        $fehler = [];
        if ($werte['name'] === '' || mb_strlen($werte['name']) > 120) {
            $fehler['name'] = 'einst.fehler.name';
        }
        if (preg_match('/^(?=.{1,190}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)*$/', $werte['domain']) !== 1) {
            $fehler['domain'] = 'einst.fehler.domain';
        }
        foreach (preg_split('/[\s,]+/', $werte['allowed_hosts'], -1, PREG_SPLIT_NO_EMPTY) ?: [] as $host) {
            if (preg_match('/^(\*\.)?[a-z0-9]([a-z0-9.-]{0,188}[a-z0-9])?$/', strtolower($host)) !== 1) {
                $fehler['allowed_hosts'] = 'einst.fehler.hosts';
                break;
            }
        }
        if (!in_array($werte['timezone'], DateTimeZone::listIdentifiers(), true)) {
            $fehler['timezone'] = 'einst.fehler.zeitzone';
        }
        $tage = $werte['retention_days'];
        if (!ctype_digit($tage) || (int) $tage < 30 || (int) $tage > 3650) {
            $fehler['retention_days'] = 'einst.fehler.aufbewahrung';
        }

        return $fehler;
    }

    /**
     * @param array<string, string> $werte
     */
    public function create(array $werte, bool $dnt, bool $gpc): string
    {
        $publicId = bin2hex(random_bytes(8));
        $this->db->run(
            'INSERT INTO ' . $this->db->table('sites') . ' (public_id, name, domain, allowed_hosts, timezone, retention_days, respect_dnt, respect_gpc, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$publicId, $werte['name'], $werte['domain'], $werte['allowed_hosts'] === '' ? null : $werte['allowed_hosts'], $werte['timezone'], (int) $werte['retention_days'], (int) $dnt, (int) $gpc, gmdate('Y-m-d H:i:s')],
        );

        return $publicId;
    }

    /**
     * @param array<string, string> $werte
     */
    public function update(int $id, array $werte, bool $dnt, bool $gpc): void
    {
        $this->db->run(
            'UPDATE ' . $this->db->table('sites') . ' SET name = ?, domain = ?, allowed_hosts = ?, timezone = ?, retention_days = ?, respect_dnt = ?, respect_gpc = ? WHERE id = ?',
            [$werte['name'], $werte['domain'], $werte['allowed_hosts'] === '' ? null : $werte['allowed_hosts'], $werte['timezone'], (int) $werte['retention_days'], (int) $dnt, (int) $gpc, $id],
        );
    }

    /** Löscht die Site mit allen Messdaten und Aggregaten. */
    public function delete(int $id): void
    {
        foreach (['events', 'sessions'] as $tabelle) {
            do {
                $n = $this->db->run('DELETE FROM ' . $this->db->table($tabelle) . ' WHERE site_id = ? LIMIT 20000', [$id])->rowCount();
            } while ($n > 0);
        }
        // Eigenschaften ohne Ereignis aufräumen.
        $this->db->run('DELETE FROM ' . $this->db->table('event_props') . ' WHERE event_id NOT IN (SELECT id FROM ' . $this->db->table('events') . ')');
        foreach (['agg_hourly', 'agg_daily', 'agg_daily_dim', 'agg_daily_prop'] as $tabelle) {
            $this->db->run('DELETE FROM ' . $this->db->table($tabelle) . ' WHERE site_id = ?', [$id]);
        }
        $this->db->run('DELETE FROM ' . $this->db->table('settings') . ' WHERE name = ?', ['agg_cursor_' . $id]);
        $this->db->run('DELETE FROM ' . $this->db->table('sites') . ' WHERE id = ?', [$id]);
    }

    /**
     * @return list<array{id: int, ip_range: string, label: string}>
     */
    public function exclusions(int $siteId): array
    {
        return array_map(static fn(array $z): array => [
            'id' => is_numeric($z['id']) ? (int) $z['id'] : 0,
            'ip_range' => is_string($z['ip_range']) ? $z['ip_range'] : '',
            'label' => is_string($z['label']) ? $z['label'] : '',
        ], $this->db->fetchAll('SELECT id, ip_range, label FROM ' . $this->db->table('site_ip_exclusions') . ' WHERE site_id = ? ORDER BY id', [$siteId]));
    }

    /** @return bool false bei ungültiger Adresse */
    public function addExclusion(int $siteId, string $range, string $label): bool
    {
        $range = trim($range);
        if (!IpAddress::isValidRange($range) || mb_strlen($label) > 120) {
            return false;
        }
        $this->db->run('INSERT INTO ' . $this->db->table('site_ip_exclusions') . ' (site_id, ip_range, label) VALUES (?, ?, ?)', [$siteId, $range, $label === '' ? null : $label]);

        return true;
    }

    public function removeExclusion(int $siteId, int $id): void
    {
        $this->db->run('DELETE FROM ' . $this->db->table('site_ip_exclusions') . ' WHERE id = ? AND site_id = ?', [$id, $siteId]);
    }

    private function select(): string
    {
        return 'SELECT id, public_id, name, domain, allowed_hosts, timezone, retention_days, respect_dnt, respect_gpc FROM ' . $this->db->table('sites');
    }

    /**
     * @param array<string, mixed> $z
     * @return SiteRow
     */
    private static function row(array $z): array
    {
        return [
            'id' => is_numeric($z['id']) ? (int) $z['id'] : 0,
            'public_id' => is_string($z['public_id']) ? $z['public_id'] : '',
            'name' => is_string($z['name']) ? $z['name'] : '',
            'domain' => is_string($z['domain']) ? $z['domain'] : '',
            'allowed_hosts' => is_string($z['allowed_hosts']) ? $z['allowed_hosts'] : '',
            'timezone' => is_string($z['timezone']) ? $z['timezone'] : 'UTC',
            'retention_days' => is_numeric($z['retention_days']) ? (int) $z['retention_days'] : 730,
            'respect_dnt' => (bool) $z['respect_dnt'],
            'respect_gpc' => (bool) $z['respect_gpc'],
        ];
    }
}
