<?php

declare(strict_types=1);

defined('PEGELSTAND_ROOT') || exit;

/** @var Pegelstand\Core\View $this */
/** @var list<array<string, mixed>> $sites */
/** @var array<string, string> $werte */
/** @var array<string, string> $fehler */
/** @var list<string> $zeitzonen */
/** @var bool $admin */
/** @var string $csrf */
/** @var array{typ: string, text: string}|null $flash */
?>
<?= $this->render('einstellungen/nav', ['aktuell' => 'websites', 'admin' => $admin, 'csrf' => $csrf, 'flash' => $flash], null) ?>
<section class="ps-card es-karte" aria-labelledby="titel">
  <div class="ps-card__body">
    <h1 id="titel"><?= $this->t('einst.websites.titel') ?></h1>
    <?php if ($sites === []) : ?>
    <p class="se-einleitung"><?= $this->t('einst.websites.leer') ?></p>
    <?php else : ?>
    <div class="ps-table-wrap">
      <table class="ps-table">
        <caption class="ps-visually-hidden"><?= $this->t('einst.websites.titel') ?></caption>
        <thead><tr><th scope="col"><?= $this->t('einst.websites.name') ?></th><th scope="col"><?= $this->t('einst.websites.domain') ?></th><th scope="col"><span class="ps-visually-hidden"><?= $this->t('einst.aktion') ?></span></th></tr></thead>
        <tbody>
        <?php foreach ($sites as $s) : ?>
          <tr>
            <td><?= $this->e($s['name']) ?></td>
            <td><?= $this->e($s['domain']) ?></td>
            <td class="num"><a href="<?= $this->e($this->url('/einstellungen/websites/' . $s['public_id'])) ?>"><?= $this->t('einst.bearbeiten') ?><span class="ps-visually-hidden"> <?= $this->e($s['name']) ?></span></a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="ps-card es-karte" aria-labelledby="neu-titel">
  <div class="ps-card__body">
    <h2 id="neu-titel"><?= $this->t('einst.websites.neu') ?></h2>
    <?= $this->render('partials/fehlerliste', ['fehler' => $fehler, 'allgemein' => ''], null) ?>
    <form method="post" action="<?= $this->e($this->url('/einstellungen/websites')) ?>" novalidate class="se-formular">
      <input type="hidden" name="_csrf" value="<?= $this->e($csrf) ?>">
      <?= $this->render('einstellungen/website-felder', ['werte' => $werte, 'fehler' => $fehler, 'zeitzonen' => $zeitzonen, 'dnt' => false, 'gpc' => false], null) ?>
      <div class="se-aktionen"><button type="submit" class="ps-btn ps-btn--primary" data-beschaeftigt-beim-senden><?= $this->t('einst.websites.anlegen') ?></button></div>
    </form>
  </div>
</section>
