<?php
declare(strict_types=1);

/** Kleine, idempotente Schema-Anpassungen für bereits installierte Seiten (läuft einmalig beim ersten Aufruf nach einem Update). */
final class Migrations
{
    public const VERSION = 3;

    private static bool $ran = false;

    public static function run(): void
    {
        if (self::$ran) {
            return;
        }
        self::$ran = true;
        try {
            if (!Db::tableExists('settings')) {
                return; // noch nicht installiert
            }
            $current = (int)Settings::get('schema_version', '1');
            if ($current >= self::VERSION) {
                return;
            }
            if ($current < 2) {
                self::v2();
            }
            if ($current < 3) {
                self::v3();
            }
            Settings::set('schema_version', (string)self::VERSION);
        } catch (Throwable $e) {
            error_log('[fp] Migration fehlgeschlagen: ' . $e->getMessage());
        }
    }

    private static function hasColumn(string $table, string $col): bool
    {
        return (bool)Db::val('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?', [$table, $col]);
    }

    /** v2: TikTok-Songs (neue Spalten in tracks, neue Einstellungen, Hinweis in der Datenschutzerklärung). */
    private static function v2(): void
    {
        if (Db::tableExists('tracks')) {
            $add = [
                'caption' => 'TEXT NULL AFTER title',
                'video_id' => "VARCHAR(32) NOT NULL DEFAULT '' AFTER url",
                'plays' => 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER image',
                'published_at' => 'DATETIME NULL AFTER plays',
                'source' => "VARCHAR(12) NOT NULL DEFAULT 'manual' AFTER published_at",
            ];
            foreach ($add as $col => $def) {
                if (!self::hasColumn('tracks', $col)) {
                    Db::pdo()->exec("ALTER TABLE tracks ADD COLUMN `$col` $def");
                }
            }
            Db::pdo()->exec('ALTER TABLE tracks MODIFY title VARCHAR(255) NOT NULL, MODIFY genre VARCHAR(160) NOT NULL DEFAULT \'\'');
        }
        Installer::ensureSettings();
        self::privacyTikTok();
    }

    /**
     * v3: Apps aus /projekte/ sauber einbinden – Beschreibungen, interne Links, Startseite; "Spiel" heißt jetzt Territoriumskrieg.
     * Nur leere oder noch unveränderte Standardwerte werden überschrieben, im Admin geänderte Texte bleiben erhalten.
     */
    private static function v3(): void
    {
        if (!Db::tableExists('projects')) {
            return;
        }
        $rows = require FP_ROOT . '/database/seed/projects.php';
        $bySlug = [];
        foreach ($rows as $r) {
            $bySlug[$r['slug']] = $r;
        }

        $old = Db::one("SELECT id, title FROM projects WHERE slug = 'spiel'");
        if ($old && $old['title'] === 'Spiel' && !Db::one("SELECT id FROM projects WHERE slug = 'territoriumskrieg'")) {
            $n = $bySlug['territoriumskrieg'];
            unset($n['featured']);
            Db::update('projects', (int)$old['id'], $n);
        }

        $promote = ['bewerbungspilot', 'techdeals24', 'trockenheld', 'weltenentdecker', 'spielearena', 'poesiealbum'];
        // MD5 der bisherigen Standardtexte (DE, EN): nur wenn der Text im Admin nicht geändert wurde, wird er ersetzt
        $oldDesc = [
            'bewerbungspilot' => ['description_de' => '9090525f5e182721a6aa5d1227be6095', 'description_en' => 'c3d0bc13269473044dd19373e40a43ad'],
            'trockenheld' => ['description_de' => 'f6b4ec80e5342ce54f9b6419b2b616b7', 'description_en' => '56758209cc27a551a56291cbbacd1aef'],
            'weltenentdecker' => ['description_de' => '5d1557bab127ebd216f2169c957e994d', 'description_en' => '24eb0d69c722310ae09eaefa844029a2'],
        ];
        foreach ($rows as $r) {
            $cur = Db::one('SELECT * FROM projects WHERE slug = ?', [$r['slug']]);
            if (!$cur) {
                $r['sort'] = (int)Db::val('SELECT COALESCE(MAX(sort), 0) FROM projects') + 10;
                $r['created_at'] = now();
                Db::insert('projects', $r);
                continue;
            }
            $upd = [];
            foreach (['description_de', 'description_en'] as $f) {
                $text = trim((string)$cur[$f]);
                if (!empty($r[$f]) && ($text === '' || md5($text) === ($oldDesc[$r['slug']][$f] ?? ''))) {
                    $upd[$f] = $r[$f];
                }
            }
            foreach (['meta_de', 'meta_en'] as $f) {
                if (!empty($r[$f]) && (trim((string)$cur[$f]) === '' || preg_match('~^panzerit\.de/~', (string)$cur[$f]))) {
                    $upd[$f] = $r[$f];
                }
            }
            if (str_starts_with((string)$r['url'], '/') && (string)$cur['url'] !== $r['url']
                && preg_match('~^https://(www\.)?(panzerit|frank-panzer)\.de/~i', (string)$cur['url'])) {
                $upd['url'] = $r['url'];
            }
            if (in_array($r['slug'], $promote, true) && empty($cur['featured'])) {
                $upd['featured'] = 1;
            }
            if ($upd) {
                Db::update('projects', (int)$cur['id'], $upd);
            }
        }
    }

