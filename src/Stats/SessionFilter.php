<?php

declare(strict_types=1);

namespace Pegelstand\Stats;

use Pegelstand\Database\Database;
use Pegelstand\Ingest\UserAgentInfo;

/**
 * Bedingungen für Sitzungen (Alias `s`), gebaut aus den Filtern der Oberfläche. Ein unbekannter Wert
 * (z. B. eine Seite, die es nie gab) ergibt eine Bedingung, die nichts findet.
 */
final class SessionFilter
{
    public const DEVICES = ['desktop' => UserAgentInfo::DEVICE_DESKTOP, 'smartphone' => UserAgentInfo::DEVICE_MOBILE, 'tablet' => UserAgentInfo::DEVICE_TABLET];

    /**
     * @param list<scalar> $params
     */
    private function __construct(
        public readonly string $sql,
        public readonly array $params,
    ) {}

    public function isEmpty(): bool
    {
        return $this->sql === '';
    }

    /**
     * @param list<array{typ: string, wert: string}> $filter
     */
    public static function build(Database $db, array $filter, ReferrerClassifier $referrer): self
    {
        $teile = [];
        $params = [];
        $suche = static function (string $tabelle, string $wert) use ($db): int {
            return $db->fetchInt(
                'SELECT id FROM ' . $db->table('dict_' . $tabelle) . ' WHERE value_hash = UNHEX(MD5(?)) AND value = ?',
                [$wert, $wert],
            );
        };
        foreach ($filter as $f) {
            $wert = $f['wert'];
            switch ($f['typ']) {
                case 'seite':
                    self::exists($teile, $params, 1, $suche('path', $wert), 'path_id', $db);
                    break;
                case 'einstieg':
                    self::gleich($teile, $params, 's.entry_path_id', $suche('path', $wert));
                    break;
                case 'ausstieg':
                    self::gleich($teile, $params, 's.exit_path_id', $suche('path', $wert));
                    break;
                case 'quelle':
                    if ($wert === 'direkt') {
                        $teile[] = '(s.referrer_id IS NULL AND s.utm_source_id IS NULL)';
                        break;
                    }
                    $ids = [];
                    foreach ($db->fetchAll('SELECT id, value FROM ' . $db->table('dict_referrer')) as $z) {
                        if (is_string($z['value']) && is_numeric($z['id']) && $referrer->classify($z['value'])['id'] === $wert) {
                            $ids[] = (int) $z['id'];
                        }
                    }
                    if ($ids === []) {
                        $teile[] = '1 = 0';
                        break;
                    }
                    $teile[] = 's.referrer_id IN (' . implode(',', $ids) . ')';
                    break;
                case 'kampagne':
                    self::gleich($teile, $params, 's.utm_campaign_id', $suche('utm', $wert));
                    break;
                case 'land':
                    $teile[] = 's.country = ?';
                    $params[] = strtoupper(substr($wert, 0, 2));
                    break;
                case 'geraet':
                    $teile[] = 's.device = ?';
                    $params[] = self::DEVICES[$wert] ?? -1;
                    break;
                case 'browser':
                    self::gleich($teile, $params, 's.browser_id', $suche('browser', $wert));
                    break;
                case 'os':
                    self::gleich($teile, $params, 's.os_id', $suche('os', $wert));
                    break;
                case 'ereignis':
                    self::exists($teile, $params, 2, $suche('event_name', $wert), 'name_id', $db);
                    break;
                default:
                    break;
            }
        }

        return new self($teile === [] ? '' : ' AND ' . implode(' AND ', $teile), $params);
    }

    public static function none(): self
    {
        return new self('', []);
    }

    /**
     * @param list<string> $teile
     * @param list<scalar> $params
     */
    private static function gleich(array &$teile, array &$params, string $spalte, int $id): void
    {
        if ($id === 0) {
            $teile[] = '1 = 0';

            return;
        }
        $teile[] = $spalte . ' = ?';
        $params[] = $id;
    }

    /**
     * @param list<string> $teile
     * @param list<scalar> $params
     */
    private static function exists(array &$teile, array &$params, int $kind, int $id, string $spalte, Database $db): void
    {
        if ($id === 0) {
            $teile[] = '1 = 0';

            return;
        }
        $teile[] = 'EXISTS (SELECT 1 FROM ' . $db->table('events') . ' fe WHERE fe.session_id = s.id AND fe.kind = ' . $kind . ' AND fe.' . $spalte . ' = ?)';
        $params[] = $id;
    }
}
