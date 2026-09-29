<?php
declare(strict_types=1);

/** Whitelist-basierte HTML-Bereinigung für Rich-Text (Blog, Seiten, FAQ). */
final class Html
{
    private const ALLOWED = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'ul', 'ol', 'li', 'h2', 'h3', 'h4', 'blockquote', 'a', 'img',
        'code', 'pre', 'hr', 'span', 'div', 'figure', 'figcaption', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'sub', 'sup', 'mark',
    ];
    private const DROP = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'svg', 'math',
        'link', 'meta', 'base', 'noscript', 'template', 'audio', 'video', 'canvas', 'applet', 'frame', 'frameset'];
    private const ATTRS = [
        'a'   => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'width', 'height', 'loading'],
        'th'  => ['colspan', 'rowspan'],
        'td'  => ['colspan', 'rowspan'],
    ];

    public static function sanitize(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }
        $prev = libxml_use_internal_errors(true);
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="fp-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = (new DOMXPath($doc))->query('//div[@id="fp-root"]')->item(0);
        if (!$root instanceof DOMElement) {
            return e(strip_tags($html));
        }
        self::clean($root);
        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }
        return trim($out);
    }

    private static function clean(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMComment) {
                $node->removeChild($child);
                continue;
            }
            if (!$child instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($child->tagName);
            if (in_array($tag, self::DROP, true)) {
                $node->removeChild($child);
                continue;
            }
            self::clean($child);
            if (!in_array($tag, self::ALLOWED, true)) {
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }
            self::cleanAttributes($child, $tag);
        }
    }

    private static function cleanAttributes(DOMElement $el, string $tag): void
    {
        $allowed = self::ATTRS[$tag] ?? [];
        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->name);
            $value = $attr->value;
            if ($name === 'class') {
                $tokens = array_filter(explode(' ', $value), static fn($t) => (bool)preg_match('/^ql-[a-z0-9-]+$/', $t));
                if ($tokens) {
                    $el->setAttribute('class', implode(' ', $tokens));
                } else {
                    $el->removeAttribute('class');
                }
                continue;
            }
            if (!in_array($name, $allowed, true)) {
                $el->removeAttribute($attr->name);
                continue;
            }
            if ($name === 'href' && !self::safeUrl($value, true)) {
                $el->removeAttribute('href');
            }
            if ($name === 'src' && !self::safeUrl($value, false)) {
                $el->removeAttribute('src');
            }
            if ($name === 'target' && !in_array($value, ['_blank', '_self'], true)) {
                $el->removeAttribute('target');
            }
        }
        if ($tag === 'a' && $el->getAttribute('target') === '_blank') {
            $el->setAttribute('rel', 'noopener noreferrer');
        }
        if ($tag === 'img' && !$el->hasAttribute('loading')) {
            $el->setAttribute('loading', 'lazy');
        }
    }

    private static function safeUrl(string $url, bool $allowMail): bool
    {
        $u = preg_replace('/[\x00-\x20\x7f]+/', '', $url) ?? '';
        if ($u === '') {
            return false;
        }
        if (preg_match('#^(https?:)?//#i', $u) || str_starts_with($u, '/') || str_starts_with($u, '#') || str_starts_with($u, '?')) {
            return true;
        }
        if ($allowMail && preg_match('/^(mailto|tel):/i', $u)) {
            return true;
        }
        // relative Pfade ohne Schema (z. B. uploads/2026/..)
        return !preg_match('/^[a-z][a-z0-9+.-]*:/i', $u);
    }

    /**
     * Vergibt IDs an <h2>/<h3> und liefert ein Inhaltsverzeichnis.
     * @return array{0:string,1:array<int,array{id:string,text:string,level:int}>}
     */
    public static function withToc(string $html): array
    {
        $toc = [];
        $used = [];
        $html = preg_replace_callback('~<h([23])>(.*?)</h\1>~s', static function (array $m) use (&$toc, &$used): string {
            $text = trim(html_entity_decode(strip_tags($m[2]), ENT_QUOTES, 'UTF-8'));
            $id = slugify($text);
            $base = $id;
            $n = 2;
            while (isset($used[$id])) {
                $id = $base . '-' . $n++;
            }
            $used[$id] = true;
            $toc[] = ['id' => $id, 'text' => $text, 'level' => (int)$m[1]];
            return '<h' . $m[1] . ' id="' . $id . '">' . $m[2] . '</h' . $m[1] . '>';
        }, $html) ?? $html;
        return [$html, $toc];
    }
}
