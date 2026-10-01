<?php

declare(strict_types=1);

namespace Pegelstand\Stats;

use Closure;
use DateTimeImmutable;
use DateTimeZone;
use Pegelstand\Core\Request;
use Pegelstand\Core\Response;
use Pegelstand\Core\Router;
use Pegelstand\Core\View;
use Pegelstand\Ingest\ClientIp;
use Pegelstand\Ingest\IpAddress;
use Pegelstand\Ingest\RateLimiter;
use Pegelstand\Ingest\SaltService;
use Pegelstand\Ingest\VisitorHasher;
use Pegelstand\Settings\SiteRepository;

/**
 * Schreibgeschütztes Dashboard über einen geheimen Link (`/oeffentlich/{token}`). Es gibt kein Konto, keine Einstellungen
 * und keinen Export. Anfragen sind pro Adresse begrenzt, weil gefilterte Ansichten Rohdaten lesen.
 */
final class PublicDashboardController
{
    private const FILTER_TYPEN = ['seite', 'einstieg', 'ausstieg', 'quelle', 'kampagne', 'land', 'geraet', 'browser', 'os', 'ziel', 'ereignis'];

    public function __construct(
        private readonly View $view,
        private readonly SiteRepository $sites,
        private readonly DashboardService $service,
        private readonly RateLimiter $limiter,
        private readonly SaltService $salts,
        private readonly ClientIp $clientIp,
        private readonly string $version,
        /** @var Closure(Request): Response */
        private readonly Closure $nichtGefunden,
        private readonly int $limit = 60,
    ) {}

    public function register(Router $r): void
    {
        $r->add('GET', '/oeffentlich/{token}', fn(Request $q, array $p): Response => $this->seite($q, $p['token']));
        $r->add('GET', '/oeffentlich/{token}/daten', fn(Request $q, array $p): Response => $this->daten($q, $p['token']));
        $r->add('GET', '/oeffentlich/{token}/daten/live', fn(Request $q, array $p): Response => $this->live($q, $p['token']));
    }

    private function jetzt(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    private function seite(Request $q, string $token): Response
    {
        $site = $this->sites->findByPublicToken($token);
        if ($site === null) {
            return ($this->nichtGefunden)($q);
        }
        $zone = new DateTimeZone($site['timezone']);
        $lokal = $this->jetzt()->setTimezone($zone);
        $bootstrap = json_encode(['sites' => [[
            'id' => $site['public_id'],
            'name' => $site['name'],
            'code' => '',
            'jetzt' => ['datum' => $lokal->format('Y-m-d'), 'stunde' => (int) $lokal->format('G'), 'zeit' => $lokal->format('H:i')],
            'min' => $this->service->ersterTag($site['id'], $zone) ?? $lokal->format('Y-m-d'),
        ]]], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        return Response::html($this->view->render('dashboard/seite', [
            'titel' => $site['name'],
            'bootstrap' => $bootstrap,
            'benutzer' => '',
            'csrf' => '',
            'version' => $this->version,
            'oeffentlich' => true,
            'apiBasis' => 'oeffentlich/' . $token . '/daten',
        ], null))->withHeader('Referrer-Policy', 'no-referrer');
    }

    private function daten(Request $q, string $token): Response
    {
        $site = $this->sites->findByPublicToken($token);
        if ($site === null) {
            return Response::json(['fehler' => 'nicht_gefunden'], 404);
        }
        if ($this->begrenzt($q)) {
            return Response::json(['fehler' => 'zu_viele_anfragen'], 429)->withHeader('Retry-After', '60');
        }
        $von = is_string($q->query['von'] ?? null) ? $q->query['von'] : null;
        $bis = is_string($q->query['bis'] ?? null) ? $q->query['bis'] : null;

        return Response::json($this->service->ansicht($site, $von, $bis, $this->filter($q->query['f'] ?? []), $this->jetzt()));
    }

    private function live(Request $q, string $token): Response
    {
        $site = $this->sites->findByPublicToken($token);
        if ($site === null) {
            return Response::json(['fehler' => 'nicht_gefunden'], 404);
        }
        if ($this->begrenzt($q)) {
            return Response::json(['fehler' => 'zu_viele_anfragen'], 429)->withHeader('Retry-After', '60');
        }

        return Response::json(['aktive' => $this->service->aktive($site['id'], $this->jetzt())]);
    }

    private function begrenzt(Request $q): bool
    {
        $jetzt = $this->jetzt();
        $ip = IpAddress::normalize($this->clientIp->resolve($q)) ?? 'unbekannt';

        return $this->limiter->exceeded('public', VisitorHasher::rateKey($this->salts->forTime($jetzt), 'ip:' . $ip), $this->limit, $jetzt);
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
}
