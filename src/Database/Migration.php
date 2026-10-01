<?php

declare(strict_types=1);

namespace Pegelstand\Database;

/**
 * Eine Datenbank-Migration. Jede Migration muss sich nach einem Abbruch erneut ausführen lassen
 * (CREATE TABLE IF NOT EXISTS), denn MySQL und MariaDB können Strukturänderungen nicht zurückrollen.
 */
interface Migration
{
    public function name(): string;

    /**
     * @param string $prefix Tabellenpräfix der Installation
     * @return list<string> SQL-Befehle, werden einzeln ausgeführt
     */
    public function statements(string $prefix): array;
}
