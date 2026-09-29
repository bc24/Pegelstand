<?php
/** @var array $e @var array $rows @var array $filterOpts @var string $filterVal */
$key = $e['key'];
$cols = $e['columns'];
$toggles = array_filter([$e['toggle'] ?? null, $e['toggle2'] ?? null]);
$toggleLabels = ['visible' => 'Sichtbar', 'featured' => 'Startseite', 'in_hero' => 'Hero'];
$sortable = !empty($e['sortable']);
?>
<div class="page-head">
  <div>
    <h2><?= e($e['title']) ?> <span class="count"><?= count($rows) ?></span></h2>
    <?php if (!empty($e['help'])): ?><p class="muted"><?= e($e['help']) ?></p><?php endif; ?>
  </div>
  <div class="head-actions">
    <?php foreach ($e['buttons'] ?? [] as [$bd, $bl, $bi]): ?><form method="post" class="inline"><?= Csrf::field() ?><input type="hidden" name="do" value="<?= e($bd) ?>"><button class="btn btn-soft" type="submit"><?= icon($bi) ?><span><?= e($bl) ?></span></button></form><?php endforeach; ?>
    <?php foreach ($e['links'] ?? [] as [$lk, $ll, $li]): ?><a class="btn btn-soft" href="<?= e(Admin::url(['p' => $lk])) ?>"><?= icon($li) ?><span><?= e($ll) ?></span></a><?php endforeach; ?>
    <?php if ($e['can_add']): ?><a class="btn btn-primary" href="<?= e(Admin::url(['p' => $key, 'a' => 'new'] + ($filterVal !== '' ? ['f' => $filterVal] : []))) ?>"><?= icon('plus') ?><span><?= e($e['singular']) ?> hinzufügen</span></a><?php endif; ?>
  </div>
</div>

<div class="card card-flush">
  <div class="toolbar">
    <label class="search"><?= icon('search') ?><input type="search" placeholder="Suchen …" data-table-search aria-label="Suchen"></label>
    <?php if ($filterOpts): ?>
    <form method="get" class="filter-form"><input type="hidden" name="p" value="<?= e($key) ?>">
      <select class="inp" name="f" data-autosubmit aria-label="<?= e($e['filter_label'] ?? 'Filter') ?>">
        <option value="">Alle · <?= e($e['filter_label'] ?? 'Filter') ?></option>
        <?php foreach ($filterOpts as $k => $l): ?><option value="<?= e((string)$k) ?>" <?= (string)$k === $filterVal ? 'selected' : '' ?>><?= e((string)$l) ?></option><?php endforeach; ?>
      </select>
    </form>
    <?php endif; ?>
    <?php if ($sortable): ?><span class="hint"><?= icon('grip-vertical') ?> Zeilen per Drag & Drop sortieren</span><?php endif; ?>
  </div>

  <?php if (!$rows): ?>
  <div class="empty"><?= icon($e['icon']) ?><p>Noch keine Einträge.</p><?php if ($e['can_add']): ?><a class="btn btn-primary" href="<?= e(Admin::url(['p' => $key, 'a' => 'new'])) ?>"><?= icon('plus') ?><span><?= e($e['singular']) ?> hinzufügen</span></a><?php endif; ?></div>
  <?php else: ?>
  <div class="table-wrap">
  <table class="tbl" <?= $sortable ? 'data-sortable' : '' ?> data-entity="<?= e($key) ?>">
    <thead><tr>
      <?php if ($sortable): ?><th class="c-handle"></th><?php endif; ?>
      <?php foreach ($cols as $c => $label): ?><th><?= e($label) ?></th><?php endforeach; ?>
      <?php foreach ($toggles as $tg): ?><th class="c-toggle"><?= e($toggleLabels[$tg] ?? $tg) ?></th><?php endforeach; ?>
      <th class="c-actions"></th>
    </tr></thead>
    <tbody>
    <?php foreach ($rows as $r): $editUrl = Admin::url(['p' => $key, 'a' => 'edit', 'id' => $r['id']]); ?>
      <tr data-id="<?= (int)$r['id'] ?>">
        <?php if ($sortable): ?><td class="c-handle"><span class="grip" draggable="true" title="Ziehen zum Sortieren"><?= icon('grip-vertical') ?></span></td><?php endif; ?>
        <?php $first = true; foreach ($cols as $c => $label): ?>
        <td<?= $first ? ' class="c-main"' : '' ?>><?php if ($first): ?><a href="<?= e($editUrl) ?>"><?= Admin::cell($e, $c, $r) ?></a><?php else: ?><?= Admin::cell($e, $c, $r) ?><?php endif; ?></td>
        <?php $first = false; endforeach; ?>
        <?php foreach ($toggles as $tg): ?>
        <td class="c-toggle"><label class="switch switch-sm"><input type="checkbox" data-toggle="<?= e($tg) ?>" data-id="<?= (int)$r['id'] ?>" <?= (int)$r[$tg] === 1 ? 'checked' : '' ?> aria-label="<?= e($toggleLabels[$tg] ?? $tg) ?>"><span class="switch-ui"></span></label></td>
        <?php endforeach; ?>
        <td class="c-actions">
          <a class="icon-btn" href="<?= e($editUrl) ?>" title="Bearbeiten" aria-label="Bearbeiten"><?= icon('pencil') ?></a>
          <?php if (!empty($e['view_url'])): $vu = str_replace('{slug}', (string)($r['slug'] ?? ''), $e['view_url']); if ($key === 'posts' && (($r['status'] ?? '') !== 'published' || strtotime((string)($r['published_at'] ?? '')) > time())) { $vu .= '?preview=1'; } ?><a class="icon-btn" href="<?= e(rtrim((string)cfg('base_path', ''), '/') . $vu) ?>" target="_blank" rel="noopener" title="Ansehen" aria-label="Ansehen"><?= icon('eye') ?></a><?php endif; ?>
          <?php if ($e['can_add']): ?>
          <form method="post" class="inline"><?= Csrf::field() ?><input type="hidden" name="do" value="duplicate"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="icon-btn" type="submit" title="Duplizieren" aria-label="Duplizieren"><?= icon('copy') ?></button></form>
          <?php endif; ?>
          <?php if ($e['can_delete']): ?>
          <form method="post" class="inline" data-confirm="<?= e($e['singular'] . ' wirklich löschen?') ?>"><?= Csrf::field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="icon-btn danger" type="submit" title="Löschen" aria-label="Löschen"><?= icon('trash-2') ?></button></form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>
