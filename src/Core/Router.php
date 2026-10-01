<?php

declare(strict_types=1);

namespace Pegelstand\Core;

use Closure;

/**
 * Einfacher Router mit festen Pfaden und Platzhaltern wie /sites/{id}.
 */
final class Router
{
    /** @var list<array{method: string, pattern: string, handler: Closure(Request, array<string, string>): Response}> */
    private array $routes = [];

    /**
     * @param Closure(Request, array<string, string>): Response $handler
     */
    public function add(string $method, string $pfad, Closure $handler): void
    {
        $muster = '';
        foreach (preg_split('#(\{[a-z_]+\})#i', $pfad, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [] as $teil) {
            $muster .= preg_match('#^\{([a-z_]+)\}$#i', $teil, $name) === 1
                ? '(?P<' . $name[1] . '>[^/]+)'
                : preg_quote($teil, '#');
        }
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => '#^' . $muster . '$#',
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): ?Response
    {
        $erlaubt = [];
        foreach ($this->routes as $route) {
            if (preg_match($route['pattern'], $request->path, $treffer) !== 1) {
                continue;
            }
            if ($route['method'] !== $request->method) {
                $erlaubt[] = $route['method'];
                continue;
            }
            $parameter = [];
            foreach ($treffer as $name => $wert) {
                if (is_string($name)) {
                    $parameter[$name] = (string) $wert;
                }
            }

            return ($route['handler'])($request, $parameter);
        }
        if ($erlaubt !== []) {
            return new Response('', 405, ['Allow' => implode(', ', array_unique($erlaubt))]);
        }

        return null;
    }
}
