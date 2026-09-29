<?php
/** @var string $content @var array $meta */
$nonce = Security::nonce();
$site = setting('site_name', 'Frank Panzer');
$lang = Lang::$code;
$otherLang = $lang === 'de' ? 'en' : 'de';
$accent = valid_hex(setting('accent')) ? setting('accent') : '#F97316';
[$ar, $ag, $ab] = hex_to_rgb($accent);
$mix = static fn(array $c, array $to, float $f): string => sprintf('#%02x%02x%02x',
    (int)round($c[0] + ($to[0] - $c[0]) * $f), (int)round($c[1] + ($to[1] - $c[1]) * $f), (int)round($c[2] + ($to[2] - $c[2]) * $f));
$accentLight = $mix([$ar, $ag, $ab], [255, 255, 255], .28);
$accentDark = $mix([$ar, $ag, $ab], [0, 0, 0], .22);
$onAccent = (0.299 * $ar + 0.587 * $ag + 0.114 * $ab) > 140 ? '#160a02' : '#ffffff';
$theme = setting('default_theme', 'dark');
$initialTheme = $theme === 'light' ? 'light' : 'dark';
$title = $meta['title'] ?? $site;
$desc = $meta['description'] ?? '';
$canonical = $meta['canonical'] ?? null;
$ogImage = !empty($meta['og_image']) ? abs_url(media_url($meta['og_image'])) : '';
$robots = $meta['robots'] ?? (sb('robots_index', true) ? 'index,follow,max-image-preview:large' : 'noindex,nofollow');
$fx = [];
foreach (['preloader', 'particles', 'cursor', 'tilt', 'marquee', 'konami', 'grain'] as $f) {
    if (sb('fx_' . $f, true)) {
        $fx[] = $f;
    }
}
$isHome = ($meta['body_class'] ?? '') === 'page-home';
$navSections = Content::nav();
$socials = Content::socials();
$footerPages = Content::footerPages();
$altPath = rtrim(Front::$path, '/') . '/';
$i18n = [
    'copied' => tr('copied'), 'sending' => tr('sending'), 'send' => tr('send'), 'liked' => tr('liked'),
    'errGeneric' => tr('err_generic'), 'konami' => tr('konami_msg'), 'menuOpen' => tr('menu_open'), 'menuClose' => tr('menu_close'),
    'reduced' => false,
];
?><!doctype html>
<html lang="<?= e($lang) ?>" data-theme="<?= e($initialTheme) ?>" class="no-js">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title) ?></title>
<?php if ($desc !== ''): ?><meta name="description" content="<?= e($desc) ?>">
<?php endif; ?>
<meta name="robots" content="<?= e($robots) ?>">
<meta name="theme-color" content="#0a0d12">
<meta name="color-scheme" content="dark light">
<?php if ($canonical): ?><link rel="canonical" href="<?= e($canonical) ?>">
<?php endif; ?>
<?php foreach (($meta['alternates'] ?? []) as $hl => $href): ?><link rel="alternate" hreflang="<?= e($hl) ?>" href="<?= e($href) ?>">
<?php endforeach; ?>
<link rel="alternate" type="application/rss+xml" title="<?= e($site) ?> Blog" href="<?= e(u('/feed.xml')) ?>">
<meta property="og:site_name" content="<?= e($site) ?>">
<meta property="og:type" content="<?= e($meta['og_type'] ?? 'website') ?>">
<meta property="og:title" content="<?= e($title) ?>">
<?php if ($desc !== ''): ?><meta property="og:description" content="<?= e($desc) ?>">
<?php endif; ?>
<?php if ($canonical): ?><meta property="og:url" content="<?= e($canonical) ?>">
<?php endif; ?>
<meta property="og:locale" content="<?= e(Lang::locale()) ?>">
<?php if ($ogImage): ?><meta property="og:image" content="<?= e($ogImage) ?>">
<meta name="twitter:image" content="<?= e($ogImage) ?>">
<?php endif; ?>
<meta name="twitter:card" content="<?= $ogImage ? 'summary_large_image' : 'summary' ?>">
<link rel="icon" type="image/svg+xml" href="<?= e(asset('img/favicon.svg')) ?>">
<link rel="preload" href="<?= e(rtrim((string)cfg('base_path', ''), '/') . '/assets/fonts/space-grotesk-latin-wght-normal.woff2') ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= e(rtrim((string)cfg('base_path', ''), '/') . '/assets/fonts/inter-latin-wght-normal.woff2') ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
<style nonce="<?= e($nonce) ?>">:root{--accent:<?= e($accent) ?>;--accent-rgb:<?= "$ar,$ag,$ab" ?>;--accent-2:<?= e($accentLight) ?>;--accent-3:<?= e($accentDark) ?>;--on-accent:<?= e($onAccent) ?>}</style>
<?php $css = setting('custom_css'); if ($css !== ''): ?><style nonce="<?= e($nonce) ?>"><?= str_replace('</', '<\/', $css) ?></style>
<?php endif; ?>
<script nonce="<?= e($nonce) ?>">
(function(){var d=document.documentElement;try{var t=localStorage.getItem('fp-theme');if(!t){var c='<?= e($theme) ?>';t=c==='auto'?(matchMedia('(prefers-color-scheme: light)').matches?'light':'dark'):c;}d.setAttribute('data-theme',t==='light'?'light':'dark');if(sessionStorage.getItem('fp-pre'))d.classList.add('no-pre');}catch(e){}d.classList.remove('no-js');d.classList.add('js');})();
</script>
<?php foreach (($meta['jsonld'] ?? []) as $ld): ?><script type="application/ld+json"><?= View::json($ld) ?></script>
<?php endforeach; ?>
<?= setting('custom_head') ?>
</head>
<body class="<?= e($meta['body_class'] ?? '') ?>" data-fx="<?= e(implode(' ', $fx)) ?>" data-lang="<?= e($lang) ?>" data-base="<?= e(rtrim((string)cfg('base_path', ''), '/')) ?>">
<a class="skip-link" href="#main"><?= e(tr('skip')) ?></a>

