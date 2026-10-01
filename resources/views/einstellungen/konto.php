<?php

declare(strict_types=1);

defined('PEGELSTAND_ROOT') || exit;

/** @var Pegelstand\Core\View $this */
/** @var Pegelstand\Auth\AuthUser $benutzer */
/** @var array<string, string> $fehler */
/** @var bool $zweiFaktor */
/** @var string|null $setupSchluessel */
/** @var string $setupLink */
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
    <?= $this->render('partials/fehlerliste', ['fehler' => array_intersect_key($fehler, ['current' => 1, 'password' => 1, 'password_repeat' => 1]), 'allgemein' => ''], null) ?>
    <form method="post" action="<?= $this->e($this->url('/einstellungen/konto')) ?>" novalidate class="se-formular">
      <input type="hidden" name="_csrf" value="<?= $this->e($csrf) ?>">
      <?= $this->render('partials/feld', ['feld' => 'current', 'label' => $this->translate('einst.konto.aktuell'), 'wert' => '', 'typ' => 'password', 'fehler' => $fehler['current'] ?? '', 'autocomplete' => 'current-password'], null) ?>
      <?= $this->render('partials/feld', ['feld' => 'password', 'label' => $this->translate('einst.konto.neu'), 'wert' => '', 'typ' => 'password', 'hinweis' => $this->translate('install.admin.password_hinweis'), 'fehler' => $fehler['password'] ?? '', 'autocomplete' => 'new-password', 'passwort' => true], null) ?>
      <?= $this->render('partials/feld', ['feld' => 'password_repeat', 'label' => $this->translate('install.admin.password_repeat'), 'wert' => '', 'typ' => 'password', 'fehler' => $fehler['password_repeat'] ?? '', 'autocomplete' => 'new-password'], null) ?>
      <div class="se-aktionen"><button type="submit" class="ps-btn ps-btn--primary" data-beschaeftigt-beim-senden><?= $this->t('einst.konto.aendern') ?></button></div>
    </form>
  </div>
</section>

<section class="ps-card es-karte" id="zwei-fa" aria-labelledby="fa-titel">
  <div class="ps-card__body">
    <h2 id="fa-titel"><?= $this->t('einst.zwei_fa.titel') ?></h2>
    <?php if ($zweiFaktor) : ?>
    <p class="se-einleitung"><?= $this->t('einst.zwei_fa.an') ?></p>
    <?php if (isset($fehler['current_aus'])) : ?>
    <?= $this->render('partials/fehlerliste', ['fehler' => ['current_aus' => $fehler['current_aus']], 'allgemein' => ''], null) ?>
    <?php endif; ?>
    <form method="post" action="<?= $this->e($this->url('/einstellungen/konto/2fa/aus')) ?>" novalidate class="se-formular">
      <input type="hidden" name="_csrf" value="<?= $this->e($csrf) ?>">
      <?= $this->render('partials/feld', ['feld' => 'current_aus', 'label' => $this->translate('einst.konto.aktuell'), 'wert' => '', 'typ' => 'password', 'hinweis' => $this->translate('einst.zwei_fa.passwort_noetig'), 'fehler' => $fehler['current_aus'] ?? '', 'autocomplete' => 'current-password'], null) ?>
      <div class="se-aktionen"><button type="submit" class="ps-btn ps-btn--secondary"><?= $this->t('einst.zwei_fa.ausschalten') ?></button></div>
    </form>
    <?php elseif ($setupSchluessel !== null) : ?>
    <p class="se-einleitung"><?= $this->t('einst.zwei_fa.schritt1') ?></p>
    <p class="ps-label"><?= $this->t('einst.zwei_fa.schluessel') ?></p>
    <pre class="es-code" tabindex="0"><code><?= $this->e(trim(chunk_split($setupSchluessel, 4, ' '))) ?></code></pre>
    <p><a href="<?= $this->e($setupLink) ?>"><?= $this->t('einst.zwei_fa.link') ?></a></p>
    <p class="se-einleitung"><?= $this->t('einst.zwei_fa.schritt2') ?></p>
    <?php if (isset($fehler['code'])) : ?>
    <?= $this->render('partials/fehlerliste', ['fehler' => ['code' => $fehler['code']], 'allgemein' => ''], null) ?>
    <?php endif; ?>
    <form method="post" action="<?= $this->e($this->url('/einstellungen/konto/2fa/bestaetigen')) ?>" novalidate class="se-formular">
      <input type="hidden" name="_csrf" value="<?= $this->e($csrf) ?>">
      <?= $this->render('partials/feld', ['feld' => 'code', 'label' => $this->translate('login.code'), 'wert' => '', 'fehler' => $fehler['code'] ?? '', 'autocomplete' => 'one-time-code', 'inputmode' => 'numeric'], null) ?>
      <div class="se-aktionen se-aktionen--getrennt">
        <button type="submit" formaction="<?= $this->e($this->url('/einstellungen/konto/2fa/abbrechen')) ?>" formnovalidate class="ps-btn ps-btn--ghost"><?= $this->t('einst.zwei_fa.abbrechen') ?></button>
        <button type="submit" class="ps-btn ps-btn--primary"><?= $this->t('einst.zwei_fa.bestaetigen') ?></button>
      </div>
    </form>
    <?php else : ?>
    <p class="se-einleitung"><?= $this->t('einst.zwei_fa.aus') ?></p>
    <form method="post" action="<?= $this->e($this->url('/einstellungen/konto/2fa/start')) ?>">
      <input type="hidden" name="_csrf" value="<?= $this->e($csrf) ?>">
      <div class="se-aktionen"><button type="submit" class="ps-btn ps-btn--primary"><?= $this->t('einst.zwei_fa.einrichten') ?></button></div>
    </form>
    <?php endif; ?>
  </div>
</section>
