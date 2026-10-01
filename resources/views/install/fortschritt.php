<?php

declare(strict_types=1);

defined('PEGELSTAND_ROOT') || exit;

/** @var Pegelstand\Core\View $this */
/** @var int $aktuell Nummer des aktuellen Schritts (1 bis 4) */
$schritte = ['pruefung', 'datenbank', 'admin', 'fertig'];
?>
<nav aria-label="<?= $this->t('install.fortschritt') ?>">
  <ol class="ps-stepper">
    <?php foreach ($schritte as $i => $schritt) : ?>
      <?php
      $nr = $i + 1;
      $zustand = $nr < $aktuell ? 'fertig' : ($nr === $aktuell ? 'aktuell' : 'offen');
      ?>
    <li class="ps-stepper__item ps-stepper__item--<?= $zustand ?>"<?= $zustand === 'aktuell' ? ' aria-current="step"' : '' ?>>
      <span class="ps-stepper__marke" aria-hidden="true"><?= $zustand === 'fertig' ? $this->icon('check', 's') : $nr ?></span>
      <span class="ps-stepper__label"><?= $this->t('install.schritte.' . $schritt) ?><span class="ps-visually-hidden"><?= $zustand === 'fertig' ? ' (' . $this->t('install.schritt_fertig') . ')' : '' ?></span></span>
    </li>
    <?php endforeach; ?>
  </ol>
</nav>
