<?php $items = Content::visible('facts'); ?>
<section class="sec sec-facts" id="<?= e($sec['anchor']) ?>" data-section="funfacts">
  <div class="wrap">
    <?= View::head($sec) ?>
    <div class="facts" data-stagger>
      <?php foreach ($items as $it): ?>
      <article class="fact spot" data-reveal>
        <span class="fact-icon"><?= icon($it['icon']) ?></span>
        <div><h3><?= e(t($it, 'title')) ?></h3><p><?= e(t($it, 'text')) ?></p></div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
