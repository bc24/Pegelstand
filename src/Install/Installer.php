<?php

declare(strict_types=1);

namespace Pegelstand\Install;

use PDOException;
use Pegelstand\Core\Paths;
use Pegelstand\Database\Database;
use Pegelstand\Database\MigrationException;
use Pegelstand\Database\Migrator;
use Pegelstand\Database\ServerVersion;
use Pegelstand\Version;

/**
 * Führt die Installation aus: Datenbank prüfen, Tabellen anlegen, Administrator erstellen, Konfiguration schreiben.
 */
final class Installer
{
    public function __construct(
        private readonly Paths $paths,
        private readonly ConfigWriter $writer = new ConfigWriter(),
    ) {}

    /**
     * Verbindet mit der Datenbank und prüft die Servergeneration.
     *
     * @throws InstallException
     */
    public function connect(DatabaseInput $eingabe): Database
    {
        try {
            $db = Database::connect($eingabe->toArray());
            $version = $db->serverVersion();
        } catch (PDOException $fehler) {
            throw $this->uebersetze($fehler);
        }
        if (!ServerVersion::isSupported($version)) {
            throw new InstallException('install.fehler.dbversion', ['version' => ServerVersion::label($version)]);
        }

        return $db;
    }

    /**
     * @throws InstallException
     */
    public function install(DatabaseInput $dbEingabe, AdminInput $admin): void
    {
        $db = $this->connect($dbEingabe);

        try {
            if ($db->tableExists('users') && $db->fetchInt('SELECT COUNT(*) FROM ' . $db->table('users')) > 0) {
                throw new InstallException('install.fehler.vorhanden', ['praefix' => $db->prefix]);
            }
            (new Migrator($db, $this->paths->migrationsDir()))->migrate();
            $db->run(
                'INSERT INTO ' . $db->table('users') . " (email, name, password_hash, role, created_at) VALUES (?, ?, ?, 'admin', UTC_TIMESTAMP())",
                [$admin->email, $admin->name, PasswordHasher::hash($admin->password)],
            );
            foreach (['installed_at' => gmdate('c'), 'installed_version' => Version::CURRENT] as $name => $wert) {
                $db->run(
                    'INSERT INTO ' . $db->table('settings') . ' (name, value, updated_at) VALUES (?, ?, UTC_TIMESTAMP()) '
                    . 'ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = VALUES(updated_at)',
                    [$name, $wert],
                );
            }
        } catch (MigrationException $fehler) {
            throw new InstallException('install.fehler.migration', ['detail' => $fehler->getMessage()], $fehler);
        } catch (PDOException $fehler) {
            throw $this->uebersetze($fehler);
        }

        $this->writer->write($this->paths->configDir(), [
            'db' => $dbEingabe->toArray(),
            'app_key' => 'base64:' . base64_encode(random_bytes(32)),
            'timezone' => 'Europe/Berlin',
            'rotation_timezone' => 'Europe/Berlin',
            'debug' => false,
            'installed_at' => gmdate('c'),
            'installed_version' => Version::CURRENT,
        ]);
    }

    /**
     * Übersetzt Datenbankfehler in verständliche Meldungen mit Lösungsweg.
     */
    public function uebersetze(PDOException $fehler): InstallException
    {
        $roh = $fehler->errorInfo[1] ?? $fehler->getCode();
        $code = is_numeric($roh) ? (int) $roh : 0;

        return match (true) {
            $code === 1045 => new InstallException('install.fehler.db_zugang', [], $fehler),
            $code === 1049 => new InstallException('install.fehler.db_unbekannt', [], $fehler),
            $code === 1044, $code === 1142, $code === 1143, $code === 1227 => new InstallException('install.fehler.db_rechte', [], $fehler),
            in_array($code, [2002, 2003, 2005, 2006], true) => new InstallException('install.fehler.db_server', [], $fehler),
            default => new InstallException('install.fehler.db_allgemein', ['code' => $code], $fehler),
        };
    }
}
