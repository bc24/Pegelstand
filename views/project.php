<?php
/** @var array $p @var array $others */
$img = media_url($p['image']);
$hue = crc32($p['slug']) % 90;
$desc = Html::sanitize(t($p, 'description'));
$tech = array_filter(array_map('trim', explode(',', (string)$p['tech'])));
$meta = t($p, 'meta');
?>
<section class="page-hero project-hero">
  <div class="hero-bg" aria-hidden="true"><i class="blob blob-1"></i><i class="blob blob-2"></i></div>
  <div class="wrap">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= e(u('/')) ?>"><?= e(tr('home')) ?></a><?= icon('chevron-right') ?><a href="<?= e(u('/projekte/')) ?>"><?= e(tr('projects_page_title')) ?></a><?= icon('chevron-right') ?><span><?= e($p['title']) ?></span></nav>
    <div class="project-head">
      <div class="project-head-copy">
        <?php if (t($p, 'badge') !== ''): ?><span class="badge"><?= icon($p['badge_icon']) ?><?= e(t($p, 'badge')) ?></span><?php endif; ?>
        <h1 class="page-title"><?= e($p['title']) ?></h1>
        <p class="page-lead"><?= e(t($p, 'tagline')) ?></p>
        <div class="btn-row">
          <?php if ($p['url'] !== ''): ?><a class="btn btn-primary magnetic" href="<?= e($p['url']) ?>" target="_blank" rel="noopener"><span><?= e(tr('visit_project')) ?></span><?= icon('arrow-up-right') ?></a><?php endif; ?>
          <?php if ($p['slug'] === 'panzerit-de'): ?><a class="btn btn-ghost magnetic" href="<?= e(u('/anfrage/')) ?>"><?= icon('send') ?><span><?= e(tr('inq_cta')) ?></span></a><?php endif; ?>
          <a class="btn btn-ghost magnetic" href="<?= e(u('/projekte/')) ?>"><?= icon('arrow-left') ?><span><?= e(tr('back_projects')) ?></span></a>
        </div>
        <dl class="facts-row">
          <div><dt><?= e(tr('status')) ?></dt><dd><span class="status status-<?= $p['status'] === 'live' ? 'done' : 'plan' ?>"><i class="pulse-dot"></i><?= e(tr('status_' . $p['status'])) ?></span></dd></div>
          <?php if ($p['year_from'] !== ''): ?><div><dt><?= e(tr('since')) ?></dt><dd><?= e($p['year_from']) ?></dd></div><?php endif; ?>
          <div><dt><?= e(tr('category')) ?></dt><dd><?= e(tr('cat_' . $p['category'])) ?></dd></div>
          <?php if ($meta !== ''): ?><div><dt>Info</dt><dd><?= e($meta) ?></dd></div><?php endif; ?>
        </dl>
      </div>
      <div class="project-visual" data-tilt data-tilt-max="6">
        <?php if ($img): ?><img src="<?= e($img) ?>" alt="<?= e($p['title']) ?>" width="929" height="985">
        <?php else: ?><span class="cover-gen cover-lg" style="--ang:<?= 110 + $hue * 2 ?>deg;--shift:<?= $hue ?>"><?= icon($p['badge_icon'] ?: 'globe', 'cover-icon') ?><span class="cover-title"><?= e($p['title']) ?></span></span><?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php if ($desc !== '' || $tech): ?>
<section class="sec sec-tight">
  <div class="wrap wrap-narrow">
    <?php if ($desc !== ''): ?><div class="prose prose-lg" data-reveal><?= $desc ?></div><?php endif; ?>
    <?php if ($tech): ?>
    <div class="tech" data-reveal><h2 class="h-sm"><?= e(tr('tech')) ?></h2><ul class="chips"><?php foreach ($tech as $tc): ?><li class="chip"><?= e($tc) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>
<?php if ($others): ?>
<section class="sec sec-tight">
  <div class="wrap">
    <header class="sec-head" data-reveal><h2 class="sec-title"><?= e(tr('more_projects')) ?></h2></header>
    <div class="grid grid-3" data-stagger>
      <?php foreach ($others as $p) { include __DIR__ . '/partials/project_card.php'; } ?>
    </div>
  </div>
</section>
<?php endif; ?>
