<?php /** @var array $rows @var array $me */ ?>
<div class="page-head"><div><h2>Benutzer</h2><p class="muted">Administratoren dürfen alles. Redakteure bearbeiten nur Inhalte (Texte, Projekte, Blog, Medien) — keine Website-Einstellungen, Benutzer oder Backups.</p></div>
  <div class="head-actions"><a class="btn btn-primary" href="<?= e(Admin::url(['p' => 'users', 'a' => 'new'])) ?>"><?= icon('plus') ?><span>Benutzer hinzufügen</span></a></div></div>
<div class="card card-flush"><div class="table-wrap"><table class="tbl">
  <thead><tr><th>Benutzer</th><th>E-Mail</th><th>Rolle</th><th>2FA</th><th>Letzter Login</th><th class="c-actions"></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $u): ?>
    <tr>
      <td class="c-main"><a href="<?= e(Admin::url(['p' => 'users', 'a' => 'edit', 'id' => $u['id']])) ?>"><span class="avatar"><?= e(mb_strtoupper(mb_substr($u['username'], 0, 1))) ?></span> <?= e($u['username']) ?><?= (int)$u['id'] === (int)$me['id'] ? ' <span class="pill">du</span>' : '' ?></a></td>
      <td><?= e($u['email'] ?: '—') ?></td>
      <td><span class="pill <?= $u['role'] === 'admin' ? 'pill-ok' : '' ?>"><?= $u['role'] === 'admin' ? 'Administrator' : 'Redakteur' ?></span></td>
      <td><?= (int)$u['totp_enabled'] ? '<span class="dot dot-on" title="aktiv"></span>' : '<span class="dot" title="aus"></span>' ?></td>
      <td><?= $u['last_login'] ? e(date('d.m.Y H:i', strtotime($u['last_login']))) : '—' ?></td>
      <td class="c-actions">
        <a class="icon-btn" href="<?= e(Admin::url(['p' => 'users', 'a' => 'edit', 'id' => $u['id']])) ?>" aria-label="Bearbeiten"><?= icon('pencil') ?></a>
        <?php if ((int)$u['id'] !== (int)$me['id']): ?><form method="post" class="inline" data-confirm="Benutzer „<?= e($u['username']) ?>“ löschen?"><?= Csrf::field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><button class="icon-btn danger" aria-label="Löschen"><?= icon('trash-2') ?></button></form><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody></table></div></div>
