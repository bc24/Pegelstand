<?php

declare(strict_types=1);

defined('PEGELSTAND_ROOT') || exit;

/** @var Pegelstand\Core\View $this */
/** @var string $art Schlüssel unter fehlerseite.*, z. B. 404 */
?>
<section class="ps-card se-karte" aria-labelledby="titel">
  <div class="ps-card__body">
    <span class="ps-empty__icon se-symbol se-symbol--fehler"><?= $this->icon('circle-alert') ?></span>
    <h1 id="titel"><?= $this->t('fehlerseite.' . $art . '.titel') ?></h1>
    <p class="se-einleitung"><?= $this->t('fehlerseite.' . $art . '.text') ?></p>
    <div class="se-aktionen">
      <a class="ps-btn ps-btn--primary" href="<?= $this->e($this->url('/')) ?>"><?= $this->t('fehlerseite.zur_startseite') ?></a>
    </div>
  </div>
</section>
