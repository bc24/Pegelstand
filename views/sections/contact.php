<?php
$socials = Content::socials();
$prefill = mb_substr(trim((string)($_GET['subject'] ?? '')), 0, 120);
$sent = ($_GET['sent'] ?? '') === '1';
$errKey = (string)($_GET['err'] ?? '');
$err = in_array($errKey, ['err_required', 'err_email', 'err_length', 'err_rate', 'err_spam'], true) ? tr($errKey) : '';
$subjects = [s('partner_subject'), Lang::$code === 'en' ? 'Project inquiry' : 'Projektanfrage', Lang::$code === 'en' ? 'Question' : 'Frage', Lang::$code === 'en' ? 'Just saying hi' : 'Einfach Hallo'];
$privacyLink = '<a href="' . e(u('/datenschutz/')) . '">' . e(tr('privacy')) . '</a>';
?>
<section class="sec sec-contact" id="<?= e($sec['anchor']) ?>" data-section="contact">
  <div class="contact-glow" aria-hidden="true"></div>
  <div class="wrap">
    <?= View::head($sec) ?>
    <div class="contact-grid">
      <aside class="contact-info" data-reveal="left">
        <h3><?= e(tr('contact_direct')) ?></h3>
        <ul class="contact-list">
          <li><span class="icon-orb icon-orb-sm"><?= icon('mail') ?></span><a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a></li>
          <li><span class="icon-orb icon-orb-sm"><?= icon('map-pin') ?></span><span><?= e(setting('location')) ?></span></li>
          <?php if (s('contact_reply_time') !== ''): ?><li><span class="icon-orb icon-orb-sm"><?= icon('clock') ?></span><span><?= e(s('contact_reply_time')) ?></span></li><?php endif; ?>
        </ul>
        <div class="social-row">
          <?php foreach ($socials as $so): ?>
          <a class="social-btn magnetic" href="<?= e($so['url']) ?>" target="_blank" rel="noopener me" aria-label="<?= e($so['label']) ?>" title="<?= e($so['label'] . ($so['handle'] ? ' · ' . $so['handle'] : '')) ?>"><?= icon($so['icon']) ?></a>
          <?php endforeach; ?>
        </div>
      </aside>

      <div class="contact-form-wrap" data-reveal="right">
        <div class="form-success" id="form-success" <?= $sent ? '' : 'hidden' ?> role="status">
          <span class="success-check"><svg viewBox="0 0 52 52" aria-hidden="true"><circle cx="26" cy="26" r="24"/><path d="M14 27l8 8 16-17"/></svg></span>
          <p><?= e(s('contact_success')) ?></p>
        </div>
        <form class="form" id="contact-form" method="post" action="<?= e(u('/api/contact')) ?>" novalidate <?= $sent ? 'hidden' : '' ?>>
          <?php if ($err): ?><p class="form-error" role="alert"><?= e($err) ?></p><?php endif; ?>
          <p class="form-error" id="form-error" role="alert" hidden></p>
          <div class="field-row">
            <label class="field"><input type="text" name="name" id="c-name" placeholder=" " required maxlength="120" autocomplete="name"><span><?= e(tr('name')) ?></span></label>
            <label class="field"><input type="email" name="email" id="c-email" placeholder=" " required maxlength="190" autocomplete="email"><span><?= e(tr('email')) ?></span></label>
          </div>
          <label class="field"><input type="text" name="subject" id="c-subject" placeholder=" " maxlength="200" list="subject-list" value="<?= e($prefill) ?>"><span><?= e(tr('subject')) ?></span></label>
          <datalist id="subject-list"><?php foreach ($subjects as $sj): ?><option value="<?= e($sj) ?>"><?php endforeach; ?></datalist>
          <label class="field field-area"><textarea name="message" id="c-message" placeholder=" " required rows="6" minlength="10" maxlength="5000"></textarea><span><?= e(tr('message')) ?></span></label>
          <div class="hp" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
          <input type="hidden" name="_t" value="<?= e(FormToken::issue('contact')) ?>">
          <p class="form-note"><?= str_replace('{link}', $privacyLink, e(tr('contact_privacy'))) ?></p>
          <button class="btn btn-primary btn-lg magnetic" type="submit"><span class="btn-label"><?= e(tr('send')) ?></span><?= icon('send') ?></button>
        </form>
      </div>
    </div>
  </div>
</section>
