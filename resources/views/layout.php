<?php

declare(strict_types=1);

defined('PEGELSTAND_ROOT') || exit;

/** @var Pegelstand\Core\View $this */
/** @var string $titel */
/** @var string $inhalt */
/** @var string|null $aktualisieren Sekunden bis zum automatischen Neuladen */
?><!DOCTYPE html>
<html lang="de" data-ps-assets="<?= $this->assetsBasis() ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <?php if (!empty($aktualisieren)) : ?>
  <meta http-equiv="refresh" content="<?= $this->e($aktualisieren) ?>">
  <?php endif; ?>
  <title><?= $this->e($titel) ?> – <?= $this->t('app.name') ?></title>
  <link rel="icon" href="data:,">
  <script src="<?= $this->asset('js/theme-init.js') ?>"></script>
  <link rel="preload" href="<?= $this->asset('fonts/inter-latin-wght-normal.woff2') ?>" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="<?= $this->asset('css/pegelstand.css') ?>">
  <link rel="stylesheet" href="<?= $this->asset('css/seiten.css') ?>">
</head>
<body class="se">
  <a class="ps-skip" href="#inhalt"><?= $this->t('layout.zum_inhalt') ?></a>

  <header class="se-kopf">
    <a class="ps-wordmark" href="<?= $this->e($this->url('/')) ?>"><?= $this->t('app.name') ?></a>
    <div class="ps-menu-wrap" data-ps-menu>
      <button type="button" class="ps-btn ps-btn--ghost ps-btn--icon" aria-haspopup="menu" aria-expanded="false" aria-controls="menue-darstellung" aria-label="<?= $this->t('layout.darstellung') ?>" data-ps-tooltip="<?= $this->t('layout.darstellung') ?>"><?= $this->icon('sun') ?></button>
      <div class="ps-menu ps-menu--end" id="menue-darstellung" role="menu" aria-label="<?= $this->t('layout.darstellung') ?>" hidden>
        <button type="button" class="ps-menu__item" role="menuitemradio" aria-checked="false" data-ps-theme="light" data-ps-gruppe="theme"><?= $this->icon('sun', 's') ?> <?= $this->t('layout.hell') ?> <span class="ps-menu__check"><?= $this->icon('check', 's') ?></span></button>
        <button type="button" class="ps-menu__item" role="menuitemradio" aria-checked="false" data-ps-theme="dark" data-ps-gruppe="theme"><?= $this->icon('moon', 's') ?> <?= $this->t('layout.dunkel') ?> <span class="ps-menu__check"><?= $this->icon('check', 's') ?></span></button>
        <button type="button" class="ps-menu__item" role="menuitemradio" aria-checked="true" data-ps-theme="system" data-ps-gruppe="theme"><?= $this->icon('monitor', 's') ?> <?= $this->t('layout.system') ?> <span class="ps-menu__check"><?= $this->icon('check', 's') ?></span></button>
      </div>
    </div>
  </header>

  <main class="se-haupt" id="inhalt" tabindex="-1">
<?= $inhalt ?>
  </main>

  <footer class="se-fuss">
    © 2026 <a href="https://frank-panzer.de">Frank Panzer</a> · <?= $this->t('layout.entwickelt_von') ?> <a href="https://panzerit.de">Panzer IT</a>
  </footer>

  <script type="module" src="<?= $this->asset('js/seiten.js') ?>"></script>
</body>
</html>
