<div class="card empty">
  <?= icon('triangle-alert') ?>
  <h2><?= e($message ?? 'Fehler') ?></h2>
  <p><a class="btn btn-soft" href="<?= e(Admin::url()) ?>">Zum Dashboard</a></p>
</div>
