<?php

declare(strict_types=1);

defined('PEGELSTAND_ROOT') || exit;

/** @var Pegelstand\Core\View $this */
/** @var string $fehler */
/** @var string $csrf */
?>
<section class="ps-card se-karte" aria-labelledby="titel">
  <div class="ps-card__body">
    <h1 id="titel"><?= $this->t('login.code_titel') ?></h1>
    <p class="se-einleitung"><?= $this->t('login.code_einleitung') ?></p>
    <?php if ($fehler !== '') : ?>
    <div class="ps-alert ps-alert--danger" role="alert" tabindex="-1" data-fehlerzusammenfassung>
      <?= $this->icon('circle-alert') ?>
      <div class="ps-alert__inhalt"><p><?= $this->e($fehler) ?></p></div>
    </div>
    <?php endif; ?>
    <form method="post" action="<?= $this->e($this->url('/login/2fa')) ?>" novalidate class="se-formular">
      <input type="hidden" name="_csrf" value="<?= $this->e($csrf) ?>">
      <?= $this->render('partials/feld', ['feld' => 'code', 'label' => $this->translate('login.code'), 'wert' => '', 'fehler' => '', 'autocomplete' => 'one-time-code', 'inputmode' => 'numeric'], null) ?>
      <div class="se-aktionen"><button type="submit" class="ps-btn ps-btn--primary" data-beschaeftigt-beim-senden><?= $this->t('login.code_pruefen') ?></button></div>
    </form>
  </div>
</section>
