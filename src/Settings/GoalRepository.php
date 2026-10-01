<?php

declare(strict_types=1);

namespace Pegelstand\Settings;

use Pegelstand\Database\Database;

/**
 * Ziele einer Website: Aufruf einer Seite oder ein Ereignis.
 *
 * @phpstan-type GoalRow array{id: int, name: string, kind: string, target: string}
 */
final class GoalRepository
{
    public function __construct(private readonly Database $db) {}

    /**
     * @return list<GoalRow>
     */
    public function forSite(int $siteId): array
    {
        return array_map(static fn(array $z): array => [
            'id' => is_numeric($z['id']) ? (int) $z['id'] : 0,
            'name' => is_string($z['name']) ? $z['name'] : '',
            'kind' => $z['kind'] === 'event' ? 'event' : 'page',
            'target' => is_string($z['target']) ? $z['target'] : '',
        ], $this->db->fetchAll('SELECT id, name, kind, target FROM ' . $this->db->table('goals') . ' WHERE site_id = ? ORDER BY id', [$siteId]));
    }

    /**
     * @return GoalRow|null
     */
    public function find(int $siteId, int $id): ?array
    {
        foreach ($this->forSite($siteId) as $g) {
            if ($g['id'] === $id) {
                return $g;
            }
        }

        return null;
    }

    /**
     * @param array<string, string> $werte name, kind, target
     * @return array<string, string> Feld => Textschlüssel der Fehlermeldung
     */
    public static function validate(array $werte): array
    {
        $fehler = [];
        if ($werte['name'] === '' || mb_strlen($werte['name']) > 120) {
            $fehler['goal_name'] = 'einst.fehler.name';
        }
        if (!in_array($werte['kind'], ['page', 'event'], true)) {
            $fehler['goal_kind'] = 'einst.fehler.ziel_art';
        }
        $ziel = $werte['target'];
        if ($ziel === '' || mb_strlen($ziel) > 1000 || preg_match('/[\x00-\x1F\x7F]/', $ziel) === 1
            || ($werte['kind'] === 'page' && $ziel[0] !== '/')
            || ($werte['kind'] === 'event' && mb_strlen($ziel) > 120)) {
            $fehler['goal_target'] = $werte['kind'] === 'page' ? 'einst.fehler.ziel_seite' : 'einst.fehler.ziel_ereignis';
        }

        return $fehler;
    }

    public function add(int $siteId, string $name, string $kind, string $target): void
    {
        $this->db->run('INSERT INTO ' . $this->db->table('goals') . ' (site_id, name, kind, target) VALUES (?, ?, ?, ?)', [$siteId, $name, $kind, $target]);
    }

    public function delete(int $siteId, int $id): void
    {
        $this->db->run('DELETE FROM ' . $this->db->table('goals') . ' WHERE id = ? AND site_id = ?', [$id, $siteId]);
    }
}
