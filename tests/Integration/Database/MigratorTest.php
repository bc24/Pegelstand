<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Integration\Database;

use PDOException;
use Pegelstand\Database\Database;
use Pegelstand\Database\MigrationException;
use Pegelstand\Database\Migrator;
use Pegelstand\Tests\Integration\DatenbankTestCase;

final class MigratorTest extends DatenbankTestCase
{
    private string $tempDir = '';

    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->tempDir !== '') {
            foreach (glob($this->tempDir . '/*') ?: [] as $datei) {
                @unlink($datei);
            }
            @rmdir($this->tempDir);
            $this->tempDir = '';
        }
    }

    private function migrator(Database $db): Migrator
    {
        return new Migrator($db, PEGELSTAND_ROOT . '/database/migrations');
    }

    /**
     * Kopiert eine Migration in ein temporäres Verzeichnis, damit Tests sie verändern können.
     */
    private function tempMigrationen(string $inhalt): string
    {
        $this->tempDir = sys_get_temp_dir() . '/ps-mig-' . bin2hex(random_bytes(4));
        mkdir($this->tempDir);
        file_put_contents($this->tempDir . '/0001_test.php', $inhalt);

        return $this->tempDir;
    }

    public function testLegtAlleTabellenAnUndMerktSichDieVersion(): void
    {
        $db = $this->datenbank();
        $migrator = $this->migrator($db);

        self::assertSame(['0001'], $migrator->pending());
        self::assertSame(['0001'], $migrator->migrate());

        foreach (['migrations', 'settings', 'users', 'sites', 'site_users'] as $tabelle) {
            self::assertTrue($db->tableExists($tabelle), $tabelle);
        }
        self::assertSame([], $migrator->pending());
        self::assertSame(['0001'], array_keys($migrator->applied()));
    }

    public function testZweiterLaufTutNichts(): void
    {
        $db = $this->datenbank();
        $migrator = $this->migrator($db);
        $migrator->migrate();

        self::assertSame([], $migrator->migrate());
        self::assertSame(1, $db->fetchInt('SELECT COUNT(*) FROM ' . $db->table('migrations')));
    }

    public function testFunktioniertOhnePraefix(): void
    {
        $db = $this->datenbank('');
        $this->migrator($db)->migrate();

        self::assertTrue($db->tableExists('users'));
        $db->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['site_users', 'sites', 'users', 'settings', 'migrations'] as $tabelle) {
            $db->pdo->exec('DROP TABLE IF EXISTS `' . $tabelle . '`');
        }
        $db->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function testMehrereInstallationenInEinerDatenbankStoerenSichNicht(): void
    {
        $a = $this->datenbank();
        $b = $this->datenbank();
        $this->migrator($a)->migrate();

        self::assertSame(['0001'], $this->migrator($b)->pending(), 'Die zweite Installation hat noch nichts angewendet.');
        $this->migrator($b)->migrate();
        self::assertSame(0, $a->fetchInt('SELECT COUNT(*) FROM ' . $a->table('users')));
    }

    public function testErkenntVeraenderteMigration(): void
    {
        $db = $this->datenbank();
        $ordner = $this->tempMigrationen($this->migrationsCode('SELECT 1'));
        $migrator = new Migrator($db, $ordner);
        $migrator->migrate();

        file_put_contents($ordner . '/0001_test.php', $this->migrationsCode('SELECT 2'));

        try {
            $migrator->migrate();
            self::fail('Ausnahme erwartet');
        } catch (MigrationException $fehler) {
            self::assertSame(MigrationException::CHANGED, $fehler->getCode());
            self::assertStringContainsString('0001', $fehler->getMessage());
        }
    }

    public function testParalleleMigrationWirdGesperrt(): void
    {
        $db = $this->datenbank();
        $anderer = Database::connect([...$this->zugang(), 'prefix' => $db->prefix]);
        $sperre = $db->name . '.' . $db->prefix . 'migrate';
        $anderer->run('SELECT GET_LOCK(?, 0)', [$sperre]);

        try {
            $this->migrator($db)->migrate();
            self::fail('Ausnahme erwartet');
        } catch (MigrationException $fehler) {
            self::assertSame(MigrationException::LOCKED, $fehler->getCode());
        } finally {
            $anderer->run('SELECT RELEASE_LOCK(?)', [$sperre]);
        }
        self::assertSame(['0001'], $this->migrator($db)->migrate(), 'Nach Freigabe läuft die Migration.');
    }

    public function testFehlerNenntBackupAlsLoesungswegUndMerktSichNichts(): void
    {
        $db = $this->datenbank();
        $ordner = $this->tempMigrationen($this->migrationsCode('SELECT * FROM `gibt_es_nicht_' . bin2hex(random_bytes(2)) . '`'));
        $migrator = new Migrator($db, $ordner);

        try {
            $migrator->migrate();
            self::fail('Ausnahme erwartet');
        } catch (MigrationException $fehler) {
            self::assertSame(MigrationException::FAILED, $fehler->getCode());
            self::assertStringContainsString('Backup', $fehler->getMessage());
            self::assertInstanceOf(PDOException::class, $fehler->getPrevious());
        }
        self::assertSame(['0001'], $migrator->pending(), 'Eine fehlgeschlagene Migration gilt nicht als angewendet.');
    }

    public function testLatestVersion(): void
    {
        self::assertSame('0001', $this->migrator($this->datenbank())->latestVersion());
    }

    public function testSchemaGrundlagen(): void
    {
        $db = $this->datenbank();
        $this->migrator($db)->migrate();

        $eigenschaften = $db->fetchAll(
            'SELECT table_name AS t, engine AS e, table_collation AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE ?',
            [str_replace('_', '\\_', $db->prefix) . '%'],
        );
        self::assertCount(5, $eigenschaften);
        foreach ($eigenschaften as $zeile) {
            self::assertSame('InnoDB', $zeile['e'], is_string($zeile['t']) ? $zeile['t'] : '');
            self::assertSame('utf8mb4_unicode_ci', $zeile['c'], is_string($zeile['t']) ? $zeile['t'] : '');
        }
    }

    public function testBeziehungenUndEindeutigkeit(): void
    {
        $db = $this->datenbank();
        $this->migrator($db)->migrate();
        $benutzer = $db->table('users');
        $sites = $db->table('sites');

        $db->run("INSERT INTO $benutzer (email, name, password_hash, role, created_at) VALUES ('a@b.de', 'A', 'x', 'admin', UTC_TIMESTAMP())");
        $db->run("INSERT INTO $sites (public_id, name, domain, created_at) VALUES ('abcd1234abcd1234', 'S', 'beispiel.de', UTC_TIMESTAMP())");
        $db->run('INSERT INTO ' . $db->table('site_users') . ' (site_id, user_id) VALUES (1, 1)');

        $zeile = $db->fetchAll("SELECT timezone, retention_days, respect_dnt FROM $sites")[0];
        self::assertSame('Europe/Berlin', $zeile['timezone']);
        self::assertEquals(730, $zeile['retention_days'], 'Standard-Aufbewahrung 24 Monate.');
        self::assertEquals(0, $zeile['respect_dnt']);

        try {
            $db->run("INSERT INTO $benutzer (email, name, password_hash, created_at) VALUES ('a@b.de', 'B', 'x', UTC_TIMESTAMP())");
            self::fail('Doppelte E-Mail muss abgelehnt werden.');
        } catch (PDOException $fehler) {
            self::assertSame('23000', $fehler->getCode() === 23000 ? '23000' : (string) $fehler->getCode());
        }

        $db->run("DELETE FROM $sites WHERE id = 1");
        self::assertSame(0, $db->fetchInt('SELECT COUNT(*) FROM ' . $db->table('site_users')), 'Löschen einer Site entfernt die Zuordnung.');
    }

    private function migrationsCode(string $sql): string
    {
        return "<?php declare(strict_types=1);\nuse Pegelstand\\Database\\Migration;\nreturn new class implements Migration {\n"
            . "public function name(): string { return 'Test'; }\n"
            . "public function statements(string \$prefix): array { return [" . var_export($sql, true) . "]; }\n};\n";
    }
}
