<?php $tools = Content::visible('tools'); if (!$tools) return; ?>
<section class="sec sec-setup" id="<?= e($sec['anchor']) ?>" data-section="setup">
  <div class="wrap">
    <?= View::head($sec) ?>
    <ul class="tools" data-stagger>
      <?php foreach ($tools as $tl): $tag = $tl['url'] ? 'a' : 'div'; ?>
      <li data-reveal>
        <<?= $tag ?> class="tool spot" <?= $tl['url'] ? 'href="' . e($tl['url']) . '" target="_blank" rel="noopener"' : '' ?>>
          <?= icon($tl['icon'] ?: 'code', 'tool-icon') ?>
          <span><?= e($tl['name']) ?></span>
        </<?= $tag ?>>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
