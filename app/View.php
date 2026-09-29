<?php
declare(strict_types=1);

final class View
{
    /** Meta-Angaben der aktuellen Seite (Titel, Beschreibung, Canonical, …) */
    public static array $meta = [];

    public static function render(string $tpl, array $vars = []): string
    {
        extract($vars, EXTR_SKIP);
        ob_start();
        include FP_ROOT . '/views/' . $tpl . '.php';
        return (string)ob_get_clean();
    }

    /** Seite im Layout ausgeben. */
    public static function page(string $tpl, array $vars = [], array $meta = [], int $status = 200): never
    {
        self::$meta = $meta + self::$meta;
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        Security::headers();
        $content = self::render($tpl, $vars);
        echo self::render('layout', ['content' => $content, 'meta' => self::$meta]);
        exit;
    }

    /** Abschnittskopf: Eyebrow, Titel, Einleitung. */
    public static function head(array $sec, string $extraClass = ''): string
    {
        $label = t($sec, 'label');
        $title = t($sec, 'title');
        $intro = t($sec, 'intro');
        $html = '<header class="sec-head ' . e($extraClass) . '" data-reveal>';
        if ($label !== '') {
            $html .= '<span class="eyebrow"><i class="eyebrow-dot"></i>' . e($label) . '</span>';
        }
        if ($title !== '') {
            $html .= '<h2 class="sec-title" data-split-words>' . e($title) . '</h2>';
        }
        if ($intro !== '') {
            $html .= '<p class="sec-intro">' . e($intro) . '</p>';
        }
        return $html . '</header>';
    }

    /** Link zu einem Abschnitt (Anker auf der Startseite). */
    public static function sectionHref(array $sec): string
    {
        if ($sec['skey'] === 'blog') {
            return u('/blog/');
        }
        return u('/') . '#' . $sec['anchor'];
    }

    public static function json(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?: '{}';
    }
}
