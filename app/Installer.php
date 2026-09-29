<?php
declare(strict_types=1);

/** Installation: Anforderungen prüfen, Schema anlegen, Startinhalte einspielen, config.php schreiben. */
final class Installer
{
    public static function requirements(): array
    {
        $uploads = FP_ROOT . '/uploads';
        $storage = FP_ROOT . '/storage';
        $config  = FP_ROOT . '/config';
        return [
            ['PHP ≥ 8.1 (gefunden: ' . PHP_VERSION . ')', version_compare(PHP_VERSION, '8.1.0', '>='), 'PHP 8.1 oder neuer wird benötigt.'],
            ['Erweiterung pdo_mysql', extension_loaded('pdo_mysql'), 'PDO MySQL muss aktiviert sein.'],
            ['Erweiterung mbstring', extension_loaded('mbstring'), 'mbstring muss aktiviert sein.'],
            ['Erweiterung fileinfo', extension_loaded('fileinfo'), 'Für sichere Uploads erforderlich.'],
            ['Erweiterung gd (Bilder, empfohlen)', extension_loaded('gd'), 'Ohne GD gibt es keine automatischen Vorschaubilder.'],
            ['Erweiterung dom (HTML-Bereinigung)', extension_loaded('dom'), 'Wird für die HTML-Bereinigung benötigt.'],
            ['Ordner config/ beschreibbar', is_writable($config), 'Schreibrechte für config/ setzen (z. B. chmod 755/775).'],
            ['Ordner storage/ beschreibbar', is_writable($storage), 'Schreibrechte für storage/ setzen.'],
            ['Ordner uploads/ beschreibbar', is_writable($uploads), 'Schreibrechte für uploads/ setzen.'],
        ];
    }

    public static function requirementsOk(): bool
    {
        foreach (self::requirements() as $r) {
            // GD ist nur empfohlen
            if (!$r[1] && !str_contains($r[0], 'empfohlen')) {
                return false;
            }
        }
        return true;
    }

    public static function isInstalled(): bool
    {
        return is_file(FP_ROOT . '/config/installed.lock');
    }

    /** Verbindung testen; legt die Datenbank nicht an. */
    public static function testDb(array $db): ?string
    {
        try {
            Db::connect($db + ['charset' => 'utf8mb4']);
            return null;
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param array $db    host, port, name, user, pass
     * @param array $admin username, email, password
     * @param array $site  site_url, base_path
     */
    public static function run(array $db, array $admin, array $site): void
    {
        $db['charset'] = 'utf8mb4';
        Db::connect($db);

        self::runSchema();
        self::seed($site);

        Db::insert('users', [
            'username'      => $admin['username'],
            'email'         => $admin['email'],
            'password_hash' => password_hash($admin['password'], PASSWORD_DEFAULT),
            'role'          => 'admin',
            'created_at'    => now(),
        ]);

        $config = [
            'db'         => $db,
            'base_path'  => $site['base_path'] ?? '',
            'site_url'   => $site['site_url'] ?? '',
            'secret'     => bin2hex(random_bytes(32)),
            'debug'      => false,
            'timezone'   => 'Europe/Berlin',
            'session_timeout' => 7200,
        ];
        $php = "<?php\n// Erzeugt vom Installer am " . date('Y-m-d H:i') . "\nreturn " . var_export($config, true) . ";\n";
        if (file_put_contents(FP_ROOT . '/config/config.php', $php, LOCK_EX) === false) {
            throw new RuntimeException('config/config.php konnte nicht geschrieben werden.');
        }
        // Web-Installer läuft als Webserver-Benutzer (Besitzer der Datei) -> 0640; per CLI ggf. anderer Benutzer -> 0644
        @chmod(FP_ROOT . '/config/config.php', PHP_SAPI === 'cli' ? 0644 : 0640);
        file_put_contents(FP_ROOT . '/config/installed.lock', date('c') . "\n");
    }

    public static function runSchema(): void
    {
        $sql = (string)file_get_contents(FP_ROOT . '/database/schema.sql');
        $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
        foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $stmt) {
            Db::pdo()->exec($stmt);
        }
    }

    public static function seed(array $site = []): void
    {
        $data = require FP_ROOT . '/database/seed.php';
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            // Einstellungen
            $defaults = Settings::defaults();
            if (!empty($site['site_url'])) {
                $defaults['site_url'] = $site['site_url'];
            }
            $defaults['schema_version'] = (string)Migrations::VERSION;
            foreach ($defaults as $k => $v) {
                Db::upsert('settings', ['skey' => $k, 'svalue' => $v], ['svalue']);
            }

            // Einfache Tabellen: Zeilen mit Sortierung einfügen
            $simple = ['sections', 'stats', 'about_badges', 'interests', 'facts', 'timeline', 'projects', 'former_sites', 'maker_projects',
                'shop_items', 'cv_entries', 'tools', 'music_genres', 'tracks', 'faq', 'partner_steps', 'social_links', 'pages'];
            foreach ($simple as $table) {
                foreach ($data[$table] as $i => $row) {
                    $row['sort'] ??= ($i + 1) * 10;
                    if ($table === 'projects') {
                        $row['created_at'] = now();
                    }
                    if ($table === 'pages') {
                        $row['updated_at'] = now();
                    }
                    Db::insert($table, $row);
                }
            }

            foreach ($data['skill_groups'] as $gi => $g) {
                $skills = $g['skills'];
                unset($g['skills']);
                $g['sort'] = ($gi + 1) * 10;
                $gid = Db::insert('skill_groups', $g);
                foreach ($skills as $si => [$name, $icon, $level]) {
                    Db::insert('skills', ['group_id' => $gid, 'name' => $name, 'icon' => $icon, 'level' => $level, 'sort' => ($si + 1) * 10]);
                }
            }

            foreach ($data['posts'] as $p) {
                $p['status'] = 'published';
                $p['content_en'] ??= '';
                $p['created_at'] = $p['published_at'];
                $p['updated_at'] = $p['published_at'];
                Db::insert('posts', $p);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** Fehlende Einstellungen (nach Updates) mit Standardwerten auffüllen. */
    public static function ensureSettings(): void
    {
        $have = Settings::all();
        foreach (Settings::defaults() as $k => $v) {
            if (!array_key_exists($k, $have)) {
                Db::upsert('settings', ['skey' => $k, 'svalue' => $v], ['svalue']);
            }
        }
        Settings::reset();
    }
}
