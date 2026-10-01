<?php

declare(strict_types=1);

defined('PEGELSTAND_ROOT') || exit;

/** @var Pegelstand\Core\View $this */
/** @var array<string, mixed> $site */
/** @var array<string, string> $werte */
/** @var array<string, string> $fehler */
/** @var list<string> $zeitzonen */
/** @var list<array{id: int, ip_range: string, label: string}> $ausschluesse */
/** @var string $code */
/** @var string $ip */
/** @var bool $admin */
/** @var string $csrf */
/** @var array{typ: string, text: string}|null $flash */
$basis = $this->url('/einstellungen/websites/' . $site['public_id']);
?>
<?= $this->render('einstellungen/nav', ['aktuell' => 'websites', 'admin' => $admin, 'csrf' => $csrf, 'flash' => $flash], null) ?>
<section class="ps-card es-karte" aria-labelledby="titel">
  <div class="ps-card__body">
    <h1 id="titel"><?= $this->e($site['name']) ?></h1>
    <p class="se-einleitung"><a href="<?= $this->e($this->url('/einstellungen/websites')) ?>"><?= $this->t('einst.websites.zurueck') ?></a></p>
    <?= $this->render('partials/fehlerliste', ['fehler' => $fehler, 'allgemein' => ''], null) ?>
    <form method="post" action="<?= $this->e($basis) ?>" novalidate class="se-formular">
      <input type="hidden" name="_csrf" value="<?= $this->e($csrf) ?>">
      <?= $this->render('einstellungen/website-felder', ['werte' => $werte, 'fehler' => $fehler, 'zeitzonen' => $zeitzonen, 'dnt' => $site['respect_dnt'], 'gpc' => $site['respect_gpc']], null) ?>
      <div class="se-aktionen"><button type="submit" class="ps-btn ps-btn--primary" data-beschaeftigt-beim-senden><?= $this->t('einst.speichern') ?></button></div>
    </form>
  </div>
</section>

<section class="ps-card es-karte" aria-labelledby="code-titel">
  <div class="ps-card__body">
    <h2 id="code-titel"><?= $this->t('einst.websites.code') ?></h2>
    <p class="se-einleitung"><?= $this->t('einst.websites.code_text') ?></p>
    <pre class="es-code" tabindex="0"><code><?= $this->e($code) ?></code></pre>
    <div class="se-aktionen"><button type="button" class="ps-btn ps-btn--secondary" data-kopiere="<?= $this->e($code) ?>"><?= $this->icon('copy', 's') ?> <?= $this->t('einst.kopieren') ?></button></div>
  </div>
</section>

<section class="ps-card es-karte" id="ausschluesse" aria-labelledby="aus-titel">
  <div class="ps-card__body">
    <h2 id="aus-titel"><?= $this->t('einst.websites.ausschluss') ?></h2>
    <p class="se-einleitung"><?= $this->t('einst.websites.ausschluss_text') ?></p>
    <?php if ($ausschluesse !== []) : ?>
    <div class="ps-table-wrap">
      <table class="ps-table">
        <caption class="ps-visually-hidden"><?= $this->t('einst.websites.ausschluss') ?></caption>
        <thead><tr><th scope="col"><?= $this->t('einst.websites.ip') ?></th><th scope="col"><?= $this->t('einst.websites.bezeichnung') ?></th><th scope="col"><span class="ps-visually-hidden"><?= $this->t('einst.aktion') ?></span></th></tr></thead>
        <tbody>
        <?php foreach ($ausschluesse as $a) : ?>
          <tr>
            <td><?= $this->e($a['ip_range']) ?></td>
            <td><?= $this->e($a['label']) ?></td>
            <td class="num">
              <form method="post" action="<?= $this->e($basis . '/ausschluss/' . $a['id'] . '/loeschen') ?>" class="es-inline">
                <input type="hidden" name="_csrf" value="<?= $this->e($csrf) ?>">
                <button type="submit" class="ps-btn ps-btn--ghost ps-btn--s"><?= $this->t('einst.entfernen') ?><span class="ps-visually-hidden"> <?= $this->e($a['ip_range']) ?></span></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
    <form method="post" action="<?= $this->e($basis . '/ausschluss') ?>" novalidate class="se-formular">
      <input type="hidden" name="_csrf" value="<?= $this->e($csrf) ?>">
      <div class="se-zeile">
        <?= $this->render('partials/feld', ['feld' => 'ip_range', 'label' => $this->translate('einst.websites.ip'), 'wert' => '', 'fehler' => '', 'hinweis' => $this->translate('einst.websites.ip_hinweis', ['ip' => $ip])], null) ?>
        <?= $this->render('partials/feld', ['feld' => 'label', 'label' => $this->translate('einst.websites.bezeichnung'), 'wert' => '', 'fehler' => ''], null) ?>
      </div>
      <div class="se-aktionen"><button type="submit" class="ps-btn ps-btn--secondary"><?= $this->t('einst.websites.ausschluss_neu_knopf') ?></button></div>
    </form>
  </div>
</section>

<section class="ps-card es-karte es-gefahr" id="loeschen" aria-labelledby="del-titel">
  <div class="ps-card__body">
    <h2 id="del-titel"><?= $this->t('einst.websites.loeschen') ?></h2>
    <p class="se-einleitung"><?= $this->t('einst.websites.loeschen_text', ['domain' => $site['domain']]) ?></p>
    <form method="post" action="<?= $this->e($basis . '/loeschen') ?>" novalidate class="se-formular">
      <input type="hidden" name="_csrf" value="<?= $this->e($csrf) ?>">
      <?= $this->render('partials/feld', ['feld' => 'bestaetigung', 'label' => $this->translate('einst.websites.bestaetigung', ['domain' => $site['domain']]), 'wert' => '', 'fehler' => ''], null) ?>
      <div class="se-aktionen"><button type="submit" class="ps-btn ps-btn--danger"><?= $this->icon('trash-2', 's') ?> <?= $this->t('einst.websites.loeschen_knopf') ?></button></div>
    </form>
  </div>
</section>
