<?php

declare(strict_types=1);

defined('PEGELSTAND_ROOT') || exit;

/** @var Pegelstand\Core\View $this */
/** @var array<string, mixed> $ziel */
/** @var list<array<string, mixed>> $sites */
/** @var array<string, string> $fehler */
/** @var bool $selbst */
/** @var bool $admin */
/** @var string $csrf */
/** @var array{typ: string, text: string}|null $flash */
$basis = $this->url('/einstellungen/benutzer/' . $ziel['id']);
?>
<?= $this->render('einstellungen/nav', ['aktuell' => 'benutzer', 'admin' => $admin, 'csrf' => $csrf, 'flash' => $flash], null) ?>
<section class="ps-card es-karte" aria-labelledby="titel">
  <div class="ps-card__body">
    <h1 id="titel"><?= $this->e($ziel['name']) ?></h1>
    <p class="se-einleitung"><?= $this->e($ziel['email']) ?> · <a href="<?= $this->e($this->url('/einstellungen/benutzer')) ?>"><?= $this->t('einst.benutzer.zurueck') ?></a></p>
    <?= $this->render('partials/fehlerliste', ['fehler' => $fehler, 'allgemein' => ''], null) ?>
    <form method="post" action="<?= $this->e($basis) ?>" novalidate class="se-formular">
      <input type="hidden" name="_csrf" value="<?= $this->e($csrf) ?>">
      <?= $this->render('partials/feld', ['feld' => 'name', 'label' => $this->translate('einst.benutzer.name'), 'wert' => $ziel['name'], 'fehler' => $fehler['name'] ?? ''], null) ?>
      <?= $this->render('einstellungen/benutzer-rechte', ['rolle' => $ziel['role'], 'sites' => $sites, 'gewaehlt' => $ziel['sites']], null) ?>
      <?php if (!$selbst) : ?>
      <label class="ps-check"><input type="checkbox" name="disabled" value="1"<?= $ziel['disabled'] ? ' checked' : '' ?>><span><?= $this->t('einst.benutzer.sperren') ?></span></label>
      <?php endif; ?>
      <?= $this->render('partials/feld', ['feld' => 'password', 'label' => $this->translate('einst.benutzer.passwort_neu'), 'wert' => '', 'typ' => 'password', 'hinweis' => $this->translate('einst.benutzer.passwort_neu_hinweis'), 'fehler' => $fehler['password'] ?? '', 'autocomplete' => 'new-password', 'passwort' => true], null) ?>
      <?= $this->render('partials/feld', ['feld' => 'password_repeat', 'label' => $this->translate('install.admin.password_repeat'), 'wert' => '', 'typ' => 'password', 'fehler' => $fehler['password_repeat'] ?? '', 'autocomplete' => 'new-password'], null) ?>
      <div class="se-aktionen"><button type="submit" class="ps-btn ps-btn--primary" data-beschaeftigt-beim-senden><?= $this->t('einst.speichern') ?></button></div>
    </form>
  </div>
</section>
<?php if (!$selbst) : ?>
<section class="ps-card es-karte es-gefahr" aria-labelledby="del-titel">
  <div class="ps-card__body">
    <h2 id="del-titel"><?= $this->t('einst.benutzer.loeschen') ?></h2>
    <p class="se-einleitung"><?= $this->t('einst.benutzer.loeschen_text') ?></p>
    <form method="post" action="<?= $this->e($basis . '/loeschen') ?>">
      <input type="hidden" name="_csrf" value="<?= $this->e($csrf) ?>">
      <div class="se-aktionen"><button type="submit" class="ps-btn ps-btn--danger"><?= $this->icon('trash-2', 's') ?> <?= $this->t('einst.benutzer.loeschen_knopf') ?></button></div>
    </form>
  </div>
</section>
<?php endif; ?>
