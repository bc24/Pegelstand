<?php
$all = Content::visible('cv_entries');
$edu = array_values(array_filter($all, static fn($r) => $r['kind'] === 'education'));
$exp = array_values(array_filter($all, static fn($r) => $r['kind'] === 'experience'));
$col = static function (string $title, string $ico, array $rows): void { ?>
  <div class="cv-col" data-reveal>
    <h3 class="cv-heading"><span class="icon-orb icon-orb-sm"><?= icon($ico) ?></span><?= e($title) ?></h3>
    <ol class="cv-list" data-stagger>
      <?php foreach ($rows as $r): ?>
      <li class="cv-item spot" data-reveal>
        <?php if ($r['period'] !== ''): ?><span class="cv-period"><?= e($r['period']) ?></span><?php endif; ?>
        <h4><?= e(t($r, 'title')) ?></h4>
        <p><?= e(t($r, 'text')) ?></p>
      </li>
      <?php endforeach; ?>
    </ol>
  </div>
<?php };
?>
<section class="sec sec-cv" id="<?= e($sec['anchor']) ?>" data-section="cv">
  <div class="wrap">
    <?= View::head($sec) ?>
    <div class="cv-grid">
      <?php $col(Lang::$code === 'en' ? 'Education & qualifications' : 'Ausbildung & Qualifikationen', 'graduation-cap', $edu); ?>
      <?php $col(Lang::$code === 'en' ? 'Work experience & projects' : 'Berufserfahrung & Projekte', 'briefcase', $exp); ?>
    </div>
  </div>
</section>
