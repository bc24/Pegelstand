<?php

declare(strict_types=1);

namespace Pegelstand\Api;

use DateTimeImmutable;
use DateTimeZone;
use Pegelstand\Auth\AuthService;
use Pegelstand\Auth\AuthUser;
use Pegelstand\Core\Request;
use Pegelstand\Core\Response;
use Pegelstand\Core\Router;
use Pegelstand\Ingest\RateLimiter;
use Pegelstand\Settings\ApiKeyRepository;
use Pegelstand\Stats\DashboardService;

/**
 * Lesende REST-Schnittstelle (Version 1). Anmeldung per `Authorization: Bearer psk_…`. Ein Schlüssel sieht dieselben Websites
 * wie sein Benutzer. Antworten sind JSON, Fehler haben die Form {"fehler": "..."}.
 */
final class ApiController
{
    private const FILTER_TYPEN = ['seite', 'einstieg', 'ausstieg', 'quelle', 'kampagne', 'land', 'geraet', 'browser', 'os', 'ziel', 'ereignis'];

    public function __construct(
        private readonly ApiKeyRepository $keys,
        private readonly AuthService $auth,
        private readonly DashboardService $service,
        private readonly RateLimiter $limiter,
        private readonly int $limit = 120,
    ) {}

    public function register(Router $r): void
    {
        $r->add('GET', '/api/v1/sites', fn(Request $q): Response => $this->gesichert($q, fn(AuthUser $u): Response => $this->sites($u)));
        $r->add('GET', '/api/v1/sites/{id}/stats', fn(Request $q, array $p): Response => $this->gesichert($q, fn(AuthUser $u): Response => $this->stats($q, $u, $p['id'])));
    }

    /**
     * @param callable(AuthUser): Response $weiter
     */
    private function gesichert(Request $q, callable $weiter): Response
    {
        $kopf = $q->header('Authorization');
        $schluessel = preg_match('/^Bearer\s+(\S+)$/i', $kopf, $m) === 1 ? $m[1] : '';
        $treffer = $schluessel === '' ? null : $this->keys->authenticate($schluessel);
        if ($treffer === null) {
            return self::fehler('nicht_autorisiert', 401)->withHeader('WWW-Authenticate', 'Bearer');
        }
        $jetzt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        if ($this->limiter->exceeded('api', substr(hash('sha256', 'api|' . $treffer['key_id'], true), 0, 16), $this->limit, $jetzt)) {
            return self::fehler('zu_viele_anfragen', 429)->withHeader('Retry-After', '60');
        }
        $user = $this->auth->userById($treffer['user_id']);

        return $user === null ? self::fehler('nicht_autorisiert', 401) : $weiter($user);
    }

    private function sites(AuthUser $u): Response
    {
        return Response::json(['sites' => array_map(static fn(array $s): array => [
            'id' => $s['public_id'],
            'name' => $s['name'],
            'domain' => $s['domain'],
            'zeitzone' => $s['timezone'],
        ], $this->auth->sites($u))]);
    }

    private function stats(Request $q, AuthUser $u, string $id): Response
    {
        foreach ($this->auth->sites($u) as $site) {
            if ($site['public_id'] !== $id) {
                continue;
            }
            $von = is_string($q->query['von'] ?? null) ? $q->query['von'] : null;
            $bis = is_string($q->query['bis'] ?? null) ? $q->query['bis'] : null;

            return Response::json($this->service->ansicht($site, $von, $bis, $this->filter($q->query['f'] ?? []), new DateTimeImmutable('now', new DateTimeZone('UTC'))));
        }

        return self::fehler('site_unbekannt', 404);
    }

    /**
     * @return list<array{typ: string, wert: string}>
     */
    private function filter(mixed $roh): array
    {
        $ergebnis = [];
        foreach (is_array($roh) ? $roh : [] as $eintrag) {
            if (!is_string($eintrag) || ($stelle = strpos($eintrag, ':')) === false) {
                continue;
            }
            $typ = substr($eintrag, 0, $stelle);
            $wert = substr($eintrag, $stelle + 1);
            if (in_array($typ, self::FILTER_TYPEN, true) && $wert !== '' && strlen($wert) <= 1100 && !in_array($typ, array_column($ergebnis, 'typ'), true)) {
                $ergebnis[] = ['typ' => $typ, 'wert' => $wert];
            }
        }

        return array_slice($ergebnis, 0, 10);
    }

    private static function fehler(string $code, int $status): Response
    {
        return Response::json(['fehler' => $code], $status);
    }
}
