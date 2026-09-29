<?php /** @var array $tables @var int $upBytes @var int $upFiles @var bool $zip @var array $saved @var string $backupDir @var string $root */ ?>
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
  <h3><?= icon('clock') ?> Automatische Server-Backups</h3>
  <p class="muted">Ein Cron-Job sichert Datenbank (<code>.sql.gz</code>) und geänderte Uploads (<code>.zip</code>) täglich nach <code>storage/backups/</code>; es bleiben die letzten 14 Sicherungen je Art. Kopiere die Dateien zusätzlich auf einen anderen Speicher — ein Backup auf demselben Server schützt nicht vor einem Serverausfall.</p>
  <p class="muted">Cron-Eintrag (täglich 03:17 Uhr, als Webserver-Benutzer):</p>
  <pre class="code-block">17 3 * * *  cd <?= e($root) ?> &amp;&amp; sudo -u www-data php bin/backup.php &gt;/dev/null 2&gt;&amp;1</pre>
  <form method="post" class="inline"><?= Csrf::field() ?><input type="hidden" name="do" value="server"><button class="btn btn-primary" type="submit"><?= icon('hard-drive') ?><span>Jetzt auf dem Server sichern</span></button></form>
  <?php if ($saved): ?>
  <div class="table-wrap"><table class="tbl"><thead><tr><th>Datei</th><th>Art</th><th>Größe</th><th>Erstellt</th><th class="c-actions"></th></tr></thead><tbody>
    <?php foreach ($saved as $f): ?>
    <tr>
      <td><code><?= e($f['name']) ?></code></td>
      <td><?= $f['type'] === 'db' ? 'Datenbank' : 'Uploads' ?></td>
      <td><?= e(Media::humanSize($f['size'])) ?></td>
      <td><?= e(date('d.m.Y H:i', $f['time'])) ?></td>
      <td class="c-actions">
        <a class="icon-btn" href="<?= e(Admin::url(['p' => 'backup', 'dl' => $f['name']])) ?>" title="Herunterladen" aria-label="Herunterladen"><?= icon('download') ?></a>
        <form method="post" class="inline" data-confirm="Sicherung wirklich löschen?"><?= Csrf::field() ?><input type="hidden" name="do" value="delete_server"><input type="hidden" name="name" value="<?= e($f['name']) ?>"><button class="icon-btn danger" type="submit" title="Löschen" aria-label="Löschen"><?= icon('trash-2') ?></button></form>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody></table></div>
  <?php else: ?><p class="muted">Noch keine Server-Sicherung vorhanden.</p><?php endif; ?>
</section>
<section class="card">
  <h3><?= icon('refresh-cw') ?> Wiederherstellen</h3>
  <p class="muted">Spielt eine zuvor hier heruntergeladene SQL-Datei ein und <strong>überschreibt alle aktuellen Inhalte</strong>. Erstelle vorher ein aktuelles Backup.</p>
  <form method="post" enctype="multipart/form-data" class="inline-form" data-confirm="Wirklich alle aktuellen Daten durch das Backup ersetzen?">
    <?= Csrf::field() ?><input type="hidden" name="do" value="restore">
    <div class="fw"><label class="lbl" for="sql">SQL-Datei (.sql oder .sql.gz)</label><input class="inp" id="sql" type="file" name="sql" accept=".sql,.gz,text/plain,application/gzip" required></div>
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
