<?php $posts = Content::posts(3); ?>
<section class="sec sec-blog" id="<?= e($sec['anchor']) ?>" data-section="blog">
  <div class="wrap">
    <?= View::head($sec) ?>
    <?php if ($posts): ?>
    <div class="grid grid-3" data-stagger>
      <?php foreach ($posts as $post) { include __DIR__ . '/../partials/post_card.php'; } ?>
    </div>
    <div class="center" data-reveal>
      <a class="btn btn-ghost magnetic" href="<?= e(u('/blog/')) ?>"><span><?= e(tr('all_posts')) ?></span><?= icon('arrow-right') ?></a>
    </div>
    <?php else: ?>
    <p class="muted center"><?= e(tr('no_posts')) ?></p>
    <?php endif; ?>
  </div>
</section>
