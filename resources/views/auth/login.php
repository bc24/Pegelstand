<?php

declare(strict_types=1);

defined('PEGELSTAND_ROOT') || exit;

/** @var Pegelstand\Core\View $this */
/** @var string $email */
/** @var string $fehler */
/** @var string $csrf */
/** @var bool $erfolg */
?>
<section class="ps-card se-karte" aria-labelledby="titel">
  <div class="ps-card__body">
    <h1 id="titel"><?= $this->t('login.titel') ?></h1>
    <p class="se-einleitung"><?= $this->t('login.einleitung') ?></p>

    <?php if (!empty($erfolg)) : ?>
    <div class="ps-alert ps-alert--success" role="status"><?= $this->icon('circle-check') ?><div class="ps-alert__inhalt"><p><?= $this->t('reset.erfolg') ?></p></div></div>
    <?php endif; ?>
    <?php if ($fehler !== '') : ?>
    <div class="ps-alert ps-alert--danger" role="alert" tabindex="-1" data-fehlerzusammenfassung>
      <?= $this->icon('circle-alert') ?>
      <div class="ps-alert__inhalt"><p><?= $this->e($fehler) ?></p></div>
    </div>
    <?php endif; ?>

    <form method="post" action="<?= $this->e($this->url('/login')) ?>" novalidate class="se-formular">
      <input type="hidden" name="_csrf" value="<?= $this->e($csrf) ?>">
      <?= $this->render('partials/feld', ['feld' => 'email', 'label' => $this->translate('login.email'), 'wert' => $email, 'typ' => 'email', 'fehler' => '', 'autocomplete' => 'username'], null) ?>
      <?= $this->render('partials/feld', ['feld' => 'password', 'label' => $this->translate('login.passwort'), 'wert' => '', 'typ' => 'password', 'fehler' => '', 'autocomplete' => 'current-password', 'passwort' => true], null) ?>
      <div class="se-aktionen se-aktionen--getrennt">
        <a href="<?= $this->e($this->url('/passwort-vergessen')) ?>"><?= $this->t('login.vergessen') ?></a>
        <button type="submit" class="ps-btn ps-btn--primary" data-beschaeftigt-beim-senden><?= $this->t('login.anmelden') ?></button>
      </div>
    </form>
  </div>
</section>
