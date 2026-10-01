<?php

declare(strict_types=1);

defined('PEGELSTAND_ROOT') || exit;

use Pegelstand\Install\Check;

/** @var Pegelstand\Core\View $this */
/** @var list<Check> $checks */
/** @var bool $blockiert */
/** @var string $csrf */
$symbole = [Check::OK => 'circle-check', Check::WARN => 'triangle-alert', Check::FAIL => 'circle-x'];
?>
<?= $this->render('install/fortschritt', ['aktuell' => 1], null) ?>
<section class="ps-card se-karte" aria-labelledby="titel">
  <div class="ps-card__body">
    <h1 id="titel"><?= $this->t('install.pruefung.titel') ?></h1>
    <p class="se-einleitung"><?= $this->t('install.pruefung.einleitung') ?></p>

    <h2 class="se-zwischentitel"><?= $this->t('install.pruefung.punkte_titel') ?></h2>
    <ul class="in-punkte">
      <?php foreach ($checks as $check) : ?>
        <?php
        $basis = 'install.pruefung.punkte.' . $check->id;
        $detail = $check->id === 'argon2' ? $basis . '.detail_' . $check->status : $basis . '.detail';
        ?>
      <li class="in-punkt in-punkt--<?= $this->e($check->status) ?>">
        <span class="in-punkt__symbol"><?= $this->icon($symbole[$check->status]) ?></span>
        <div class="in-punkt__text">
          <strong><?= $this->t($basis . '.label') ?><span class="ps-visually-hidden">: <?= $this->t('install.pruefung.status.' . $check->status) ?></span></strong>
          <span class="in-punkt__detail"><?= $this->t($detail, $check->params) ?></span>
          <?php if ($check->status !== Check::OK) : ?>
          <span class="in-punkt__hinweis"><?= $this->t($basis . '.hinweis') ?></span>
          <?php endif; ?>
        </div>
      </li>
      <?php endforeach; ?>
      <li class="in-punkt in-punkt--pruefe" data-zugriffscheck
        data-urls="<?= $this->e(implode(' ', [$this->url('/config/config.example.php'), $this->url('/src/Version.php'), $this->url('/storage/.htaccess')])) ?>">
        <span class="in-punkt__symbol" data-symbole><?= $this->icon('clock') ?></span>
        <div class="in-punkt__text">
          <strong><?= $this->t('install.pruefung.punkte.zugriff.label') ?><span class="ps-visually-hidden" data-status>: <?= $this->t('install.pruefung.status.pruefe') ?></span></strong>
          <span class="in-punkt__detail" data-detail
            data-text-ok="<?= $this->t('install.pruefung.punkte.zugriff.detail_ok') ?>"
            data-text-warn="<?= $this->t('install.pruefung.punkte.zugriff.detail_warn') ?>"
            data-status-ok="<?= $this->t('install.pruefung.status.ok') ?>"
            data-status-warn="<?= $this->t('install.pruefung.status.warn') ?>"><noscript><?= $this->t('install.pruefung.punkte.zugriff.detail_ohne_js') ?></noscript><?= $this->t('install.pruefung.punkte.zugriff.detail_pruefe') ?></span>
          <span class="in-punkt__hinweis" data-hinweis hidden><?= $this->t('install.pruefung.punkte.zugriff.hinweis') ?></span>
        </div>
      </li>
    </ul>

    <?php if ($blockiert) : ?>
    <div class="ps-alert ps-alert--danger" role="alert">
      <?= $this->icon('circle-alert') ?>
      <div class="ps-alert__inhalt">
        <p class="ps-alert__titel"><?= $this->t('install.pruefung.blockiert_titel') ?></p>
        <p><?= $this->t('install.pruefung.blockiert') ?></p>
      </div>
    </div>
    <div class="se-aktionen">
      <a class="ps-btn ps-btn--primary" href="<?= $this->e($this->url('/install')) ?>"><?= $this->icon('refresh-cw', 's') ?> <?= $this->t('install.pruefung.erneut') ?></a>
    </div>
    <?php else : ?>
    <form method="post" action="<?= $this->e($this->url('/install')) ?>" class="se-aktionen">
      <input type="hidden" name="_csrf" value="<?= $this->e($csrf) ?>">
      <button type="submit" class="ps-btn ps-btn--primary"><?= $this->t('install.pruefung.weiter') ?> <?= $this->icon('arrow-right', 's') ?></button>
    </form>
    <?php endif; ?>
  </div>
</section>
