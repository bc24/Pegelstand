<?php
declare(strict_types=1);

/** Generische Verwaltung (Liste, Formular, Speichern, Sortieren, Löschen) für alle Einträge aus entities.php. */
final class Crud
{
    public static function handle(string $key): never
    {
        $ents = Admin::entities();
        if (!isset($ents[$key])) {
            http_response_code(404);
            Admin::render('error', ['message' => 'Diesen Bereich gibt es nicht.'], 'Nicht gefunden');
        }
        $e = $ents[$key] + ['key' => $key, 'can_add' => true, 'can_delete' => true, 'sortable' => false];

        if (is_post()) {
            Csrf::check();
            match ($_POST['do'] ?? 'save') {
                'save' => self::save($e),
                'delete' => self::delete($e),
                'toggle' => self::toggle($e),
                'reorder' => self::reorder($e),
                'duplicate' => self::duplicate($e),
                default => self::custom($e),
            };
        }

        $action = (string)($_GET['a'] ?? 'list');
        if ($action === 'new' && $e['can_add']) {
            self::form($e, self::defaults($e), null);
        }
        if ($action === 'edit') {
            $row = Db::one("SELECT * FROM `{$e['table']}` WHERE id = ?", [(int)($_GET['id'] ?? 0)]);
            if (!$row) {
                Admin::flash('err', 'Eintrag nicht gefunden.');
                Admin::back(['p' => $key]);
            }
            self::form($e, $row, $row);
        }
        self::list($e);
    }

    /* ------------------------------------------------------------ Ansichten */

    private static function list(array $e): never
    {
        $where = '';
        $params = [];
        $filterField = $e['filter'] ?? null;
        $filterVal = $filterField ? (string)($_GET['f'] ?? '') : '';
        if ($filterField && $filterVal !== '') {
            $where = " WHERE `$filterField` = ?";
            $params[] = $filterVal;
        }
        $rows = Db::all("SELECT * FROM `{$e['table']}`$where ORDER BY {$e['order']}", $params);
        $filterOpts = [];
        if ($filterField) {
            foreach ($e['fields'] as $f) {
                if ($f['name'] === $filterField) {
                    if (isset($f['options_from'])) {
                        [$t, $k, $l] = $f['options_from'];
                        foreach (Db::all("SELECT `$k` AS k, `$l` AS l FROM `$t` ORDER BY sort ASC, id ASC") as $r) {
                            $filterOpts[$r['k']] = $r['l'];
                        }
                    } else {
                        $filterOpts = $f['options'] ?? [];
                    }
                    $e['filter_label'] = $f['label'];
                }
            }
        }
        Admin::render('list', ['e' => $e, 'rows' => $rows, 'filterOpts' => $filterOpts, 'filterVal' => $filterVal], $e['title']);
    }

    private static function form(array $e, array $row, ?array $old, array $errors = []): never
    {
        Admin::render('form', ['e' => $e, 'row' => $row, 'old' => $old, 'errors' => $errors], ($old ? 'Bearbeiten: ' : 'Neu: ') . $e['singular']);
    }

    private static function defaults(array $e): array
    {
        $row = [];
        foreach ($e['fields'] as $f) {
            if (!empty($f['bilingual'])) {
                $row[$f['name'] . '_de'] = $f['default'] ?? '';
                $row[$f['name'] . '_en'] = $f['default'] ?? '';
            } else {
                $row[$f['name']] = $f['default'] ?? '';
            }
        }
        if (isset($_GET['f']) && !empty($e['filter'])) {
            $row[$e['filter']] = $_GET['f'];
        }
        return $row;
    }

    /* ------------------------------------------------------------ Aktionen */

