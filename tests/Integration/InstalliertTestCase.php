<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Integration;

use Pegelstand\Core\Application;
use Pegelstand\Core\ArraySession;
use Pegelstand\Core\Paths;
use Pegelstand\Database\Database;
use Pegelstand\Install\AdminInput;
use Pegelstand\Install\DatabaseInput;
use Pegelstand\Install\Installer;

/**
 * Eine echte, vom Installer eingerichtete Installation in einem temporären Verzeichnis (Konfiguration, Storage, Datenbank).
 */
abstract class InstalliertTestCase extends DatenbankTestCase
{
    protected string $temp = '';
    protected Database $db;

    protected function setUp(): void
    {
        $this->temp = sys_get_temp_dir() . '/ps-inst-' . bin2hex(random_bytes(4));
        mkdir($this->temp . '/config', 0777, true);
        mkdir($this->temp . '/storage', 0777, true);
        $z = $this->zugang();
        $praefix = $this->neuerPraefix();
        (new Installer($this->paths()))->install(
            new DatabaseInput($z['host'], $z['port'], $z['name'], $z['user'], $z['password'], $praefix),
            new AdminInput('Frank', 'frank@beispiel.de', 'ein sehr langer satz', 'ein sehr langer satz'),
        );
        $this->db = Database::connect([...$z, 'prefix' => $praefix]);
        $this->db->run(
            'INSERT INTO ' . $this->db->table('sites') . ' (public_id, name, domain, created_at) VALUES (?, ?, ?, ?)',
            ['abcd1234abcd1234', 'Test', 'beispiel.de', '2026-10-01 00:00:00'],
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->loesche($this->temp);
    }

    protected function paths(): Paths
    {
        return new Paths(PEGELSTAND_ROOT, $this->temp . '/config', $this->temp . '/storage');
    }

    protected function app(): Application
    {
        return new Application($this->paths(), new ArraySession());
    }

    protected function konfiguration(string $zusatz): void
    {
        $datei = $this->temp . '/config/config.php';
        file_put_contents($datei, str_replace('return array (', "return array (\n" . $zusatz, (string) file_get_contents($datei)));
    }

    private function loesche(string $pfad): void
    {
        foreach (glob($pfad . '/{,.}[!.]*', GLOB_BRACE) ?: [] as $eintrag) {
            is_dir($eintrag) ? $this->loesche($eintrag) : @unlink($eintrag);
        }
        @rmdir($pfad);
    }
}
