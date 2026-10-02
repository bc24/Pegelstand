<?php

declare(strict_types=1);

defined('PEGELSTAND_ROOT') || exit;

/** @var Pegelstand\Core\View $this */
/** @var Pegelstand\Auth\AuthUser $benutzer */
/** @var array<string, string> $fehler */
/** @var list<array{id: int, name: string, key_prefix: string, created_at: string, last_used_at: string}> $apiSchluessel */
/** @var string|null $neuerSchluessel */
/** @var list<array<string, mixed>> $berichtSites */
/** @var list<string> $berichtGewaehlt */
/** @var bool $mailAktiv */
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

<section class="ps-card es-karte" id="berichte" aria-labelledby="ber-titel">
  <div class="ps-card__body">
    <h2 id="ber-titel"><?= $this->t('einst.berichte.titel') ?></h2>
    <p class="se-einleitung"><?= $this->t('einst.berichte.text') ?></p>
    <?php if (!$mailAktiv) : ?>
    <div class="ps-alert ps-alert--warning" role="status"><?= $this->icon('triangle-alert') ?><div class="ps-alert__inhalt"><p><?= $this->t($admin ? 'einst.berichte.kein_versand_admin' : 'einst.berichte.kein_versand') ?></p></div></div>
    <?php endif; ?>
    <?php if ($berichtSites === []) : ?>
    <p><?= $this->t('einst.berichte.keine_sites') ?></p>
    <?php else : ?>
    <form method="post" action="<?= $this->e($this->url('/einstellungen/konto/berichte')) ?>" class="se-formular">
      <input type="hidden" name="_csrf" value="<?= $this->e($csrf) ?>">
      <?php foreach ($berichtSites as $s) : ?>
      <fieldset class="es-checks es-fieldset">
        <legend class="ps-label"><?= $this->e($s['name']) ?> (<?= $this->e($s['domain']) ?>)</legend>
        <label class="ps-check"><input type="checkbox" name="bericht[]" value="<?= $this->e($s['id']) ?>:weekly"<?= in_array($s['id'] . ':weekly', $berichtGewaehlt, true) ? ' checked' : '' ?>><span><?= $this->t('einst.berichte.woechentlich') ?></span></label>
        <label class="ps-check"><input type="checkbox" name="bericht[]" value="<?= $this->e($s['id']) ?>:monthly"<?= in_array($s['id'] . ':monthly', $berichtGewaehlt, true) ? ' checked' : '' ?>><span><?= $this->t('einst.berichte.monatlich') ?></span></label>
      </fieldset>
      <?php endforeach; ?>
      <div class="se-aktionen"><button type="submit" class="ps-btn ps-btn--secondary"><?= $this->t('einst.speichern') ?></button></div>
    </form>
    <?php endif; ?>
  </div>
</section>

<section class="ps-card es-karte" id="api" aria-labelledby="api-titel">
  <div class="ps-card__body">
    <h2 id="api-titel"><?= $this->t('einst.api.titel') ?></h2>
    <p class="se-einleitung"><?= $this->t('einst.api.text') ?></p>
    <?php if ($neuerSchluessel !== null) : ?>
    <div class="ps-alert ps-alert--success" role="status">
      <?= $this->icon('circle-check') ?>
      <div class="ps-alert__inhalt">
        <p class="ps-alert__titel"><?= $this->t('einst.api.neu_titel') ?></p>
        <p><?= $this->t('einst.api.neu_text') ?></p>
        <pre class="es-code" tabindex="0"><code><?= $this->e($neuerSchluessel) ?></code></pre>
      </div>
    </div>
    <?php endif; ?>
    <?php if ($apiSchluessel !== []) : ?>
    <div class="ps-table-wrap">
      <table class="ps-table">
        <caption class="ps-visually-hidden"><?= $this->t('einst.api.titel') ?></caption>
        <thead><tr><th scope="col"><?= $this->t('einst.api.name') ?></th><th scope="col"><?= $this->t('einst.api.beginn') ?></th><th scope="col"><?= $this->t('einst.api.zuletzt') ?></th><th scope="col"><span class="ps-visually-hidden"><?= $this->t('einst.aktion') ?></span></th></tr></thead>
        <tbody>
        <?php foreach ($apiSchluessel as $k) : ?>
          <tr>
            <td><?= $this->e($k['name']) ?></td>
            <td><code><?= $this->e($k['key_prefix']) ?>…</code></td>
            <td><?= $k['last_used_at'] === '' ? $this->t('einst.api.nie') : $this->e($k['last_used_at']) . ' UTC' ?></td>
            <td class="num">
              <form method="post" action="<?= $this->e($this->url('/einstellungen/konto/api-schluessel/' . $k['id'] . '/loeschen')) ?>" class="es-inline">
                <input type="hidden" name="_csrf" value="<?= $this->e($csrf) ?>">
                <button type="submit" class="ps-btn ps-btn--ghost ps-btn--s"><?= $this->t('einst.entfernen') ?><span class="ps-visually-hidden"> <?= $this->e($k['name']) ?></span></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
    <?php if (isset($fehler['key_name'])) : ?>
    <?= $this->render('partials/fehlerliste', ['fehler' => ['key_name' => $fehler['key_name']], 'allgemein' => ''], null) ?>
    <?php endif; ?>
    <form method="post" action="<?= $this->e($this->url('/einstellungen/konto/api-schluessel')) ?>" novalidate class="se-formular">
      <input type="hidden" name="_csrf" value="<?= $this->e($csrf) ?>">
      <?= $this->render('partials/feld', ['feld' => 'key_name', 'label' => $this->translate('einst.api.name'), 'wert' => '', 'fehler' => $fehler['key_name'] ?? '', 'hinweis' => $this->translate('einst.api.name_hinweis')], null) ?>
      <div class="se-aktionen"><button type="submit" class="ps-btn ps-btn--secondary"><?= $this->t('einst.api.anlegen') ?></button></div>
    </form>
  </div>
</section>