    private static function save(array $e): never
    {
        $id = (int)($_POST['id'] ?? 0);
        $old = null;
        if ($id) {
            $old = Db::one("SELECT * FROM `{$e['table']}` WHERE id = ?", [$id]);
            if (!$old) {
                Admin::flash('err', 'Eintrag nicht gefunden.');
                Admin::back(['p' => $e['key']]);
            }
        } elseif (!$e['can_add']) {
            Admin::back(['p' => $e['key']]);
        }

        [$data, $errors] = self::collect($e, $_POST, $old);
        if ($errors) {
            self::form($e, array_merge($old ?? [], self::postedRow($e, $_POST)), $old, $errors);
        }
        if (isset($e['before_save'])) {
            $data = ($e['before_save'])($data, $old);
        }

        if ($old) {
            Db::update($e['table'], $id, $data);
            Auth::log('update', $e['key'], (string)$id, self::labelOf($e, $data + $old));
        } else {
            if ($e['sortable']) {
                $data['sort'] = (int)Db::val("SELECT COALESCE(MAX(sort), 0) + 10 FROM `{$e['table']}`");
            }
            $id = Db::insert($e['table'], $data);
            Auth::log('create', $e['key'], (string)$id, self::labelOf($e, $data));
        }
        Admin::flash('ok', $e['singular'] . ' gespeichert.');
        if (!empty($_POST['stay'])) {
            Admin::back(['p' => $e['key'], 'a' => 'edit', 'id' => $id]);
        }
        Admin::back(['p' => $e['key']] + (!empty($e['filter']) && !empty($_POST['_f']) ? ['f' => $_POST['_f']] : []));
    }

    private static function custom(array $e): never
    {
        $do = (string)($_POST['do'] ?? '');
        if (isset($e['handlers'][$do])) {
            ($e['handlers'][$do])();
        }
        Admin::back(['p' => $e['key']]);
    }

    private static function delete(array $e): never
    {
        $id = (int)($_POST['id'] ?? 0);
        $row = Db::one("SELECT * FROM `{$e['table']}` WHERE id = ?", [$id]);
        if ($row && $e['can_delete']) {
            Db::delete($e['table'], $id);
            Auth::log('delete', $e['key'], (string)$id, self::labelOf($e, $row));
            Admin::flash('ok', $e['singular'] . ' gelöscht.');
        }
        Admin::back(['p' => $e['key']]);
    }

    private static function toggle(array $e): never
    {
        $field = (string)($_POST['field'] ?? '');
        $allowed = array_filter([$e['toggle'] ?? null, $e['toggle2'] ?? null]);
        if (!in_array($field, $allowed, true)) {
            json_out(['ok' => false], 400);
        }
        $id = (int)($_POST['id'] ?? 0);
        $cur = Db::val("SELECT `$field` FROM `{$e['table']}` WHERE id = ?", [$id]);
        if ($cur === null) {
            json_out(['ok' => false], 404);
        }
        $new = (int)$cur === 1 ? 0 : 1;
        Db::update($e['table'], $id, [$field => $new]);
        Auth::log('toggle', $e['key'], (string)$id, $field . '=' . $new);
        json_out(['ok' => true, 'value' => $new]);
    }

    private static function reorder(array $e): never
    {
        if (!$e['sortable']) {
            json_out(['ok' => false], 400);
        }
        $ids = array_values(array_filter(array_map('intval', (array)($_POST['ids'] ?? []))));
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        foreach ($ids as $i => $id) {
            Db::update($e['table'], $id, ['sort' => ($i + 1) * 10]);
        }
        $pdo->commit();
        Auth::log('reorder', $e['key'], '', count($ids) . ' Einträge');
        json_out(['ok' => true]);
    }

