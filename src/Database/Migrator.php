<?php

declare(strict_types=1);

namespace Pegelstand\Database;

use PDOException;
use RuntimeException;

/**
 * Führt Migrationen aus database/migrations in der Reihenfolge ihrer Dateinamen aus (0001_name.php).
 * Ein Sperrbefehl der Datenbank verhindert, dass zwei Aufrufe gleichzeitig migrieren.
 */
final class Migrator
{
    public function __construct(
        private readonly Database $db,
        private readonly string $dir,
    ) {}

    /**
     * @return array<string, array{file: string, checksum: string}> Version zu Datei, aufsteigend sortiert
     */
    public function available(): array
    {
        $dateien = glob($this->dir . '/[0-9][0-9][0-9][0-9]_*.php');
        if ($dateien === false) {
            return [];
        }
        sort($dateien);
        $liste = [];
        foreach ($dateien as $datei) {
            $version = substr(basename($datei), 0, 4);
            $liste[$version] = ['file' => $datei, 'checksum' => hash_file('sha256', $datei) ?: ''];
        }

        return $liste;
    }

    public function latestVersion(): string
    {
        $versionen = array_keys($this->available());

        return $versionen === [] ? '0000' : (string) end($versionen);
    }

    /**
     * @return array<string, string> Version zu Prüfsumme der bereits angewendeten Migrationen
     */
    public function applied(): array
    {
        if (!$this->db->tableExists('migrations')) {
            return [];
        }
        $ergebnis = [];
        foreach ($this->db->fetchAll('SELECT version, checksum FROM ' . $this->db->table('migrations') . ' ORDER BY version') as $zeile) {
            $version = $zeile['version'];
            $pruefsumme = $zeile['checksum'];
            $ergebnis[is_scalar($version) ? (string) $version : ''] = is_scalar($pruefsumme) ? (string) $pruefsumme : '';
        }

        return $ergebnis;
    }

    /**
     * @return list<string> Versionen, die noch fehlen
     */
    public function pending(): array
    {
        return array_values(array_diff(array_keys($this->available()), array_keys($this->applied())));
    }

    /**
     * Wendet alle offenen Migrationen an.
     *
     * @return list<string> angewendete Versionen
     */
    public function migrate(): array
    {
        $sperre = $this->db->name . '.' . $this->db->prefix . 'migrate';
        $erhalten = $this->db->fetchValue('SELECT GET_LOCK(?, 0)', [$sperre]);
        if (!is_numeric($erhalten) || (int) $erhalten !== 1) {
            throw new MigrationException(
                'Eine andere Anfrage aktualisiert gerade die Datenbank. Warte einen Moment und lade die Seite neu.',
                MigrationException::LOCKED,
            );
        }
        try {
            return $this->migriereGesperrt();
        } finally {
            $this->db->run('SELECT RELEASE_LOCK(?)', [$sperre]);
        }
    }

    /**
     * @return list<string>
     */
    private function migriereGesperrt(): array
    {
        $this->legeVerwaltungstabelleAn();
        $angewendet = $this->applied();
        $verfuegbar = $this->available();

        foreach ($angewendet as $version => $pruefsumme) {
            if (isset($verfuegbar[$version]) && $verfuegbar[$version]['checksum'] !== $pruefsumme) {
                throw new MigrationException(
                    sprintf('Die Migration %s wurde nach dem Anwenden verändert. Stelle die ursprüngliche Datei wieder her.', $version),
                    MigrationException::CHANGED,
                );
            }
        }

        $neu = [];
        foreach ($verfuegbar as $version => $info) {
            if (isset($angewendet[$version])) {
                continue;
            }
            $migration = (static fn(): mixed => require $info['file'])();
            if (!$migration instanceof Migration) {
                throw new RuntimeException(sprintf('Die Datei der Migration %s gibt keine Migration zurück.', $version));
            }
            try {
                foreach ($migration->statements($this->db->prefix) as $sql) {
                    // query() statt exec(): Liefert ein Befehl Zeilen, blockiert exec() die Folgeabfragen.
                    $ergebnis = $this->db->pdo->query($sql);
                    if ($ergebnis !== false) {
                        $ergebnis->closeCursor();
                    }
                }
            } catch (PDOException $fehler) {
                throw new MigrationException(
                    sprintf(
                        'Die Migration %s (%s) ist fehlgeschlagen: %s. Spiele bei Bedarf dein Backup ein und starte den Vorgang erneut.',
                        $version,
                        $migration->name(),
                        $fehler->getMessage(),
                    ),
                    MigrationException::FAILED,
                    $fehler,
                );
            }
            $this->db->run(
                'INSERT INTO ' . $this->db->table('migrations') . ' (version, name, checksum, applied_at) VALUES (?, ?, ?, UTC_TIMESTAMP())',
                [$version, $migration->name(), $info['checksum']],
            );
            $neu[] = $version;
        }

        return $neu;
    }

    private function legeVerwaltungstabelleAn(): void
    {
        $this->db->pdo->exec(
            'CREATE TABLE IF NOT EXISTS ' . $this->db->table('migrations') . ' (
                version    VARCHAR(32)  NOT NULL PRIMARY KEY,
                name       VARCHAR(190) NOT NULL,
                checksum   CHAR(64)     NOT NULL,
                applied_at DATETIME     NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        );
    }
}
