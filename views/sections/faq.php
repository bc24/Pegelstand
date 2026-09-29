<?php $items = Content::visible('faq'); if (!$items) return; ?>
<section class="sec sec-faq" id="<?= e($sec['anchor']) ?>" data-section="faq">
  <div class="wrap wrap-narrow">
    <?= View::head($sec) ?>
    <div class="faq" data-stagger>
      <?php foreach ($items as $i => $it): ?>
      <div class="faq-item" data-reveal>
        <h3><button type="button" class="faq-q" aria-expanded="false" aria-controls="faq-a-<?= $i ?>" id="faq-q-<?= $i ?>"><span><?= e(t($it, 'q')) ?></span><?= icon('plus', 'faq-icon') ?></button></h3>
        <div class="faq-a" id="faq-a-<?= $i ?>" role="region" aria-labelledby="faq-q-<?= $i ?>"><div><div class="prose"><?= Html::sanitize(t($it, 'a')) ?></div></div></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
