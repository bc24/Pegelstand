<?php
$badges = Content::visible('about_badges');
$img = media_url(setting('about_image'));
$heading = s('about_heading') ?: t($sec, 'title');
?>
<section class="sec sec-about" id="<?= e($sec['anchor']) ?>" data-section="about">
  <div class="wrap">
    <div class="about-grid">
      <div class="about-photo" data-reveal="left">
        <div class="photo-card" data-tilt data-tilt-max="6">
          <span class="photo-frame" aria-hidden="true"></span>
          <?php if ($img): ?><img src="<?= e($img) ?>" alt="<?= e(setting('hero_name', 'Frank Panzer')) ?>" width="520" height="530" loading="lazy"><?php endif; ?>
          <span class="photo-tag"><?= icon('map-pin') ?><?= e(setting('location')) ?></span>
        </div>
      </div>
      <div class="about-text">
        <header class="sec-head" data-reveal>
          <span class="eyebrow"><i class="eyebrow-dot"></i><?= e(t($sec, 'label')) ?></span>
          <h2 class="sec-title" data-split-words><?= e($heading) ?></h2>
        </header>
        <p data-reveal><?= e(s('about_text1')) ?></p>
        <p data-reveal><?= e(s('about_text2')) ?></p>
        <?php if ($badges): ?>
        <ul class="badges" data-stagger>
          <?php foreach ($badges as $b): ?>
          <li class="badge" data-reveal><?= icon($b['icon']) ?><span><?= e(t($b, 'text')) ?></span></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <div class="about-cta" data-reveal>
          <a class="btn btn-primary magnetic" href="<?= e(u('/') . '#kontakt') ?>"><span><?= e(tr('send')) ?></span><?= icon('arrow-right') ?></a>
        </div>
      </div>
    </div>
  </div>
</section>
