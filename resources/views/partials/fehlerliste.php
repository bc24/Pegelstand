<?php

declare(strict_types=1);

defined('PEGELSTAND_ROOT') || exit;

/** @var Pegelstand\Core\View $this */
/** @var array<string, string> $fehler Feld zu Meldungstext */
/** @var string|null $allgemein Meldung ohne Feldbezug */
if ($fehler === [] && ($allgemein ?? '') === '') {
    return;
}
?>
<div class="ps-alert ps-alert--danger" role="alert" tabindex="-1" data-fehlerzusammenfassung>
  <?= $this->icon('circle-alert') ?>
  <div class="ps-alert__inhalt">
    <p class="ps-alert__titel"><?= $this->t($fehler === [] ? 'install.fehler.zusammenfassung_eins' : 'install.fehler.zusammenfassung') ?></p>
    <?php if (($allgemein ?? '') !== '') : ?>
    <p><?= $this->e($allgemein) ?></p>
    <?php endif; ?>
    <?php if ($fehler !== []) : ?>
    <ul>
      <?php foreach ($fehler as $feld => $text) : ?>
      <li><a href="#f-<?= $this->e($feld) ?>"><?= $this->e($text) ?></a></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </div>
</div>