<?php if (in_array('preloader', $fx, true) && $isHome): ?>
<div class="preloader" id="preloader" aria-hidden="true">
  <div class="preloader-inner">
    <span class="preloader-tank"><?= icon('tank') ?></span>
    <span class="preloader-name"><?= e(setting('hero_name', $site)) ?></span>
    <span class="preloader-bar"><i></i></span>
  </div>
</div>
<?php endif; ?>

<div class="progress" id="progress" aria-hidden="true"></div>
<?php if (in_array('cursor', $fx, true)): ?><div class="cursor" id="cursor" aria-hidden="true"><i class="cursor-dot"></i><i class="cursor-ring"></i></div>
<?php endif; ?>
<?php if (in_array('grain', $fx, true)): ?><div class="grain" aria-hidden="true"></div>
<?php endif; ?>

<header class="site-header" id="header">
  <div class="wrap header-inner">
    <a class="brand" href="<?= e(u('/')) ?>" aria-label="<?= e($site . ' — ' . tr('home')) ?>">
      <span class="brand-mark"><?= icon('tank') ?></span>
      <span class="brand-name"><?= e($site) ?></span>
    </a>
    <nav class="nav" aria-label="<?= e(tr('nav_main')) ?>">
      <ul>
        <?php foreach ($navSections as $ns): ?>
        <li><a href="<?= e(View::sectionHref($ns)) ?>" data-spy="<?= e($ns['anchor']) ?>"><?= e(t($ns, 'nav') ?: t($ns, 'label')) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <div class="header-actions">
      <a class="lang-switch" href="<?= e(u($altPath, $otherLang)) ?>" hreflang="<?= e($otherLang) ?>" lang="<?= e($otherLang) ?>" aria-label="<?= e(tr('lang_switch')) ?>">
        <span class="<?= $lang === 'de' ? 'is-active' : '' ?>">DE</span><span class="<?= $lang === 'en' ? 'is-active' : '' ?>">EN</span>
      </a>
      <button class="icon-btn theme-toggle" type="button" id="theme-toggle" aria-label="<?= e(tr('theme')) ?>">
        <span class="ti ti-sun"><?= icon('sun') ?></span><span class="ti ti-moon"><?= icon('moon') ?></span>
      </button>
      <button class="icon-btn burger" type="button" id="burger" aria-expanded="false" aria-controls="mobile-menu" aria-label="<?= e(tr('menu_open')) ?>">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
  <div class="mobile-menu" id="mobile-menu" aria-hidden="true">
    <nav aria-label="<?= e(tr('nav_main')) ?>">
      <ul>
        <?php foreach ($navSections as $i => $ns): ?>
        <li style="--i:<?= $i ?>"><a href="<?= e(View::sectionHref($ns)) ?>"><span class="mm-num"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span><?= e(t($ns, 'nav') ?: t($ns, 'label')) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <div class="mobile-menu-foot">
      <div class="social-row">
        <?php foreach ($socials as $so): ?>
        <a class="social-btn" href="<?= e($so['url']) ?>" target="_blank" rel="noopener me" aria-label="<?= e($so['label']) ?>"><?= icon($so['icon']) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</header>

