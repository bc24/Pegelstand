<?php
/** @var array $p */
$img = media_url($p['image']);
$detail = u('/projekt/' . $p['slug'] . '/');
$meta = t($p, 'meta');
$badge = t($p, 'badge');
$hue = crc32($p['slug']) % 90;
?>
<article class="pcard spot" data-cat="<?= e($p['category']) ?>" data-tilt data-tilt-max="4" data-reveal>
  <a class="pcard-media" href="<?= e($detail) ?>" tabindex="-1" aria-hidden="true">
    <?php if ($img): ?>
    <img src="<?= e($img) ?>" alt="" width="640" height="440" loading="lazy">
    <?php else: ?>
    <span class="cover-gen" style="--ang:<?= 110 + $hue * 2 ?>deg;--shift:<?= $hue ?>">
      <?= icon($p['badge_icon'] ?: 'globe', 'cover-icon') ?>
      <span class="cover-title"><?= e($p['title']) ?></span>
    </span>
    <?php endif; ?>
    <?php if ($p['status'] !== 'live'): ?><span class="pcard-status"><?= e(tr('status_' . $p['status'])) ?></span><?php endif; ?>
  </a>
  <div class="pcard-body">
    <?php if ($badge !== ''): ?><span class="badge badge-sm"><?= icon($p['badge_icon']) ?><?= e($badge) ?></span><?php endif; ?>
    <h3 class="pcard-title"><a href="<?= e($detail) ?>"><?= e($p['title']) ?></a></h3>
    <?php if ($meta !== ''): ?><p class="pcard-meta"><?= e($meta) ?></p><?php endif; ?>
    <p class="pcard-text"><?= e(t($p, 'tagline')) ?></p>
    <div class="pcard-foot">
      <a class="link-arrow" href="<?= e($detail) ?>"><?= e(tr('read_more')) ?><?= icon('arrow-right') ?></a>
      <?php if ($p['url'] !== ''): ?>
      <a class="pcard-ext" href="<?= e($p['url']) ?>" target="_blank" rel="noopener" aria-label="<?= e(tr('visit_project') . ': ' . $p['title']) ?>" title="<?= e(tr('visit')) ?>"><?= icon('arrow-up-right') ?></a>
      <?php endif; ?>
    </div>
  </div>
</article>
