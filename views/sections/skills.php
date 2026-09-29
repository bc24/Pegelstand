<?php $groups = Content::skillGroups(); ?>
<section class="sec sec-skills" id="<?= e($sec['anchor']) ?>" data-section="skills">
  <div class="wrap">
    <?= View::head($sec) ?>
    <div class="legend" data-reveal aria-label="<?= e(tr('skill_legend')) ?>">
      <span class="legend-title"><?= e(tr('skill_legend')) ?>:</span>
      <?php foreach ([3, 2, 1] as $lv): ?>
      <span class="legend-item"><span class="meter" data-level="<?= $lv ?>"><i></i><i></i><i></i></span><?= e(tr('lvl_' . $lv)) ?></span>
      <?php endforeach; ?>
    </div>
    <div class="grid grid-3 skill-groups" data-stagger>
      <?php foreach ($groups as $g): ?>
      <article class="card skill-card spot" data-reveal>
        <h3 class="skill-title"><?= icon($g['icon']) ?><?= e(t($g, 'title')) ?></h3>
        <ul class="skill-list">
          <?php foreach ($g['skills'] as $sk): ?>
          <li class="skill" title="<?= e(tr('lvl_' . (int)$sk['level'])) ?>">
            <?= icon($sk['icon'] ?: 'code', 'skill-icon') ?>
            <span class="skill-name"><?= e($sk['name']) ?></span>
            <span class="meter" data-level="<?= (int)$sk['level'] ?>" role="img" aria-label="<?= e(tr('lvl_' . (int)$sk['level'])) ?>"><i></i><i></i><i></i></span>
          </li>
          <?php endforeach; ?>
        </ul>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
