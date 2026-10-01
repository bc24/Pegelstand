<?php

declare(strict_types=1);

defined('PEGELSTAND_ROOT') || exit;

/** @var Pegelstand\Core\View $this */
/** @var Pegelstand\Auth\AuthUser $benutzer */
/** @var array<string, string> $fehler */
/** @var bool $admin */
/** @var string $csrf */
/** @var array{typ: string, text: string}|null $flash */
?>
<?= $this->render('einstellungen/nav', ['aktuell' => 'konto', 'admin' => $admin, 'csrf' => $csrf, 'flash' => $flash], null) ?>
<section class="ps-card es-karte" aria-labelledby="titel">
  <div class="ps-card__body">
    <h1 id="titel"><?= $this->t('einst.konto.titel') ?></h1>
    <p class="se-einleitung"><?= $this->e($benutzer->name) ?> · <?= $this->e($benutzer->email) ?> · <?= $benutzer->isAdmin() ? $this->t('einst.benutzer.rolle_admin') : $this->t('einst.benutzer.rolle_viewer') ?></p>
  </div>
</section>
<section class="ps-card es-karte" aria-labelledby="pw-titel">
  <div class="ps-card__body">
    <h2 id="pw-titel"><?= $this->t('einst.konto.passwort') ?></h2>
    <?= $this->render('partials/fehlerliste', ['fehler' => $fehler, 'allgemein' => ''], null) ?>
    <form method="post" action="<?= $this->e($this->url('/einstellungen/konto')) ?>" novalidate class="se-formular">
      <input type="hidden" name="_csrf" value="<?= $this->e($csrf) ?>">
      <?= $this->render('partials/feld', ['feld' => 'current', 'label' => $this->translate('einst.konto.aktuell'), 'wert' => '', 'typ' => 'password', 'fehler' => $fehler['current'] ?? '', 'autocomplete' => 'current-password'], null) ?>
      <?= $this->render('partials/feld', ['feld' => 'password', 'label' => $this->translate('einst.konto.neu'), 'wert' => '', 'typ' => 'password', 'hinweis' => $this->translate('install.admin.password_hinweis'), 'fehler' => $fehler['password'] ?? '', 'autocomplete' => 'new-password', 'passwort' => true], null) ?>
      <?= $this->render('partials/feld', ['feld' => 'password_repeat', 'label' => $this->translate('install.admin.password_repeat'), 'wert' => '', 'typ' => 'password', 'fehler' => $fehler['password_repeat'] ?? '', 'autocomplete' => 'new-password'], null) ?>
      <div class="se-aktionen"><button type="submit" class="ps-btn ps-btn--primary" data-beschaeftigt-beim-senden><?= $this->t('einst.konto.aendern') ?></button></div>
    </form>
  </div>
</section>
