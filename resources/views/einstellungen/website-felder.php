<?php

declare(strict_types=1);

defined('PEGELSTAND_ROOT') || exit;

/** @var Pegelstand\Core\View $this */
/** @var array<string, string> $werte */
/** @var array<string, string> $fehler */
/** @var list<string> $zeitzonen */
/** @var bool $dnt */
/** @var bool $gpc */
?>
<?= $this->render('partials/feld', ['feld' => 'name', 'label' => $this->translate('einst.websites.name'), 'wert' => $werte['name'], 'fehler' => $fehler['name'] ?? '', 'hinweis' => $this->translate('einst.websites.name_hinweis')], null) ?>
<?= $this->render('partials/feld', ['feld' => 'domain', 'label' => $this->translate('einst.websites.domain'), 'wert' => $werte['domain'], 'fehler' => $fehler['domain'] ?? '', 'hinweis' => $this->translate('einst.websites.domain_hinweis')], null) ?>
<div class="ps-field">
  <label class="ps-label" for="f-allowed_hosts"><?= $this->t('einst.websites.hosts') ?></label>
  <textarea class="ps-textarea" id="f-allowed_hosts" name="allowed_hosts" rows="3" spellcheck="false" aria-describedby="f-allowed_hosts-hinweis"<?= isset($fehler['allowed_hosts']) ? ' aria-invalid="true"' : '' ?>><?= $this->e($werte['allowed_hosts']) ?></textarea>
  <span class="ps-hint" id="f-allowed_hosts-hinweis"><?= $this->t('einst.websites.hosts_hinweis') ?></span>
  <?php if (isset($fehler['allowed_hosts'])) : ?><span class="ps-error"><?= $this->icon('circle-alert', 's') ?><span><?= $this->e($fehler['allowed_hosts']) ?></span></span><?php endif; ?>
</div>
<div class="se-zeile">
  <div class="ps-field">
    <label class="ps-label" for="f-timezone"><?= $this->t('einst.websites.zeitzone') ?></label>
    <div class="ps-select">
      <select id="f-timezone" name="timezone"<?= isset($fehler['timezone']) ? ' aria-invalid="true"' : '' ?>>
        <?php foreach ($zeitzonen as $z) : ?>
        <option value="<?= $this->e($z) ?>"<?= $z === $werte['timezone'] ? ' selected' : '' ?>><?= $this->e($z) ?></option>
        <?php endforeach; ?>
      </select>
      <?= $this->icon('chevron-down', 's') ?>
    </div>
    <?php if (isset($fehler['timezone'])) : ?><span class="ps-error"><?= $this->icon('circle-alert', 's') ?><span><?= $this->e($fehler['timezone']) ?></span></span><?php endif; ?>
  </div>
  <?= $this->render('partials/feld', ['feld' => 'retention_days', 'label' => $this->translate('einst.websites.aufbewahrung'), 'wert' => $werte['retention_days'], 'fehler' => $fehler['retention_days'] ?? '', 'inputmode' => 'numeric', 'hinweis' => $this->translate('einst.websites.aufbewahrung_hinweis')], null) ?>
</div>
<fieldset class="es-checks es-fieldset">
  <legend class="ps-label"><?= $this->t('einst.websites.signale') ?></legend>
  <label class="ps-check"><input type="checkbox" name="respect_dnt" value="1"<?= $dnt ? ' checked' : '' ?>><span><?= $this->t('einst.websites.dnt') ?></span></label>
  <label class="ps-check"><input type="checkbox" name="respect_gpc" value="1"<?= $gpc ? ' checked' : '' ?>><span><?= $this->t('einst.websites.gpc') ?></span></label>
</fieldset>
