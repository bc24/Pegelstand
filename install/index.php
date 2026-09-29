<?php
declare(strict_types=1);

/** Web-Installer: prüft Anforderungen, legt Tabellen und Startinhalte an, erzeugt config/config.php. */
define('FP_ROOT', dirname(__DIR__));
spl_autoload_register(static function (string $c): void {
    $f = FP_ROOT . '/app/' . $c . '.php';
    if (is_file($f)) {
        require $f;
    }
});
require FP_ROOT . '/app/helpers.php';

session_name('fp_install');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => is_https()]);
session_start();
$_SESSION['t'] ??= bin2hex(random_bytes(16));
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'self'; style-src 'unsafe-inline'; img-src 'self' data:; form-action 'self'; frame-ancestors 'none'");

$scheme = is_https() ? 'https' : 'http';
$host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
$dir = rtrim(str_replace('\\', '/', dirname(dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/install/index.php')))), '/');
$dir = $dir === '.' ? '' : $dir;
$defaultUrl = $scheme . '://' . $host . $dir;

$installed = Installer::isInstalled();
$errors = [];
$done = null;
$v = [
    'db_host' => 'localhost', 'db_port' => '3306', 'db_name' => '', 'db_user' => '', 'db_pass' => '',
    'admin' => 'frank', 'email' => 'frank@panzerit.de', 'site_url' => $defaultUrl,
];

if ($installed && is_post() && ($_POST['do'] ?? '') === 'remove' && hash_equals($_SESSION['t'], (string)($_POST['t'] ?? '')) && !empty($_SESSION['just_installed'])) {
    // Installer-Ordner löschen (nur direkt nach der Installation in derselben Sitzung)
    $rm = static function (string $p) use (&$rm): void {
        foreach (glob($p . '/{,.}*', GLOB_BRACE) ?: [] as $f) {
            if (in_array(basename($f), ['.', '..'], true)) {
                continue;
            }
            is_dir($f) ? $rm($f) : @unlink($f);
        }
        @rmdir($p);
    };
    $rm(__DIR__);
    header('Location: ' . ($dir ?: '') . '/admin/');
    exit;
}

if (!$installed && is_post() && hash_equals($_SESSION['t'], (string)($_POST['t'] ?? ''))) {
    foreach (array_keys($v) as $k) {
        $v[$k] = trim((string)($_POST[$k] ?? ''));
    }
    $pw = (string)($_POST['password'] ?? '');
    if (!Installer::requirementsOk()) {
        $errors[] = 'Die Systemvoraussetzungen sind nicht erfüllt (siehe oben).';
    }
    if ($v['db_name'] === '' || $v['db_user'] === '') {
        $errors[] = 'Bitte Datenbankname und Benutzer angeben.';
    }
    if (!preg_match('/^[A-Za-z0-9._-]{3,40}$/', $v['admin'])) {
        $errors[] = 'Benutzername: 3–40 Zeichen (Buchstaben, Zahlen, . _ -).';
    }
    if ($v['email'] !== '' && !filter_var($v['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Bitte eine gültige E-Mail-Adresse angeben.';
    }
    if (($msg = AdminPages::passwordProblem($pw)) !== null) {
        $errors[] = $msg;
    } elseif ($pw !== (string)($_POST['password2'] ?? '')) {
        $errors[] = 'Die Passwörter stimmen nicht überein.';
    }
    if (!preg_match('~^https?://[^\s]+$~', $v['site_url'])) {
        $errors[] = 'Bitte eine gültige Website-URL angeben (https://…).';
    }
    if (!$errors) {
        $db = ['host' => $v['db_host'], 'port' => (int)$v['db_port'], 'name' => $v['db_name'], 'user' => $v['db_user'], 'pass' => $v['db_pass']];
        if (($err = Installer::testDb($db)) !== null) {
            $errors[] = 'Datenbankverbindung fehlgeschlagen: ' . $err;
        } else {
            try {
                if (Db::tableExists('users')) {
                    throw new RuntimeException('In dieser Datenbank existieren bereits Tabellen (users). Bitte eine leere Datenbank verwenden.');
                }
                Installer::run($db, ['username' => $v['admin'], 'email' => $v['email'], 'password' => $pw],
                    ['site_url' => rtrim($v['site_url'], '/'), 'base_path' => $dir]);
                $_SESSION['just_installed'] = true;
                $done = ['admin' => $v['admin'], 'url' => rtrim($v['site_url'], '/')];
                $installed = true;
            } catch (Throwable $e) {
                $errors[] = 'Installation fehlgeschlagen: ' . $e->getMessage();
            }
        }
    }
}
$reqs = Installer::requirements();
$reqOk = Installer::requirementsOk();
$t = $_SESSION['t'];
?><!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Installation · Frank Panzer</title>
<style>
:root{--bg:#0b0f15;--surface:#121821;--line:rgba(255,255,255,.12);--text:#e9eef6;--muted:#8b97aa;--accent:#F97316;--ok:#34d399;--err:#f87171}
*{box-sizing:border-box}
body{margin:0;min-height:100vh;background:radial-gradient(60vw 50vw at 0% 0%,rgba(249,115,22,.22),transparent 70%),radial-gradient(50vw 50vw at 100% 100%,rgba(124,58,237,.18),transparent 70%),var(--bg);color:var(--text);font:400 16px/1.6 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;display:grid;place-items:start center;padding:2rem 1rem}
main{width:min(720px,100%)}
h1{font-size:2rem;margin:.2rem 0 .3rem;letter-spacing:-.02em}h2{font-size:1.1rem;margin:1.6rem 0 .6rem}
.logo{display:inline-grid;place-items:center;width:46px;height:46px;border-radius:14px;background:linear-gradient(135deg,#fdba74,#F97316,#c2410c);color:#160a02;font-weight:800;box-shadow:0 10px 30px -10px #F97316}
.card{background:rgba(18,24,33,.85);border:1px solid var(--line);border-radius:18px;padding:1.6rem;margin-top:1.2rem;backdrop-filter:blur(14px)}
.muted{color:var(--muted)}
ul.req{list-style:none;margin:0;padding:0;display:grid;gap:.35rem}
ul.req li{display:flex;gap:.6rem;align-items:baseline}
.ok::before{content:"✓";color:var(--ok);font-weight:700}.bad::before{content:"✕";color:var(--err);font-weight:700}
label{display:block;font-weight:600;font-size:.88rem;margin:.9rem 0 .3rem}
input{width:100%;padding:.7rem .85rem;border-radius:10px;border:1px solid var(--line);background:#182030;color:var(--text);font:inherit}
input:focus{outline:2px solid var(--accent);outline-offset:1px}
.row{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
@media(max-width:560px){.row{grid-template-columns:1fr}}
button,.btn{display:inline-block;margin-top:1.4rem;padding:.85rem 1.4rem;border:0;border-radius:11px;background:linear-gradient(180deg,#fb9a52,#F97316);color:#160a02;font:700 1rem system-ui;cursor:pointer;text-decoration:none}
.btn.ghost{background:transparent;border:1px solid var(--line);color:var(--text);margin-left:.5rem}
.err{background:rgba(248,113,113,.12);border:1px solid rgba(248,113,113,.45);border-radius:12px;padding:.8rem 1rem;margin:1rem 0}
.err p{margin:.15rem 0}
.success{border-color:rgba(52,211,153,.5)}
code{background:#182030;padding:.1em .4em;border-radius:6px}
small{color:var(--muted)}
</style>
</head>
<body>
<main>
  <span class="logo">FP</span>
  <h1>Frank Panzer — Installation</h1>
  <p class="muted">In wenigen Schritten ist deine Website samt Admin-Bereich einsatzbereit. Alle Beispielinhalte stammen von frank-panzer.de und sind später frei änderbar.</p>

<?php if ($done): ?>
  <div class="card success">
    <h2 style="margin-top:0">Installation abgeschlossen</h2>
    <p>Dein Administrator-Konto <strong><?= e($done['admin']) ?></strong> wurde angelegt.</p>
    <p><strong>Wichtig:</strong> Lösche jetzt den Ordner <code>install/</code>, damit niemand die Installation erneut aufrufen kann.</p>
    <form method="post"><input type="hidden" name="t" value="<?= e($t) ?>"><input type="hidden" name="do" value="remove">
      <button type="submit">Installer löschen &amp; zum Admin-Bereich</button>
      <a class="btn ghost" href="<?= e($dir . '/') ?>">Zur Website</a></form>
    <p><small>Schlägt das Löschen fehl (Dateirechte), entferne den Ordner <code>install/</code> per FTP/SSH.</small></p>
  </div>
<?php elseif ($installed): ?>
  <div class="card">
    <h2 style="margin-top:0">Bereits installiert</h2>
    <p>Diese Installation ist abgeschlossen. Aus Sicherheitsgründen ist der Installer gesperrt. Lösche den Ordner <code>install/</code> vom Server.</p>
    <a class="btn" href="<?= e($dir . '/admin/') ?>">Zum Admin-Bereich</a><a class="btn ghost" href="<?= e($dir . '/') ?>">Zur Website</a>
  </div>
<?php else: ?>
  <div class="card">
    <h2 style="margin-top:0">1 · Systemvoraussetzungen</h2>
    <ul class="req"><?php foreach ($reqs as [$label, $ok, $hint]): ?><li class="<?= $ok ? 'ok' : 'bad' ?>"><span><?= e($label) ?><?php if (!$ok): ?> <small>— <?= e($hint) ?></small><?php endif; ?></span></li><?php endforeach; ?></ul>
  </div>

  <form method="post" class="card" autocomplete="off">
    <input type="hidden" name="t" value="<?= e($t) ?>">
    <?php if ($errors): ?><div class="err" role="alert"><?php foreach ($errors as $er): ?><p><?= e($er) ?></p><?php endforeach; ?></div><?php endif; ?>
    <h2 style="margin-top:0">2 · Datenbank (MySQL / MariaDB)</h2>
    <div class="row"><div><label for="db_host">Host</label><input id="db_host" name="db_host" value="<?= e($v['db_host']) ?>" required></div>
      <div><label for="db_port">Port</label><input id="db_port" name="db_port" value="<?= e($v['db_port']) ?>" inputmode="numeric" required></div></div>
    <label for="db_name">Datenbankname</label><input id="db_name" name="db_name" value="<?= e($v['db_name']) ?>" required>
    <div class="row"><div><label for="db_user">Benutzer</label><input id="db_user" name="db_user" value="<?= e($v['db_user']) ?>" required></div>
      <div><label for="db_pass">Passwort</label><input id="db_pass" name="db_pass" type="password" value=""></div></div>
    <small>Die Datenbank muss leer sein und bereits existieren (z. B. im Plesk/cPanel angelegt).</small>

    <h2>3 · Administrator</h2>
    <div class="row"><div><label for="admin">Benutzername</label><input id="admin" name="admin" value="<?= e($v['admin']) ?>" required></div>
      <div><label for="email">E-Mail</label><input id="email" name="email" type="email" value="<?= e($v['email']) ?>"></div></div>
    <div class="row"><div><label for="password">Passwort</label><input id="password" name="password" type="password" autocomplete="new-password" required></div>
      <div><label for="password2">Passwort wiederholen</label><input id="password2" name="password2" type="password" autocomplete="new-password" required></div></div>
    <small>Mindestens 10 Zeichen mit Buchstaben und Zahlen.</small>

    <h2>4 · Website</h2>
    <label for="site_url">Website-URL</label><input id="site_url" name="site_url" value="<?= e($v['site_url']) ?>" required>
    <small>Ohne abschließenden Slash. Wird für Canonical-Links, Sitemap und Social-Vorschau genutzt.</small>

    <button type="submit" <?= $reqOk ? '' : 'disabled style="opacity:.5;cursor:not-allowed"' ?>>Jetzt installieren</button>
  </form>
<?php endif; ?>
</main>
</body>
</html>
