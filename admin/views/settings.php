<?php
/** @var array $schema @var string $group @var array $fields @var array $row @var array $errors */
$isAdmin = Auth::isAdmin();
$hasLang = false;
foreach ($fields as $f) { if (!empty($f['bilingual'])) { $hasLang = true; break; } }
?>
<form method="post" class="editor" data-editor action="<?= e(Admin::url(['p' => 'settings', 'g' => $group])) ?>" novalidate>
  <?= Csrf::field() ?>
  <input type="hidden" name="do" value="save">
  <div class="page-head">
    <div>
      <h2><?= icon($schema[$group]['icon']) ?> <?= e($schema[$group]['label']) ?></h2>
      <p class="muted">Änderungen gelten sofort auf der öffentlichen Seite.</p>
    </div>
    <?php if ($hasLang): ?>
    <div class="lang-tabs" role="tablist" aria-label="Sprache">
      <button type="button" class="is-active" data-lang-tab="de" role="tab">Deutsch</button>
      <button type="button" data-lang-tab="en" role="tab">English</button>
    </div>
    <?php endif; ?>
  </div>
  <?php if ($errors): ?><div class="flash flash-err" role="alert"><?= icon('triangle-alert') ?><span>Bitte prüfe die markierten Felder.</span></div><?php endif; ?>
  <div class="card form-card" data-lang="de">
    <div class="fields">
      <?php foreach ($fields as $f): ?>
        <?= Admin::field($f, $row, $errors) ?>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="action-bar">
    <span class="grow"></span>
    <span class="dirty-note" hidden>Ungespeicherte Änderungen</span>
    <button class="btn btn-primary" type="submit"><?= icon('check') ?><span>Speichern</span></button>
  </div>
</form>
<?php if ($group === 'contact' && $isAdmin): ?>
<form method="post" class="card" style="margin-top:1.2rem" action="<?= e(Admin::url(['p' => 'settings', 'g' => 'contact'])) ?>">
  <?= Csrf::field() ?><input type="hidden" name="do" value="testmail">
  <h3>Mail-Versand testen</h3>
  <p class="muted">Sendet eine Testnachricht an <?= e(setting('contact_notify') ?: setting('contact_email')) ?> — speichere vorher deine Änderungen.</p>
  <button class="btn btn-soft" type="submit"><?= icon('send') ?><span>Testmail senden</span></button>
</form>
<?php endif; ?>
