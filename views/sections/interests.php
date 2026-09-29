<?php $items = Content::visible('interests'); ?>
<section class="sec sec-interests" id="<?= e($sec['anchor']) ?>" data-section="interests">
  <div class="wrap">
    <?= View::head($sec) ?>
    <div class="grid grid-4" data-stagger>
      <?php foreach ($items as $it): ?>
      <article class="card card-icon spot" data-reveal data-tilt data-tilt-max="5">
        <span class="icon-orb"><?= icon($it['icon']) ?></span>
        <h3><?= e(t($it, 'title')) ?></h3>
        <p><?= e(t($it, 'text')) ?></p>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
