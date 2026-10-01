<?php

declare(strict_types=1);

namespace Pegelstand\Settings;

use Pegelstand\Auth\AuthService;
use Pegelstand\Core\Csrf;
use Pegelstand\Core\Request;
use Pegelstand\Core\Response;
use Pegelstand\Core\Router;
use Pegelstand\Core\Session;
use Pegelstand\Core\Translator;
use Pegelstand\Core\View;
use Pegelstand\Mail\Mailer;
use Pegelstand\Mail\MailException;
use Pegelstand\Mail\Message;

/** E-Mail-Einstellungen (SMTP) mit Testversand. Nur für Administratoren. */
final class MailController
{
    public function __construct(
        private readonly View $view,
        private readonly Translator $t,
        private readonly Csrf $csrf,
        private readonly Session $session,
        private readonly AuthService $auth,
        private readonly SettingsStore $store,
        private readonly Mailer $mailer,
    ) {}

    public function register(Router $r): void
    {
        $r->add('GET', '/einstellungen/email', fn(Request $q): Response => $this->seite($q, [], []));
        $r->add('POST', '/einstellungen/email', fn(Request $q): Response => $this->speichern($q));
        $r->add('POST', '/einstellungen/email/test', fn(Request $q): Response => $this->test($q));
    }

    /**
     * @param array<string, string> $werte
     * @param array<string, string> $fehler
     */
    private function seite(Request $q, array $werte, array $fehler, int $status = 200): Response
    {
        $u = $this->auth->user();
        if ($u === null) {
            return Response::redirect($q->url('/login'));
        }
        if (!$u->isAdmin()) {
            return new Response('', 403);
        }
        $flash = $this->session->get('flash');
        $this->session->remove('flash');
        $s = $this->store;
        $vorgabe = [
            'host' => $s->get('mail.host'),
            'port' => $s->get('mail.port', '587'),
            'security' => $s->get('mail.security', 'starttls'),
            'user' => $s->get('mail.user'),
            'from_address' => $s->get('mail.from_address'),
            'from_name' => $s->get('mail.from_name', 'Pegelstand'),
            'base_url' => $s->get('mail.base_url', ($q->https ? 'https' : 'http') . '://' . $this->host($q) . $q->basePath),
        ];

        return Response::html($this->view->render('einstellungen/email', [
            'titel' => $this->t->get('einst.email.titel'),
            'werte' => $werte + $vorgabe,
            'fehler' => $fehler,
            'passwortGesetzt' => $s->getSecret('mail.password') !== '',
            'eingerichtet' => $this->mailer->isConfigured(),
            'meine' => $u->email,
            'csrf' => $this->csrf->token(),
            'admin' => true,
            'flash' => is_array($flash) ? $flash : null,
            'breit' => true,
        ]), $status);
    }

    private function host(Request $q): string
    {
        $host = $q->header('Host');

        return preg_match('/^[A-Za-z0-9.\-:\[\]]{1,255}$/', $host) === 1 ? $host : 'example.org';
    }

    private function speichern(Request $q): Response
    {
        $u = $this->auth->user();
        if ($u === null || !$u->isAdmin()) {
            return $u === null ? Response::redirect($q->url('/login')) : new Response('', 403);
        }
        if (!$this->csrf->verify($q->input('_csrf'))) {
            return Response::redirect($q->url('/einstellungen'));
        }
        $w = [];
        foreach (['host', 'port', 'security', 'user', 'from_address', 'from_name', 'base_url'] as $f) {
            $w[$f] = trim($q->input($f));
        }
        $fehler = [];
        if ($w['host'] === '' || preg_match('/^[A-Za-z0-9.\-]{1,253}$/', $w['host']) !== 1) {
            $fehler['host'] = $this->t->get('einst.email.fehler_host');
        }
        if (!ctype_digit($w['port']) || (int) $w['port'] < 1 || (int) $w['port'] > 65535) {
            $fehler['port'] = $this->t->get('einst.email.fehler_port');
        }
        if (!in_array($w['security'], ['starttls', 'tls', 'none'], true)) {
            $w['security'] = 'starttls';
        }
        if (filter_var($w['from_address'], FILTER_VALIDATE_EMAIL) === false) {
            $fehler['from_address'] = $this->t->get('einst.email.fehler_absender');
        }
        if ($w['from_name'] === '' || mb_strlen($w['from_name']) > 80 || preg_match('/[\r\n]/', $w['from_name']) === 1) {
            $fehler['from_name'] = $this->t->get('einst.email.fehler_name');
        }
        if (preg_match('#^https?://[A-Za-z0-9.\-:\[\]]+(/[^\s?\#]*)?$#', $w['base_url']) !== 1) {
            $fehler['base_url'] = $this->t->get('einst.email.fehler_adresse');
        }
        if ($fehler !== []) {
            return $this->seite($q, $w, $fehler, 422);
        }
        $s = $this->store;
        foreach (['host', 'port', 'security', 'user', 'from_address', 'from_name'] as $f) {
            $s->set('mail.' . $f, $w[$f]);
        }
        $s->set('mail.base_url', rtrim($w['base_url'], '/'));
        if ($q->input('password') !== '') {
            $s->setSecret('mail.password', $q->input('password'));
        }
        $this->session->set('flash', ['typ' => 'success', 'text' => $this->t->get('einst.email.gespeichert')]);

        return Response::redirect($q->url('/einstellungen/email'));
    }

    private function test(Request $q): Response
    {
        $u = $this->auth->user();
        if ($u === null || !$u->isAdmin()) {
            return $u === null ? Response::redirect($q->url('/login')) : new Response('', 403);
        }
        if ($this->csrf->verify($q->input('_csrf'))) {
            try {
                $this->mailer->send(new Message($u->email, $this->t->get('einst.email.test_betreff'), $this->t->get('einst.email.test_text')));
                $flash = ['typ' => 'success', 'text' => $this->t->get('einst.email.test_ok', ['adresse' => $u->email])];
            } catch (MailException $e) {
                $flash = ['typ' => 'danger', 'text' => $this->t->get('einst.email.test_fehler', ['grund' => $e->getMessage()])];
            }
            $this->session->set('flash', $flash);
        }

        return Response::redirect($q->url('/einstellungen/email'));
    }
}
