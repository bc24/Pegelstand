<?php
/** @var array $rows @var int $page @var int $pages @var int $total @var string $q */
?>
<div class="page-head">
  <div><h2>Medien <span class="count"><?= (int)$total ?></span></h2><p class="muted">Bilder und PDFs für Projekte, Blog und Seiten. Bilder werden verkleinert und von Metadaten (z. B. GPS) bereinigt.</p></div>
  <form class="search-form" method="get"><input type="hidden" name="p" value="media"><label class="search"><?= icon('search') ?><input type="search" name="q" value="<?= e($q) ?>" placeholder="Dateien suchen …"></label></form>
</div>

<form class="dropzone" id="dropzone" method="post" enctype="multipart/form-data" action="<?= e(Admin::url(['p' => 'media'])) ?>" data-dropzone>
  <?= Csrf::field() ?><input type="hidden" name="do" value="upload">
  <?= icon('upload') ?>
  <p><strong>Dateien hierher ziehen</strong> oder <label class="link-btn">durchsuchen<input type="file" name="files[]" multiple accept="image/jpeg,image/png,image/webp,image/gif,application/pdf" hidden></label></p>
  <small>JPG, PNG, WebP, GIF, PDF · max. <?= e(ini_get('upload_max_filesize')) ?> pro Datei</small>
  <div class="upload-progress" hidden></div>
</form>

<?php if (!$rows): ?><div class="card empty"><?= icon('image') ?><p>Noch keine Dateien.</p></div><?php endif; ?>
<div class="media-grid" id="media-grid">
<?php foreach ($rows as $m): $thumb = media_url($m['thumb'] ?: $m['path']); $isImg = str_starts_with($m['mime'], 'image/'); ?>
  <button type="button" class="media-item" data-media='<?= e(json_encode(['id' => (int)$m['id'], 'url' => media_url($m['path']), 'path' => $m['path'], 'name' => $m['original_name'], 'alt' => $m['alt'], 'size' => Media::humanSize((int)$m['size']), 'w' => (int)$m['width'], 'h' => (int)$m['height'], 'mime' => $m['mime'], 'img' => $isImg], JSON_UNESCAPED_UNICODE)) ?>'>
    <?php if ($isImg): ?><img src="<?= e($thumb) ?>" alt="<?= e($m['alt']) ?>" loading="lazy"><?php else: ?><span class="file-ico"><?= icon('file-text') ?><b>PDF</b></span><?php endif; ?>
    <span class="media-name"><?= e($m['original_name']) ?></span>
  </button>
<?php endforeach; ?>
</div>
<?php if ($pages > 1): ?><nav class="pager"><?php for ($i = 1; $i <= $pages; $i++): ?><a class="<?= $i === $page ? 'is-active' : '' ?>" href="<?= e(Admin::url(['p' => 'media', 'page' => $i, 'q' => $q])) ?>"><?= $i ?></a><?php endfor; ?></nav><?php endif; ?>

<div class="modal" id="media-detail" hidden>
  <div class="modal-box modal-lg">
    <button class="modal-x icon-btn" type="button" data-close aria-label="Schließen">×</button>
    <div class="media-detail">
      <div class="md-preview"></div>
      <div class="md-info">
        <h3 class="md-name"></h3>
        <p class="muted md-meta"></p>
        <div class="fw"><label class="lbl">Adresse</label><div class="copy-row"><input class="inp md-url" readonly><button class="btn btn-soft" type="button" data-copy>Kopieren</button></div></div>
        <div class="fw"><label class="lbl" for="md-alt">Alternativtext (Barrierefreiheit &amp; SEO)</label><input class="inp" id="md-alt" maxlength="255"></div>
        <div class="btn-row"><button class="btn btn-primary" type="button" data-save-alt>Speichern</button>
          <button class="btn btn-danger" type="button" data-delete-media>Löschen</button></div>
      </div>
    </div>
  </div>
</div>
