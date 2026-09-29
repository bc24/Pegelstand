<?php
declare(strict_types=1);

/** Kleine, idempotente Schema-Anpassungen für bereits installierte Seiten (läuft einmalig beim ersten Aufruf nach einem Update). */
final class Migrations
{
    public const VERSION = 2;

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
