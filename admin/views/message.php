<?php
/** @var array $m */
$subject = $m['subject'] ?: 'Deine Nachricht';
$reply = 'mailto:' . rawurlencode($m['email']) . '?subject=' . rawurlencode('Re: ' . $subject)
    . '&body=' . rawurlencode("\n\n\n--- Deine Nachricht vom " . date('d.m.Y H:i', strtotime($m['created_at'])) . " ---\n" . $m['message']);
?>
<?php $details = json_decode((string)($m['details'] ?? ''), true); $details = is_array($details) ? $details : []; ?>
<div class="page-head"><div><a class="back" href="<?= e(Admin::url(['p' => 'messages'])) ?>"><?= icon('arrow-left') ?> Nachrichten</a><h2><?= e($subject) ?></h2></div></div>
<div class="card msg-detail">
  <div class="msg-head">
    <span class="avatar avatar-lg"><?= e(mb_strtoupper(mb_substr($m['name'], 0, 1))) ?></span>
    <div><strong><?= e($m['name']) ?></strong><br><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></div>
    <div class="msg-when"><time><?= e(date('d.m.Y, H:i', strtotime($m['created_at']))) ?> Uhr</time><?php if (($m['kind'] ?? 'contact') !== 'contact'): ?><span class="pill pill-info"><?= e(['project' => 'Projektanfrage', 'booking' => 'Booking'][$m['kind']] ?? $m['kind']) ?></span><?php endif; ?><span class="pill"><?= strtoupper(e($m['lang'])) ?></span><span class="pill <?= $m['status'] === 'replied' ? 'pill-ok' : '' ?>"><?= e(['new' => 'neu', 'read' => 'gelesen', 'replied' => 'beantwortet', 'archived' => 'archiviert', 'spam' => 'Spam'][$m['status']] ?? $m['status']) ?></span></div>
  </div>
  <?php if ($details): ?>
  <dl class="msg-details">
    <?php foreach ($details as $label => $value): ?><div><dt><?= e($label) ?></dt><dd><?= str_starts_with((string)$value, 'http') ? '<a href="' . e($value) . '" target="_blank" rel="noopener">' . e($value) . '</a>' : e($value) ?></dd></div><?php endforeach; ?>
  </dl>
  <?php endif; ?>
  <?php if (trim((string)$m['message']) !== ''): ?><div class="msg-body"><?= nl2br(e($m['message'])) ?></div><?php endif; ?>
  <div class="msg-actions">
    <a class="btn btn-primary" href="<?= e($reply) ?>"><?= icon('mail') ?><span>Antworten</span></a>
    <?php foreach (['replied' => ['check', 'Als beantwortet markieren'], 'archived' => ['folders', 'Archivieren'], 'spam' => ['triangle-alert', 'Spam'], 'new' => ['inbox', 'Als ungelesen']] as $st => [$ic, $lb]): if ($m['status'] === $st) { continue; } ?>
    <form method="post" class="inline"><?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="btn btn-soft" name="do" value="<?= e($st) ?>"><?= icon($ic) ?><span><?= e($lb) ?></span></button></form>
    <?php endforeach; ?>
    <form method="post" class="inline" data-confirm="Nachricht endgültig löschen?"><?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="btn btn-danger" name="do" value="delete"><?= icon('trash-2') ?><span>Löschen</span></button></form>
  </div>
</div>
