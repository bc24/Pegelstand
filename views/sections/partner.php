<?php $steps = Content::visible('partner_steps'); ?>
<section class="sec sec-partner" id="<?= e($sec['anchor']) ?>" data-section="partner">
  <div class="wrap">
    <?= View::head($sec) ?>
    <div class="partner-card spot" data-reveal>
      <div class="partner-glow" aria-hidden="true"></div>
      <div class="partner-intro">
        <h3><?= e(s('partner_title')) ?></h3>
        <p><?= e(s('partner_text')) ?></p>
      </div>
      <?php if ($steps): ?>
      <ol class="steps" data-stagger>
        <?php foreach ($steps as $i => $st): ?>
        <li class="step" data-reveal>
          <span class="step-num"><?= $i + 1 ?></span>
          <h4><?= e(t($st, 'title')) ?></h4>
          <p><?= e(t($st, 'text')) ?></p>
        </li>
        <?php endforeach; ?>
      </ol>
      <?php endif; ?>
      <a class="btn btn-primary magnetic" href="<?= e(u('/') . '?subject=' . rawurlencode(s('partner_subject')) . '#kontakt') ?>" data-subject="<?= e(s('partner_subject')) ?>"><span><?= e(s('partner_cta')) ?></span><?= icon('arrow-right') ?></a>
    </div>
  </div>
</section>
