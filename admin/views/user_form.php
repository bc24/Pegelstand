<?php /** @var array $u @var array $errors */
$new = (int)$u['id'] === 0;
$fld = static fn(string $n, string $label, string $type, string $val, string $help = '', bool $req = false): string =>
    '<div class="fw' . (isset($errors[$n]) ? ' has-err' : '') . '"><label class="lbl" for="u-' . $n . '">' . e($label) . ($req ? ' <b class="req">*</b>' : '') . '</label>'
    . '<input class="inp" id="u-' . $n . '" name="' . $n . '" type="' . $type . '" value="' . e($val) . '"' . ($type === 'password' ? ' autocomplete="new-password"' : '') . ($req ? ' required' : '') . '>'
    . ($help ? '<p class="help">' . e($help) . '</p>' : '') . (isset($errors[$n]) ? '<p class="err">' . e($errors[$n]) . '</p>' : '') . '</div>';
?>
<form method="post" class="editor" data-editor action="<?= e(Admin::url(['p' => 'users'])) ?>">
  <?= Csrf::field() ?><input type="hidden" name="do" value="save"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
  <div class="page-head"><div><a class="back" href="<?= e(Admin::url(['p' => 'users'])) ?>"><?= icon('arrow-left') ?> Benutzer</a><h2><?= $new ? 'Neuer Benutzer' : 'Benutzer bearbeiten' ?></h2></div></div>
  <div class="card form-card"><div class="fields">
    <?= $fld('username', 'Benutzername', 'text', (string)$u['username'], '', true) ?>
    <?= $fld('email', 'E-Mail', 'email', (string)$u['email']) ?>
    <div class="fw<?= isset($errors['role']) ? ' has-err' : '' ?>"><label class="lbl" for="u-role">Rolle</label>
      <select class="inp" id="u-role" name="role"><option value="editor" <?= $u['role'] === 'editor' ? 'selected' : '' ?>>Redakteur (nur Inhalte)</option><option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Administrator (alles)</option></select>
      <?php if (isset($errors['role'])): ?><p class="err"><?= e($errors['role']) ?></p><?php endif; ?></div>
    <?= $fld('password', $new ? 'Passwort' : 'Neues Passwort', 'password', '', $new ? 'Mindestens 10 Zeichen, Buchstaben und Zahlen.' : 'Leer lassen, um das Passwort nicht zu ändern.', $new) ?>
  </div></div>
  <div class="action-bar"><a class="btn btn-ghost" href="<?= e(Admin::url(['p' => 'users'])) ?>">Abbrechen</a><span class="grow"></span><button class="btn btn-primary" type="submit"><?= icon('check') ?><span>Speichern</span></button></div>
</form>
<?php if (!$new && !empty($u['totp_enabled'])): ?>
<form method="post" class="card" style="margin-top:1.2rem" data-confirm="Zwei-Faktor-Anmeldung für diesen Benutzer zurücksetzen?"><?= Csrf::field() ?><input type="hidden" name="do" value="reset2fa"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
  <h3>Zwei-Faktor-Anmeldung</h3><p class="muted">Der Benutzer hat 2FA aktiviert. Falls das Handy verloren ging, kannst du sie hier zurücksetzen.</p><button class="btn btn-soft" type="submit">2FA zurücksetzen</button></form>
<?php endif; ?>
