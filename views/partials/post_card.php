<?php
/** @var array $post */
$img = media_url($post['image']);
$url = u('/blog/' . $post['slug'] . '/');
$hue = crc32($post['slug']) % 90;
?>
<article class="postcard spot" data-reveal data-tilt data-tilt-max="4">
  <a class="postcard-media" href="<?= e($url) ?>" tabindex="-1" aria-hidden="true">
    <?php if ($img): ?><img src="<?= e($img) ?>" alt="" width="640" height="360" loading="lazy">
    <?php else: ?><span class="cover-gen" style="--ang:<?= 110 + $hue * 2 ?>deg;--shift:<?= $hue ?>"><?= icon($post['icon'] ?: 'newspaper', 'cover-icon') ?></span><?php endif; ?>
  </a>
  <div class="postcard-body">
    <div class="postcard-meta">
      <?php if (t($post, 'category') !== ''): ?><span class="badge badge-sm"><?= e(t($post, 'category')) ?></span><?php endif; ?>
      <time datetime="<?= e(substr((string)$post['published_at'], 0, 10)) ?>"><?= e(format_date($post['published_at'])) ?></time>
    </div>
    <h3><a href="<?= e($url) ?>"><?= e(t($post, 'title')) ?></a></h3>
    <p><?= e(excerpt(t($post, 'excerpt') ?: t($post, 'content'), 150)) ?></p>
    <a class="link-arrow" href="<?= e($url) ?>"><?= e(tr('read_more')) ?><?= icon('arrow-right') ?></a>
  </div>
</article>
