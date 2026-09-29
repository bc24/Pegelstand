<?php
declare(strict_types=1);

/** Seiten des Admin-Bereichs, die nicht über die generische Verwaltung laufen. */
final class AdminPages
{
    /* ------------------------------------------------------------ Dashboard */

    public static function dashboard(): never
    {
        $counts = [
            'messages' => (int)Db::val("SELECT COUNT(*) FROM messages WHERE status = 'new'"),
            'comments' => (int)Db::val("SELECT COUNT(*) FROM comments WHERE status = 'pending'"),
            'posts'    => (int)Db::val("SELECT COUNT(*) FROM posts WHERE status = 'published' AND published_at <= ?", [now()]),
            'scheduled' => (int)Db::val("SELECT COUNT(*) FROM posts WHERE status = 'published' AND published_at > ?", [now()]),
            'drafts'   => (int)Db::val("SELECT COUNT(*) FROM posts WHERE status = 'draft'"),
            'projects' => (int)Db::val('SELECT COUNT(*) FROM projects WHERE visible = 1'),
            'media'    => (int)Db::val('SELECT COUNT(*) FROM media'),
        ];
        $perDay = Visits::perDay(14);
        $checks = [];
        $installDir = is_dir(FP_ROOT . '/install');
        $checks[] = [!$installDir, $installDir ? 'Der Ordner /install/ existiert noch. Lösche ihn nach der Einrichtung vom Server.' : 'Installer-Ordner entfernt.', $installDir ? 'warn' : 'ok'];
        $checks[] = [is_https() || in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true), is_https() ? 'Verbindung ist per HTTPS verschlüsselt.' : 'Die Seite wird ohne HTTPS aufgerufen — aktiviere SSL beim Hoster.', is_https() ? 'ok' : 'warn'];
        $checks[] = [!cfg('debug', false), cfg('debug', false) ? 'Debug-Modus ist an (config.php) — im Livebetrieb ausschalten.' : 'Debug-Modus ist aus.', cfg('debug', false) ? 'warn' : 'ok'];
        $checks[] = [is_writable(FP_ROOT . '/uploads'), is_writable(FP_ROOT . '/uploads') ? 'Uploads-Ordner beschreibbar.' : 'Uploads-Ordner ist nicht beschreibbar.', is_writable(FP_ROOT . '/uploads') ? 'ok' : 'err'];
        $checks[] = [extension_loaded('gd'), extension_loaded('gd') ? 'GD aktiv (Vorschaubilder, EXIF-Bereinigung).' : 'GD fehlt: keine Vorschaubilder/Bildbereinigung.', extension_loaded('gd') ? 'ok' : 'warn'];
        $mailOk = setting('mail_transport', 'mail') === 'smtp' ? setting('smtp_host') !== '' : true;
        $checks[] = [$mailOk, $mailOk ? 'Mail-Versand konfiguriert (' . setting('mail_transport', 'mail') . ').' : 'SMTP ist gewählt, aber kein Host eingetragen.', $mailOk ? 'ok' : 'warn'];
        $last = Backup::newest();
        if ($last === null) {
            $checks[] = [false, 'Noch keine automatische Server-Sicherung. Unter Backup steht der Cron-Befehl (bin/backup.php).', 'info'];
        } else {
            $days = (int)floor((time() - $last) / 86400);
            $checks[] = [$days <= 2, $days <= 2 ? 'Letzte Server-Sicherung: ' . date('d.m.Y H:i', $last) . '.' : 'Letzte Server-Sicherung ist ' . $days . ' Tage alt — läuft der Cron-Job noch?', $days <= 2 ? 'ok' : 'warn'];
        }
        $me = Auth::user();
        $checks[] = [(int)$me['totp_enabled'] === 1, (int)$me['totp_enabled'] === 1 ? 'Zwei-Faktor-Anmeldung ist für dein Konto aktiv.' : 'Empfehlung: Zwei-Faktor-Anmeldung unter „Mein Konto“ aktivieren.', (int)$me['totp_enabled'] === 1 ? 'ok' : 'info'];

