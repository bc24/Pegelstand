<?php

declare(strict_types=1);

defined('PEGELSTAND_ROOT') || exit;

/** @var Pegelstand\Core\View $this */
/** @var bool $verfuegbar */
/** @var bool $gesendet */
/** @var string $hinweis */
/** @var bool|null $abgelaufen */
/** @var string $csrf */
?>
<section class="ps-card se-karte" aria-labelledby="titel">
  <div class="ps-card__body">
    <h1 id="titel"><?= $this->t($abgelaufen ?? false ? 'reset.abgelaufen_titel' : 'reset.titel') ?></h1>
    <?php if ($abgelaufen ?? false) : ?>
    <p class="se-einleitung"><?= $this->t('reset.abgelaufen') ?></p>
    <?php endif; ?>
    <?php if ($gesendet) : ?>
    <div class="ps-alert ps-alert--success" role="status"><?= $this->icon('circle-check') ?><div class="ps-alert__inhalt"><p><?= $this->e($hinweis) ?></p></div></div>
    <?php elseif (!$verfuegbar) : ?>
    <p class="se-einleitung"><?= $this->t('reset.nicht_verfuegbar') ?></p>
    <?php else : ?>
    <?php if (!($abgelaufen ?? false)) : ?><p class="se-einleitung"><?= $this->t('reset.einleitung') ?></p><?php endif; ?>
    <form method="post" action="<?= $this->e($this->url('/passwort-vergessen')) ?>" novalidate class="se-formular">
      <input type="hidden" name="_csrf" value="<?= $this->e($csrf) ?>">
      <?= $this->render('partials/feld', ['feld' => 'email', 'label' => $this->translate('login.email'), 'wert' => '', 'typ' => 'email', 'fehler' => '', 'autocomplete' => 'username'], null) ?>
      <div class="se-aktionen"><button type="submit" class="ps-btn ps-btn--primary" data-beschaeftigt-beim-senden><?= $this->t('reset.senden') ?></button></div>
    </form>
    <?php endif; ?>
    <p><a href="<?= $this->e($this->url('/login')) ?>"><?= $this->t('reset.zurueck') ?></a></p>
  </div>
</section>