    private static function duplicate(array $e): never
    {
        $row = Db::one("SELECT * FROM `{$e['table']}` WHERE id = ?", [(int)($_POST['id'] ?? 0)]);
        if ($row && $e['can_add']) {
            unset($row['id']);
            foreach (['slug'] as $c) {
                if (isset($row[$c])) {
                    $row[$c] = self::uniqueSlug($e['table'], $row[$c] . '-kopie', 0);
                }
            }
            foreach (['title', 'title_de', 'name'] as $c) {
                if (isset($row[$c]) && $row[$c] !== '') {
                    $row[$c] .= ' (Kopie)';
                    break;
                }
            }
            foreach (['visible'] as $c) {
                if (array_key_exists($c, $row)) {
                    $row[$c] = 0;
                }
            }
            if (isset($row['status']) && $e['key'] === 'posts') {
                $row['status'] = 'draft';
                $row['likes'] = $row['views'] = 0;
            }
            if (isset($row['created_at'])) {
                $row['created_at'] = now();
            }
            if ($e['sortable']) {
                $row['sort'] = (int)Db::val("SELECT COALESCE(MAX(sort), 0) + 10 FROM `{$e['table']}`");
            }
            $newId = Db::insert($e['table'], $row);
            Auth::log('duplicate', $e['key'], (string)$newId);
            Admin::flash('ok', 'Als Kopie angelegt (unsichtbar bzw. Entwurf).');
            Admin::back(['p' => $e['key'], 'a' => 'edit', 'id' => $newId]);
        }
        Admin::back(['p' => $e['key']]);
    }

    /* ------------------------------------------------------------ Validierung */

    /** Beim Fehler wieder anzeigbare Werte aus POST. */
    private static function postedRow(array $e, array $post): array
    {
        $row = [];
        foreach ($e['fields'] as $f) {
            $names = !empty($f['bilingual']) ? [$f['name'] . '_de', $f['name'] . '_en'] : [$f['name']];
            foreach ($names as $n) {
                if (array_key_exists($n, $post)) {
                    $row[$n] = is_array($post[$n]) ? '' : (string)$post[$n];
                }
            }
        }
        return $row;
    }

    /** @return array{0:array,1:array} [Daten, Fehler] */
    public static function collect(array $e, array $post, ?array $old): array
    {
        $data = [];
        $errors = [];
        foreach ($e['fields'] as $f) {
            $variants = [];
            if (!empty($f['bilingual'])) {
                foreach (['de', 'en'] as $lg) {
                    $fl = $f;
                    $fl['name'] = $f['name'] . '_' . $lg;
                    unset($fl['bilingual']);
                    if ($lg === 'en') {
                        unset($fl['required'], $fl['required_de']);
                    } elseif (!empty($f['required_de'])) {
                        $fl['required'] = true;
                    }
                    $variants[] = $fl;
                }
            } else {
                $variants[] = $f;
            }
            foreach ($variants as $fl) {
                $name = $fl['name'];
                $type = $fl['type'] ?? 'text';
                if ($type === 'readonly') {
                    continue;
                }
                $raw = $post[$name] ?? '';
                if (is_array($raw)) {
                    $raw = '';
                }
                $raw = (string)$raw;
                $value = self::coerce($fl, $type, $raw, $e, $old, $errors);
                if ($value !== self::SKIP) {
                    $data[$name] = $value;
                }
            }
        }
        return [$data, $errors];
    }

    private const SKIP = "\0skip";

