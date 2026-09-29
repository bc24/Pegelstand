<?php
declare(strict_types=1);

/** Routen der öffentlichen Seite. */
final class Front
{
    /** Pfad ohne Sprachpräfix und ohne abschließenden Slash, z. B. "/blog/mein-artikel". */
    public static string $path = '/';

    public static function dispatch(string $path, string $method): void
    {
        self::$path = $path;

        if (setting('maintenance') === '1' && !Auth::hasSessionCookie() && !str_starts_with($path, '/api/')
            && !in_array($path, ['/robots.txt', '/sitemap.xml'], true)) {
            header('Retry-After: 3600');
            View::page('maintenance', [], ['title' => tr('maintenance_title') . ' — ' . setting('site_name', 'Frank Panzer'), 'robots' => 'noindex,nofollow'], 503);
        }

        if ($method === 'POST') {
            if ($path === '/api/contact') {
                self::contact();
            }
            if (preg_match('#^/blog/([a-z0-9-]+)/(like|comment)$#', $path, $m)) {
                $m[2] === 'like' ? self::like($m[1]) : self::comment($m[1]);
            }
            json_out(['ok' => false, 'error' => 'Not found'], 404);
        }

        switch (true) {
            case $path === '/':
                self::home();
            case $path === '/blog':
                self::blogList();
            case (bool)preg_match('#^/blog/([a-z0-9-]+)$#', $path, $m):
                self::blogPost($m[1]);
            case $path === '/projekte':
                self::projects();
            case (bool)preg_match('#^/projekt/([a-z0-9-]+)$#', $path, $m):
                self::project($m[1]);
            case $path === '/sitemap.xml':
                self::sitemap();
            case $path === '/feed.xml':
                self::feed();
            case $path === '/robots.txt':
                self::robots();
            case (bool)preg_match('#^/([a-z0-9-]+)$#', $path, $m):
                if (self::page($m[1])) {
                    return;
                }
        }
        self::notFound();
    }

    /* ------------------------------------------------------------ Seiten */

    private static function home(): never
    {
        Visits::track(self::$path === '/' ? (Lang::$code === 'en' ? '/en/' : '/') : '/');
        $sections = Content::sections();
        $desc = s('meta_desc');
        $title = s('meta_title') ?: setting('site_name') . ' — ' . s('site_tagline');
        View::page('home', [
            'sections' => $sections,
            'sectionMap' => Content::sectionMap(),
        ], [
            'title' => $title,
            'description' => $desc,
            'canonical' => page_url('/'),
            'alternates' => self::alternates('/'),
            'og_image' => setting('og_image'),
            'body_class' => 'page-home',
            'jsonld' => [self::personSchema(), self::websiteSchema()],
        ]);
    }

    private static function blogList(): never
    {
        Visits::track(self::visitPath());
        $posts = Content::posts();
        View::page('blog_list', ['posts' => $posts], [
            'title' => s('blog_title') . ' — ' . setting('site_name'),
            'description' => s('blog_intro'),
            'canonical' => page_url('/blog/'),
            'alternates' => self::alternates('/blog/'),
            'og_image' => setting('og_image'),
            'body_class' => 'page-blog',
        ]);
    }

    private static function blogPost(string $slug): never
    {
        $post = Content::post($slug);
        if (!$post) {
            self::notFound();
        }
        if (Visits::countable()) {
            Db::q('UPDATE posts SET views = views + 1 WHERE id = ?', [$post['id']]);
            Visits::track(self::visitPath());
        }
        $title = t($post, 'title');
        $content = t($post, 'content');
        $fallbackDe = Lang::$code === 'en' && trim((string)$post['content_en']) === '';
        $img = $post['image'] ?: setting('og_image');
        View::page('blog_post', [
            'post' => $post,
            'neighbours' => Content::neighbours($post),
            'comments' => Content::comments((int)$post['id']),
            'fallbackDe' => $fallbackDe,
            'formToken' => FormToken::issue('comment'),
            'flash' => (string)($_GET['c'] ?? ''),
        ], [
            'title' => $title . ' — ' . setting('site_name'),
            'description' => excerpt(t($post, 'excerpt') ?: $content, 170),
            'canonical' => page_url('/blog/' . $slug . '/'),
            'alternates' => $fallbackDe ? [] : self::alternates('/blog/' . $slug . '/'),
            'og_image' => $img,
            'og_type' => 'article',
            'body_class' => 'page-post',
            'jsonld' => [[
                '@context' => 'https://schema.org', '@type' => 'BlogPosting',
                'headline' => $title, 'description' => excerpt(t($post, 'excerpt') ?: $content, 200),
                'datePublished' => date('c', strtotime((string)$post['published_at'])),
                'dateModified' => date('c', strtotime((string)$post['updated_at'])),
                'inLanguage' => Lang::$code,
                'mainEntityOfPage' => page_url('/blog/' . $slug . '/'),
                'author' => ['@type' => 'Person', 'name' => setting('site_name'), 'url' => page_url('/')],
                'image' => $img ? [abs_url(media_url($img))] : [],
            ]],
        ]);
    }

