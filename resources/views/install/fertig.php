<?php

declare(strict_types=1);

defined('PEGELSTAND_ROOT') || exit;

/** @var Pegelstand\Core\View $this */
?>
<?= $this->render('install/fortschritt', ['aktuell' => 4], null) ?>
<section class="ps-card se-karte" aria-labelledby="titel">
  <div class="ps-card__body">
    <span class="ps-empty__icon se-symbol se-symbol--ok"><?= $this->icon('circle-check') ?></span>
    <h1 id="titel"><?= $this->t('install.fertig.titel') ?></h1>
    <p class="se-einleitung"><?= $this->t('install.fertig.einleitung') ?></p>

    <h2 class="se-zwischentitel"><?= $this->t('install.fertig.naechste_titel') ?></h2>
    <ul class="se-liste">
      <li><?= $this->icon('shield', 's') ?><span><?= $this->t('install.fertig.sichern') ?></span></li>
      <li><?= $this->icon('download', 's') ?><span><?= $this->t('install.fertig.backup') ?></span></li>
    </ul>

    <div class="se-aktionen">
      <a class="ps-btn ps-btn--primary" href="<?= $this->e($this->url('/')) ?>"><?= $this->t('install.fertig.weiter') ?> <?= $this->icon('arrow-right', 's') ?></a>
    </div>
  </div>
</section>
