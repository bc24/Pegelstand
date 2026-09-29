<?php
declare(strict_types=1);

/** Kern des Admin-Bereichs: URLs, Layout, Navigation, Formularfelder. */
final class Admin
{
    private static ?array $entities = null;

    public static function entities(): array
    {
        return self::$entities ??= require FP_ROOT . '/app/entities.php';
    }

    public static function url(array $q = []): string
    {
        $base = rtrim((string)cfg('base_path', ''), '/') . '/admin/';
        return $q ? $base . '?' . http_build_query($q) : $base;
    }

    public static function flash(string $type, string $msg): void
    {
        $_SESSION['flash'][] = [$type, $msg];
    }

    public static function takeFlash(): array
    {
        $f = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $f;
    }

    public static function back(array $q = []): never
    {
        redirect(self::url($q));
    }

    /** Seite im Admin-Layout ausgeben. */
    public static function render(string $tpl, array $vars = [], string $title = ''): never
    {
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store');
        $vars['pageTitle'] = $title;
        $content = self::partial($tpl, $vars);
        echo self::partial('layout', $vars + ['content' => $content]);
        exit;
    }

    public static function partial(string $tpl, array $vars = []): string
    {
        extract($vars, EXTR_SKIP);
        ob_start();
        include FP_ROOT . '/admin/views/' . $tpl . '.php';
        return (string)ob_get_clean();
    }

    public static function requireAdmin(): void
    {
        if (!Auth::isAdmin()) {
            http_response_code(403);
            self::render('error', ['message' => 'Dieser Bereich ist nur für Administratoren.'], 'Kein Zugriff');
        }
    }

    /** Seitenleiste: Gruppen mit Einträgen [Ziel, Beschriftung, Icon, Rolle]. */
    public static function nav(): array
    {
        $ents = self::entities();
        $e = static fn(string $k, ?string $label = null): array => [['p' => $k], $label ?? $ents[$k]['title'], $ents[$k]['icon'], 'editor'];
        $s = static fn(string $g, string $label, string $icon, string $role = 'editor'): array => [['p' => 'settings', 'g' => $g], $label, $icon, $role];
        $pending = (int)Db::val("SELECT COUNT(*) FROM comments WHERE status = 'pending'");
        $newMsg = (int)Db::val("SELECT COUNT(*) FROM messages WHERE status = 'new'");
        return [
            ['Übersicht', [
                [['p' => 'dashboard'], 'Dashboard', 'layout-dashboard', 'editor'],
                [['p' => 'messages'], 'Nachrichten', 'inbox', 'editor', $newMsg],
                [['p' => 'comments'], 'Kommentare', 'message-square', 'editor', $pending],
            ]],
            ['Startseite', [
                $e('sections', 'Bereiche & Reihenfolge'),
                $s('hero', 'Startbereich (Hero)', 'sparkles'),
                $e('stats'),
                $s('about', 'Über mich', 'user'),
                $e('about_badges'),
                $s('motto', 'Motto', 'quote'),
                $e('interests'),
                $e('facts'),
                $e('timeline'),
                $e('projects'),
                $e('former_sites'),
                $e('maker_projects'),
                $s('shop', 'Shop', 'shopping-cart'),
                $e('shop_items'),
                $e('skill_groups'),
                $e('skills'),
                $e('cv_entries'),
                $s('music', 'Musik', 'music'),
                $e('music_genres'),
                $e('tracks'),
                $e('tools'),
                $e('faq'),
                $s('partner', 'Partner-Programm', 'handshake'),
                $e('partner_steps'),
            ]],
            ['Blog & Seiten', [
                $e('posts'),
                $e('pages'),
                $s('blog', 'Blog-Einstellungen', 'settings'),
            ]],
            ['Website', [
                $s('general', 'Allgemein & Design', 'palette', 'admin'),
                $e('social_links'),
                $s('contact', 'Kontakt & E-Mail', 'mail', 'admin'),
                $s('seo', 'SEO & Social', 'search', 'admin'),
                $s('effects', 'Animationen', 'wand-sparkles', 'admin'),
                $s('privacy', 'Datenschutz-Hinweis', 'shield-check', 'admin'),
                $s('advanced', 'Erweitert', 'code', 'admin'),
                [['p' => 'media'], 'Medien', 'image', 'editor'],
            ]],
            ['System', [
                [['p' => 'users'], 'Benutzer', 'users', 'admin'],
                [['p' => 'profile'], 'Mein Konto', 'user-round', 'editor'],
                [['p' => 'backup'], 'Backup', 'hard-drive', 'admin'],
                [['p' => 'log'], 'Aktivität', 'history', 'admin'],
            ]],
        ];
    }

    public static function isActive(array $target): bool
    {
        $p = $_GET['p'] ?? 'dashboard';
        if (($target['p'] ?? '') !== $p) {
            return false;
        }
        return $p !== 'settings' || ($target['g'] ?? '') === ($_GET['g'] ?? 'general');
    }

