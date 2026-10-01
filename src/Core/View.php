<?php

declare(strict_types=1);

namespace Pegelstand\Core;

use RuntimeException;

/**
 * Templates in plain PHP. Ausgaben laufen konsequent durch e() oder t(), die HTML maskieren.
 * Nur icon() und die Ergebnisse von Teil-Templates sind bereits sicheres HTML.
 */
final class View
{
    public function __construct(
        private readonly string $dir,
        private readonly Translator $translator,
        private readonly string $basePath = '',
    ) {}

    public function e(mixed $wert): string
    {
        return htmlspecialchars(is_scalar($wert) || $wert === null ? (string) $wert : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Übersetzter Text, bereits HTML-maskiert.
     *
     * @param array<string, scalar> $params
     */
    public function t(string $key, array $params = []): string
    {
        return $this->e($this->translator->get($key, $params));
    }

    /**
     * Übersetzter Text ohne Maskierung, zur Übergabe an Teil-Templates, die selbst maskieren.
     *
     * @param array<string, scalar> $params
     */
    public function translate(string $key, array $params = []): string
    {
        return $this->translator->get($key, $params);
    }

    public function url(string $pfad): string
    {
        return $this->basePath . '/' . ltrim($pfad, '/');
    }

    public function asset(string $pfad): string
    {
        return $this->e($this->basePath . '/assets/' . ltrim($pfad, '/'));
    }

    public function assetsBasis(): string
    {
        return $this->e($this->basePath . '/assets');
    }

    /** Icon aus dem Sprite (siehe bin/build-assets.mjs). */
    public function icon(string $name, string $groesse = ''): string
    {
        $klasse = 'ps-icon' . ($groesse === 's' ? ' ps-icon--s' : ($groesse === 'l' ? ' ps-icon--l' : ''));

        return sprintf(
            '<svg class="%s" aria-hidden="true" focusable="false"><use href="%s/icons.svg#ps-icon-%s"/></svg>',
            $klasse,
            $this->assetsBasis(),
            $this->e($name),
        );
    }

    /**
     * @param array<string, mixed> $vars
     */
    public function render(string $template, array $vars = [], ?string $layout = 'layout'): string
    {
        $inhalt = $this->include($template, $vars);
        if ($layout === null) {
            return $inhalt;
        }

        return $this->include($layout, [...$vars, 'inhalt' => $inhalt]);
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function include(string $template, array $vars): string
    {
        if (preg_match('#^[a-z0-9/_-]+$#i', $template) !== 1) {
            throw new RuntimeException('Ungültiger Template-Name.');
        }
        $datei = $this->dir . '/' . $template . '.php';
        if (!is_file($datei)) {
            throw new RuntimeException(sprintf('Template "%s" fehlt.', $template));
        }
        // Die Closure ist an die View gebunden, Templates erreichen sie über $this.
        $ausgabe = function (string $datei, array $vars): string {
            extract($vars, EXTR_SKIP);
            ob_start();
            try {
                include $datei;
            } catch (\Throwable $fehler) {
                ob_end_clean();
                throw $fehler;
            }

            return (string) ob_get_clean();
        };

        return $ausgabe($datei, $vars);
    }
}
