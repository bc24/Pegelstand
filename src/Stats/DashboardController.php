<?php

declare(strict_types=1);

namespace Pegelstand\Stats;

use DateTimeImmutable;
use DateTimeZone;
use Pegelstand\Auth\AuthService;
use Pegelstand\Auth\AuthUser;
use Pegelstand\Core\Csrf;
use Pegelstand\Core\Request;
use Pegelstand\Core\Response;
use Pegelstand\Core\Router;
use Pegelstand\Core\Translator;
use Pegelstand\Core\View;

/** Die Dashboard-Seite und ihre JSON-Schnittstelle. Beides nur für angemeldete Benutzer. */
final class DashboardController
{
    private const FILTER_TYPEN = ['seite', 'einstieg', 'ausstieg', 'quelle', 'kampagne', 'land', 'geraet', 'browser', 'os', 'ziel', 'ereignis'];

    public function __construct(
        private readonly View $view,
        private readonly Translator $translator,
        private readonly Csrf $csrf,
        private readonly AuthService $auth,
        private readonly DashboardService $service,
        private readonly string $version,
        private readonly string $scriptPath = '/p.js',
        private readonly string $endpointPath = '/api/event',
    ) {}

    public function register(Router $router): void
    {
        $router->add('GET', '/', fn(Request $r): Response => $this->seite($r));
        $router->add('GET', '/api/dashboard', fn(Request $r): Response => $this->daten($r));
        $router->add('GET', '/export/{name}', fn(Request $r, array $p): Response => $this->export($r, $p['name']));
        $router->add('GET', '/api/dashboard/live', fn(Request $r): Response => $this->live($r));
    }

    private function seite(Request $request): Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            return Response::redirect($request->url('/login'));
        }
        $sites = $this->auth->sites($user);
        if ($sites === []) {
            return Response::html($this->view->render('dashboard/keine-site', [
                'titel' => $this->translator->get('keine_site.titel'),
                'admin' => $user->isAdmin(),
                'csrf' => $this->csrf->token(),
            ]));
        }

        $jetzt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $liste = [];
        foreach ($sites as $site) {
            $zone = new DateTimeZone($site['timezone']);
            $lokal = $jetzt->setTimezone($zone);
            $liste[] = [
                'id' => $site['public_id'],
                'name' => $site['name'],
                'code' => $this->trackingCode($request, $site['public_id']),
                'jetzt' => ['datum' => $lokal->format('Y-m-d'), 'stunde' => (int) $lokal->format('G'), 'zeit' => $lokal->format('H:i')],
                'min' => $this->service->ersterTag($site['id'], $zone) ?? $lokal->format('Y-m-d'),
            ];
        }
        $bootstrap = json_encode(['sites' => $liste], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        return Response::html($this->view->render('dashboard/seite', [
            'titel' => $this->translator->get('dashboard.titel'),
            'bootstrap' => $bootstrap,
            'benutzer' => $user->name,
            'csrf' => $this->csrf->token(),
            'version' => $this->version,
            'oeffentlich' => false,
            'apiBasis' => '',
        ], null));
    }

    private function daten(Request $request): Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            return Response::json(['fehler' => 'nicht_angemeldet'], 401);
        }
        $site = $this->site($user, $request);
        if ($site === null) {
            return Response::json(['fehler' => 'site_unbekannt'], 404);
        }
        $von = is_string($request->query['von'] ?? null) ? $request->query['von'] : null;
        $bis = is_string($request->query['bis'] ?? null) ? $request->query['bis'] : null;

        return Response::json($this->service->ansicht(
            $site,
            $von,
            $bis,
            $this->filter($request->query['f'] ?? []),
            new DateTimeImmutable('now', new DateTimeZone('UTC')),
        ));
    }

    private function export(Request $request, string $name): Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            return Response::redirect($request->url('/login'));
        }
        $site = $this->site($user, $request);
        if ($site === null || !isset(CsvExport::TABLES[$name])) {
            return new Response('', 404);
        }
        $von = is_string($request->query['von'] ?? null) ? $request->query['von'] : null;
        $bis = is_string($request->query['bis'] ?? null) ? $request->query['bis'] : null;
        $daten = $this->service->ansicht($site, $von, $bis, $this->filter($request->query['f'] ?? []), new DateTimeImmutable('now', new DateTimeZone('UTC')));
        $csv = CsvExport::build($name, $daten);
        $zeitraum = is_array($daten['zeitraum'] ?? null) ? $daten['zeitraum'] : [];
        $datei = sprintf('pegelstand-%s-%s-%s-%s.csv', preg_replace('/[^a-z0-9]+/', '-', strtolower($site['domain'])) ?: 'site', $name, is_string($zeitraum['von'] ?? null) ? $zeitraum['von'] : 'von', is_string($zeitraum['bis'] ?? null) ? $zeitraum['bis'] : 'bis');

        return new Response($csv ?? '', 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $datei . '"',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function live(Request $request): Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            return Response::json(['fehler' => 'nicht_angemeldet'], 401);
        }
        $site = $this->site($user, $request);
        if ($site === null) {
            return Response::json(['fehler' => 'site_unbekannt'], 404);
        }

        return Response::json(['aktive' => $this->service->aktive($site['id'], new DateTimeImmutable('now', new DateTimeZone('UTC')))]);
    }

    /**
     * @return array{id: int, public_id: string, name: string, domain: string, timezone: string, created_at: string}|null
     */
    private function site(AuthUser $user, Request $request): ?array
    {
        $gewuenscht = $request->query['site'] ?? null;
        foreach ($this->auth->sites($user) as $site) {
            if ($site['public_id'] === $gewuenscht) {
                return $site;
            }
        }

        return null;
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
            if (!in_array($typ, self::FILTER_TYPEN, true) || $wert === '' || strlen($wert) > 1100) {
                continue;
            }
            foreach ($ergebnis as $vorhanden) {
                if ($vorhanden['typ'] === $typ) {
                    continue 2;
                }
            }
            $ergebnis[] = ['typ' => $typ, 'wert' => $wert];
            if (count($ergebnis) >= 10) {
                break;
            }
        }

        return $ergebnis;
    }

    private function trackingCode(Request $request, string $siteId): string
    {
        $host = $request->header('Host');
        if (preg_match('/^[A-Za-z0-9.\-:\[\]]{1,255}$/', $host) !== 1) {
            $host = 'example.org';
        }
        $ursprung = ($request->https ? 'https' : 'http') . '://' . $host . $request->basePath;
        $code = '<script defer data-site="' . $siteId . '" src="' . $ursprung . $this->scriptPath . '"';
        if ($this->endpointPath !== '/api/event' || dirname($this->scriptPath) !== '/') {
            $code .= ' data-api="' . $ursprung . $this->endpointPath . '"';
        }

        return $code . '></script>';
    }
}
