<?php
$genres = Content::visible('music_genres');
$tracks = Content::visible('tracks');
?>
<section class="sec sec-music" id="<?= e($sec['anchor']) ?>" data-section="music">
  <div class="wrap">
    <div class="music-grid">
      <div class="music-copy">
        <?= View::head($sec) ?>
        <p data-reveal><?= e(s('music_text')) ?></p>
        <?php if ($genres): ?>
        <ul class="chips" data-stagger>
          <?php foreach ($genres as $g): ?><li class="chip" data-reveal><?= icon($g['icon']) ?><?= e($g['name']) ?></li><?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <?php if ($tracks): ?>
        <ul class="tracks" data-stagger>
          <?php foreach ($tracks as $tr): ?>
          <li class="track spot" data-reveal>
            <?php if ($tr['image']): ?><img src="<?= e(media_url($tr['image'])) ?>" alt="" width="56" height="56" loading="lazy"><?php else: ?><span class="track-ph"><?= icon('music') ?></span><?php endif; ?>
            <div><strong><?= e($tr['title']) ?></strong><small><?= e(trim($tr['genre'] . ' ' . ($tr['platform'] ? '· ' . $tr['platform'] : ''), ' ·')) ?></small></div>
            <?php if ($tr['url']): ?><a class="track-play" href="<?= e($tr['url']) ?>" target="_blank" rel="noopener" aria-label="<?= e(tr('tracks_listen') . ': ' . $tr['title']) ?>"><?= icon('play') ?></a><?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <p class="soon" data-reveal><i class="pulse-dot"></i><span><?= e(s('music_soon')) ?></span></p>
        <?php endif; ?>
      </div>
      <div class="music-visual" data-reveal="right" aria-hidden="true">
        <div class="vinyl"><i class="vinyl-label"><?= e(setting('music_name', 'DJ-Frankus')) ?></i></div>
        <div class="eq"><?php for ($i = 0; $i < 24; $i++): ?><i style="--d:<?= ($i * 137) % 900 ?>ms;--h:<?= 30 + (($i * 53) % 70) ?>%"></i><?php endfor; ?></div>
        <span class="soon-badge"><?= icon('audio-lines') ?><?= e(s('music_soon_badge')) ?></span>
      </div>
    </div>
  </div>
</section>
