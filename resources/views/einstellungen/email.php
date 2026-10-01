<?php

declare(strict_types=1);

defined('PEGELSTAND_ROOT') || exit;

/** @var Pegelstand\Core\View $this */
/** @var array<string, string> $werte */
/** @var array<string, string> $fehler */
/** @var bool $passwortGesetzt */
/** @var bool $eingerichtet */
/** @var string $meine */
/** @var bool $admin */
/** @var string $csrf */
/** @var array{typ: string, text: string}|null $flash */
?>
<?= $this->render('einstellungen/nav', ['aktuell' => 'email', 'admin' => $admin, 'csrf' => $csrf, 'flash' => $flash], null) ?>
<section class="ps-card es-karte" aria-labelledby="titel">
  <div class="ps-card__body">
    <h1 id="titel"><?= $this->t('einst.email.titel') ?></h1>
    <p class="se-einleitung"><?= $this->t('einst.email.text') ?></p>
    <?= $this->render('partials/fehlerliste', ['fehler' => $fehler, 'allgemein' => ''], null) ?>
    <form method="post" action="<?= $this->e($this->url('/einstellungen/email')) ?>" novalidate class="se-formular">
      <input type="hidden" name="_csrf" value="<?= $this->e($csrf) ?>">
      <div class="se-zeile">
        <?= $this->render('partials/feld', ['feld' => 'host', 'label' => $this->translate('einst.email.host'), 'wert' => $werte['host'], 'fehler' => $fehler['host'] ?? '', 'hinweis' => $this->translate('einst.email.host_hinweis')], null) ?>
        <?= $this->render('partials/feld', ['feld' => 'port', 'label' => $this->translate('einst.email.port'), 'wert' => $werte['port'], 'fehler' => $fehler['port'] ?? '', 'inputmode' => 'numeric'], null) ?>
      </div>
      <div class="ps-field">
        <label class="ps-label" for="f-security"><?= $this->t('einst.email.sicherheit') ?></label>
        <div class="ps-select">
          <select id="f-security" name="security" aria-describedby="f-security-hinweis">
            <option value="starttls"<?= $werte['security'] === 'starttls' ? ' selected' : '' ?>><?= $this->t('einst.email.sicherheit_starttls') ?></option>
            <option value="tls"<?= $werte['security'] === 'tls' ? ' selected' : '' ?>><?= $this->t('einst.email.sicherheit_tls') ?></option>
            <option value="none"<?= $werte['security'] === 'none' ? ' selected' : '' ?>><?= $this->t('einst.email.sicherheit_keine') ?></option>
          </select>
          <?= $this->icon('chevron-down', 's') ?>
        </div>
        <span class="ps-hint" id="f-security-hinweis"><?= $this->t('einst.email.sicherheit_hinweis') ?></span>
      </div>
      <div class="se-zeile">
        <?= $this->render('partials/feld', ['feld' => 'user', 'label' => $this->translate('einst.email.benutzer'), 'wert' => $werte['user'], 'fehler' => '', 'autocomplete' => 'off'], null) ?>
        <?= $this->render('partials/feld', ['feld' => 'password', 'label' => $this->translate('einst.email.passwort'), 'wert' => '', 'typ' => 'password', 'fehler' => '', 'autocomplete' => 'new-password', 'passwort' => true, 'hinweis' => $passwortGesetzt ? $this->translate('einst.email.passwort_gesetzt') : ''], null) ?>
      </div>
      <div class="se-zeile">
        <?= $this->render('partials/feld', ['feld' => 'from_address', 'label' => $this->translate('einst.email.absender'), 'wert' => $werte['from_address'], 'typ' => 'email', 'fehler' => $fehler['from_address'] ?? ''], null) ?>
        <?= $this->render('partials/feld', ['feld' => 'from_name', 'label' => $this->translate('einst.email.absender_name'), 'wert' => $werte['from_name'], 'fehler' => $fehler['from_name'] ?? ''], null) ?>
      </div>
      <?= $this->render('partials/feld', ['feld' => 'base_url', 'label' => $this->translate('einst.email.adresse'), 'wert' => $werte['base_url'], 'fehler' => $fehler['base_url'] ?? '', 'hinweis' => $this->translate('einst.email.adresse_hinweis')], null) ?>
      <div class="se-aktionen"><button type="submit" class="ps-btn ps-btn--primary" data-beschaeftigt-beim-senden><?= $this->t('einst.speichern') ?></button></div>
    </form>
  </div>
</section>

<section class="ps-card es-karte" aria-labelledby="test-titel">
  <div class="ps-card__body">
    <h2 id="test-titel"><?= $this->t('einst.email.test') ?></h2>
    <p class="se-einleitung"><?= $this->t('einst.email.test_text_hinweis', ['adresse' => $meine]) ?></p>
    <form method="post" action="<?= $this->e($this->url('/einstellungen/email/test')) ?>">
      <input type="hidden" name="_csrf" value="<?= $this->e($csrf) ?>">
      <div class="se-aktionen"><button type="submit" class="ps-btn ps-btn--secondary"<?= $eingerichtet ? '' : ' disabled' ?>><?= $this->t('einst.email.test_senden') ?></button></div>
    </form>
  </div>
</section>
