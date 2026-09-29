<?php
declare(strict_types=1);

/** Volltextsuche über sichtbare Projekte und veröffentlichte Blog-Beiträge (LIKE, alle Suchbegriffe müssen vorkommen). */
final class Search
{
    public const MIN = 2;
    private const MAX_TERMS = 5;
    private const LIMIT = 60;

    /** @return string[] */
    public static function terms(string $q): array
    {
        $out = [];
        foreach (preg_split('/\s+/u', trim($q)) ?: [] as $t) {
            $t = mb_strtolower(trim($t));
            if ($t !== '' && !in_array($t, $out, true)) {
                $out[] = $t;
            }
        }
        return array_slice($out, 0, self::MAX_TERMS);
    }

    /** @return array{projects: array, posts: array} */
    public static function run(array $terms): array
    {
        return [
            'projects' => self::projects($terms),
            'posts' => self::posts($terms),
        ];
    }

    private static function where(array $cols, array $terms, array &$params): string
    {
        $parts = [];
        foreach ($terms as $t) {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $t) . '%';
            $or = [];
            foreach ($cols as $c) {
                $or[] = "`$c` LIKE ?";
                $params[] = $like;
            }
            $parts[] = '(' . implode(' OR ', $or) . ')';
        }
        return implode(' AND ', $parts);
    }

    private static function hay(array $row, array $fields): string
    {
        $s = '';
        foreach ($fields as $f) {
            $s .= ' ' . strip_tags((string)($row[$f] ?? ''));
        }
        return mb_strtolower(html_entity_decode($s, ENT_QUOTES, 'UTF-8'));
    }

    /** Punkte: Treffer im Titel zählen am meisten. */
    private static function score(array $row, array $terms, array $title, array $mid, array $body): int
    {
        $t = self::hay($row, $title);
        $m = self::hay($row, $mid);
        $b = self::hay($row, $body);
        $score = 0;
        foreach ($terms as $term) {
            $score += (str_contains($t, $term) ? 5 : 0) + (str_contains($m, $term) ? 2 : 0) + (str_contains($b, $term) ? 1 : 0);
        }
        return $score;
    }

    private static function projects(array $terms): array
    {
        $params = [];
        $w = self::where(['title', 'tagline_de', 'tagline_en', 'description_de', 'description_en', 'tech', 'meta_de', 'meta_en', 'badge_de', 'badge_en'], $terms, $params);
        $rows = Db::all('SELECT * FROM projects WHERE visible = 1 AND ' . $w . ' LIMIT ' . self::LIMIT, $params);
        foreach ($rows as &$r) {
            $r['_score'] = self::score($r, $terms, ['title'], ['tagline_de', 'tagline_en', 'tech', 'badge_de', 'badge_en', 'meta_de', 'meta_en'], ['description_de', 'description_en']);
        }
        unset($r);
        usort($rows, static fn($a, $b) => [$b['_score'], $a['sort']] <=> [$a['_score'], $b['sort']]);
        return $rows;
    }

    private static function posts(array $terms): array
    {
        $params = [now()];
        $w = self::where(['title_de', 'title_en', 'excerpt_de', 'excerpt_en', 'content_de', 'content_en', 'category_de', 'category_en'], $terms, $params);
        $rows = Db::all("SELECT * FROM posts WHERE status = 'published' AND published_at <= ? AND " . $w . ' LIMIT ' . self::LIMIT, $params);
        foreach ($rows as &$r) {
            $r['_score'] = self::score($r, $terms, ['title_de', 'title_en'], ['excerpt_de', 'excerpt_en', 'category_de', 'category_en'], ['content_de', 'content_en']);
        }
        unset($r);
        usort($rows, static fn($a, $b) => [$b['_score'], $b['published_at']] <=> [$a['_score'], $a['published_at']]);
        return $rows;
    }

    /** Textauszug rund um den ersten Treffer. */
    public static function snippet(string $html, array $terms, int $len = 190): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8')) ?? '');
        if (mb_strlen($text) <= $len) {
            return $text;
        }
        $pos = null;
        foreach ($terms as $t) {
            $p = mb_stripos($text, $t);
            if ($p !== false && ($pos === null || $p < $pos)) {
                $pos = $p;
            }
        }
        $start = $pos === null ? 0 : max(0, $pos - 60);
        $out = mb_substr($text, $start, $len);
        return ($start > 0 ? '…' : '') . trim($out) . ($start + $len < mb_strlen($text) ? '…' : '');
    }

    /** Text HTML-sicher ausgeben und Suchbegriffe mit <mark> hervorheben. */
    public static function mark(string $text, array $terms): string
    {
        $safe = e($text);
        $pats = array_map(static fn($t) => preg_quote(e($t), '~'), $terms);
        if (!$pats) {
            return $safe;
        }
        // HTML-Entities (&amp; usw.) nicht zerschneiden
        $chunks = preg_split('~(&(?:[a-z]+|#\d+|#x[0-9a-f]+);)~i', $safe, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$safe];
        foreach ($chunks as $i => $c) {
            if ($i % 2 === 0) {
                $chunks[$i] = preg_replace('~(' . implode('|', $pats) . ')~iu', '<mark>$1</mark>', $c) ?? $c;
            }
        }
        return implode('', $chunks);
    }
}
