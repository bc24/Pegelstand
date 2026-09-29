<?php
/** @var array $rows @var string $tab @var array $counts @var string $kind @var array $kinds @var int $page @var int $pages */
$kindLabels = ['contact' => 'Kontakt', 'project' => 'Projektanfrage', 'booking' => 'Booking'];
$tabs = ['inbox' => ['Posteingang', ($counts['new'] ?? 0) + ($counts['read'] ?? 0) + ($counts['replied'] ?? 0)], 'new' => ['Ungelesen', $counts['new'] ?? 0],
    'archived' => ['Archiv', $counts['archived'] ?? 0], 'spam' => ['Spam', $counts['spam'] ?? 0], 'all' => ['Alle', array_sum($counts)]];
?>
<div class="page-head"><div><h2>Nachrichten</h2><p class="muted">Eingänge aus Kontaktformular, Projektanfrage und Booking-Anfrage. Zusätzlich gehen sie per E-Mail an dich.</p></div></div>
<div class="tabs">
  <?php foreach ($tabs as $k => [$label, $n]): ?><a class="<?= $tab === $k ? 'is-active' : '' ?>" href="<?= e(Admin::url(['p' => 'messages', 's' => $k])) ?>"><?= e($label) ?><b><?= (int)$n ?></b></a><?php endforeach; ?>
</div>
<div class="tabs tabs-sub">
  <a class="<?= $kind === '' ? 'is-active' : '' ?>" href="<?= e(Admin::url(['p' => 'messages', 's' => $tab])) ?>">Alle Arten</a>
  <?php foreach ($kindLabels as $k => $label): ?><a class="<?= $kind === $k ? 'is-active' : '' ?>" href="<?= e(Admin::url(['p' => 'messages', 's' => $tab, 'k' => $k])) ?>"><?= e($label) ?><b><?= (int)($kinds[$k] ?? 0) ?></b></a><?php endforeach; ?>
</div>
<div class="card card-flush">
<?php if (!$rows): ?><div class="empty"><?= icon('inbox') ?><p>Keine Nachrichten in dieser Ansicht.</p></div><?php else: ?>
<ul class="msg-list">
  <?php foreach ($rows as $m): ?>
  <li class="<?= $m['status'] === 'new' ? 'is-new' : '' ?>">
    <a href="<?= e(Admin::url(['p' => 'messages', 'id' => $m['id']])) ?>">
      <span class="avatar"><?= e(mb_strtoupper(mb_substr($m['name'], 0, 1))) ?></span>
      <span class="msg-main"><strong><?= e($m['name']) ?></strong> <small><?= e($m['email']) ?></small><em><?= e($m['subject'] ?: '(ohne Betreff)') ?></em><span class="msg-snip"><?php $sum = Inquiry::summary($m); ?><?= $sum !== '' ? e($sum) : e(mb_substr(preg_replace('/\s+/', ' ', $m['message']) ?? '', 0, 130)) ?></span></span>
      <span class="msg-meta"><time><?= e(date('d.m.Y H:i', strtotime($m['created_at']))) ?></time><?php if ($m['status'] === 'replied'): ?><span class="pill pill-ok">beantwortet</span><?php elseif ($m['status'] === 'new'): ?><span class="pill pill-warn">neu</span><?php endif; ?><?php if ($m['kind'] !== 'contact'): ?><span class="pill pill-info"><?= e($kindLabels[$m['kind']] ?? $m['kind']) ?></span><?php endif; ?><span class="pill"><?= strtoupper(e($m['lang'])) ?></span></span>
    </a>
  </li>
  <?php endforeach; ?>
</ul>
<?php endif; ?>
</div>
<?php if ($pages > 1): ?><nav class="pager"><?php for ($i = 1; $i <= $pages; $i++): ?><a class="<?= $i === $page ? 'is-active' : '' ?>" href="<?= e(Admin::url(['p' => 'messages', 's' => $tab, 'k' => $kind, 'page' => $i])) ?>"><?= $i ?></a><?php endfor; ?></nav><?php endif; ?>