    private static function coerce(array $f, string $type, string $raw, array $e, ?array $old, array &$errors): mixed
    {
        $name = $f['name'];
        $label = $f['label'];
        $required = !empty($f['required']);
        $trim = trim(str_replace("\0", '', $raw));

        switch ($type) {
            case 'bool':
                return ($trim !== '' && $trim !== '0') ? 1 : 0;
            case 'richtext':
            case 'richtext_mini':
                $html = Html::sanitize($raw);
                if (preg_match('~^(<p>(<br>|\s|&nbsp;)*</p>\s*)*$~', $html)) {
                    $html = '';
                }
                if ($required && $html === '') {
                    $errors[$name] = "$label ist ein Pflichtfeld.";
                }
                return $html;
            case 'textarea':
            case 'code':
                if ($required && $trim === '') {
                    $errors[$name] = "$label ist ein Pflichtfeld.";
                }
                return mb_substr(str_replace("\r\n", "\n", $raw), 0, 30000);
            case 'number':
                if ($trim === '') {
                    if ($required) {
                        $errors[$name] = "$label ist ein Pflichtfeld.";
                    }
                    return $f['default'] ?? 0;
                }
                if (!is_numeric($trim)) {
                    $errors[$name] = "$label muss eine Zahl sein.";
                    return self::SKIP;
                }
                $n = str_contains($trim, '.') || str_contains((string)($f['step'] ?? ''), '.') ? (float)$trim : (int)$trim;
                if (isset($f['min'])) {
                    $n = max($f['min'], $n);
                }
                if (isset($f['max'])) {
                    $n = min($f['max'], $n);
                }
                return $n;
            case 'select':
                $opts = $f['options'] ?? [];
                if (isset($f['options_from'])) {
                    [$t, $k] = $f['options_from'];
                    $opts = array_flip(array_map('strval', Db::col("SELECT `$k` FROM `$t`")));
                }
                if (!array_key_exists($trim, $opts)) {
                    $errors[$name] = "$label: ungültige Auswahl.";
                    return self::SKIP;
                }
                return $trim;
            case 'url':
                if ($trim === '') {
                    if ($required) {
                        $errors[$name] = "$label ist ein Pflichtfeld.";
                    }
                    return '';
                }
                if (!preg_match('~^(https?://|/|#|mailto:)~i', $trim)) {
                    $errors[$name] = "$label muss mit https:// beginnen.";
                    return self::SKIP;
                }
                return mb_substr($trim, 0, 255);
            case 'email':
                if ($trim !== '' && !filter_var($trim, FILTER_VALIDATE_EMAIL)) {
                    $errors[$name] = "$label ist keine gültige E-Mail-Adresse.";
                    return self::SKIP;
                }
                return $trim;
            case 'image':
                if ($trim !== '' && (str_contains($trim, '..') || !preg_match('~^(uploads/|assets/|https?://)~', $trim))) {
                    $errors[$name] = "$label: ungültiger Pfad.";
                    return self::SKIP;
                }
                return $trim;
            case 'color':
                if ($trim === '') {
                    return '';
                }
                if (!valid_hex($trim)) {
                    $errors[$name] = "$label: bitte einen Hex-Wert wie #F97316 angeben.";
                    return self::SKIP;
                }
                return strtoupper($trim);
            case 'icon':
                return mb_substr(preg_replace('/[<>"\']/', '', $trim) ?? '', 0, 60);
            case 'datetime':
                if ($trim === '') {
                    return null;
                }
                $ts = strtotime($trim);
                if (!$ts) {
                    $errors[$name] = "$label: ungültiges Datum.";
                    return self::SKIP;
                }
                return date('Y-m-d H:i:s', $ts);
            case 'slug':
                $slug = slugify($trim !== '' ? $trim : (string)($_POST[$f['from'] ?? ''] ?? ''));
                if (in_array($slug, $e['reserved_slugs'] ?? [], true)) {
                    $errors[$name] = "„{$slug}“ ist reserviert.";
                    return self::SKIP;
                }
                return self::uniqueSlug($e['table'], $slug, (int)($old['id'] ?? 0));
            default: // text
                if ($required && $trim === '') {
                    $errors[$name] = "$label ist ein Pflichtfeld.";
                    return '';
                }
                if ($name === 'anchor' && !preg_match('/^[a-z0-9-]+$/', $trim)) {
                    $errors[$name] = "$label: nur Kleinbuchstaben, Zahlen und Bindestriche.";
                    return self::SKIP;
                }
                return mb_substr($trim, 0, (int)($f['max'] ?? 255));
        }
    }

    public static function uniqueSlug(string $table, string $slug, int $ignoreId): string
    {
        $base = $slug = slugify($slug);
        $i = 2;
        while (Db::val("SELECT COUNT(*) FROM `$table` WHERE slug = ? AND id <> ?", [$slug, $ignoreId])) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    private static function labelOf(array $e, array $row): string
    {
        foreach (['title_de', 'title', 'label_de', 'name', 'domain', 'q_de', 'text_de', 'year'] as $c) {
            if (!empty($row[$c])) {
                return mb_substr(strip_tags((string)$row[$c]), 0, 80);
            }
        }
        return $e['singular'];
    }
}
