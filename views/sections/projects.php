<?php
$projects = Content::projects(true);
$cats = Content::projectCategories($projects);
?>
<section class="sec sec-projects" id="<?= e($sec['anchor']) ?>" data-section="projects">
  <div class="wrap">
    <?= View::head($sec) ?>
    <?php if (count($cats) > 1): ?>
    <div class="filters" role="tablist" data-reveal aria-label="<?= e(tr('category')) ?>">
      <button type="button" class="chip-btn is-active" data-filter="*" role="tab" aria-selected="true"><?= e(tr('cat_all')) ?><small><?= count($projects) ?></small></button>
      <?php foreach ($cats as $key => $label): ?>
      <button type="button" class="chip-btn" data-filter="<?= e($key) ?>" role="tab" aria-selected="false"><?= e($label) ?><small><?= count(array_filter($projects, static fn($p) => $p['category'] === $key)) ?></small></button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div class="grid grid-3 pgrid" data-filter-grid data-stagger>
      <?php foreach ($projects as $p) { include __DIR__ . '/../partials/project_card.php'; } ?>
    </div>
    <div class="center" data-reveal>
      <a class="btn btn-ghost magnetic" href="<?= e(u('/projekte/')) ?>"><span><?= e(tr('all_projects')) ?></span><?= icon('arrow-right') ?></a>
    </div>
  </div>
</section>
