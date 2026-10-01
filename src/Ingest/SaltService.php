<?php

declare(strict_types=1);

namespace Pegelstand\Ingest;

use DateTimeImmutable;
use DateTimeZone;
use Pegelstand\Database\Database;

/**
 * Liefert das Tages-Salt für den Besucher-Hash. Es wechselt um Mitternacht in der Rotations-Zeitzone,
 * vorherige Salts werden gelöscht. Danach lassen sich alte Besucher-Hashes nicht mehr nachrechnen.
 */
final class SaltService
{
    private ?string $day = null;
    private ?string $salt = null;

    public function __construct(
        private readonly Database $db,
        private readonly DateTimeZone $rotationZone,
    ) {}

    public function forTime(DateTimeImmutable $now): string
    {
        $tag = $now->setTimezone($this->rotationZone)->format('Y-m-d');
        if ($this->day === $tag && $this->salt !== null) {
            return $this->salt;
        }

        $salt = $this->lade($tag);
        if ($salt === null) {
            $this->db->run(
                'INSERT IGNORE INTO ' . $this->db->table('daily_salts') . ' (salt_date, salt, created_at) VALUES (?, ?, ?)',
                [$tag, random_bytes(32), gmdate('Y-m-d H:i:s')],
            );
            $salt = $this->lade($tag);
            if ($salt === null) {
                throw new \RuntimeException('Das Tages-Salt konnte nicht angelegt werden.');
            }
            $this->db->run('DELETE FROM ' . $this->db->table('daily_salts') . ' WHERE salt_date < ?', [$tag]);
        }
        $this->day = $tag;
        $this->salt = $salt;

        return $salt;
    }

    private function lade(string $tag): ?string
    {
        $wert = $this->db->fetchValue('SELECT salt FROM ' . $this->db->table('daily_salts') . ' WHERE salt_date = ?', [$tag]);

        return is_string($wert) && $wert !== '' ? $wert : null;
    }
}