    private static function privacyTikTok(): void
    {
        $page = Db::one("SELECT id, content_de FROM pages WHERE slug = 'datenschutz'");
        if (!$page || str_contains((string)$page['content_de'], 'TikTok')) {
            return;
        }
        $html = (string)$page['content_de'];
        $html = str_replace(
            'Es werden keine externen Schriftarten, Skripte oder Inhalte von Drittanbietern nachgeladen.',
            'Es werden keine externen Schriftarten, Skripte oder Inhalte von Drittanbietern automatisch nachgeladen; die einzige Ausnahme ist der TikTok-Player, der erst nach Ihrem Klick geladen wird (siehe Abschnitt 9).',
            $html
        );
        // Abschnitte ab "9." um eins hochzählen und den neuen Abschnitt davor einfügen
        $html = preg_replace_callback('~<h2>(\d+)\. ~', static fn($m) => '<h2>' . ((int)$m[1] >= 9 ? (int)$m[1] + 1 : (int)$m[1]) . '. ', $html) ?? $html;
        $block = self::tiktokSection(9);
        $pos = strpos($html, '<h2>10. ');
        $html = $pos === false ? $html . "\n" . $block : substr($html, 0, $pos) . $block . "\n" . substr($html, $pos);
        Db::update('pages', (int)$page['id'], ['content_de' => $html, 'updated_at' => now()]);
    }

    public static function tiktokSection(int $n): string
    {
        return '<h2>' . $n . '. TikTok-Einbettung (Songs)</h2>' . "\n"
            . '<p>Im Bereich „Musik“ werden Songs als Vorschaubilder angezeigt. Die Vorschaubilder liegen auf diesem Server; beim Aufruf der Seite findet keine Verbindung zu TikTok statt. '
            . 'Erst wenn Sie auf einen Song klicken, wird der Video-Player von TikTok (TikTok Technology Limited, Irland; TikTok Inc., USA) in einem eingebetteten Fenster geladen. '
            . 'Dabei kann TikTok Daten wie Ihre IP-Adresse verarbeiten und Cookies oder ähnliche Technologien einsetzen. Weitere Informationen finden Sie in der Datenschutzerklärung von TikTok.</p>' . "\n"
            . '<p>Rechtsgrundlage: Art. 6 Abs. 1 lit. a DSGVO (Ihre Einwilligung durch den Klick auf den Song).</p>';
    }
}
