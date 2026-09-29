<?php
/** @var string $content @var string $pageTitle */
$nonce = Security::nonce();
$me = Auth::user();
$isAdmin = Auth::isAdmin();
$flashes = Admin::takeFlash();
$site = setting('site_name', 'Frank Panzer');
$cfg = ['csrf' => Csrf::token(), 'admin' => Admin::url(), 'base' => rtrim((string)cfg('base_path', ''), '/'), 'icons' => Icons::names(),
    'sprite' => rtrim((string)cfg('base_path', ''), '/') . '/assets/img/icons.svg?v=' . Icons::version()];
$nav = Admin::nav();
?><!doctype html>
<html lang="de" data-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= e(($pageTitle ?? '') !== '' ? $pageTitle . ' · ' : '') ?>Admin · <?= e($site) ?></title>
<link rel="icon" type="image/svg+xml" href="<?= e(asset('img/favicon.svg')) ?>">
<link rel="stylesheet" href="<?= e(asset('vendor/quill/quill.snow.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
<style nonce="<?= e($nonce) ?>">:root{--accent:<?= e(valid_hex(setting('accent')) ? setting('accent') : '#F97316') ?>}</style>
<script nonce="<?= e($nonce) ?>">(function(){try{var t=localStorage.getItem('fp-admin-theme');if(t)document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script>
</head>
<body class="admin">
<div class="shell">
  <aside class="sidebar" id="sidebar" aria-label="Navigation">
    <a class="side-brand" href="<?= e(Admin::url()) ?>">
      <span class="brand-mark"><?= icon('tank') ?></span>
      <span><strong><?= e($site) ?></strong><small>Admin-Bereich</small></span>
    </a>
    <nav class="side-nav">
      <?php foreach ($nav as $group): [$gName, $items] = $group; ?>
      <?php $visibleItems = array_filter($items, static fn($it) => ($it[3] ?? 'editor') !== 'admin' || $isAdmin); if (!$visibleItems) { continue; } ?>
      <div class="side-group">
        <h3><?= e($gName) ?></h3>
        <ul>
          <?php foreach ($visibleItems as $it): [$target, $label, $ico] = $it; $badge = (int)($it[4] ?? 0); ?>
          <li><a class="<?= Admin::isActive($target) ? 'is-active' : '' ?>" href="<?= e(Admin::url($target)) ?>"><?= icon($ico) ?><span><?= e($label) ?></span><?php if ($badge > 0): ?><b class="badge-count"><?= $badge ?></b><?php endif; ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endforeach; ?>
    </nav>
    <div class="side-foot">
      <div class="side-user"><span class="avatar"><?= e(mb_strtoupper(mb_substr((string)$me['username'], 0, 1))) ?></span><span><strong><?= e($me['username']) ?></strong><small><?= $isAdmin ? 'Administrator' : 'Redakteur' ?></small></span></div>
      <form method="post" action="<?= e(Admin::url(['p' => 'logout'])) ?>"><?= Csrf::field() ?><button class="icon-btn" type="submit" title="Abmelden" aria-label="Abmelden"><?= icon('log-out') ?></button></form>
    </div>
  </aside>
  <div class="scrim" id="scrim"></div>

  <div class="main">
    <header class="topbar">
      <button class="icon-btn burger" type="button" id="side-toggle" aria-label="Menü"><?= icon('menu') ?></button>
      <h1 class="top-title"><?= e($pageTitle ?? '') ?></h1>
      <div class="top-actions">
        <a class="btn btn-soft" href="<?= e(rtrim((string)cfg('base_path', ''), '/') . '/') ?>" target="_blank" rel="noopener"><?= icon('external-link') ?><span>Website ansehen</span></a>
        <button class="icon-btn" type="button" id="admin-theme" aria-label="Design wechseln"><?= icon('sun') ?></button>
      </div>
    </header>
    <main class="content" id="content">
      <?php if ($flashes): ?>
      <div class="flashes" role="status">
        <?php foreach ($flashes as [$type, $msg]): ?><div class="flash flash-<?= e($type) ?>"><?= icon($type === 'ok' ? 'circle-check' : 'triangle-alert') ?><span><?= e($msg) ?></span><button type="button" class="flash-x" aria-label="Schließen">×</button></div><?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?= $content ?>
    </main>
  </div>
</div>
<div class="toasts" id="toasts" aria-live="polite"></div>
<script type="application/json" id="fp-admin-cfg"><?= View::json($cfg) ?></script>
<script src="<?= e(asset('vendor/quill/quill.js')) ?>"></script>
<script src="<?= e(asset('vendor/qrcode.js')) ?>"></script>
<script src="<?= e(asset('js/admin.js')) ?>"></script>
</body>
</html>