        Admin::render('dashboard', [
            'counts' => $counts,
            'perDay' => $perDay,
            'top' => Visits::topPages(30, 6),
            'total7' => array_sum(array_slice($perDay, -7)),
            'total30' => (int)Db::val('SELECT COALESCE(SUM(hits),0) FROM visits WHERE day >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)'),
            'messages' => Db::all('SELECT * FROM messages ORDER BY created_at DESC LIMIT 5'),
            'pendingComments' => Db::all("SELECT c.*, p.title_de AS post_title FROM comments c JOIN posts p ON p.id = c.post_id WHERE c.status = 'pending' ORDER BY c.created_at DESC LIMIT 5"),
            'activity' => Auth::isAdmin() ? Db::all("SELECT * FROM activity_log WHERE action NOT IN ('toggle','reorder') ORDER BY id DESC LIMIT 8") : [],
            'checks' => $checks,
            'dbVersion' => (string)Db::val('SELECT VERSION()'),
        ], 'Dashboard');
    }

    /* ------------------------------------------------------------ Einstellungen */

    public static function settings(): never
    {
        $schema = Settings::schema();
        $group = (string)($_GET['g'] ?? 'general');
        if (!isset($schema[$group])) {
            $group = 'general';
        }
        if (!empty($schema[$group]['admin_only'])) {
            Admin::requireAdmin();
        }
        $isAdmin = Auth::isAdmin();
        $fields = [];
        foreach ($schema[$group]['fields'] as $f) {
            if (!empty($f['admin_only']) && !$isAdmin) {
                continue;
            }
            $f['name'] = $f['key'];
            $fields[] = $f;
        }
        $errors = [];
        $row = Settings::all();

        if (is_post()) {
            Csrf::check();
            if (($_POST['do'] ?? '') === 'testmail') {
                $to = setting('contact_notify') ?: setting('contact_email');
                $ok = Mailer::send($to, 'Testmail von ' . setting('site_name'), "Das ist eine Testnachricht aus dem Admin-Bereich.\nWenn du sie liest, funktioniert der Mail-Versand.\n");
                Admin::flash($ok ? 'ok' : 'err', $ok ? "Testmail an $to gesendet." : 'Versand fehlgeschlagen: ' . Mailer::$lastError);
                Admin::back(['p' => 'settings', 'g' => $group]);
            }
            [$data, $errors] = Crud::collect(['fields' => $fields, 'table' => 'settings', 'key' => 'settings'], $_POST, null);
            if (!$errors) {
                foreach ($fields as $f) {
                    if (($f['type'] ?? '') === 'password' && ($data[$f['name']] ?? '') === '') {
                        unset($data[$f['name']]);
                    }
                }
                if (isset($data['site_url'])) {
                    $data['site_url'] = rtrim((string)$data['site_url'], '/');
                }
                foreach ($data as $k => $v) {
                    Settings::set((string)$k, (string)$v);
                }
                Auth::log('settings', 'settings', $group, $schema[$group]['label']);
                Admin::flash('ok', 'Einstellungen gespeichert.');
                Admin::back(['p' => 'settings', 'g' => $group]);
            }
            $row = array_merge($row, $_POST);
        }
        Admin::render('settings', ['schema' => $schema, 'group' => $group, 'fields' => $fields, 'row' => $row, 'errors' => $errors], $schema[$group]['label']);
    }

    /* ------------------------------------------------------------ Nachrichten */

    public static function messages(): never
    {
        if (is_post()) {
            Csrf::check();
            $id = (int)($_POST['id'] ?? 0);
            $do = (string)($_POST['do'] ?? '');
            if ($do === 'delete') {
                Db::delete('messages', $id);
                Auth::log('delete', 'messages', (string)$id);
                Admin::flash('ok', 'Nachricht gelöscht.');
                Admin::back(['p' => 'messages']);
            }
            if (in_array($do, ['new', 'read', 'replied', 'archived', 'spam'], true)) {
                Db::update('messages', $id, ['status' => $do]);
                Admin::flash('ok', 'Status geändert.');
                Admin::back(['p' => 'messages'] + ($do === 'new' ? [] : ['id' => $id]));
            }
            Admin::back(['p' => 'messages']);
        }
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            $m = Db::one('SELECT * FROM messages WHERE id = ?', [$id]);
            if ($m) {
                if ($m['status'] === 'new') {
                    Db::update('messages', $id, ['status' => 'read']);
                    $m['status'] = 'read';
                }
                Admin::render('message', ['m' => $m], 'Nachricht von ' . $m['name']);
            }
        }
        $tab = (string)($_GET['s'] ?? 'inbox');
        $where = match ($tab) {
            'new' => "status = 'new'",
            'archived' => "status = 'archived'",
            'spam' => "status = 'spam'",
            'all' => '1=1',
            default => "status IN ('new','read','replied')",
        };
        $kind = in_array($_GET['k'] ?? '', ['contact', 'project', 'booking'], true) ? (string)$_GET['k'] : '';
        if ($kind !== '') {
            $where .= ' AND kind = ' . Db::pdo()->quote($kind);
        }
        $per = 25;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $total = (int)Db::val("SELECT COUNT(*) FROM messages WHERE $where");
        $rows = Db::all("SELECT * FROM messages WHERE $where ORDER BY created_at DESC LIMIT $per OFFSET " . (($page - 1) * $per));
        $counts = [];
        foreach (Db::all('SELECT status, COUNT(*) AS c FROM messages GROUP BY status') as $r) {
            $counts[$r['status']] = (int)$r['c'];
        }
        $kinds = [];
        foreach (Db::all("SELECT kind, COUNT(*) AS c FROM messages WHERE status IN ('new','read','replied') GROUP BY kind") as $r) {
            $kinds[$r['kind']] = (int)$r['c'];
        }
        Admin::render('messages', ['rows' => $rows, 'tab' => $tab, 'counts' => $counts, 'kind' => $kind, 'kinds' => $kinds, 'page' => $page, 'pages' => (int)ceil($total / $per)], 'Nachrichten');
    }

    /* ------------------------------------------------------------ Kommentare */

    public static function comments(): never
    {
        if (is_post()) {
            Csrf::check();
            $id = (int)($_POST['id'] ?? 0);
            $do = (string)($_POST['do'] ?? '');
            if ($do === 'delete') {
                Db::delete('comments', $id);
            } elseif (in_array($do, ['approved', 'pending', 'spam'], true)) {
                Db::update('comments', $id, ['status' => $do]);
            }
            Auth::log($do, 'comments', (string)$id);
            Admin::flash('ok', 'Kommentar aktualisiert.');
            Admin::back(['p' => 'comments', 's' => $_POST['_s'] ?? 'pending']);
        }
        $tab = in_array($_GET['s'] ?? '', ['pending', 'approved', 'spam'], true) ? $_GET['s'] : 'pending';
        $rows = Db::all(
            'SELECT c.*, p.title_de AS post_title, p.slug AS post_slug FROM comments c JOIN posts p ON p.id = c.post_id WHERE c.status = ? ORDER BY c.created_at DESC LIMIT 200',
            [$tab]
        );
        $counts = [];
        foreach (Db::all('SELECT status, COUNT(*) AS c FROM comments GROUP BY status') as $r) {
            $counts[$r['status']] = (int)$r['c'];
        }
        Admin::render('comments', ['rows' => $rows, 'tab' => $tab, 'counts' => $counts], 'Kommentare');
    }

    /* ------------------------------------------------------------ Medien */

    public static function media(): never
    {
        if (is_post()) {
            Csrf::check();
            $do = (string)($_POST['do'] ?? '');
            $xhr = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
            if ($do === 'upload') {
                $files = [];
                foreach ((array)($_FILES['files']['name'] ?? []) as $i => $n) {
                    $files[] = ['name' => $n, 'type' => $_FILES['files']['type'][$i], 'tmp_name' => $_FILES['files']['tmp_name'][$i],
                        'error' => $_FILES['files']['error'][$i], 'size' => $_FILES['files']['size'][$i]];
                }
                $results = [];
                foreach ($files as $f) {
                    $r = Media::store($f);
                    if ($r['ok']) {
                        Auth::log('upload', 'media', (string)$r['id'], $f['name']);
                    }
                    $results[] = $r + ['name' => $f['name']];
                }
                if ($xhr) {
                    json_out(['ok' => true, 'results' => array_map(static fn($r) => $r + (isset($r['path']) ? ['url' => media_url($r['path']), 'thumb_url' => media_url($r['thumb'] ?? $r['path'])] : []), $results)]);
                }
                $okN = count(array_filter($results, static fn($r) => $r['ok']));
                foreach ($results as $r) {
                    if (!$r['ok']) {
                        Admin::flash('err', $r['name'] . ': ' . $r['error']);
                    }
                }
                if ($okN) {
                    Admin::flash('ok', $okN . ' Datei(en) hochgeladen.');
                }
                Admin::back(['p' => 'media']);
            }
            $id = (int)($_POST['id'] ?? 0);
            if ($do === 'alt') {
                Db::update('media', $id, ['alt' => mb_substr(trim((string)($_POST['alt'] ?? '')), 0, 255)]);
                if ($xhr) {
                    json_out(['ok' => true]);
                }
                Admin::flash('ok', 'Alternativtext gespeichert.');
            }
            if ($do === 'delete') {
                Media::delete($id);
                Auth::log('delete', 'media', (string)$id);
                if ($xhr) {
                    json_out(['ok' => true]);
                }
                Admin::flash('ok', 'Datei gelöscht.');
            }
            Admin::back(['p' => 'media']);
        }

        $per = 48;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $q = trim((string)($_GET['q'] ?? ''));
        $where = $q !== '' ? 'WHERE original_name LIKE ? OR alt LIKE ?' : '';
        $params = $q !== '' ? ['%' . $q . '%', '%' . $q . '%'] : [];
        $total = (int)Db::val("SELECT COUNT(*) FROM media $where", $params);
        $rows = Db::all("SELECT * FROM media $where ORDER BY id DESC LIMIT $per OFFSET " . (($page - 1) * $per), $params);

        if (($_GET['a'] ?? '') === 'json') {
            header('Cache-Control: no-store');
            json_out(['ok' => true, 'page' => $page, 'pages' => (int)ceil($total / $per), 'items' => array_map(static fn($m) => [
                'id' => (int)$m['id'], 'path' => $m['path'], 'url' => media_url($m['path']), 'thumb' => media_url($m['thumb'] ?: $m['path']),
                'name' => $m['original_name'], 'alt' => $m['alt'], 'mime' => $m['mime'], 'size' => Media::humanSize((int)$m['size']),
                'w' => (int)$m['width'], 'h' => (int)$m['height'],
            ], $rows)]);
        }
        Admin::render('media', ['rows' => $rows, 'page' => $page, 'pages' => (int)ceil($total / $per), 'total' => $total, 'q' => $q], 'Medien');
    }

    /* ------------------------------------------------------------ Benutzer */

    public static function users(): never
    {
        Admin::requireAdmin();
        $me = Auth::user();
        if (is_post()) {
            Csrf::check();
            $do = (string)($_POST['do'] ?? '');
            $id = (int)($_POST['id'] ?? 0);
            if ($do === 'delete') {
                $admins = (int)Db::val("SELECT COUNT(*) FROM users WHERE role = 'admin'");
                $target = Db::one('SELECT * FROM users WHERE id = ?', [$id]);
                if ($id === (int)$me['id']) {
                    Admin::flash('err', 'Du kannst dein eigenes Konto nicht löschen.');
                } elseif ($target && $target['role'] === 'admin' && $admins <= 1) {
                    Admin::flash('err', 'Der letzte Administrator kann nicht gelöscht werden.');
                } elseif ($target) {
                    Db::delete('users', $id);
                    Auth::log('delete', 'users', (string)$id, $target['username']);
                    Admin::flash('ok', 'Benutzer gelöscht.');
                }
                Admin::back(['p' => 'users']);
            }
            if ($do === 'reset2fa') {
                Db::update('users', $id, ['totp_enabled' => 0, 'totp_secret' => null]);
                Auth::log('reset2fa', 'users', (string)$id);
                Admin::flash('ok', 'Zwei-Faktor-Anmeldung zurückgesetzt.');
                Admin::back(['p' => 'users']);
            }
            // save
            $username = trim((string)($_POST['username'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $role = ($_POST['role'] ?? 'editor') === 'admin' ? 'admin' : 'editor';
            $password = (string)($_POST['password'] ?? '');
            $errors = [];
            if (!preg_match('/^[A-Za-z0-9._-]{3,40}$/', $username)) {
                $errors['username'] = 'Benutzername: 3–40 Zeichen (Buchstaben, Zahlen, . _ -).';
            } elseif (Db::val('SELECT COUNT(*) FROM users WHERE username = ? AND id <> ?', [$username, $id])) {
                $errors['username'] = 'Dieser Benutzername ist bereits vergeben.';
            }
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Ungültige E-Mail-Adresse.';
            }
            if (($id === 0 || $password !== '') && ($msg = self::passwordProblem($password))) {
                $errors['password'] = $msg;
            }
            if ($id && $id === (int)$me['id'] && $role !== 'admin') {
                $errors['role'] = 'Du kannst dir die Administrator-Rolle nicht selbst entziehen.';
            }
            if ($errors) {
                Admin::render('user_form', ['u' => ['id' => $id, 'username' => $username, 'email' => $email, 'role' => $role], 'errors' => $errors], $id ? 'Benutzer bearbeiten' : 'Neuer Benutzer');
            }
            $data = ['username' => $username, 'email' => $email, 'role' => $role];
            if ($password !== '') {
                $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }
            if ($id) {
                Db::update('users', $id, $data);
                Auth::log('update', 'users', (string)$id, $username);
            } else {
                $data['created_at'] = now();
                $id = Db::insert('users', $data);
                Auth::log('create', 'users', (string)$id, $username);
            }
            Admin::flash('ok', 'Benutzer gespeichert.');
            Admin::back(['p' => 'users']);
        }
        $a = (string)($_GET['a'] ?? 'list');
        if ($a === 'new') {
            Admin::render('user_form', ['u' => ['id' => 0, 'username' => '', 'email' => '', 'role' => 'editor'], 'errors' => []], 'Neuer Benutzer');
        }
        if ($a === 'edit') {
            $u = Db::one('SELECT id, username, email, role, totp_enabled FROM users WHERE id = ?', [(int)($_GET['id'] ?? 0)]);
            if ($u) {
                Admin::render('user_form', ['u' => $u, 'errors' => []], 'Benutzer bearbeiten');
            }
        }
        Admin::render('users', ['rows' => Db::all('SELECT id, username, email, role, totp_enabled, last_login, created_at FROM users ORDER BY username'), 'me' => $me], 'Benutzer');
    }

    public static function passwordProblem(string $pw): ?string
    {
        if (mb_strlen($pw) < 10) {
            return 'Das Passwort muss mindestens 10 Zeichen lang sein.';
        }
        if (!preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) {
            return 'Das Passwort braucht Buchstaben und Zahlen.';
        }
        return null;
    }

    /* ------------------------------------------------------------ Mein Konto */

    public static function profile(): never
    {
        $me = Auth::user();
        $errors = [];
        if (is_post()) {
            Csrf::check();
            $do = (string)($_POST['do'] ?? '');
            $row = Db::one('SELECT * FROM users WHERE id = ?', [$me['id']]);
            if ($do === 'email') {
                $email = trim((string)($_POST['email'] ?? ''));
                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors['email'] = 'Ungültige E-Mail-Adresse.';
                } else {
                    Db::update('users', (int)$me['id'], ['email' => $email]);
                    Admin::flash('ok', 'E-Mail gespeichert.');
                    Admin::back(['p' => 'profile']);
                }
            }
            if ($do === 'password') {
                if (!password_verify((string)($_POST['current'] ?? ''), $row['password_hash'])) {
                    $errors['current'] = 'Das aktuelle Passwort stimmt nicht.';
                } elseif (($msg = self::passwordProblem((string)($_POST['password'] ?? ''))) !== null) {
                    $errors['password'] = $msg;
                } elseif (($_POST['password'] ?? '') !== ($_POST['password2'] ?? '')) {
                    $errors['password2'] = 'Die Passwörter stimmen nicht überein.';
                } else {
                    Db::update('users', (int)$me['id'], ['password_hash' => password_hash((string)$_POST['password'], PASSWORD_DEFAULT)]);
                    session_regenerate_id(true);
                    Auth::log('password', 'users', (string)$me['id']);
                    Admin::flash('ok', 'Passwort geändert.');
                    Admin::back(['p' => 'profile']);
                }
            }
            if ($do === '2fa_start') {
                $_SESSION['totp_pending'] = Totp::secret();
                Admin::back(['p' => 'profile', 'a' => '2fa']);
            }
            if ($do === '2fa_enable') {
                $secret = (string)($_SESSION['totp_pending'] ?? '');
                if ($secret !== '' && Totp::verify($secret, (string)($_POST['code'] ?? ''))) {
                    Db::update('users', (int)$me['id'], ['totp_secret' => $secret, 'totp_enabled' => 1]);
                    unset($_SESSION['totp_pending']);
                    Auth::log('2fa_on', 'users', (string)$me['id']);
                    Admin::flash('ok', 'Zwei-Faktor-Anmeldung ist jetzt aktiv.');
                    Admin::back(['p' => 'profile']);
                }
                $errors['code'] = 'Der Code stimmt nicht. Prüfe die Uhrzeit deines Handys und versuche es erneut.';
            }
            if ($do === '2fa_disable') {
                if (password_verify((string)($_POST['current'] ?? ''), $row['password_hash'])) {
                    Db::update('users', (int)$me['id'], ['totp_enabled' => 0, 'totp_secret' => null]);
                    Auth::log('2fa_off', 'users', (string)$me['id']);
                    Admin::flash('ok', 'Zwei-Faktor-Anmeldung deaktiviert.');
                    Admin::back(['p' => 'profile']);
                }
                $errors['current2'] = 'Das Passwort stimmt nicht.';
            }
        }
        $pending = (string)($_SESSION['totp_pending'] ?? '');
        Admin::render('profile', [
            'me' => Db::one('SELECT id, username, email, role, totp_enabled, last_login FROM users WHERE id = ?', [$me['id']]),
            'errors' => $errors,
            'pending' => ($_GET['a'] ?? '') === '2fa' ? $pending : '',
            'otpUri' => $pending !== '' ? Totp::uri($pending, (string)$me['username'], setting('site_name', 'Frank Panzer')) : '',
        ], 'Mein Konto');
    }

    /* ------------------------------------------------------------ Backup */

    public static function backup(): never
    {
        Admin::requireAdmin();
        if (is_post()) {
            Csrf::check();
            $do = (string)($_POST['do'] ?? '');
            if ($do === 'dump') {
                self::dump();
            }
            if ($do === 'zip') {
                self::zipUploads();
            }
            if ($do === 'server') {
                self::serverBackup();
            }
            if ($do === 'delete_server') {
                self::deleteServerBackup((string)($_POST['name'] ?? ''));
            }
            if ($do === 'restore') {
                self::restore();
            }
            Admin::back(['p' => 'backup']);
        }
        if (isset($_GET['dl'])) {
            self::downloadServerBackup((string)$_GET['dl']);
        }
        $tables = [];
        foreach (Db::all('SELECT table_name AS t, table_rows AS r, data_length + index_length AS s FROM information_schema.tables WHERE table_schema = DATABASE() ORDER BY table_name') as $r) {
            $tables[] = $r;
        }
        $upBytes = 0;
        $upFiles = 0;
        $dir = FP_ROOT . '/uploads';
        if (is_dir($dir)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $f) {
                if ($f->isFile() && $f->getFilename() !== '.htaccess' && $f->getFilename() !== '.gitkeep') {
                    $upBytes += $f->getSize();
                    $upFiles++;
                }
            }
        }
        Admin::render('backup', [
            'tables' => $tables, 'upBytes' => $upBytes, 'upFiles' => $upFiles, 'zip' => class_exists('ZipArchive'),
            'saved' => Backup::list(), 'backupDir' => Backup::dir(), 'root' => FP_ROOT,
        ], 'Backup');
    }

    private static function dump(): never
    {
        @set_time_limit(300);
        Auth::log('backup', 'system', '', 'SQL-Dump');
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="frank-panzer-backup-' . date('Y-m-d-His') . '.sql"');
        header('Cache-Control: no-store');
        Backup::writeSql(static function (string $chunk): void {
            echo $chunk;
        });
        exit;
    }

    private static function zipUploads(): never
    {
        if (!class_exists('ZipArchive')) {
            Admin::flash('err', 'Die PHP-Erweiterung zip ist nicht verfügbar.');
            Admin::back(['p' => 'backup']);
        }
        @set_time_limit(300);
        $tmp = (string)tempnam(sys_get_temp_dir(), 'fpz');
        Backup::zipUploads($tmp);
        Auth::log('backup', 'system', '', 'Uploads-ZIP');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="frank-panzer-uploads-' . date('Y-m-d-His') . '.zip"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        @unlink($tmp);
        exit;
    }

    /** Sicherung jetzt auf dem Server anlegen (wie der Cron-Aufruf). */
    private static function serverBackup(): never
    {
        try {
            $res = Backup::run();
            Auth::log('backup', 'system', '', 'Server-Backup');
            $names = implode(', ', array_column($res['files'], 'name'));
            Admin::flash('ok', 'Sicherung angelegt: ' . $names . ($res['skipped'] ? ' (' . implode('; ', $res['skipped']) . ')' : ''));
        } catch (Throwable $e) {
            Admin::flash('err', 'Sicherung fehlgeschlagen: ' . $e->getMessage());
        }
        Admin::back(['p' => 'backup']);
    }

    private static function deleteServerBackup(string $name): never
    {
        if (Backup::validName($name) && @unlink(Backup::dir() . '/' . $name)) {
            Auth::log('delete', 'backup', '', $name);
            Admin::flash('ok', 'Sicherung gelöscht.');
        } else {
            Admin::flash('err', 'Sicherung nicht gefunden.');
        }
        Admin::back(['p' => 'backup']);
    }

    private static function downloadServerBackup(string $name): never
    {
        $path = Backup::dir() . '/' . $name;
        if (!Backup::validName($name) || !is_file($path)) {
            Admin::flash('err', 'Sicherung nicht gefunden.');
            Admin::back(['p' => 'backup']);
        }
        Auth::log('backup', 'system', '', 'Download ' . $name);
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-store');
        readfile($path);
        exit;
    }

    private static function restore(): never
    {
        $f = $_FILES['sql'] ?? null;
        if (!$f || $f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            Admin::flash('err', 'Bitte eine .sql- oder .sql.gz-Datei auswählen.');
            Admin::back(['p' => 'backup']);
        }
        if (($_POST['confirm'] ?? '') !== 'WIEDERHERSTELLEN') {
            Admin::flash('err', 'Zur Bestätigung bitte WIEDERHERSTELLEN eintippen.');
            Admin::back(['p' => 'backup']);
        }
        @set_time_limit(300);
        $allowed = array_flip(Db::col('SHOW TABLES'));
        $gz = (string)file_get_contents($f['tmp_name'], false, null, 0, 2) === "\x1f\x8b";
        $h = fopen(($gz ? 'compress.zlib://' : '') . $f['tmp_name'], 'rb');
        $n = 0;
        try {
            while (($line = fgets($h)) !== false) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '--')) {
                    continue;
                }
                // INSERT-Blöcke laufen über mehrere Zeilen bis zum abschließenden ";"
                while (!str_ends_with($line, ';') && ($next = fgets($h)) !== false) {
                    $line .= "\n" . rtrim($next);
                }
                if (preg_match('/^(SET NAMES utf8mb4|SET FOREIGN_KEY_CHECKS=[01]);$/', $line)) {
                    Db::pdo()->exec($line);
                    continue;
                }
                if (!preg_match('/^(DROP TABLE IF EXISTS|CREATE TABLE|INSERT INTO) `([a-z0-9_]+)`/', $line, $m) || !isset($allowed[$m[2]])) {
                    throw new RuntimeException('Unerlaubte oder unbekannte Anweisung in der Datei.');
                }
                Db::pdo()->exec($line);
                $n++;
            }
            Admin::flash('ok', "Wiederherstellung abgeschlossen ($n Anweisungen).");
            Auth::log('restore', 'system', '', $n . ' Anweisungen');
        } catch (Throwable $e) {
            Admin::flash('err', 'Wiederherstellung abgebrochen: ' . $e->getMessage());
        } finally {
            fclose($h);
            try {
                Db::pdo()->exec('SET FOREIGN_KEY_CHECKS=1');
            } catch (Throwable) {
            }
        }
        Admin::back(['p' => 'backup']);
    }

    /* ------------------------------------------------------------ Aktivität */

    public static function log(): never
    {
        Admin::requireAdmin();
        $per = 40;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $total = (int)Db::val('SELECT COUNT(*) FROM activity_log');
        $rows = Db::all("SELECT * FROM activity_log ORDER BY id DESC LIMIT $per OFFSET " . (($page - 1) * $per));
        Admin::render('log', ['rows' => $rows, 'page' => $page, 'pages' => (int)ceil($total / $per)], 'Aktivität');
    }
}