    /* ------------------------------------------------------------ Formularfelder */

    /** HTML eines Feldes. Bilinguale Felder erzeugen DE- und EN-Variante. */
    public static function field(array $f, array $row, array $errors = []): string
    {
        if (!empty($f['bilingual'])) {
            $html = '';
            foreach (['de' => 'Deutsch', 'en' => 'English'] as $lg => $lgName) {
                $fl = $f;
                $fl['name'] = $f['name'] . '_' . $lg;
                unset($fl['bilingual']);
                $fl['lang'] = $lg;
                $fl['label'] = $f['label'] . ' · ' . strtoupper($lg);
                if ($lg === 'en') {
                    unset($fl['required'], $fl['required_de']);
                } elseif (!empty($f['required_de'])) {
                    $fl['required'] = true;
                }
                $html .= '<div class="lang-pane lang-' . $lg . '">' . self::field($fl, $row, $errors) . '</div>';
            }
            return $html;
        }

        $name = $f['name'];
        $type = $f['type'] ?? 'text';
        $val = $row[$name] ?? ($f['default'] ?? '');
        $id = 'f-' . $name;
        $err = $errors[$name] ?? '';
        $label = '<label class="lbl" for="' . e($id) . '">' . e($f['label'])
            . (!empty($f['required']) ? ' <b class="req" title="Pflichtfeld">*</b>' : '') . '</label>';
        $help = !empty($f['help']) ? '<p class="help">' . e($f['help']) . '</p>' : '';
        $errHtml = $err ? '<p class="err">' . e($err) . '</p>' : '';
        $cls = 'fw fw-' . $type . ($err ? ' has-err' : '') . (($f['width'] ?? '') === 'half' ? ' fw-half' : '');
        $req = !empty($f['required']) ? ' required' : '';

        $input = match ($type) {
            'readonly' => '<input class="inp" id="' . e($id) . '" type="text" value="' . e((string)$val) . '" readonly>',
            'textarea' => '<textarea class="inp" id="' . e($id) . '" name="' . e($name) . '" rows="' . (int)($f['rows'] ?? 4) . '"' . $req . '>' . e((string)$val) . '</textarea>',
            'code' => '<textarea class="inp inp-code" id="' . e($id) . '" name="' . e($name) . '" rows="' . (int)($f['rows'] ?? 6) . '" spellcheck="false">' . e((string)$val) . '</textarea>',
            'richtext', 'richtext_mini' => '<div class="rte" data-toolbar="' . ($type === 'richtext' ? 'full' : 'mini') . '"><textarea class="rte-src" name="' . e($name) . '" id="' . e($id) . '" hidden>'
                . e((string)$val) . '</textarea><div class="rte-editor"></div></div>',
            'select' => self::select($f, $name, $id, (string)$val, $req),
            'bool' => '<label class="switch"><input type="hidden" name="' . e($name) . '" value="0"><input type="checkbox" id="' . e($id) . '" name="' . e($name) . '" value="1"'
                . ((string)$val === '1' || $val === 1 || $val === true ? ' checked' : '') . '><span class="switch-ui"></span><span class="switch-text">' . e($f['label']) . '</span></label>',
            'icon' => '<div class="icon-field" data-icon-field><span class="icon-preview">' . icon((string)$val ?: 'sparkles') . '</span>'
                . '<input class="inp" id="' . e($id) . '" name="' . e($name) . '" value="' . e((string)$val) . '" maxlength="60" placeholder="z. B. rocket oder ein Emoji" autocomplete="off">'
                . '<button class="btn btn-soft" type="button" data-icon-pick>Icon wählen</button></div>',
            'image' => self::imageField($name, $id, (string)$val),
            'datetime' => '<input class="inp" id="' . e($id) . '" name="' . e($name) . '" type="datetime-local" value="'
                . e($val ? date('Y-m-d\TH:i', strtotime((string)$val) ?: time()) : '') . '">',
            'password' => '<input class="inp" id="' . e($id) . '" name="' . e($name) . '" type="password" value="" autocomplete="new-password" placeholder="' . ($val !== '' ? '•••••••• (unverändert)' : '') . '">',
            'color' => '<div class="color-field"><input type="color" value="' . e(valid_hex((string)$val) ? (string)$val : '#F97316') . '" data-color-picker>'
                . '<input class="inp" id="' . e($id) . '" name="' . e($name) . '" value="' . e((string)$val) . '" maxlength="7" pattern="#[0-9a-fA-F]{6}"></div>',
            'slug' => '<div class="slug-field"><span>/</span><input class="inp" id="' . e($id) . '" name="' . e($name) . '" value="' . e((string)$val)
                . '" data-slug-from="' . e((string)($f['from'] ?? '')) . '" maxlength="120" pattern="[a-z0-9\-]*"></div>',
            'number' => '<input class="inp" id="' . e($id) . '" name="' . e($name) . '" type="number" value="' . e((string)$val) . '"'
                . (isset($f['step']) ? ' step="' . e((string)$f['step']) . '"' : '') . (isset($f['min']) ? ' min="' . (int)$f['min'] . '"' : '')
                . (isset($f['max']) ? ' max="' . (int)$f['max'] . '"' : '') . $req . '>',
            default => '<input class="inp" id="' . e($id) . '" name="' . e($name) . '" type="' . ($type === 'url' ? 'text' : ($type === 'email' ? 'email' : 'text')) . '" value="' . e((string)$val) . '"'
                . ' maxlength="' . (int)($f['max'] ?? 255) . '"' . $req . ($type === 'url' ? ' inputmode="url" placeholder="https://"' : '') . '>',
        };

        if ($type === 'bool') {
            return '<div class="' . e($cls) . '">' . $input . $help . '</div>';
        }
        return '<div class="' . e($cls) . '">' . $label . $input . $help . $errHtml . '</div>';
    }

