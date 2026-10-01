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
<?= $this->render('install/fortschritt', ['aktuell' => 2], null) ?>
<section class="ps-card se-karte" aria-labelledby="titel">
  <div class="ps-card__body">
    <h1 id="titel"><?= $this->t('install.datenbank.titel') ?></h1>
    <p class="se-einleitung"><?= $this->t('install.datenbank.einleitung') ?></p>

    <?= $this->render('partials/fehlerliste', ['fehler' => $fehler, 'allgemein' => $allgemein], null) ?>

    <form method="post" action="<?= $this->e($this->url('/install/datenbank')) ?>" novalidate class="se-formular">
      <input type="hidden" name="_csrf" value="<?= $this->e($csrf) ?>">
      <div class="se-zeile">
        <?= $this->render('partials/feld', ['feld' => 'host', 'label' => $this->translate('install.datenbank.host'), 'wert' => $werte['host'], 'hinweis' => $this->translate('install.datenbank.host_hinweis'), 'fehler' => $f('host'), 'autocomplete' => 'off'], null) ?>
        <?= $this->render('partials/feld', ['feld' => 'port', 'label' => $this->translate('install.datenbank.port'), 'wert' => $werte['port'], 'hinweis' => $this->translate('install.datenbank.port_hinweis'), 'fehler' => $f('port'), 'autocomplete' => 'off', 'inputmode' => 'numeric'], null) ?>
      </div>
      <?= $this->render('partials/feld', ['feld' => 'name', 'label' => $this->translate('install.datenbank.name'), 'wert' => $werte['name'], 'fehler' => $f('name'), 'autocomplete' => 'off'], null) ?>
      <div class="se-zeile">
        <?= $this->render('partials/feld', ['feld' => 'user', 'label' => $this->translate('install.datenbank.user'), 'wert' => $werte['user'], 'fehler' => $f('user'), 'autocomplete' => 'off'], null) ?>
        <?= $this->render('partials/feld', ['feld' => 'password', 'label' => $this->translate('install.datenbank.password'), 'wert' => '', 'typ' => 'password', 'hinweis' => ($fehler !== [] || $allgemein !== '') ? $this->translate('install.datenbank.passwort_erneut') : null, 'fehler' => $f('password'), 'autocomplete' => 'off', 'passwort' => true], null) ?>
      </div>
      <?= $this->render('partials/feld', ['feld' => 'prefix', 'label' => $this->translate('install.datenbank.prefix'), 'wert' => $werte['prefix'], 'hinweis' => $this->translate('install.datenbank.prefix_hinweis'), 'fehler' => $f('prefix'), 'autocomplete' => 'off'], null) ?>

      <div class="se-aktionen se-aktionen--getrennt">
        <a class="ps-btn ps-btn--ghost" href="<?= $this->e($this->url('/install')) ?>"><?= $this->icon('arrow-left', 's') ?> <?= $this->t('install.datenbank.zurueck') ?></a>
        <button type="submit" class="ps-btn ps-btn--primary"><?= $this->t('install.datenbank.weiter') ?> <?= $this->icon('arrow-right', 's') ?></button>
      </div>
    </form>
  </div>
</section>
