<?php
/** @var string $q @var array $terms @var ?array $res @var bool $short @var bool $limited */
$total = $res ? count($res['projects']) + count($res['posts']) : 0;
?>
<section class="page-hero page-hero-sm">
  <div class="hero-bg" aria-hidden="true"><i class="blob blob-1"></i></div>
  <div class="wrap wrap-narrow">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= e(u('/')) ?>"><?= e(tr('home')) ?></a><?= icon('chevron-right') ?><span><?= e(tr('search_title')) ?></span></nav>
    <h1 class="page-title"><?= e(tr('search_title')) ?></h1>
    <form class="search-form" role="search" method="get" action="<?= e(u('/suche/')) ?>">
      <?= icon('search') ?>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(tr('search_placeholder')) ?>" maxlength="80" autocomplete="off" aria-label="<?= e(tr('search_title')) ?>"<?= $q === '' ? ' autofocus' : '' ?>>
      <button class="btn btn-primary" type="submit"><span><?= e(tr('search_go')) ?></span></button>
    </form>
  </div>
</section>
<section class="sec sec-tight">
  <div class="wrap wrap-narrow">
    <?php if ($limited): ?>
      <p class="notice"><?= icon('clock') ?><?= e(tr('err_rate')) ?></p>
    <?php elseif ($short): ?>
      <p class="notice"><?= icon('info') ?><?= e(tr('search_short')) ?></p>
    <?php elseif ($res === null): ?>
      <p class="muted"><?= e(tr('search_intro')) ?></p>
    <?php elseif ($total === 0): ?>
      <p class="notice"><?= icon('search') ?><?= e(tr('search_none', ['q' => $q])) ?></p>
    <?php else: ?>
      <p class="search-count" role="status"><?= e(tr('search_results', ['n' => $total, 'q' => $q])) ?></p>
      <?php if ($res['projects']): ?>
      <h2 class="h-sm search-h"><?= icon('folders') ?><?= e(tr('search_projects')) ?> <small><?= count($res['projects']) ?></small></h2>
      <ol class="results">
        <?php foreach ($res['projects'] as $p): $text = t($p, 'description') !== '' ? t($p, 'description') : t($p, 'tagline'); ?>
        <li><a href="<?= e(u('/projekt/' . $p['slug'] . '/')) ?>">
          <span class="res-ico"><?= icon($p['badge_icon'] ?: 'globe') ?></span>
          <span class="res-main">
            <strong><?= Search::mark($p['title'], $terms) ?></strong>
            <small><?= e(t($p, 'badge') ?: tr('cat_' . $p['category'])) ?></small>
            <span class="res-text"><?= Search::mark(Search::snippet(t($p, 'tagline') . ' ' . $text, $terms), $terms) ?></span>
          </span>
        </a></li>
        <?php endforeach; ?>
      </ol>
      <?php endif; ?>
      <?php if ($res['posts']): ?>
      <h2 class="h-sm search-h"><?= icon('newspaper') ?><?= e(tr('search_posts')) ?> <small><?= count($res['posts']) ?></small></h2>
      <ol class="results">
        <?php foreach ($res['posts'] as $post): $ex = t($post, 'excerpt'); ?>
        <li><a href="<?= e(u('/blog/' . $post['slug'] . '/')) ?>">
          <span class="res-ico"><?= icon($post['icon'] ?: 'newspaper') ?></span>
          <span class="res-main">
            <strong><?= Search::mark(t($post, 'title'), $terms) ?></strong>
            <small><?= e(format_date($post['published_at'])) ?><?= t($post, 'category') !== '' ? ' · ' . e(t($post, 'category')) : '' ?></small>
            <span class="res-text"><?= Search::mark(Search::snippet(t($post, 'content') !== '' ? t($post, 'content') : $ex, $terms), $terms) ?></span>
          </span>
        </a></li>
        <?php endforeach; ?>
      </ol>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