    private static function select(array $f, string $name, string $id, string $val, string $req): string
    {
        $opts = $f['options'] ?? [];
        if (isset($f['options_from'])) {
            [$table, $key, $labelCol] = $f['options_from'];
            $opts = [];
            foreach (Db::all("SELECT `$key` AS k, `$labelCol` AS l FROM `$table` ORDER BY sort ASC, id ASC") as $r) {
                $opts[$r['k']] = $r['l'];
            }
        }
        $html = '<select class="inp" id="' . e($id) . '" name="' . e($name) . '"' . $req . '>';
        foreach ($opts as $k => $l) {
            $html .= '<option value="' . e((string)$k) . '"' . ((string)$k === $val ? ' selected' : '') . '>' . e((string)$l) . '</option>';
        }
        return $html . '</select>';
    }

    private static function imageField(string $name, string $id, string $val): string
    {
        $url = $val !== '' ? media_url($val) : '';
        return '<div class="image-field" data-image-field>'
            . '<div class="image-preview' . ($url ? ' has-img' : '') . '">' . ($url ? '<img src="' . e($url) . '" alt="">' : icon('image')) . '</div>'
            . '<div class="image-actions"><input class="inp" id="' . e($id) . '" name="' . e($name) . '" value="' . e($val) . '" placeholder="Pfad oder URL" autocomplete="off">'
            . '<div class="btn-row"><button class="btn btn-soft" type="button" data-image-pick>' . icon('image') . ' Aus Medien wählen</button>'
            . '<button class="btn btn-ghost" type="button" data-image-clear>Entfernen</button></div></div></div>';
    }

    /** Anzeige einer Spalte in Listen. */
    public static function cell(array $entity, string $col, array $row): string
    {
        $val = $row[$col] ?? '';
        $field = null;
        foreach ($entity['fields'] as $f) {
            if ($f['name'] === $col || (($f['bilingual'] ?? false) && ($f['name'] . '_de') === $col)) {
                $field = $f;
                break;
            }
        }
        $type = $field['type'] ?? 'text';
        if ($col === 'icon' || $type === 'icon') {
            return $val !== '' ? '<span class="cell-icon">' . icon((string)$val) . '</span>' : '';
        }
        if ($type === 'image' || $col === 'image') {
            return $val !== '' ? '<img class="cell-thumb" src="' . e(media_url((string)$val)) . '" alt="" loading="lazy">' : '<span class="muted">—</span>';
        }
        if ($type === 'bool') {
            return ((int)$val === 1) ? '<span class="dot dot-on" title="Ja"></span>' : '<span class="dot" title="Nein"></span>';
        }
        if ($type === 'select' && $field) {
            $opts = $field['options'] ?? [];
            if (isset($field['options_from'])) {
                [$table, $key, $labelCol] = $field['options_from'];
                return e((string)(Db::val("SELECT `$labelCol` FROM `$table` WHERE `$key` = ?", [$val]) ?? $val));
            }
            $label = $opts[$val] ?? $val;
            if ($col === 'status') {
                $cls = in_array($val, ['published', 'live', 'done'], true) ? 'pill-ok' : (in_array($val, ['draft', 'planned', 'beta'], true) ? 'pill-warn' : '');
                return '<span class="pill ' . $cls . '">' . e((string)$label) . '</span>';
            }
            return e((string)$label);
        }
        if ($type === 'datetime') {
            return $val ? e(date('d.m.Y H:i', strtotime((string)$val) ?: 0)) : '<span class="muted">—</span>';
        }
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string)$val)) ?? '');
        return e(mb_strlen($text) > 90 ? mb_substr($text, 0, 89) . '…' : $text);
    }
}
