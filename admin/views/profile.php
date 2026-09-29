<?php /** @var array $me @var array $errors @var string $pending @var string $otpUri */
$fld = static fn(string $n, string $label, string $type, string $val = '', string $ac = ''): string =>
    '<div class="fw' . (isset($errors[$n]) ? ' has-err' : '') . '"><label class="lbl" for="p-' . $n . '">' . e($label) . '</label><input class="inp" id="p-' . $n . '" name="' . $n . '" type="' . $type . '" value="' . e($val) . '"'
    . ($ac ? ' autocomplete="' . $ac . '"' : '') . '>' . (isset($errors[$n]) ? '<p class="err">' . e($errors[$n]) . '</p>' : '') . '</div>';
?>
<div class="page-head"><div><h2>Mein Konto</h2><p class="muted">Angemeldet als <strong><?= e($me['username']) ?></strong> (<?= $me['role'] === 'admin' ? 'Administrator' : 'Redakteur' ?>)<?= $me['last_login'] ? ' · letzter Login ' . e(date('d.m.Y H:i', strtotime($me['last_login']))) : '' ?></p></div></div>
<div class="two-col">
  <form method="post" class="card">
    <?= Csrf::field() ?><input type="hidden" name="do" value="email">
    <h3>E-Mail</h3>
    <?= $fld('email', 'E-Mail-Adresse', 'email', (string)$me['email'], 'email') ?>
    <button class="btn btn-primary" type="submit">Speichern</button>
  </form>

  <form method="post" class="card">
    <?= Csrf::field() ?><input type="hidden" name="do" value="password">
    <h3>Passwort ändern</h3>
    <?= $fld('current', 'Aktuelles Passwort', 'password', '', 'current-password') ?>
    <?= $fld('password', 'Neues Passwort', 'password', '', 'new-password') ?>
    <?= $fld('password2', 'Neues Passwort wiederholen', 'password', '', 'new-password') ?>
    <p class="help">Mindestens 10 Zeichen mit Buchstaben und Zahlen.</p>
    <button class="btn btn-primary" type="submit">Passwort ändern</button>
  </form>
</div>

<section class="card">
  <h3>Zwei-Faktor-Anmeldung (2FA)</h3>
  <?php if ((int)$me['totp_enabled'] === 1): ?>
    <p><span class="pill pill-ok">aktiv</span> Beim Login wird zusätzlich ein Code aus deiner Authenticator-App verlangt.</p>
    <form method="post" class="inline-form" data-confirm="2FA wirklich deaktivieren?">
      <?= Csrf::field() ?><input type="hidden" name="do" value="2fa_disable">
      <?= $fld('current2', 'Zur Bestätigung: aktuelles Passwort', 'password', '', 'current-password') ?>
      <button class="btn btn-danger" type="submit">2FA deaktivieren</button>
    </form>
  <?php elseif ($pending !== ''): ?>
    <p>Scanne den QR-Code mit einer Authenticator-App (z. B. Google Authenticator, Aegis, 1Password) und gib den angezeigten 6-stelligen Code ein.</p>
    <div class="twofa">
      <div class="qr" id="qr" data-uri="<?= e($otpUri) ?>" aria-label="QR-Code"></div>
      <div>
        <p class="muted small">Oder Schlüssel manuell eintragen:</p>
        <code class="secret"><?= e(trim(chunk_split($pending, 4, ' '))) ?></code>
        <form method="post" class="form" autocomplete="off">
          <?= Csrf::field() ?><input type="hidden" name="do" value="2fa_enable">
          <?= $fld('code', 'Code aus der App', 'text') ?>
          <button class="btn btn-primary" type="submit">Aktivieren</button>
        </form>
      </div>
    </div>
  <?php else: ?>
    <p><span class="pill">aus</span> Schütze dein Konto zusätzlich mit einem Einmal-Code aus einer Authenticator-App.</p>
    <form method="post"><?= Csrf::field() ?><input type="hidden" name="do" value="2fa_start"><button class="btn btn-soft" type="submit"><?= icon('shield-check') ?><span>2FA einrichten</span></button></form>
  <?php endif; ?>
</section>
