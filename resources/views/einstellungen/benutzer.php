<?php

declare(strict_types=1);

defined('PEGELSTAND_ROOT') || exit;

/** @var Pegelstand\Core\View $this */
/** @var list<array<string, mixed>> $liste */
/** @var list<array<string, mixed>> $sites */
/** @var array<string, string> $werte */
/** @var list<int> $gewaehlt */
/** @var array<string, string> $fehler */
/** @var bool $admin */
/** @var string $csrf */
/** @var array{typ: string, text: string}|null $flash */
?>
<?= $this->render('einstellungen/nav', ['aktuell' => 'benutzer', 'admin' => $admin, 'csrf' => $csrf, 'flash' => $flash], null) ?>
<section class="ps-card es-karte" aria-labelledby="titel">
  <div class="ps-card__body">
    <h1 id="titel"><?= $this->t('einst.benutzer.titel') ?></h1>
    <div class="ps-table-wrap">
      <table class="ps-table">
        <caption class="ps-visually-hidden"><?= $this->t('einst.benutzer.titel') ?></caption>
        <thead><tr><th scope="col"><?= $this->t('einst.benutzer.name') ?></th><th scope="col"><?= $this->t('einst.benutzer.email') ?></th><th scope="col"><?= $this->t('einst.benutzer.rolle') ?></th><th scope="col"><span class="ps-visually-hidden"><?= $this->t('einst.aktion') ?></span></th></tr></thead>
        <tbody>
        <?php foreach ($liste as $b) : ?>
          <tr>
            <td><?= $this->e($b['name']) ?></td>
            <td><?= $this->e($b['email']) ?></td>
            <td><?= $b['role'] === 'admin' ? $this->t('einst.benutzer.rolle_admin') : $this->t('einst.benutzer.rolle_viewer') ?><?php if ($b['disabled']) : ?> <span class="ps-badge ps-badge--warning"><?= $this->t('einst.benutzer.gesperrt') ?></span><?php endif; ?></td>
            <td class="num"><a href="<?= $this->e($this->url('/einstellungen/benutzer/' . $b['id'])) ?>"><?= $this->t('einst.bearbeiten') ?><span class="ps-visually-hidden"> <?= $this->e($b['name']) ?></span></a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<section class="ps-card es-karte" aria-labelledby="neu-titel">
  <div class="ps-card__body">
    <h2 id="neu-titel"><?= $this->t('einst.benutzer.neu') ?></h2>
    <?= $this->render('partials/fehlerliste', ['fehler' => $fehler, 'allgemein' => ''], null) ?>
    <form method="post" action="<?= $this->e($this->url('/einstellungen/benutzer')) ?>" novalidate class="se-formular">
      <input type="hidden" name="_csrf" value="<?= $this->e($csrf) ?>">
      <?= $this->render('partials/feld', ['feld' => 'name', 'label' => $this->translate('einst.benutzer.name'), 'wert' => $werte['name'], 'fehler' => $fehler['name'] ?? '', 'autocomplete' => 'off'], null) ?>
      <?= $this->render('partials/feld', ['feld' => 'email', 'label' => $this->translate('einst.benutzer.email'), 'wert' => $werte['email'], 'typ' => 'email', 'fehler' => $fehler['email'] ?? '', 'autocomplete' => 'off'], null) ?>
      <?= $this->render('partials/feld', ['feld' => 'password', 'label' => $this->translate('einst.benutzer.passwort'), 'wert' => '', 'typ' => 'password', 'hinweis' => $this->translate('install.admin.password_hinweis'), 'fehler' => $fehler['password'] ?? '', 'autocomplete' => 'new-password', 'passwort' => true], null) ?>
      <?= $this->render('partials/feld', ['feld' => 'password_repeat', 'label' => $this->translate('install.admin.password_repeat'), 'wert' => '', 'typ' => 'password', 'fehler' => $fehler['password_repeat'] ?? '', 'autocomplete' => 'new-password'], null) ?>
      <?= $this->render('einstellungen/benutzer-rechte', ['rolle' => $werte['role'], 'sites' => $sites, 'gewaehlt' => $gewaehlt], null) ?>
      <div class="se-aktionen"><button type="submit" class="ps-btn ps-btn--primary" data-beschaeftigt-beim-senden><?= $this->t('einst.benutzer.anlegen') ?></button></div>
    </form>
  </div>
</section>