    private static function projects(): never
    {
        Visits::track(self::visitPath());
        $projects = Content::projects();
        View::page('projects', ['projects' => $projects, 'cats' => Content::projectCategories($projects)], [
            'title' => tr('projects_page_title') . ' — ' . setting('site_name'),
            'description' => tr('projects_page_intro'),
            'canonical' => page_url('/projekte/'),
            'alternates' => self::alternates('/projekte/'),
            'og_image' => setting('og_image'),
            'body_class' => 'page-projects',
        ]);
    }

    private static function project(string $slug): never
    {
        $p = Content::project($slug);
        if (!$p) {
            self::notFound();
        }
        Visits::track(self::visitPath());
        $others = array_values(array_filter(Content::projects(), static fn($x) => $x['id'] !== $p['id']));
        shuffle($others);
        View::page('project', ['p' => $p, 'others' => array_slice($others, 0, 3)], [
            'title' => $p['title'] . ' — ' . setting('site_name'),
            'description' => excerpt(t($p, 'tagline'), 170),
            'canonical' => page_url('/projekt/' . $slug . '/'),
            'alternates' => self::alternates('/projekt/' . $slug . '/'),
            'og_image' => $p['image'] ?: setting('og_image'),
            'body_class' => 'page-project',
        ]);
    }

    /** @return bool true, wenn die Seite existierte und ausgeliefert wurde. */
    private static function page(string $slug): bool
    {
        $page = Content::page($slug);
        if (!$page) {
            return false;
        }
        Visits::track(self::visitPath());
        $fallbackDe = Lang::$code === 'en' && trim((string)$page['content_en']) === '';
        View::page('page', ['page' => $page, 'fallbackDe' => $fallbackDe], [
            'title' => t($page, 'title') . ' — ' . setting('site_name'),
            'description' => excerpt(t($page, 'content'), 160),
            'canonical' => page_url('/' . $slug . '/'),
            'alternates' => $fallbackDe ? [] : self::alternates('/' . $slug . '/'),
            'robots' => $slug === 'datenschutz' || $slug === 'impressum' ? 'noindex,follow' : null,
            'body_class' => 'page-legal',
        ]);
    }

    public static function notFound(): never
    {
        View::page('404', [], ['title' => tr('not_found_title') . ' — ' . setting('site_name'), 'robots' => 'noindex,nofollow', 'body_class' => 'page-404'], 404);
    }

    /* ------------------------------------------------------------ Aktionen */