<main id="main">
<?= $content ?>
</main>

<footer class="site-footer" id="footer">
  <div class="footer-glow" aria-hidden="true"></div>
  <div class="wrap">
    <div class="footer-big" aria-hidden="true"><?= e(setting('hero_name', $site)) ?></div>
    <div class="footer-grid">
      <div class="footer-col footer-about">
        <a class="brand" href="<?= e(u('/')) ?>"><span class="brand-mark"><?= icon('tank') ?></span><span class="brand-name"><?= e($site) ?></span></a>
        <p><?= e(s('footer_text')) ?></p>
        <a class="footer-mail" href="mailto:<?= e(setting('contact_email')) ?>"><?= icon('mail') ?><?= e(setting('contact_email')) ?></a>
      </div>
      <div class="footer-col">
        <h3><?= e(tr('menu')) ?></h3>
        <ul>
          <?php foreach ($navSections as $ns): ?><li><a href="<?= e(View::sectionHref($ns)) ?>"><?= e(t($ns, 'nav') ?: t($ns, 'label')) ?></a></li>
          <?php endforeach; ?>
          <li><a href="<?= e(u('/projekte/')) ?>"><?= e(tr('projects_page_title')) ?></a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h3><?= e(tr('social')) ?></h3>
        <ul class="footer-social">
          <?php foreach ($socials as $so): ?>
          <li><a href="<?= e($so['url']) ?>" target="_blank" rel="noopener me"><?= icon($so['icon']) ?><span><?= e($so['label']) ?></span></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> <?= e($site) ?> · Panzer IT</span>
      <ul>
        <?php foreach ($footerPages as $fp): ?><li><a href="<?= e(u('/' . $fp['slug'] . '/')) ?>"><?= e(t($fp, 'title')) ?></a></li>
        <?php endforeach; ?>
        <li><a href="<?= e(u('/feed.xml')) ?>">RSS</a></li>
      </ul>
    </div>
  </div>
</footer>

<button class="to-top" id="to-top" type="button" aria-label="<?= e(tr('to_top')) ?>">
  <svg class="to-top-ring" viewBox="0 0 44 44" aria-hidden="true"><circle cx="22" cy="22" r="20"/></svg>
  <?= icon('arrow-up') ?>
</button>

<?php if (sb('cookie_banner', true)): ?>
<div class="consent" id="consent" role="dialog" aria-live="polite" aria-label="<?= e(tr('privacy')) ?>" hidden>
  <div class="consent-icon"><?= icon('shield-check') ?></div>
  <p><?= e(s('cookie_text')) ?> <a href="<?= e(u('/datenschutz/')) ?>"><?= e(tr('learn_more')) ?></a></p>
  <div class="consent-actions">
    <button type="button" class="btn btn-sm btn-primary" data-consent="yes"><?= e(tr('cookie_accept')) ?></button>
    <button type="button" class="btn btn-sm btn-ghost" data-consent="no"><?= e(tr('cookie_decline')) ?></button>
  </div>
</div>
<?php endif; ?>

<script type="application/json" id="fp-i18n"><?= View::json($i18n) ?></script>
<script src="<?= e(asset('js/site.js')) ?>" defer></script>
</body>
</html>
