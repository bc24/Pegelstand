<?php

declare(strict_types=1);

namespace Pegelstand\Auth;

use DateTimeImmutable;
use DateTimeZone;
use Pegelstand\Core\Csrf;
use Pegelstand\Core\Request;
use Pegelstand\Core\Response;
use Pegelstand\Core\Router;
use Pegelstand\Core\Translator;
use Pegelstand\Core\View;
use Pegelstand\Ingest\ClientIp;
use Pegelstand\Ingest\IpAddress;
use Pegelstand\Ingest\RateLimiter;
use Pegelstand\Ingest\SaltService;
use Pegelstand\Ingest\VisitorHasher;

/** Anmeldung und Abmeldung. Fehlversuche werden pro Adresse und pro E-Mail-Adresse begrenzt. */
final class LoginController
{
    private const WINDOW = 900;
    public function __construct(
        private readonly View $view,
        private readonly Translator $translator,
        private readonly Csrf $csrf,
        private readonly AuthService $auth,
        private readonly RateLimiter $limiter,
        private readonly SaltService $salts,
        private readonly ClientIp $clientIp,
        private readonly int $limit = 10,
    ) {}

    public function register(Router $router): void
    {
        $router->add('GET', '/login', fn(Request $r): Response => $this->formular($r));
        $router->add('POST', '/login', fn(Request $r): Response => $this->anmelden($r));
        $router->add('GET', '/login/2fa', fn(Request $r): Response => $this->codeFormular($r));
        $router->add('POST', '/login/2fa', fn(Request $r): Response => $this->codePruefen($r));
        $router->add('POST', '/logout', fn(Request $r): Response => $this->abmelden($r));
    }

    private function formular(Request $request): Response
    {
        if ($this->auth->user() !== null) {
            return Response::redirect($request->url('/'));
        }

        return $this->seite('', '', 200, $request->query['zurueckgesetzt'] ?? null);
    }

    private function anmelden(Request $request): Response
    {
        if (!$this->csrf->verify($request->input('_csrf'))) {
            return $this->seite($request->input('email'), $this->translator->get('login.fehler_sitzung'), 419);
        }
        $email = trim($request->input('email'));
        $jetzt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $salt = $this->salts->forTime($jetzt);
        $ip = IpAddress::normalize($this->clientIp->resolve($request)) ?? 'unbekannt';
        $zuViele = $this->limiter->exceeded('login', VisitorHasher::rateKey($salt, 'ip:' . $ip), $this->limit, $jetzt, self::WINDOW);
        $zuViele = $this->limiter->exceeded('login', VisitorHasher::rateKey($salt, 'mail:' . mb_strtolower($email)), $this->limit, $jetzt, self::WINDOW) || $zuViele;
        if ($zuViele) {
            return $this->seite($email, $this->translator->get('login.fehler_zuviele'), 429);
        }

        $ergebnis = $email === '' || $request->input('password') === '' ? 'fail' : $this->auth->attempt($email, $request->input('password'), $jetzt);
        if ($ergebnis === 'fail') {
            return $this->seite($email, $this->translator->get('login.fehler_zugang'), 422);
        }

        return Response::redirect($request->url($ergebnis === 'totp' ? '/login/2fa' : '/'));
    }

    private function codeFormular(Request $request): Response
    {
        if (!$this->auth->hatAusstehendenCode(new DateTimeImmutable('now', new DateTimeZone('UTC')))) {
            return Response::redirect($request->url('/login'));
        }

        return $this->codeSeite('');
    }

    private function codePruefen(Request $request): Response
    {
        $jetzt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        if (!$this->csrf->verify($request->input('_csrf')) || !$this->auth->hatAusstehendenCode($jetzt)) {
            return Response::redirect($request->url('/login'));
        }
        $salt = $this->salts->forTime($jetzt);
        $ip = IpAddress::normalize($this->clientIp->resolve($request)) ?? 'unbekannt';
        if ($this->limiter->exceeded('login', VisitorHasher::rateKey($salt, 'ip:' . $ip), $this->limit, $jetzt, self::WINDOW)) {
            return $this->codeSeite($this->translator->get('login.fehler_zuviele'), 429);
        }
        if (!$this->auth->completeTotp($request->input('code'), $jetzt)) {
            return $this->codeSeite($this->translator->get('login.fehler_code'), 422);
        }

        return Response::redirect($request->url('/'));
    }

    private function codeSeite(string $fehler, int $status = 200): Response
    {
        return Response::html($this->view->render('auth/code', [
            'titel' => $this->translator->get('login.code_titel'),
            'fehler' => $fehler,
            'csrf' => $this->csrf->token(),
        ]), $status);
    }

    private function abmelden(Request $request): Response
    {
        if ($this->csrf->verify($request->input('_csrf'))) {
            $this->auth->logout();
        }

        return Response::redirect($request->url('/login'));
    }

    private function seite(string $email, string $fehler, int $status = 200, mixed $erfolg = null): Response
    {
        return Response::html($this->view->render('auth/login', [
            'titel' => $this->translator->get('login.titel'),
            'email' => $email,
            'fehler' => $fehler,
            'csrf' => $this->csrf->token(),
            'erfolg' => $erfolg === '1',
        ]), $status);
    }
}
