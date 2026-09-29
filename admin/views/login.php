<?php
/** @var string $step @var string $error @var bool $loggedOut */
$nonce = Security::nonce();
$site = setting('site_name', 'Frank Panzer');
?><!doctype html>
<html lang="de" data-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Anmelden · <?= e($site) ?> Admin</title>
<link rel="icon" type="image/svg+xml" href="<?= e(asset('img/favicon.svg')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
<style nonce="<?= e($nonce) ?>">:root{--accent:<?= e(valid_hex(setting('accent')) ? setting('accent') : '#F97316') ?>}</style>
</head>
<body class="admin login-page">
<div class="login-bg" aria-hidden="true"><i></i><i></i><i></i></div>
<main class="login-card">
  <div class="login-brand"><span class="brand-mark"><?= icon('tank') ?></span><div><strong><?= e($site) ?></strong><small>Admin-Bereich</small></div></div>
  <?php if ($loggedOut): ?><p class="flash flash-ok"><?= icon('circle-check') ?><span>Du wurdest abgemeldet.</span></p><?php endif; ?>
  <?php if ($error): ?><p class="flash flash-err" role="alert"><?= icon('triangle-alert') ?><span><?= e($error) ?></span></p><?php endif; ?>

  <?php if ($step === '2fa'): ?>
  <h1>Zwei-Faktor-Code</h1>
  <p class="muted">Gib den 6-stelligen Code aus deiner Authenticator-App ein.</p>
  <form method="post" class="form" autocomplete="off">
    <?= Csrf::field() ?><input type="hidden" name="do" value="2fa">
    <div class="fw"><label class="lbl" for="code">Code</label><input class="inp inp-lg code-input" id="code" name="code" inputmode="numeric" pattern="[0-9 ]*" maxlength="7" autocomplete="one-time-code" autofocus required></div>
    <button class="btn btn-primary btn-block" type="submit">Bestätigen</button>
  </form>
  <form method="post"><?= Csrf::field() ?><input type="hidden" name="do" value="cancel2fa"><button class="link-btn" type="submit">Zurück zur Anmeldung</button></form>
  <?php else: ?>
  <h1>Willkommen zurück</h1>
  <p class="muted">Melde dich an, um deine Website zu verwalten.</p>
  <form method="post" class="form">
    <?= Csrf::field() ?><input type="hidden" name="do" value="login">
    <div class="fw"><label class="lbl" for="username">Benutzername</label><input class="inp inp-lg" id="username" name="username" autocomplete="username" autofocus required></div>
    <div class="fw"><label class="lbl" for="password">Passwort</label><input class="inp inp-lg" id="password" name="password" type="password" autocomplete="current-password" required></div>
    <button class="btn btn-primary btn-block" type="submit">Anmelden</button>
  </form>
  <?php endif; ?>
  <a class="login-back" href="<?= e(rtrim((string)cfg('base_path', ''), '/') . '/') ?>"><?= icon('arrow-left') ?> Zur Website</a>
</main>
</body>
</html>
