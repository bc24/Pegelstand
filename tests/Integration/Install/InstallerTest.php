<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Integration\Install;

use Pegelstand\Core\Config;
use Pegelstand\Core\Paths;
use Pegelstand\Install\AdminInput;
use Pegelstand\Install\DatabaseInput;
use Pegelstand\Install\Installer;
use Pegelstand\Install\InstallException;
use Pegelstand\Tests\Integration\DatenbankTestCase;
use Pegelstand\Version;

final class InstallerTest extends DatenbankTestCase
{
    private string $configDir = '';

    protected function setUp(): void
    {
        $this->configDir = sys_get_temp_dir() . '/ps-inst-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        foreach (glob($this->configDir . '/*') ?: [] as $datei) {
            @unlink($datei);
        }
        @rmdir($this->configDir);
    }

    private function installer(): Installer
    {
        return new Installer(new Paths(PEGELSTAND_ROOT, $this->configDir));
    }

    private function dbEingabe(string $praefix, ?string $passwort = null, ?string $name = null): DatabaseInput
    {
        $z = $this->zugang();

        return new DatabaseInput($z['host'], $z['port'], $name ?? $z['name'], $z['user'], $passwort ?? $z['password'], $praefix);
    }

    private function admin(): AdminInput
    {
        return new AdminInput('Frank Panzer', 'frank@beispiel.de', 'ein sehr langer satz', 'ein sehr langer satz');
    }

    public function testErfolgreicheInstallationSchreibtKonfigurationUndSperre(): void
    {
        $this->installer()->install($this->dbEingabe($this->neuerPraefix()), $this->admin());

        self::assertFileExists($this->configDir . '/config.php');
        self::assertFileExists($this->configDir . '/installed.lock');
    }

    public function testInstallationLegtAdministratorUndKonfigurationAn(): void
    {
        $praefix = $this->neuerPraefix();
        $this->installer()->install($this->dbEingabe($praefix), $this->admin());

        $db = \Pegelstand\Database\Database::connect([...$this->zugang(), 'prefix' => $praefix]);
        $benutzer = $db->fetchAll('SELECT email, name, role, password_hash FROM ' . $db->table('users'));
        self::assertCount(1, $benutzer);
        self::assertSame('frank@beispiel.de', $benutzer[0]['email']);
        self::assertSame('admin', $benutzer[0]['role']);
        self::assertIsString($benutzer[0]['password_hash']);
        self::assertTrue(password_verify('ein sehr langer satz', $benutzer[0]['password_hash']));
        self::assertSame(Version::CURRENT, $db->fetchValue('SELECT value FROM ' . $db->table('settings') . " WHERE name = 'installed_version'"));
        self::assertSame(1, $db->fetchInt('SELECT COUNT(*) FROM ' . $db->table('migrations')));

        $config = Config::load($this->configDir . '/config.php');
        self::assertNotNull($config);
        self::assertSame($praefix, $config->string('db.prefix'));
        self::assertMatchesRegularExpression('/^base64:.{40,}$/', $config->string('app_key'));
        self::assertSame('Europe/Berlin', $config->string('rotation_timezone'));
    }

    public function testZweiteInstallationInDieselbeTabellenWirdAbgelehnt(): void
    {
        $praefix = $this->neuerPraefix();
        $this->installer()->install($this->dbEingabe($praefix), $this->admin());

        try {
            $this->installer()->install($this->dbEingabe($praefix), $this->admin());
            self::fail('Ausnahme erwartet');
        } catch (InstallException $fehler) {
            self::assertSame('install.fehler.vorhanden', $fehler->messageKey);
            self::assertSame($praefix, $fehler->params['praefix']);
        }
    }

    public function testAbgebrochenerVersuchOhneBenutzerLaesstSichFortsetzen(): void
    {
        $praefix = $this->neuerPraefix();
        $db = \Pegelstand\Database\Database::connect([...$this->zugang(), 'prefix' => $praefix]);
        (new \Pegelstand\Database\Migrator($db, PEGELSTAND_ROOT . '/database/migrations'))->migrate();

        $this->installer()->install($this->dbEingabe($praefix), $this->admin());

        self::assertSame(1, $db->fetchInt('SELECT COUNT(*) FROM ' . $db->table('users')));
        self::assertFileExists($this->configDir . '/config.php');
    }

    public function testFalschesPasswort(): void
    {
        try {
            $this->installer()->connect($this->dbEingabe('ps_', 'falsches-passwort-xyz'));
            self::fail('Ausnahme erwartet');
        } catch (InstallException $fehler) {
            self::assertSame('install.fehler.db_zugang', $fehler->messageKey);
        }
    }

    public function testUnbekannteDatenbank(): void
    {
        try {
            $this->installer()->connect($this->dbEingabe('ps_', null, 'gibt_es_nicht_' . bin2hex(random_bytes(3))));
            self::fail('Ausnahme erwartet');
        } catch (InstallException $fehler) {
            self::assertContains($fehler->messageKey, ['install.fehler.db_unbekannt', 'install.fehler.db_rechte']);
        }
    }

    public function testUnerreichbarerServer(): void
    {
        $z = $this->zugang();
        try {
            $this->installer()->connect(new DatabaseInput($z['host'], 1, $z['name'], $z['user'], $z['password'], 'ps_'));
            self::fail('Ausnahme erwartet');
        } catch (InstallException $fehler) {
            self::assertContains($fehler->messageKey, ['install.fehler.db_server', 'install.fehler.db_allgemein']);
        }
    }

    public function testVerbindungLiefertDatenbankObjekt(): void
    {
        $db = $this->installer()->connect($this->dbEingabe('ps_'));

        self::assertSame('ps_', $db->prefix);
        self::assertNotSame('', $db->serverVersion());
    }

    public function testKonfigurationWirdNurBeiErfolgGeschrieben(): void
    {
        try {
            $this->installer()->install($this->dbEingabe('ps_', 'falsch-xyz'), $this->admin());
        } catch (InstallException) {
            // erwartet
        }

        self::assertFileDoesNotExist($this->configDir . '/config.php');
    }
}
