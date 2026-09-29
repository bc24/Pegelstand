<?php $items = Content::visible('shop_items'); ?>
<section class="sec sec-shop" id="<?= e($sec['anchor']) ?>" data-section="shop">
  <div class="wrap">
    <?= View::head($sec) ?>
    <div class="grid grid-4" data-stagger>
      <?php foreach ($items as $it): ?>
      <article class="card card-icon spot" data-reveal>
        <span class="icon-orb"><?= icon($it['icon']) ?></span>
        <h3><?= e(t($it, 'title')) ?></h3>
        <p><?= e(t($it, 'text')) ?></p>
      </article>
      <?php endforeach; ?>
    </div>
    <div class="center btn-row" data-reveal>
      <?php if (setting('shop_url') !== ''): ?><a class="btn btn-primary magnetic" href="<?= e(setting('shop_url')) ?>" target="_blank" rel="noopener"><span><?= e(s('shop_cta')) ?></span><?= icon('arrow-up-right') ?></a><?php endif; ?>
      <?php if (setting('shop_url2') !== ''): ?><a class="btn btn-ghost magnetic" href="<?= e(setting('shop_url2')) ?>" target="_blank" rel="noopener"><span><?= e(s('shop_cta2')) ?></span><?= icon('arrow-up-right') ?></a><?php endif; ?>
    </div>
  </div>
</section>
