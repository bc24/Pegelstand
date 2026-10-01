<?php

declare(strict_types=1);

defined('PEGELSTAND_ROOT') || exit;

/** @var Pegelstand\Core\View $this */
/** @var string $aktuell */
/** @var bool $admin */
/** @var string $csrf */
/** @var array{typ: string, text: string}|null $flash */
$punkte = $admin ? ['websites' => 'einst.nav.websites', 'benutzer' => 'einst.nav.benutzer', 'konto' => 'einst.nav.konto'] : ['konto' => 'einst.nav.konto'];
?>
<nav class="es-nav" aria-label="<?= $this->t('einst.nav.label') ?>">
  <a class="ps-btn ps-btn--ghost" href="<?= $this->e($this->url('/')) ?>"><?= $this->icon('arrow-left', 's') ?> <?= $this->t('einst.nav.dashboard') ?></a>
  <span class="es-nav__abstand"></span>
  <?php foreach ($punkte as $id => $schluessel) : ?>
  <a class="ps-btn ps-btn--ghost" href="<?= $this->e($this->url('/einstellungen/' . $id)) ?>"<?= $aktuell === $id ? ' aria-current="page"' : '' ?>><?= $this->t($schluessel) ?></a>
  <?php endforeach; ?>
  <form method="post" action="<?= $this->e($this->url('/logout')) ?>" class="es-inline">
    <input type="hidden" name="_csrf" value="<?= $this->e($csrf) ?>">
    <button type="submit" class="ps-btn ps-btn--ghost"><?= $this->t('login.abmelden') ?></button>
  </form>
</nav>
<?php if (!empty($flash)) : ?>
<div class="ps-alert ps-alert--<?= $flash['typ'] === 'danger' ? 'danger' : 'success' ?>" role="<?= $flash['typ'] === 'danger' ? 'alert' : 'status' ?>">
  <?= $this->icon($flash['typ'] === 'danger' ? 'circle-alert' : 'circle-check') ?>
  <div class="ps-alert__inhalt"><p><?= $this->e($flash['text']) ?></p></div>
</div>
<?php endif; ?>