    private static function wantsJson(): bool
    {
        return str_contains((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
    }

    private static function reply(bool $ok, string $message, string $redirect, array $extra = []): never
    {
        if (self::wantsJson()) {
            json_out(['ok' => $ok, 'message' => $message] + $extra, $ok ? 200 : 422);
        }
        redirect($redirect);
    }

    private static function contact(): never
    {
        $ok = u('/') . '?sent=1#kontakt';
        $fail = static fn(string $key) => self::reply(false, tr($key), u('/') . '?err=' . $key . '#kontakt', ['code' => $key]);

        $name = trim(preg_replace('/[\x00-\x1f\x7f]+/', ' ', (string)($_POST['name'] ?? '')) ?? '');
        $email = trim((string)($_POST['email'] ?? ''));
        $subject = trim(preg_replace('/[\x00-\x1f\x7f]+/', ' ', (string)($_POST['subject'] ?? '')) ?? '');
        $message = trim(str_replace("\0", '', (string)($_POST['message'] ?? '')));

        // Honeypot + Zeit-Token: Bots bekommen eine "Erfolgs"-Antwort, damit sie nichts lernen
        if (trim((string)($_POST['website'] ?? '')) !== '') {
            self::reply(true, s('contact_success'), $ok);
        }
        if (!FormToken::verify((string)($_POST['_t'] ?? ''), 'contact')) {
            $fail('err_spam');
        }
        if ($name === '' || $email === '' || $message === '') {
            $fail('err_required');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            $fail('err_email');
        }
        if (mb_strlen($name) > 120 || mb_strlen($subject) > 200 || mb_strlen($message) < 10 || mb_strlen($message) > 5000) {
            $fail('err_length');
        }
        $ipKey = 'contact:' . substr(ip_hash('contact'), 0, 32);
        if (!RateLimit::hit($ipKey, 5, 3600) || !RateLimit::hit('contact:all', 60, 3600)) {
            $fail('err_rate');
        }

        $id = Db::insert('messages', [
            'name' => $name, 'email' => $email, 'subject' => $subject, 'message' => $message,
            'status' => 'new', 'lang' => Lang::$code, 'created_at' => now(),
        ]);

        $notify = setting('contact_notify') ?: setting('contact_email');
        $mailBody = "Neue Nachricht über " . page_url('/') . "\n\n"
            . "Von:     $name <$email>\nBetreff: " . ($subject ?: '(ohne Betreff)') . "\nSprache: " . strtoupper(Lang::$code) . "\n\n"
            . $message . "\n\n—\nIm Admin-Bereich ansehen: " . abs_url('/admin/?p=messages&id=' . $id) . "\n";
        Mailer::send($notify, '[' . setting('site_name') . '] ' . ($subject ?: 'Neue Nachricht von ' . $name), $mailBody, $email);

        self::reply(true, s('contact_success'), $ok);
    }

    private static function comment(string $slug): never
    {
        $post = Content::post($slug);
        $base = u('/blog/' . $slug . '/');
        $back = $base . '#kommentare';
        if (!$post || !sb('comments_enabled', true) || !(int)$post['allow_comments']) {
            self::reply(false, tr('comments_closed'), $back);
        }
        $name = trim(preg_replace('/[\x00-\x1f\x7f]+/', ' ', (string)($_POST['name'] ?? '')) ?? '');
        $text = trim(str_replace("\0", '', (string)($_POST['message'] ?? '')));

        if (trim((string)($_POST['website'] ?? '')) !== '') {
            self::reply(true, tr('comment_thanks'), $back);
        }
        if (!FormToken::verify((string)($_POST['_t'] ?? ''), 'comment')) {
            self::reply(false, tr('err_spam'), $back);
        }
        if ($name === '' || $text === '') {
            self::reply(false, tr('err_required'), $back);
        }
        if (mb_strlen($name) > 80 || mb_strlen($text) < 3 || mb_strlen($text) > 1200) {
            self::reply(false, tr('err_length'), $back);
        }
        if (preg_match('~(https?:|www\.|://|\b[a-z0-9-]+\.(com|net|org|de|ru|info|xyz|top|link)\b)~i', $text . ' ' . $name)) {
            self::reply(false, tr('err_links'), $back);
        }
        if (!RateLimit::hit('comment:' . substr(ip_hash('comment'), 0, 32), 3, 3600)) {
            self::reply(false, tr('err_rate'), $back);
        }
        $moderate = sb('comments_moderation', true);
        Db::insert('comments', [
            'post_id' => $post['id'], 'name' => $name, 'message' => $text,
            'status' => $moderate ? 'pending' : 'approved', 'created_at' => now(),
        ]);
        $msg = $moderate ? tr('comment_thanks') : tr('comment_posted');
        if ($moderate) {
            $notify = setting('contact_notify') ?: setting('contact_email');
            Mailer::send($notify, '[' . setting('site_name') . '] Neuer Kommentar wartet auf Freigabe',
                "Beitrag: " . t($post, 'title') . "\nVon: $name\n\n$text\n\nFreigeben: " . abs_url('/admin/?p=comments') . "\n");
        }
        self::reply(true, $msg, $base . '?c=1#kommentare', ['moderated' => $moderate]);
    }

    private static function like(string $slug): never
    {
        $post = Content::post($slug);
        if (!$post || !sb('likes_enabled', true)) {
            json_out(['ok' => false], 404);
        }
        $key = 'like:' . $post['id'] . ':' . substr(ip_hash('like'), 0, 24);
        if (RateLimit::hit($key, 1, 86400)) {
            Db::q('UPDATE posts SET likes = likes + 1 WHERE id = ?', [$post['id']]);
            $post['likes']++;
            json_out(['ok' => true, 'likes' => (int)$post['likes'], 'already' => false]);
        }
        json_out(['ok' => true, 'likes' => (int)$post['likes'], 'already' => true]);
    }

    /* ------------------------------------------------------------ Feeds */

    private static function sitemap(): never
    {
        header('Content-Type: application/xml; charset=utf-8');
        $urls = [];
        $add = static function (string $path, ?string $lastmod = null, string $freq = 'monthly', string $prio = '0.6', bool $bilingual = true) use (&$urls): void {
            $urls[] = compact('path', 'lastmod', 'freq', 'prio', 'bilingual');
        };
        $add('/', null, 'weekly', '1.0');
        $add('/blog/', null, 'weekly', '0.8');
        $add('/projekte/', null, 'monthly', '0.8');
        foreach (Content::projects() as $p) {
            $add('/projekt/' . $p['slug'] . '/', null, 'monthly', '0.6');
        }
        foreach (Content::posts() as $p) {
            $add('/blog/' . $p['slug'] . '/', substr((string)$p['updated_at'], 0, 10), 'yearly', '0.7', trim((string)$p['content_en']) !== '');
        }
        foreach (Content::footerPages() as $pg) {
            $add('/' . $pg['slug'] . '/', null, 'yearly', '0.2', false);
        }
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
        foreach ($urls as $u) {
            foreach (($u['bilingual'] ? ['de', 'en'] : ['de']) as $lang) {
                echo "  <url>\n    <loc>" . e(page_url($u['path'], $lang)) . "</loc>\n";
                if ($u['lastmod']) {
                    echo '    <lastmod>' . e($u['lastmod']) . "</lastmod>\n";
                }
                echo '    <changefreq>' . $u['freq'] . "</changefreq>\n    <priority>" . $u['prio'] . "</priority>\n";
                if ($u['bilingual']) {
                    echo '    <xhtml:link rel="alternate" hreflang="de" href="' . e(page_url($u['path'], 'de')) . "\"/>\n";
                    echo '    <xhtml:link rel="alternate" hreflang="en" href="' . e(page_url($u['path'], 'en')) . "\"/>\n";
                }
                echo "  </url>\n";
            }
        }
        echo "</urlset>\n";
        exit;
    }

    private static function feed(): never
    {
        header('Content-Type: application/rss+xml; charset=utf-8');
        $posts = Content::posts(20);
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"><channel>' . "\n";
        echo '<title>' . e(setting('site_name') . ' — ' . s('blog_title')) . "</title>\n";
        echo '<link>' . e(page_url('/blog/')) . "</link>\n<description>" . e(s('blog_intro')) . "</description>\n";
        echo '<language>' . Lang::$code . "</language>\n";
        echo '<atom:link href="' . e(page_url('/feed.xml')) . '" rel="self" type="application/rss+xml"/>' . "\n";
        foreach ($posts as $p) {
            $url = page_url('/blog/' . $p['slug'] . '/');
            echo "<item>\n<title>" . e(t($p, 'title')) . "</title>\n<link>" . e($url) . "</link>\n<guid isPermaLink=\"true\">" . e($url) . "</guid>\n";
            echo '<pubDate>' . date(DATE_RSS, strtotime((string)$p['published_at'])) . "</pubDate>\n";
            echo '<description>' . e(t($p, 'excerpt') ?: excerpt(t($p, 'content'), 300)) . "</description>\n</item>\n";
        }
        echo "</channel></rss>\n";
        exit;
    }

    private static function robots(): never
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\n";
        echo sb('robots_index', true) ? "Allow: /\nDisallow: /admin/\nDisallow: /install/\n" : "Disallow: /\n";
        echo 'Sitemap: ' . abs_url('/sitemap.xml') . "\n";
        exit;
    }

    /* ------------------------------------------------------------ Helfer */

    private static function visitPath(): string
    {
        return (Lang::$code === 'en' ? '/en' : '') . rtrim(self::$path, '/') . '/';
    }

    /** hreflang-Alternativen für einen App-Pfad. */
    public static function alternates(string $path): array
    {
        return ['de' => page_url($path, 'de'), 'en' => page_url($path, 'en'), 'x-default' => page_url($path, 'de')];
    }

    private static function personSchema(): array
    {
        $same = array_values(array_filter(array_map(static fn($r) => $r['url'], Content::socials())));
        return [
            '@context' => 'https://schema.org', '@type' => 'Person',
            'name' => setting('hero_name', 'Frank Panzer'),
            'url' => page_url('/'),
            'jobTitle' => s('job_title'),
            'image' => abs_url(media_url(setting('hero_image'))),
            'email' => 'mailto:' . setting('contact_email'),
            'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Bremen', 'addressCountry' => 'DE'],
            'worksFor' => ['@type' => 'Organization', 'name' => 'Panzer IT', 'url' => 'https://panzerit.de'],
            'sameAs' => $same,
        ];
    }

    private static function websiteSchema(): array
    {
        return [
            '@context' => 'https://schema.org', '@type' => 'WebSite',
            'name' => setting('site_name', 'Frank Panzer'), 'url' => page_url('/'), 'inLanguage' => Lang::$code,
        ];
    }
}
