<?php
/** @var array $counts @var array $perDay @var array $top @var int $total7 @var int $total30 @var array $messages @var array $pendingComments @var array $activity @var array $checks @var string $dbVersion */
$max = max(1, max($perDay));
$n = count($perDay);
$hour = (int)date('G');
$greet = $hour < 11 ? 'Guten Morgen' : ($hour < 18 ? 'Hallo' : 'Guten Abend');
?>
<section class="hello">
  <div>
    <h2><?= e($greet) ?>, <?= e(Auth::user()['username']) ?>.</h2>
    <p class="muted">Hier siehst du auf einen Blick, was auf deiner Website passiert.</p>
  </div>
  <div class="quick">
    <a class="btn btn-primary" href="<?= e(Admin::url(['p' => 'posts', 'a' => 'new'])) ?>"><?= icon('plus') ?><span>Neuer Beitrag</span></a>
    <a class="btn btn-soft" href="<?= e(Admin::url(['p' => 'projects', 'a' => 'new'])) ?>"><?= icon('plus') ?><span>Neues Projekt</span></a>
    <a class="btn btn-soft" href="<?= e(Admin::url(['p' => 'media'])) ?>"><?= icon('upload') ?><span>Bild hochladen</span></a>
  </div>
</section>

<section class="stat-cards">
  <a class="stat-card" href="<?= e(Admin::url(['p' => 'messages'])) ?>"><span class="sc-ico"><?= icon('inbox') ?></span><b><?= $counts['messages'] ?></b><span>Neue Nachrichten</span></a>
  <a class="stat-card" href="<?= e(Admin::url(['p' => 'comments'])) ?>"><span class="sc-ico"><?= icon('message-square') ?></span><b><?= $counts['comments'] ?></b><span>Kommentare warten</span></a>
  <a class="stat-card" href="<?= e(Admin::url(['p' => 'posts'])) ?>"><span class="sc-ico"><?= icon('newspaper') ?></span><b><?= $counts['posts'] ?></b><span>Beiträge online<?= $counts['scheduled'] ? ' · ' . $counts['scheduled'] . ' geplant' : '' ?><?= $counts['drafts'] ? ' · ' . $counts['drafts'] . ' Entwurf' : '' ?></span></a>
  <a class="stat-card" href="<?= e(Admin::url(['p' => 'projects'])) ?>"><span class="sc-ico"><?= icon('folders') ?></span><b><?= $counts['projects'] ?></b><span>Projekte sichtbar</span></a>
  <div class="stat-card"><span class="sc-ico"><?= icon('eye') ?></span><b><?= number_format($total7, 0, ',', '.') ?></b><span>Aufrufe · 7 Tage</span></div>
  <div class="stat-card"><span class="sc-ico"><?= icon('chart-column') ?></span><b><?= number_format($total30, 0, ',', '.') ?></b><span>Aufrufe · 30 Tage</span></div>
</section>

