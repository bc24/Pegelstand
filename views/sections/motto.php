<?php $motto = s('motto_text'); if ($motto === '') return; ?>
<section class="motto" id="<?= e($sec['anchor']) ?>" data-section="motto">
  <div class="motto-bg" aria-hidden="true"></div>
  <div class="wrap">
    <?= icon('quote', 'motto-mark') ?>
    <blockquote class="motto-text" data-words-scroll><?= e($motto) ?></blockquote>
    <?php if (setting('motto_author') !== ''): ?><cite class="motto-author" data-reveal><?= e(setting('motto_author')) ?></cite><?php endif; ?>
  </div>
</section>
