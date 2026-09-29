<?php
/** @var array $rows @var string $tab @var array $counts */
$tabs = ['pending' => 'Zur Freigabe', 'approved' => 'Freigegeben', 'spam' => 'Spam'];
?>
<div class="page-head"><div><h2>Kommentare</h2><p class="muted">Kommentare aus dem Blog. Neue Kommentare erscheinen erst nach deiner Freigabe (Einstellung: Blog).</p></div></div>
<div class="tabs"><?php foreach ($tabs as $k => $l): ?><a class="<?= $tab === $k ? 'is-active' : '' ?>" href="<?= e(Admin::url(['p' => 'comments', 's' => $k])) ?>"><?= e($l) ?><b><?= (int)($counts[$k] ?? 0) ?></b></a><?php endforeach; ?></div>
<?php if (!$rows): ?><div class="card empty"><?= icon('message-square') ?><p>Keine Kommentare in dieser Ansicht.</p></div><?php endif; ?>
<div class="comment-cards">
<?php foreach ($rows as $c): ?>
  <article class="card comment-card">
    <header><span class="avatar"><?= e(mb_strtoupper(mb_substr($c['name'], 0, 1))) ?></span><div><strong><?= e($c['name']) ?></strong><small>zu <a href="<?= e(rtrim((string)cfg('base_path', ''), '/') . '/blog/' . $c['post_slug'] . '/') ?>" target="_blank" rel="noopener"><?= e($c['post_title']) ?></a> · <?= e(date('d.m.Y H:i', strtotime($c['created_at']))) ?></small></div></header>
    <p><?= nl2br(e($c['message'])) ?></p>
    <footer>
      <?php foreach (['approved' => ['check', 'Freigeben', 'btn-primary'], 'pending' => ['clock', 'Zurückstellen', 'btn-soft'], 'spam' => ['triangle-alert', 'Spam', 'btn-soft']] as $st => [$ic, $lb, $cl]): if ($tab === $st) { continue; } ?>
      <form method="post" class="inline"><?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><input type="hidden" name="_s" value="<?= e($tab) ?>"><button class="btn <?= $cl ?>" name="do" value="<?= e($st) ?>"><?= icon($ic) ?><span><?= e($lb) ?></span></button></form>
      <?php endforeach; ?>
      <form method="post" class="inline" data-confirm="Kommentar löschen?"><?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><input type="hidden" name="_s" value="<?= e($tab) ?>"><button class="btn btn-danger" name="do" value="delete"><?= icon('trash-2') ?><span>Löschen</span></button></form>
    </footer>
  </article>
<?php endforeach; ?>
</div>
