<?php
/** @var array $post @var array $neighbours @var array $comments @var bool $fallbackDe @var string $formToken @var string $flash @var bool $preview */
[$body, $toc] = Html::withToc(Html::sanitize(t($post, 'content')));
$url = page_url('/blog/' . $post['slug'] . '/');
$title = t($post, 'title');
$img = media_url($post['image']);
$commentsOpen = sb('comments_enabled', true) && (int)$post['allow_comments'];
$share = [
    ['WhatsApp', 'https://wa.me/?text=' . rawurlencode($title . ' ' . $url), 'brand-whatsapp'],
    ['Facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($url), 'brand-facebook'],
    ['X', 'https://x.com/intent/post?text=' . rawurlencode($title) . '&url=' . rawurlencode($url), 'brand-x'],
];
?>
<?php if (!empty($preview)):
    $future = $post['status'] === 'published' && $post['published_at'] && strtotime((string)$post['published_at']) > time(); ?>
<div class="preview-bar" role="status"><?= icon('eye') ?><span><?= e($future ? tr('preview_scheduled', ['date' => format_date($post['published_at'], null, true) . ', ' . date('H:i', strtotime((string)$post['published_at']))]) : tr('preview_banner')) ?></span></div>
<?php endif; ?>
<article class="post" data-post="<?= e($post['slug']) ?>">
  <header class="page-hero post-hero">
    <div class="hero-bg" aria-hidden="true"><i class="blob blob-1"></i><i class="blob blob-2"></i></div>
    <div class="wrap wrap-narrow">
      <a class="back-link" href="<?= e(u('/blog/')) ?>"><?= icon('arrow-left') ?><?= e(tr('back_blog')) ?></a>
      <div class="post-meta">
        <?php if (t($post, 'category') !== ''): ?><span class="badge"><?= e(t($post, 'category')) ?></span><?php endif; ?>
        <time datetime="<?= e(substr((string)$post['published_at'], 0, 10)) ?>"><?= e(format_date($post['published_at'])) ?></time>
        <span><?= icon('clock') ?><?= reading_minutes($body) ?> <?= e(tr('min_read')) ?></span>
      </div>
      <h1 class="page-title"><?= e($title) ?></h1>
      <?php if (t($post, 'excerpt') !== ''): ?><p class="page-lead"><?= e(t($post, 'excerpt')) ?></p><?php endif; ?>
      <?php if ($fallbackDe): ?><p class="notice"><?= icon('languages') ?><?= e(tr('only_german')) ?></p><?php endif; ?>
    </div>
  </header>

  <?php if ($img): ?>
  <div class="wrap wrap-narrow"><figure class="post-cover"><img src="<?= e($img) ?>" alt="" width="1200" height="630"></figure></div>
  <?php endif; ?>

  <div class="wrap post-layout">
    <?php if (count($toc) > 2): ?>
    <aside class="toc" aria-label="Inhalt">
      <ul>
        <?php foreach ($toc as $ti): ?><li class="toc-l<?= $ti['level'] ?>"><a href="#<?= e($ti['id']) ?>"><?= e($ti['text']) ?></a></li><?php endforeach; ?>
      </ul>
    </aside>
    <?php endif; ?>
    <div class="post-main">
      <div class="prose prose-lg" id="post-body"><?= $body ?></div>

      <div class="post-actions">
        <?php if (sb('likes_enabled', true)): ?>
        <button class="like-btn" type="button" id="like-btn" data-url="<?= e(u('/blog/' . $post['slug'] . '/like')) ?>" data-slug="<?= e($post['slug']) ?>" aria-pressed="false">
          <?= icon('heart') ?><span class="like-count" id="like-count"><?= (int)$post['likes'] ?></span><span class="like-label"><?= e(tr('likes')) ?></span>
        </button>
        <?php endif; ?>
        <div class="share">
          <span><?= e(tr('share')) ?></span>
          <?php foreach ($share as [$label, $href, $ico]): ?>
          <a class="social-btn" href="<?= e($href) ?>" target="_blank" rel="noopener" aria-label="<?= e($label) ?>"><?= icon($ico) ?></a>
          <?php endforeach; ?>
          <button class="social-btn" type="button" id="copy-link" data-url="<?= e($url) ?>" aria-label="<?= e(tr('copy_link')) ?>"><?= icon('link') ?></button>
        </div>
      </div>

      <nav class="post-nav" aria-label="Blog">
        <?php if ($neighbours['prev']): ?><a class="post-nav-item is-prev" href="<?= e(u('/blog/' . $neighbours['prev']['slug'] . '/')) ?>"><small><?= icon('arrow-left') ?><?= e(tr('prev_post')) ?></small><span><?= e(t($neighbours['prev'], 'title')) ?></span></a><?php else: ?><span></span><?php endif; ?>
        <?php if ($neighbours['next']): ?><a class="post-nav-item is-next" href="<?= e(u('/blog/' . $neighbours['next']['slug'] . '/')) ?>"><small><?= e(tr('next_post')) ?><?= icon('arrow-right') ?></small><span><?= e(t($neighbours['next'], 'title')) ?></span></a><?php endif; ?>
      </nav>

      <section class="comments" id="kommentare">
        <h2><?= e(tr('comments')) ?> <small>(<?= count($comments) ?>)</small></h2>
        <?php if ($flash === '1'): ?><p class="notice notice-ok" role="status"><?= icon('circle-check') ?><?= e(sb('comments_moderation', true) ? tr('comment_thanks') : tr('comment_posted')) ?></p><?php endif; ?>
        <?php if (!$comments): ?><p class="muted"><?= e(tr('comment_none')) ?></p><?php endif; ?>
        <ul class="comment-list">
          <?php foreach ($comments as $c): ?>
          <li class="comment">
            <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($c['name'], 0, 1))) ?></span>
            <div><strong><?= e($c['name']) ?></strong><time><?= e(format_date($c['created_at'], null, true)) ?></time><p><?= nl2br(e($c['message'])) ?></p></div>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php if ($commentsOpen): ?>
        <form class="form comment-form" id="comment-form" method="post" action="<?= e(u('/blog/' . $post['slug'] . '/comment')) ?>">
          <h3><?= e(tr('comment_leave')) ?></h3>
          <p class="form-error" id="comment-error" role="alert" hidden></p>
          <label class="field"><input type="text" name="name" placeholder=" " required maxlength="80" autocomplete="nickname"><span><?= e(tr('comment_name')) ?></span></label>
          <label class="field field-area"><textarea name="message" placeholder=" " required rows="4" maxlength="1200"></textarea><span><?= e(tr('comment_text')) ?></span></label>
          <div class="hp" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
          <input type="hidden" name="_t" value="<?= e($formToken) ?>">
          <p class="form-note"><?= e(sb('comments_moderation', true) ? tr('comment_hint') : tr('comment_hint_open')) ?></p>
          <button class="btn btn-primary" type="submit"><span class="btn-label"><?= e(tr('comment_send')) ?></span><?= icon('send') ?></button>
        </form>
        <?php else: ?><p class="muted"><?= e(tr('comments_closed')) ?></p><?php endif; ?>
      </section>
    </div>
  </div>
</article>
