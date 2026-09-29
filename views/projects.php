<?php /** @var array $projects @var array $cats */ ?>
<section class="page-hero">
  <div class="hero-bg" aria-hidden="true"><i class="blob blob-1"></i><i class="blob blob-2"></i></div>
  <div class="wrap">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= e(u('/')) ?>"><?= e(tr('home')) ?></a><?= icon('chevron-right') ?><span><?= e(tr('projects_page_title')) ?></span></nav>
    <span class="eyebrow"><i class="eyebrow-dot"></i><?= e(str_replace('{n}', (string)count($projects), tr('projects_count'))) ?></span>
    <h1 class="page-title" data-split-words><?= e(tr('projects_page_title')) ?></h1>
    <p class="page-lead"><?= e(tr('projects_page_intro')) ?></p>
  </div>
</section>
<section class="sec sec-tight sec-projects">
  <div class="wrap">
    <div class="filters" role="tablist" aria-label="<?= e(tr('category')) ?>">
      <button type="button" class="chip-btn is-active" data-filter="*" role="tab" aria-selected="true"><?= e(tr('cat_all')) ?><small><?= count($projects) ?></small></button>
      <?php foreach ($cats as $key => $label): ?>
      <button type="button" class="chip-btn" data-filter="<?= e($key) ?>" role="tab" aria-selected="false"><?= e($label) ?><small><?= count(array_filter($projects, static fn($p) => $p['category'] === $key)) ?></small></button>
      <?php endforeach; ?>
    </div>
    <div class="grid grid-3 pgrid" data-filter-grid data-stagger>
      <?php foreach ($projects as $p) { include __DIR__ . '/partials/project_card.php'; } ?>
    </div>
  </div>
</section>
