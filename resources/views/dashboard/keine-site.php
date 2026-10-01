<?php

declare(strict_types=1);

defined('PEGELSTAND_ROOT') || exit;

/** @var Pegelstand\Core\View $this */
/** @var bool $admin */
/** @var string $csrf */
?>
<section class="ps-card se-karte" aria-labelledby="titel">
  <div class="ps-card__body">
    <span class="ps-empty__icon se-symbol"><?= $this->icon('globe') ?></span>
    <h1 id="titel"><?= $this->t('keine_site.titel') ?></h1>
    <p class="se-einleitung"><?= $this->t($admin ? 'keine_site.text_admin' : 'keine_site.text_viewer') ?></p>
    <?php if ($admin) : ?>
    <div class="se-aktionen"><a class="ps-btn ps-btn--primary" href="<?= $this->e($this->url('/einstellungen/websites')) ?>"><?= $this->t('keine_site.anlegen') ?></a></div>
    <?php endif; ?>
    <form method="post" action="<?= $this->e($this->url('/logout')) ?>" class="se-aktionen">
      <input type="hidden" name="_csrf" value="<?= $this->e($csrf) ?>">
      <button type="submit" class="ps-btn ps-btn--secondary"><?= $this->icon('log-out', 's') ?> <?= $this->t('keine_site.abmelden') ?></button>
    </form>
  </div>
</section>
