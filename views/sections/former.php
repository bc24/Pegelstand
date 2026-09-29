<?php $items = Content::visible('former_sites', 'sort ASC, id ASC'); if (!$items) return; ?>
<section class="sec sec-former" id="<?= e($sec['anchor']) ?>" data-section="former">
  <div class="wrap">
    <?= View::head($sec) ?>
    <ul class="archive" data-stagger>
      <?php foreach ($items as $it): ?>
      <li class="arch-card" data-reveal>
        <span class="arch-year"><?= e($it['year'] !== '' ? $it['year'] : '—') ?></span>
        <span class="arch-domain"><?= e($it['domain']) ?></span>
        <span class="arch-text"><?= e(t($it, 'text')) ?></span>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
