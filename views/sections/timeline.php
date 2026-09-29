<?php $items = Content::visible('timeline'); ?>
<section class="sec sec-timeline" id="<?= e($sec['anchor']) ?>" data-section="timeline">
  <div class="wrap">
    <?= View::head($sec) ?>
    <ol class="timeline" data-timeline>
      <span class="timeline-line" aria-hidden="true"><i data-timeline-fill></i></span>
      <?php foreach ($items as $i => $it): ?>
      <li class="tl-item <?= (int)$it['accent'] ? 'is-accent' : '' ?> <?= $i % 2 ? 'is-right' : 'is-left' ?>" data-reveal="<?= $i % 2 ? 'right' : 'left' ?>">
        <span class="tl-dot" aria-hidden="true"></span>
        <div class="tl-card spot">
          <span class="tl-year"><?= e($it['year']) ?></span>
          <h3><?= e(t($it, 'title')) ?></h3>
          <p><?= e(t($it, 'text')) ?></p>
        </div>
      </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
