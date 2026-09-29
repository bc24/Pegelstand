<?php /** @var array $page @var bool $fallbackDe */ ?>
<section class="page-hero page-hero-sm">
  <div class="hero-bg" aria-hidden="true"><i class="blob blob-1"></i></div>
  <div class="wrap wrap-narrow">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= e(u('/')) ?>"><?= e(tr('home')) ?></a><?= icon('chevron-right') ?><span><?= e(t($page, 'title')) ?></span></nav>
    <h1 class="page-title"><?= e(t($page, 'title')) ?></h1>
    <?php if (t($page, 'subtitle') !== ''): ?><p class="page-lead"><?= e(t($page, 'subtitle')) ?></p><?php endif; ?>
    <?php if ($fallbackDe): ?><p class="notice"><?= icon('languages') ?><?= e(tr('only_german_page')) ?></p><?php endif; ?>
  </div>
</section>
<section class="sec sec-tight">
  <div class="wrap wrap-narrow"><div class="prose prose-lg"><?= Html::sanitize(t($page, 'content')) ?></div></div>
</section>
