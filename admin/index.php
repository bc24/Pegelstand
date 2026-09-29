<?php
declare(strict_types=1);

/** Einstiegspunkt des Admin-Bereichs. */
require dirname(__DIR__) . '/app/bootstrap.php';
Lang::init('de');
Auth::start();
Security::adminHeaders();

$p = (string)($_GET['p'] ?? 'dashboard');

try {
    // Abmelden (nur per POST + CSRF)
    if ($p === 'logout' && is_post()) {
        Csrf::check();
        Auth::logout();
        redirect(Admin::url(['loggedout' => 1]));
    }

    if (!Auth::check()) {
        $error = '';
        $step = Auth::pending2fa() ? '2fa' : 'login';
        if (is_post()) {
            Csrf::check();
            $do = (string)($_POST['do'] ?? '');
            if ($do === 'login') {
                $r = Auth::attempt(trim((string)($_POST['username'] ?? '')), (string)($_POST['password'] ?? ''));
                if ($r === 'ok') {
                    Admin::back();
                }
                $step = $r === '2fa' ? '2fa' : 'login';
                $error = match ($r) {
                    'locked' => 'Zu viele Fehlversuche. Bitte warte 15 Minuten.',
                    'invalid' => 'Benutzername oder Passwort ist falsch.',
                    default => '',
                };
            } elseif ($do === '2fa') {
                if (Auth::verify2fa((string)($_POST['code'] ?? ''))) {
                    Admin::back();
                }
                $step = Auth::pending2fa() ? '2fa' : 'login';
                $error = Auth::pending2fa() ? 'Der Code ist ungültig oder abgelaufen.' : 'Die Anmeldung ist abgelaufen. Bitte erneut anmelden.';
            } elseif ($do === 'cancel2fa') {
                unset($_SESSION['pending_2fa']);
                $step = 'login';
            }
        }
        header('Content-Type: text/html; charset=utf-8');
        echo Admin::partial('login', ['step' => $step, 'error' => $error, 'loggedOut' => isset($_GET['loggedout'])]);
        exit;
    }

    switch ($p) {
        case 'dashboard':
            AdminPages::dashboard();
        case 'settings':
            AdminPages::settings();
        case 'messages':
            AdminPages::messages();
        case 'comments':
            AdminPages::comments();
        case 'media':
            AdminPages::media();
        case 'users':
            AdminPages::users();
        case 'profile':
            AdminPages::profile();
        case 'backup':
            AdminPages::backup();
        case 'log':
            AdminPages::log();
        default:
            Crud::handle($p);
    }
} catch (Throwable $e) {
    error_log('[fp-admin] ' . $e::class . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    if (Auth::check()) {
        echo Admin::partial('layout', [
            'pageTitle' => 'Fehler',
            'content' => '<div class="card"><h2>Ein Fehler ist aufgetreten</h2><p class="muted">' . e(cfg('debug', false) ? $e->getMessage() : 'Details stehen im Fehlerprotokoll (storage/logs/php-error.log).') . '</p></div>',
        ]);
    } else {
        echo 'Fehler.';
    }
}
