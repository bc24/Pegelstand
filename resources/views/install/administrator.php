<?php

declare(strict_types=1);

defined('PEGELSTAND_ROOT') || exit;

/** @var Pegelstand\Core\View $this */
/** @var array<string, string> $werte */
/** @var array<string, string> $fehler */
/** @var string $allgemein */
/** @var string $csrf */
$f = static fn(string $feld): string => $fehler[$feld] ?? '';
?>
<?= $this->render('install/fortschritt', ['aktuell' => 3], null) ?>
<section class="ps-card se-karte" aria-labelledby="titel">
  <div class="ps-card__body">
    <h1 id="titel"><?= $this->t('install.admin.titel') ?></h1>
    <p class="se-einleitung"><?= $this->t('install.admin.einleitung') ?></p>

    <?= $this->render('partials/fehlerliste', ['fehler' => $fehler, 'allgemein' => $allgemein], null) ?>

    <form method="post" action="<?= $this->e($this->url('/install/administrator')) ?>" novalidate class="se-formular">
      <input type="hidden" name="_csrf" value="<?= $this->e($csrf) ?>">
      <?= $this->render('partials/feld', ['feld' => 'name', 'label' => $this->translate('install.admin.name'), 'wert' => $werte['name'], 'fehler' => $f('name'), 'autocomplete' => 'name'], null) ?>
      <?= $this->render('partials/feld', ['feld' => 'email', 'label' => $this->translate('install.admin.email'), 'wert' => $werte['email'], 'typ' => 'email', 'hinweis' => $this->translate('install.admin.email_hinweis'), 'fehler' => $f('email'), 'autocomplete' => 'email'], null) ?>
      <?= $this->render('partials/feld', ['feld' => 'password', 'label' => $this->translate('install.admin.password'), 'wert' => '', 'typ' => 'password', 'hinweis' => $this->translate('install.admin.password_hinweis'), 'fehler' => $f('password'), 'autocomplete' => 'new-password', 'passwort' => true], null) ?>
      <?= $this->render('partials/feld', ['feld' => 'password_repeat', 'label' => $this->translate('install.admin.password_repeat'), 'wert' => '', 'typ' => 'password', 'fehler' => $f('password_repeat'), 'autocomplete' => 'new-password'], null) ?>

      <div class="se-aktionen se-aktionen--getrennt">
        <a class="ps-btn ps-btn--ghost" href="<?= $this->e($this->url('/install/datenbank')) ?>"><?= $this->icon('arrow-left', 's') ?> <?= $this->t('install.admin.zurueck') ?></a>
        <button type="submit" class="ps-btn ps-btn--primary" data-beschaeftigt-beim-senden><?= $this->t('install.admin.installieren') ?></button>
      </div>
    </form>
  </div>
</section>
