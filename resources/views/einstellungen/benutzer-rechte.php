<?php

declare(strict_types=1);

defined('PEGELSTAND_ROOT') || exit;

/** @var Pegelstand\Core\View $this */
/** @var string $rolle */
/** @var list<array<string, mixed>> $sites */
/** @var list<int> $gewaehlt */
?>
<div class="ps-field">
  <label class="ps-label" for="f-role"><?= $this->t('einst.benutzer.rolle') ?></label>
  <div class="ps-select">
    <select id="f-role" name="role" aria-describedby="f-role-hinweis">
      <option value="viewer"<?= $rolle !== 'admin' ? ' selected' : '' ?>><?= $this->t('einst.benutzer.rolle_viewer') ?></option>
      <option value="admin"<?= $rolle === 'admin' ? ' selected' : '' ?>><?= $this->t('einst.benutzer.rolle_admin') ?></option>
    </select>
    <?= $this->icon('chevron-down', 's') ?>
  </div>
  <span class="ps-hint" id="f-role-hinweis"><?= $this->t('einst.benutzer.rolle_hinweis') ?></span>
</div>
<?php if ($sites !== []) : ?>
<fieldset class="es-checks es-fieldset">
  <legend class="ps-label"><?= $this->t('einst.benutzer.sites') ?></legend>
  <?php foreach ($sites as $s) : ?>
  <label class="ps-check"><input type="checkbox" name="sites[]" value="<?= $this->e($s['id']) ?>"<?= in_array($s['id'], $gewaehlt, true) ? ' checked' : '' ?>><span><?= $this->e($s['name']) ?> (<?= $this->e($s['domain']) ?>)</span></label>
  <?php endforeach; ?>
  <span class="ps-hint"><?= $this->t('einst.benutzer.sites_hinweis') ?></span>
</fieldset>
<?php endif; ?>
