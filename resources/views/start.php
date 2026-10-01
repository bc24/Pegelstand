<?php

declare(strict_types=1);

defined('PEGELSTAND_ROOT') || exit;

/** @var Pegelstand\Core\View $this */
/** @var string $version */
?>
<section class="ps-card se-karte" aria-labelledby="titel">
  <div class="ps-card__body">
    <span class="ps-empty__icon se-symbol se-symbol--ok"><?= $this->icon('circle-check') ?></span>
    <h1 id="titel"><?= $this->t('start.titel') ?></h1>
    <p class="se-einleitung"><?= $this->t('start.text', ['version' => $version]) ?></p>
  </div>
</section>
