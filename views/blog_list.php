<?php /** @var array $posts */ ?>
<section class="page-hero">
  <div class="hero-bg" aria-hidden="true"><i class="blob blob-1"></i><i class="blob blob-2"></i></div>
  <div class="wrap">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= e(u('/')) ?>"><?= e(tr('home')) ?></a><?= icon('chevron-right') ?><span><?= e(s('blog_title')) ?></span></nav>
    <span class="eyebrow"><i class="eyebrow-dot"></i>Blog</span>
    <h1 class="page-title" data-split-words><?= e(s('blog_title')) ?></h1>
    <p class="page-lead"><?= e(s('blog_intro')) ?></p>
  </div>
</section>
<section class="sec sec-tight">
  <div class="wrap">
    <?php if ($posts): ?>
    <div class="grid grid-3" data-stagger>
      <?php foreach ($posts as $post) { include __DIR__ . '/partials/post_card.php'; } ?>
    </div>
    <?php else: ?><p class="muted center"><?= e(tr('no_posts')) ?></p><?php endif; ?>
  </div>
</section>
