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
     * @param array<string, string> $headers Kopfzeilen mit klein geschriebenen Namen, z. B. "user-agent"
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly string $basePath = '',
        public readonly array $query = [],
        public readonly array $post = [],
        public readonly bool $https = false,
        public readonly array $headers = [],
        public readonly string $ip = '',
        public readonly string $body = '',
    ) {}

    /**
     * @param array<mixed> $server
     * @param array<mixed> $query
     * @param array<mixed> $post
     */
    public static function fromGlobals(array $server, array $query, array $post, string $body = ''): self
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
            self::headersFrom($server),
            is_string($server['REMOTE_ADDR'] ?? null) ? $server['REMOTE_ADDR'] : '',
            $body,
        );
    }

    /**
     * @param array<mixed> $server
     * @return array<string, string>
     */
    private static function headersFrom(array $server): array
    {
        $headers = [];
        foreach ($server as $name => $wert) {
            if (!is_string($name) || !is_string($wert)) {
                continue;
            }
            if (str_starts_with($name, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($name, 5)))] = $wert;
            } elseif ($name === 'CONTENT_TYPE') {
                $headers['content-type'] = $wert;
            }
        }

        return $headers;
    }

    public function header(string $name): string
    {
        return $this->headers[strtolower($name)] ?? '';
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
