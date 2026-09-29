<?php /** @var array $tables @var int $upBytes @var int $upFiles @var bool $zip */ ?>
<div class="page-head"><div><h2>Backup & Wiederherstellung</h2><p class="muted">Sichere regelmäßig Datenbank <em>und</em> Uploads. Die SQL-Datei enthält alle Inhalte, Einstellungen, Benutzer (Passwort-Hashes) und Nachrichten — bewahre sie sicher auf.</p></div></div>
<div class="two-col">
  <section class="card">
    <h3><?= icon('database') ?> Datenbank</h3>
    <p class="muted"><?= count($tables) ?> Tabellen · <?= e(Media::humanSize((int)array_sum(array_map(static fn($t) => (int)$t['s'], $tables)))) ?></p>
    <form method="post"><?= Csrf::field() ?><input type="hidden" name="do" value="dump"><button class="btn btn-primary" type="submit"><?= icon('download') ?><span>SQL-Backup herunterladen</span></button></form>
  </section>
  <section class="card">
    <h3><?= icon('image') ?> Uploads</h3>
    <p class="muted"><?= (int)$upFiles ?> Dateien · <?= e(Media::humanSize($upBytes)) ?></p>
    <?php if ($zip): ?><form method="post"><?= Csrf::field() ?><input type="hidden" name="do" value="zip"><button class="btn btn-primary" type="submit"><?= icon('download') ?><span>Uploads als ZIP</span></button></form>
    <?php else: ?><p class="muted">Die PHP-Erweiterung <code>zip</code> fehlt. Kopiere den Ordner <code>uploads/</code> per FTP/SSH.</p><?php endif; ?>
  </section>
</div>
<section class="card">
  <h3><?= icon('refresh-cw') ?> Wiederherstellen</h3>
  <p class="muted">Spielt eine zuvor hier heruntergeladene SQL-Datei ein und <strong>überschreibt alle aktuellen Inhalte</strong>. Erstelle vorher ein aktuelles Backup.</p>
  <form method="post" enctype="multipart/form-data" class="inline-form" data-confirm="Wirklich alle aktuellen Daten durch das Backup ersetzen?">
    <?= Csrf::field() ?><input type="hidden" name="do" value="restore">
    <div class="fw"><label class="lbl" for="sql">SQL-Datei</label><input class="inp" id="sql" type="file" name="sql" accept=".sql,text/plain" required></div>
    <div class="fw"><label class="lbl" for="confirm">Zur Bestätigung „WIEDERHERSTELLEN“ eintippen</label><input class="inp" id="confirm" name="confirm" autocomplete="off" required></div>
    <button class="btn btn-danger" type="submit">Wiederherstellen</button>
  </form>
</section>
<section class="card card-flush">
  <header class="card-head pad"><h2>Tabellen</h2></header>
  <div class="table-wrap"><table class="tbl"><thead><tr><th>Tabelle</th><th>Zeilen (ca.)</th><th>Größe</th></tr></thead><tbody>
    <?php foreach ($tables as $t): ?><tr><td><code><?= e($t['t']) ?></code></td><td><?= (int)$t['r'] ?></td><td><?= e(Media::humanSize((int)$t['s'])) ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>
