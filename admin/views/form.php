<?php
/** @var array $e @var array $row @var ?array $old @var array $errors */
$hasLang = false;
foreach ($e['fields'] as $f) { if (!empty($f['bilingual'])) { $hasLang = true; break; } }
$title = $old ? ($e['singular'] . ' bearbeiten') : ($e['singular'] . ' hinzufügen');
?>
<form method="post" class="editor" data-editor action="<?= e(Admin::url(['p' => $e['key']])) ?>" novalidate>
  <?= Csrf::field() ?>
  <input type="hidden" name="do" value="save">
  <input type="hidden" name="id" value="<?= (int)($old['id'] ?? 0) ?>">
  <?php if (!empty($_GET['f'])): ?><input type="hidden" name="_f" value="<?= e((string)$_GET['f']) ?>"><?php endif; ?>

  <div class="page-head">
    <div>
      <a class="back" href="<?= e(Admin::url(['p' => $e['key']])) ?>"><?= icon('arrow-left') ?> <?= e($e['title']) ?></a>
      <h2><?= e($title) ?></h2>
    </div>
    <?php if ($hasLang): ?>
    <div class="lang-tabs" role="tablist" aria-label="Sprache">
      <button type="button" class="is-active" data-lang-tab="de" role="tab">Deutsch</button>
      <button type="button" data-lang-tab="en" role="tab">English</button>
    </div>
    <?php endif; ?>
  </div>

  <?php if ($errors): ?>
  <div class="flash flash-err" role="alert"><?= icon('triangle-alert') ?><span>Bitte prüfe die markierten Felder (<?= count($errors) ?>).</span></div>
  <?php endif; ?>

  <div class="card form-card" data-lang="de">
    <div class="fields">
      <?php foreach ($e['fields'] as $f): ?>
        <?= Admin::field($f, $row, $errors) ?>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="action-bar">
    <a class="btn btn-ghost" href="<?= e(Admin::url(['p' => $e['key']])) ?>">Abbrechen</a>
    <span class="grow"></span>
    <span class="dirty-note" hidden>Ungespeicherte Änderungen</span>
    <button class="btn btn-soft" type="submit" name="stay" value="1"><?= icon('save') ?><span>Speichern & weiter bearbeiten</span></button>
    <button class="btn btn-primary" type="submit"><?= icon('check') ?><span>Speichern</span></button>
  </div>
</form>
