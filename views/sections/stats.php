<?php $stats = Content::stats(); if (!$stats) return; ?>
<section class="strip" id="<?= e($sec['anchor']) ?>" data-section="stats">
  <div class="wrap">
    <ul class="stats" data-stagger>
      <?php foreach ($stats as $st):
          $dec = (int)$st['decimals'];
          $to = (float)$st['count_to'];
          $suffix = t($st, 'suffix');
          $shown = number_format($to, $dec, Lang::$code === 'en' ? '.' : ',', Lang::$code === 'en' ? ',' : '.');
      ?>
      <li class="stat" data-reveal>
        <span class="stat-num" data-count="<?= e((string)$to) ?>" data-decimals="<?= $dec ?>" data-prefix="<?= e($st['prefix']) ?>" data-suffix="<?= e($suffix) ?>"><?= e($st['prefix'] . $shown . $suffix) ?></span>
        <span class="stat-label"><?= e(t($st, 'label')) ?></span>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
