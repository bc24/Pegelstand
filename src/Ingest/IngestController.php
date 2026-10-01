<?php

declare(strict_types=1);

namespace Pegelstand\Ingest;

use Pegelstand\Core\Request;
use Pegelstand\Core\Response;
use Pegelstand\Core\Router;

/**
 * Öffentliche Schnittstelle für das Tracking: der Endpunkt für Messpunkte und die Auslieferung des Scripts.
 */
final class IngestController
{
    private const CORS = [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'POST, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type',
        'Access-Control-Max-Age' => '86400',
    ];

    public function __construct(
        private readonly Collector $collector,
        private readonly string $scriptFile,
        private readonly string $scriptPath = '/p.js',
        private readonly string $endpointPath = '/api/event',
    ) {}

    public function register(Router $router): void
    {
        $router->add('POST', $this->endpointPath, fn(Request $r): Response => $this->event($r));
        $router->add('OPTIONS', $this->endpointPath, static fn(): Response => new Response('', 204, [...self::CORS, 'Cache-Control' => 'public, max-age=86400']));
        $router->add('GET', $this->scriptPath, fn(Request $r): Response => $this->script($r));
    }

    private function event(Request $request): Response
    {
        $ergebnis = $this->collector->collect($request);

        return new Response('', $ergebnis->status(), [
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function script(Request $request): Response
    {
        $inhalt = is_file($this->scriptFile) ? file_get_contents($this->scriptFile) : false;
        if ($inhalt === false) {
            return new Response('', 404, ['Cache-Control' => 'no-store']);
        }
        $etag = '"' . md5($inhalt) . '"';
        $kopf = [
            'Content-Type' => 'text/javascript; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
            'ETag' => $etag,
            'Cross-Origin-Resource-Policy' => 'cross-origin',
        ];
        if ($request->header('If-None-Match') === $etag) {
            return new Response('', 304, $kopf);
        }

        return new Response($inhalt, 200, $kopf);
    }
}
