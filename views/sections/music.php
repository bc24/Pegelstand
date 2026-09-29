<?php
$genres = Content::visible('music_genres');
$tracks = Content::visible('tracks', 'sort ASC, id ASC');
$ttUser = trim(setting('tiktok_user', 'dj.frankus'));
$ttUrl = $ttUser !== '' ? 'https://www.tiktok.com/@' . $ttUser : '';
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
        <div class="btn-row" data-reveal>
          <a class="btn btn-primary magnetic" href="<?= e(u('/booking/')) ?>"><?= icon('calendar-heart') ?><span><?= e(tr('book_cta')) ?></span></a>
          <?php if ($tracks && $ttUrl): ?><a class="btn btn-ghost magnetic" href="<?= e($ttUrl) ?>" target="_blank" rel="noopener me"><?= icon('brand-tiktok') ?><span><?= e(s('music_follow')) ?></span></a><?php endif; ?>
        </div>
        <?php if (!$tracks): ?>
        <p class="soon" data-reveal><i class="pulse-dot"></i><span><?= e(s('music_soon')) ?></span></p>
        <?php endif; ?>
      </div>
      <div class="music-visual" data-reveal="right" aria-hidden="true">
        <div class="vinyl"><i class="vinyl-label"><?= e(setting('music_name', 'DJ-Frankus')) ?></i></div>
        <div class="eq"><?php for ($i = 0; $i < 24; $i++): ?><i style="--d:<?= ($i * 137) % 900 ?>ms;--h:<?= 30 + (($i * 53) % 70) ?>%"></i><?php endfor; ?></div>
        <?php if (!$tracks): ?><span class="soon-badge"><?= icon('audio-lines') ?><?= e(s('music_soon_badge')) ?></span><?php endif; ?>
      </div>
    </div>

    <?php if ($tracks): ?>
    <div class="songs" data-songs data-reveal>
      <header class="songs-head">
        <h3><?= icon('brand-tiktok') ?><?= e(s('music_tracks_title')) ?></h3>
        <div class="songs-nav">
          <button type="button" class="icon-btn" data-rail-prev aria-label="<?= e(tr('songs_prev')) ?>"><?= icon('arrow-left') ?></button>
          <button type="button" class="icon-btn" data-rail-next aria-label="<?= e(tr('songs_next')) ?>"><?= icon('arrow-right') ?></button>
        </div>
      </header>
      <ul class="song-rail" data-rail tabindex="0" aria-label="<?= e(s('music_tracks_title')) ?>">
        <?php foreach ($tracks as $tk):
            $isTt = $tk['video_id'] !== '';
            $img = media_url($tk['image']);
            $when = $tk['published_at'] ? format_date($tk['published_at'], null, true) : '';
        ?>
        <li class="song">
          <<?= $isTt ? 'button type="button" data-tiktok="' . e($tk['video_id']) . '" data-title="' . e($tk['title']) . '" data-url="' . e($tk['url']) . '"' : 'a href="' . e($tk['url']) . '" target="_blank" rel="noopener"' ?> class="song-card" aria-label="<?= e(($isTt ? tr('song_play') : tr('song_open')) . ': ' . $tk['title']) ?>">
            <span class="song-cover">
              <?php if ($img): ?><img src="<?= e($img) ?>" alt="" width="360" height="360" loading="lazy"><?php else: ?><span class="song-ph"><?= icon('music') ?></span><?php endif; ?>
              <span class="song-play"><?= icon($isTt ? 'play' : 'arrow-up-right') ?></span>
              <?php if ($tk['platform'] !== ''): ?><span class="song-platform"><?= $isTt ? icon('brand-tiktok') : '' ?><?= e($tk['platform']) ?></span><?php endif; ?>
              <?php if ((int)$tk['plays'] > 0): ?><span class="song-plays"><?= icon('play') ?><?= e(number_format((int)$tk['plays'], 0, ',', Lang::$code === 'en' ? ',' : '.')) ?></span><?php endif; ?>
            </span>
            <span class="song-info">
              <strong><?= e($tk['title']) ?></strong>
              <small><?= e(trim($when . ($when && $tk['genre'] ? ' · ' : '') . ($tk['genre'] ? '#' . str_replace(', ', ' #', $tk['genre']) : ''))) ?></small>
            </span>
          </<?= $isTt ? 'button' : 'a' ?>>
        </li>
        <?php endforeach; ?>
      </ul>
      <p class="songs-note"><?= icon('shield-check') ?><?= e(tr('song_privacy')) ?> <a href="<?= e(u('/datenschutz/')) ?>"><?= e(tr('privacy')) ?></a></p>
    </div>

    <div class="player" id="player" hidden role="dialog" aria-modal="true" aria-label="<?= e(tr('song_play')) ?>">
      <div class="player-box">
        <button type="button" class="player-x icon-btn" data-player-close aria-label="<?= e(tr('song_close')) ?>"><?= icon('x') ?></button>
        <div class="player-frame" id="player-frame"></div>
        <div class="player-meta"><strong id="player-title"></strong><a class="link-arrow" id="player-link" href="#" target="_blank" rel="noopener"><?= e(tr('song_open')) ?><?= icon('arrow-up-right') ?></a></div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>
