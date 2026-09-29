<?php
/** @var string $kind @var string $title @var string $intro @var bool $sent @var string $error */
$isBooking = $kind === 'booking';
$types = Inquiry::options($isBooking ? 'book_types' : 'inq_types');
$times = $isBooking ? [] : Inquiry::options('inq_timeframes');
$budgets = $isBooking ? [] : Inquiry::options('inq_budgets');
$success = s($isBooking ? 'book_success' : 'inq_success');
$privacyLink = '<a href="' . e(u('/datenschutz/')) . '">' . e(tr('privacy')) . '</a>';
$today = date('Y-m-d');
$select = static function (string $name, string $label, array $opts): string {
    $h = '<label class="field field-static"><select name="' . e($name) . '" required><option value="">' . e(tr('choose')) . '</option>';
    foreach ($opts as $i => $o) {
        $h .= '<option value="' . $i . '">' . e($o) . '</option>';
    }
    return $h . '</select><span>' . e($label) . '</span></label>';
};
?>
<section class="page-hero page-hero-sm">
  <div class="hero-bg" aria-hidden="true"><i class="blob blob-1"></i><i class="blob blob-2"></i></div>
  <div class="wrap">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= e(u('/')) ?>"><?= e(tr('home')) ?></a><?= icon('chevron-right') ?><span><?= e($title) ?></span></nav>
    <span class="eyebrow"><i class="eyebrow-dot"></i><?= e($isBooking ? 'DJ-Frankus' : 'Panzer IT') ?></span>
    <h1 class="page-title" data-split-words><?= e($title) ?></h1>
    <p class="page-lead"><?= e($intro) ?></p>
  </div>
</section>
<section class="sec sec-tight sec-contact">
  <div class="wrap">
    <div class="contact-grid">
      <aside class="contact-info" data-reveal="left">
        <h3><?= e(tr('contact_direct')) ?></h3>
        <ul class="contact-list">
          <li><span class="icon-orb icon-orb-sm"><?= icon('mail') ?></span><a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a></li>
          <?php if (s('contact_reply_time') !== ''): ?><li><span class="icon-orb icon-orb-sm"><?= icon('clock') ?></span><span><?= e(s('contact_reply_time')) ?></span></li><?php endif; ?>
        </ul>
        <p class="form-note"><a href="<?= e(u('/') . '#kontakt') ?>"><?= e(tr($isBooking ? 'book_other' : 'inq_other')) ?></a></p>
      </aside>
      <div class="contact-form-wrap" data-reveal="right">
        <div class="form-success" id="inq-success" <?= $sent ? '' : 'hidden' ?> role="status">
          <span class="success-check"><svg viewBox="0 0 52 52" aria-hidden="true"><circle cx="26" cy="26" r="24"/><path d="M14 27l8 8 16-17"/></svg></span>
          <p><?= e($success) ?></p>
        </div>
        <form class="form" id="inquiry-form" method="post" action="<?= e(u('/api/inquiry')) ?>" novalidate <?= $sent ? 'hidden' : '' ?>>
          <?php if ($error !== ''): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
          <p class="form-error" id="inq-error" role="alert" hidden></p>
          <input type="hidden" name="kind" value="<?= e($kind) ?>">
          <div class="field-row">
            <label class="field"><input type="text" name="name" placeholder=" " required maxlength="120" autocomplete="name"><span><?= e(tr('name')) ?></span></label>
            <label class="field"><input type="email" name="email" placeholder=" " required maxlength="190" autocomplete="email"><span><?= e(tr('email')) ?></span></label>
          </div>
          <?php if ($isBooking): ?>
          <div class="field-row">
            <label class="field field-static"><input type="date" name="date" required min="<?= e($today) ?>"><span><?= e(tr('book_date')) ?></span></label>
            <?= $select('type', tr('book_type'), $types) ?>
          </div>
          <div class="field-row">
            <label class="field"><input type="text" name="location" placeholder=" " required maxlength="160"><span><?= e(tr('book_location')) ?></span></label>
            <label class="field"><input type="tel" name="phone" placeholder=" " maxlength="40" autocomplete="tel"><span><?= e(tr('phone_opt')) ?></span></label>
          </div>
          <div class="field-row">
            <label class="field"><input type="number" name="guests" placeholder=" " min="1" max="100000" inputmode="numeric"><span><?= e(tr('book_guests')) ?></span></label>
            <label class="field"><input type="text" name="timespan" placeholder=" " maxlength="80"><span><?= e(tr('book_time')) ?></span></label>
          </div>
          <label class="field field-area"><textarea name="message" placeholder=" " rows="5" maxlength="5000"></textarea><span><?= e(tr('book_message')) ?></span></label>
          <?php else: ?>
          <div class="field-row">
            <label class="field"><input type="tel" name="phone" placeholder=" " maxlength="40" autocomplete="tel"><span><?= e(tr('phone_opt')) ?></span></label>
            <label class="field"><input type="text" name="company" placeholder=" " maxlength="120" autocomplete="organization"><span><?= e(tr('company_opt')) ?></span></label>
          </div>
          <div class="field-row">
            <?= $select('type', tr('inq_type'), $types) ?>
            <?= $select('timeframe', tr('inq_timeframe'), $times) ?>
          </div>
          <div class="field-row">
            <?= $select('budget', tr('inq_budget'), $budgets) ?>
            <label class="field"><input type="text" name="current_site" placeholder=" " maxlength="190" inputmode="url"><span><?= e(tr('current_site_opt')) ?></span></label>
          </div>
          <label class="field field-area"><textarea name="message" placeholder=" " required rows="6" minlength="10" maxlength="5000"></textarea><span><?= e(tr('inq_message')) ?></span></label>
          <?php endif; ?>
          <div class="hp" aria-hidden="true"><label>Homepage<input type="text" name="homepage" tabindex="-1" autocomplete="off"></label></div>
          <input type="hidden" name="_t" value="<?= e(FormToken::issue('inquiry-' . $kind)) ?>">
          <p class="form-note"><?= str_replace('{link}', $privacyLink, e(tr('contact_privacy'))) ?></p>
          <button class="btn btn-primary btn-lg magnetic" type="submit"><span class="btn-label"><?= e(tr($isBooking ? 'book_send' : 'inq_send')) ?></span><?= icon('send') ?></button>
        </form>
      </div>
    </div>
  </div>
</section>
