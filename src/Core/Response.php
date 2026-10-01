<?php

declare(strict_types=1);

namespace Pegelstand\Core;

/**
 * Eine Antwort. Sicherheits-Header setzt send() für jede Antwort, sofern sie nicht überschrieben werden.
 */
final class Response
{
    /** @var array<string, string> */
    public const SECURITY_HEADERS = [
        'Content-Security-Policy' => "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; "
            . "font-src 'self'; connect-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'",
        'X-Frame-Options' => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'same-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
    ];

    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public readonly string $body = '',
        public readonly int $status = 200,
        public readonly array $headers = [],
    ) {}

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    public static function redirect(string $url, int $status = 303): self
    {
        return new self('', $status, ['Location' => $url, 'Cache-Control' => 'no-store']);
    }

    public function withHeader(string $name, string $value): self
    {
        return new self($this->body, $this->status, [...$this->headers, $name => $value]);
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ([...self::SECURITY_HEADERS, ...$this->headers] as $name => $value) {
            header($name . ': ' . $value);
        }
        echo $this->body;
    }
}
