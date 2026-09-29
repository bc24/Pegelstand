<?php
declare(strict_types=1);

/** Lesezugriff auf alle Inhalte für die öffentliche Seite. */
final class Content
{
    /** Sichtbare Zeilen einer Tabelle in Anzeige-Reihenfolge. */
    public static function visible(string $table, string $order = 'sort ASC, id ASC'): array
    {
        return Db::all("SELECT * FROM `$table` WHERE visible = 1 ORDER BY $order");
    }

    public static function sections(): array
    {
        return Db::all('SELECT * FROM sections WHERE visible = 1 ORDER BY sort ASC, id ASC');
    }

    /** Alle Abschnitte (auch versteckte) nach Schlüssel. */
    public static function sectionMap(): array
    {
        $map = [];
        foreach (Db::all('SELECT * FROM sections') as $s) {
            $map[$s['skey']] = $s;
        }
        return $map;
    }

    public static function nav(): array
    {
        return array_values(array_filter(self::sections(), static fn($s) => (int)$s['in_nav'] === 1));
    }

    public static function stats(): array
    {
        $rows = self::visible('stats');
        $since = (int)setting('online_since', '2001');
        foreach ($rows as &$r) {
            if ((int)$r['auto_years'] === 1) {
                $r['count_to'] = (float)max(0, (int)date('Y') - $since);
            }
        }
        return $rows;
    }

    public static function skillGroups(): array
    {
        $groups = self::visible('skill_groups');
        $skills = self::visible('skills');
        $by = [];
        foreach ($skills as $s) {
            $by[(int)$s['group_id']][] = $s;
        }
        foreach ($groups as &$g) {
            $g['skills'] = $by[(int)$g['id']] ?? [];
        }
        return $groups;
    }

    public static function socials(?bool $heroOnly = null): array
    {
        $rows = self::visible('social_links');
        if ($heroOnly === true) {
            return array_values(array_filter($rows, static fn($r) => (int)$r['in_hero'] === 1));
        }
        return $rows;
    }

    public static function projects(bool $featuredOnly = false): array
    {
        return Db::all('SELECT * FROM projects WHERE visible = 1' . ($featuredOnly ? ' AND featured = 1' : '') . ' ORDER BY featured DESC, sort ASC, id ASC');
    }

    public static function project(string $slug): ?array
    {
        return Db::one('SELECT * FROM projects WHERE slug = ? AND visible = 1', [$slug]);
    }

    public static function footerPages(): array
    {
        return Db::all('SELECT slug, title_de, title_en FROM pages WHERE visible = 1 AND in_footer = 1 ORDER BY sort ASC, id ASC');
    }

    public static function page(string $slug): ?array
    {
        return Db::one('SELECT * FROM pages WHERE slug = ? AND visible = 1', [$slug]);
    }

    public static function posts(int $limit = 0, int $offset = 0): array
    {
        $sql = "SELECT * FROM posts WHERE status = 'published' AND published_at <= NOW() ORDER BY published_at DESC, id DESC";
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;
        }
        return Db::all($sql);
    }

    public static function post(string $slug): ?array
    {
        return Db::one("SELECT * FROM posts WHERE slug = ? AND status = 'published' AND published_at <= NOW()", [$slug]);
    }

    /** @return array{prev:?array,next:?array} älterer/neuerer Beitrag */
    public static function neighbours(array $post): array
    {
        $prev = Db::one(
            "SELECT slug, title_de, title_en FROM posts WHERE status='published' AND published_at <= NOW() AND (published_at < ? OR (published_at = ? AND id < ?)) ORDER BY published_at DESC, id DESC LIMIT 1",
            [$post['published_at'], $post['published_at'], $post['id']]
        );
        $next = Db::one(
            "SELECT slug, title_de, title_en FROM posts WHERE status='published' AND published_at <= NOW() AND (published_at > ? OR (published_at = ? AND id > ?)) ORDER BY published_at ASC, id ASC LIMIT 1",
            [$post['published_at'], $post['published_at'], $post['id']]
        );
        return ['prev' => $prev, 'next' => $next];
    }

    public static function comments(int $postId): array
    {
        return Db::all("SELECT name, message, created_at FROM comments WHERE post_id = ? AND status = 'approved' ORDER BY created_at ASC", [$postId]);
    }

    /** Kategorien (Schlüssel → Anzeigename) aus den vorhandenen Projekten. */
    public static function projectCategories(array $projects): array
    {
        $cats = [];
        foreach ($projects as $p) {
            $cats[$p['category']] = true;
        }
        $order = ['web', 'shop', 'game', 'tool'];
        $out = [];
        foreach ($order as $c) {
            if (isset($cats[$c])) {
                $out[$c] = tr('cat_' . $c);
            }
        }
        foreach (array_keys($cats) as $c) {
            if (!isset($out[$c])) {
                $out[$c] = ucfirst($c);
            }
        }
        return $out;
    }
}
