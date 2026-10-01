<?php

declare(strict_types=1);

defined('PEGELSTAND_ROOT') || exit;

/** @var Pegelstand\Core\View $this */
/** @var string $feld Name und Kennung des Feldes */
/** @var string $label */
/** @var string $wert */
/** @var string|null $typ */
/** @var string|null $hinweis */
/** @var string|null $fehler */
/** @var string|null $autocomplete */
/** @var string|null $inputmode */
/** @var bool|null $passwort Fügt einen Anzeigen-Knopf hinzu */
$typ = $typ ?? 'text';
$id = 'f-' . $feld;
$beschreibung = trim(($hinweis ?? '' ? $id . '-hinweis ' : '') . (($fehler ?? '') !== '' ? $id . '-fehler' : ''));
?>
<div class="ps-field">
  <label class="ps-label" for="<?= $this->e($id) ?>"><?= $this->e($label) ?></label>
  <div class="<?= !empty($passwort) ? 'in-passwort' : '' ?>">
    <input class="ps-input" id="<?= $this->e($id) ?>" name="<?= $this->e($feld) ?>" type="<?= $this->e($typ) ?>" value="<?= $this->e($wert) ?>"
      <?= ($autocomplete ?? '') !== '' ? 'autocomplete="' . $this->e($autocomplete) . '"' : '' ?>
      <?= ($inputmode ?? '') !== '' ? 'inputmode="' . $this->e($inputmode) . '"' : '' ?>
      <?= ($fehler ?? '') !== '' ? 'aria-invalid="true"' : '' ?>
      <?= $beschreibung !== '' ? 'aria-describedby="' . $this->e($beschreibung) . '"' : '' ?>
      spellcheck="false" autocapitalize="off">
    <?php if (!empty($passwort)) : ?>
    <button type="button" class="ps-btn ps-btn--ghost ps-btn--s" data-passwort-umschalten="<?= $this->e($id) ?>" aria-pressed="false" hidden
      data-text-anzeigen="<?= $this->t('install.admin.anzeigen') ?>" data-text-verbergen="<?= $this->t('install.admin.verbergen') ?>"><?= $this->t('install.admin.anzeigen') ?></button>
    <?php endif; ?>
  </div>
  <?php if (($hinweis ?? '') !== '') : ?>
  <span class="ps-hint" id="<?= $this->e($id) ?>-hinweis"><?= $this->e($hinweis) ?></span>
  <?php endif; ?>
  <?php if (($fehler ?? '') !== '') : ?>
  <span class="ps-error" id="<?= $this->e($id) ?>-fehler"><?= $this->icon('circle-alert', 's') ?><span><?= $this->e($fehler) ?></span></span>
  <?php endif; ?>
</div>
