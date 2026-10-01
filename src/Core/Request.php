<?php

declare(strict_types=1);

namespace Pegelstand\Core;

/**
 * Eine eingehende Anfrage. Der Pfad ist relativ zum Installationsverzeichnis, damit
 * Pegelstand auch in einem Unterverzeichnis läuft.
 */
final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly string $basePath = '',
        public readonly array $query = [],
        public readonly array $post = [],
        public readonly bool $https = false,
    ) {}

    /**
     * @param array<mixed> $server
     * @param array<mixed> $query
     * @param array<mixed> $post
     */
    public static function fromGlobals(array $server, array $query, array $post): self
    {
        $skript = is_string($server['SCRIPT_NAME'] ?? null) ? $server['SCRIPT_NAME'] : '/index.php';
        $basis = rtrim(str_replace('\\', '/', dirname($skript)), '/');
        $uri = is_string($server['REQUEST_URI'] ?? null) ? $server['REQUEST_URI'] : '/';
        $pfad = rawurldecode((string) parse_url($uri, PHP_URL_PATH));
        if ($basis !== '' && str_starts_with($pfad, $basis)) {
            $pfad = substr($pfad, strlen($basis));
        }
        // index.php im Pfad (z. B. ohne Rewrite) wird wie das Wurzelverzeichnis behandelt.
        if ($pfad === '/index.php') {
            $pfad = '/';
        }
        $pfad = '/' . trim($pfad, '/');
        $methode = is_string($server['REQUEST_METHOD'] ?? null) ? strtoupper($server['REQUEST_METHOD']) : 'GET';
        $https = (!empty($server['HTTPS']) && $server['HTTPS'] !== 'off')
            || ($server['SERVER_PORT'] ?? null) === '443';

        return new self(
            $methode,
            $pfad,
            $basis,
            array_filter($query, 'is_string', ARRAY_FILTER_USE_KEY),
            array_filter($post, 'is_string', ARRAY_FILTER_USE_KEY),
            $https,
        );
    }

    /**
     * Adresse innerhalb der Installation, berücksichtigt das Unterverzeichnis.
     */
    public function url(string $pfad): string
    {
        return $this->basePath . '/' . ltrim($pfad, '/');
    }

    public function input(string $name, string $default = ''): string
    {
        $wert = $this->post[$name] ?? $default;

        return is_string($wert) ? $wert : $default;
    }
}
