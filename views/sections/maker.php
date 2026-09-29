<?php $items = Content::visible('maker_projects'); if (!$items) return; ?>
<section class="sec sec-maker" id="<?= e($sec['anchor']) ?>" data-section="maker">
  <div class="wrap">
    <?= View::head($sec) ?>
    <div class="grid grid-3 maker-grid" data-stagger>
      <?php foreach ($items as $it): $done = $it['status'] === 'done'; ?>
      <article class="maker spot <?= $done ? 'is-done' : 'is-planned' ?>" data-reveal>
        <span class="icon-orb icon-orb-sm"><?= icon($it['icon']) ?></span>
        <h3><?= e(t($it, 'title')) ?></h3>
        <span class="status <?= $done ? 'status-done' : 'status-plan' ?>"><?= $done ? icon('check') : '<i class="pulse-dot"></i>' ?><?= e(tr($done ? 'done' : 'planned')) ?></span>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