<div class="dash-grid">
  <section class="card span-2">
    <header class="card-head"><h2>Seitenaufrufe (14 Tage)</h2><span class="muted small">anonym gezählt, ohne Cookies</span></header>
    <?php if (sb('count_visits', true)): ?>
    <svg class="chart" viewBox="0 0 1100 250" role="img" aria-label="Seitenaufrufe pro Tag">
      <?php foreach (array_unique([0, (int)ceil($max / 2), $max]) as $tick): $y = 200 - $tick / $max * 180; ?><line x1="34" x2="1100" y1="<?= round($y, 1) ?>" y2="<?= round($y, 1) ?>" class="grid"/><text x="0" y="<?= round($y + 4, 1) ?>" class="ax"><?= (int)$tick ?></text><?php endforeach; ?>
      <?php $i = 0; $bw = (1100 - 44) / $n; foreach ($perDay as $day => $hits): $h = $hits / $max * 180; $x = 44 + $i * $bw + 6; ?>
      <g><rect x="<?= round($x, 1) ?>" y="<?= round(200 - $h, 1) ?>" width="<?= round($bw - 12, 1) ?>" height="<?= round(max($h, 2), 1) ?>" rx="5" class="bar<?= $day === date('Y-m-d') ? ' is-today' : '' ?>"><title><?= e(date('d.m.', strtotime($day)) . ': ' . $hits . ' Aufrufe') ?></title></rect>
        <text x="<?= round($x + ($bw - 12) / 2, 1) ?>" y="228" class="ax ax-c"><?= e(date('d.m.', strtotime($day))) ?></text></g>
      <?php $i++; endforeach; ?>
    </svg>
    <?php else: ?><p class="muted">Der Besuchszähler ist unter Einstellungen → Datenschutz-Hinweis ausgeschaltet.</p><?php endif; ?>
    <?php if ($top): ?>
    <ul class="toplist"><?php foreach ($top as $t): ?><li><code><?= e($t['path']) ?></code><b><?= (int)$t['h'] ?></b></li><?php endforeach; ?></ul>
    <?php endif; ?>
  </section>

  <section class="card">
    <header class="card-head"><h2>System-Check</h2></header>
    <ul class="checks">
      <?php foreach ($checks as [$ok, $text, $level]): ?>
      <li class="chk chk-<?= e($level) ?>"><?= icon($level === 'ok' ? 'circle-check' : ($level === 'info' ? 'info' : 'triangle-alert')) ?><span><?= e($text) ?></span></li>
      <?php endforeach; ?>
    </ul>
    <p class="muted small">PHP <?= e(PHP_VERSION) ?> · MySQL/MariaDB <?= e($dbVersion) ?> · Version <?= e(FP_VERSION) ?></p>
  </section>

  <section class="card">
    <header class="card-head"><h2>Letzte Nachrichten</h2><a class="link" href="<?= e(Admin::url(['p' => 'messages'])) ?>">Alle</a></header>
    <?php if (!$messages): ?><p class="muted">Noch keine Nachrichten.</p><?php endif; ?>
    <ul class="mini-list">
      <?php foreach ($messages as $m): ?>
      <li><a href="<?= e(Admin::url(['p' => 'messages', 'id' => $m['id']])) ?>" class="<?= $m['status'] === 'new' ? 'is-new' : '' ?>"><strong><?= e($m['name']) ?></strong><span><?= e($m['subject'] ?: mb_substr($m['message'], 0, 60)) ?></span><time><?= e(date('d.m. H:i', strtotime($m['created_at']))) ?></time></a></li>
      <?php endforeach; ?>
    </ul>
  </section>

  <section class="card">
    <header class="card-head"><h2>Kommentare zur Freigabe</h2><a class="link" href="<?= e(Admin::url(['p' => 'comments'])) ?>">Alle</a></header>
    <?php if (!$pendingComments): ?><p class="muted">Nichts zu prüfen.</p><?php endif; ?>
    <ul class="mini-list">
      <?php foreach ($pendingComments as $c): ?>
      <li><a href="<?= e(Admin::url(['p' => 'comments'])) ?>"><strong><?= e($c['name']) ?></strong><span><?= e(mb_substr($c['message'], 0, 70)) ?></span><time><?= e($c['post_title']) ?></time></a></li>
      <?php endforeach; ?>
    </ul>
  </section>

  <?php if ($activity): ?>
  <section class="card span-2">
    <header class="card-head"><h2>Letzte Aktivität</h2><a class="link" href="<?= e(Admin::url(['p' => 'log'])) ?>">Protokoll</a></header>
    <ul class="activity">
      <?php foreach ($activity as $a): ?>
      <li><time><?= e(date('d.m. H:i', strtotime($a['created_at']))) ?></time><strong><?= e($a['username'] ?: '—') ?></strong><span><?= e($a['action']) ?> <?= e($a['entity']) ?> <em><?= e($a['detail']) ?></em></span></li>
      <?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>
</div>
