<?php
$words = lines(s('hero_words'));
$chips = array_slice(lines(setting('hero_chips')), 0, 4);
$marquee = lines(setting('marquee_words'));
$heroSocials = Content::socials(true);
$name = setting('hero_name', 'Frank Panzer');
$img = media_url(setting('hero_image'));
$cta1 = s('hero_cta1_text');
$cta2 = s('hero_cta2_text');
$link = static fn(string $l): string => str_starts_with($l, '#') ? u('/') . $l : (str_starts_with($l, '/') ? u($l) : $l);
?>
<section class="hero" id="top" data-section="hero">
  <div class="hero-bg" aria-hidden="true">
    <i class="blob blob-1"></i><i class="blob blob-2"></i><i class="blob blob-3"></i>
    <div class="hero-grid"></div>
  </div>
  <canvas class="particles" id="particles" aria-hidden="true"></canvas>

  <div class="wrap hero-inner">
    <div class="hero-copy">
      <?php if (s('hero_eyebrow') !== ''): ?>
      <p class="chip hero-eyebrow" data-hero-step><?= icon('map-pin') ?><span><?= e(s('hero_eyebrow')) ?></span></p>
      <?php endif; ?>
      <h1 class="hero-name" aria-label="<?= e($name) ?>"><span data-split-chars><?= e($name) ?></span></h1>
      <p class="hero-role" data-hero-step>
        <?php if (s('hero_lead') !== ''): ?><span class="hero-role-lead"><?= e(s('hero_lead')) ?></span><?php endif; ?>
        <span class="typewriter" id="typewriter" data-words="<?= e(View::json($words)) ?>"><?= e($words[0] ?? '') ?></span><i class="caret" aria-hidden="true"></i>
      </p>
      <p class="hero-desc" data-hero-step><?= e(s('hero_desc')) ?></p>
      <div class="hero-cta" data-hero-step>
        <?php if ($cta1 !== ''): ?><a class="btn btn-primary magnetic" href="<?= e($link(setting('hero_cta1_link'))) ?>"><span><?= e($cta1) ?></span><?= icon('arrow-down') ?></a><?php endif; ?>
        <?php if ($cta2 !== ''): ?><a class="btn btn-ghost magnetic" href="<?= e($link(setting('hero_cta2_link'))) ?>"><span><?= e($cta2) ?></span></a><?php endif; ?>
      </div>
      <?php if ($heroSocials): ?>
      <ul class="social-row hero-social" data-hero-step aria-label="<?= e(tr('social')) ?>">
        <?php foreach ($heroSocials as $so): ?>
        <li><a class="social-btn magnetic" href="<?= e($so['url']) ?>" target="_blank" rel="noopener me" aria-label="<?= e($so['label']) ?>" title="<?= e($so['label']) ?>"><?= icon($so['icon']) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
      <?php if (sb('hero_available')): ?>
      <p class="available" data-hero-step><i class="pulse-dot"></i><?= e(s('hero_available_text')) ?></p>
      <?php endif; ?>
    </div>

    <div class="hero-visual" data-hero-step>
      <div class="portrait" data-tilt data-tilt-max="8">
        <div class="portrait-ring" aria-hidden="true"></div>
        <div class="portrait-frame">
          <?php if ($img): ?><img src="<?= e($img) ?>" alt="<?= e($name) ?>" width="520" height="530" fetchpriority="high"><?php endif; ?>
          <div class="portrait-shine" aria-hidden="true"></div>
        </div>
        <?php foreach ($chips as $i => $chip): ?>
        <span class="float-chip fc-<?= $i + 1 ?>" aria-hidden="true"><?= e($chip) ?></span>
        <?php endforeach; ?>
        <?php if ($words): ?>
        <svg class="seal" viewBox="0 0 120 120" aria-hidden="true">
          <circle class="seal-bg" cx="60" cy="60" r="58"/><defs><path id="seal-path" d="M60,60 m-44,0 a44,44 0 1,1 88,0 a44,44 0 1,1 -88,0"/></defs>
          <text><textPath href="#seal-path" startOffset="0" textLength="272" lengthAdjust="spacing"><?= e(mb_strtoupper(implode(' • ', $words)) . ' • ') ?></textPath></text>
        </svg>
        <span class="seal-core" aria-hidden="true"><?= icon('tank') ?></span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <a class="scroll-hint" href="#zahlen" aria-label="<?= e(tr('scroll')) ?>"><span class="scroll-mouse"><i></i></span><span class="scroll-text"><?= e(tr('scroll')) ?></span></a>
</section>

<?php if ($marquee && sb('fx_marquee', true)): ?>
<div class="marquee" aria-hidden="true">
  <div class="marquee-track">
    <?php for ($r = 0; $r < 2; $r++): ?>
    <ul class="marquee-list">
      <?php foreach ($marquee as $w): ?><li><?= e($w) ?><i></i></li><?php endforeach; ?>
    </ul>
    <?php endfor; ?>
  </div>
</div>
<?php endif; ?>
