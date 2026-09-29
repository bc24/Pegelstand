<?php /** @var array $rows @var int $page @var int $pages */ ?>
<div class="page-head"><div><h2>Aktivitätsprotokoll</h2><p class="muted">Wer hat wann was geändert? IP-Adressen werden nicht im Klartext gespeichert.</p></div></div>
<div class="card card-flush"><div class="table-wrap"><table class="tbl">
  <thead><tr><th>Zeit</th><th>Benutzer</th><th>Aktion</th><th>Bereich</th><th>Details</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr><td><?= e(date('d.m.Y H:i:s', strtotime($r['created_at']))) ?></td><td><?= e($r['username'] ?: '—') ?></td>
      <td><span class="pill <?= in_array($r['action'], ['login_failed', 'delete'], true) ? 'pill-warn' : '' ?>"><?= e($r['action']) ?></span></td>
      <td><?= e($r['entity']) ?> <?= $r['entity_id'] !== '' ? '<small class="muted">#' . e($r['entity_id']) . '</small>' : '' ?></td><td><?= e($r['detail']) ?></td></tr>
  <?php endforeach; ?>
  </tbody></table></div></div>
<?php if ($pages > 1): ?><nav class="pager"><?php for ($i = 1; $i <= min($pages, 30); $i++): ?><a class="<?= $i === $page ? 'is-active' : '' ?>" href="<?= e(Admin::url(['p' => 'log', 'page' => $i])) ?>"><?= $i ?></a><?php endfor; ?></nav><?php endif; ?>
