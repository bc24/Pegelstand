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
use Pegelstand\Database\Database;
use Pegelstand\Ingest\ClientIp;
use Pegelstand\Ingest\IpAddress;
use Pegelstand\Ingest\RateLimiter;
use Pegelstand\Ingest\SaltService;
use Pegelstand\Ingest\VisitorHasher;
use Pegelstand\Install\AdminInput;
use Pegelstand\Install\PasswordHasher;
use Pegelstand\Mail\Mailer;
use Pegelstand\Mail\Message;
use Pegelstand\Settings\SettingsStore;
use Throwable;

/**
 * Passwort vergessen: Einmal-Link per E-Mail (eine Stunde gültig). Die Antwort ist immer gleich, egal ob es die Adresse gibt.
 * Die Zwei-Faktor-Anmeldung bleibt davon unberührt.
 */
final class PasswordResetController
{
    private const VALID_SECONDS = 3600;

    public function __construct(
        private readonly View $view,
        private readonly Translator $t,
        private readonly Csrf $csrf,
        private readonly Database $db,
        private readonly Mailer $mailer,
        private readonly SettingsStore $settings,
        private readonly RateLimiter $limiter,
        private readonly SaltService $salts,
        private readonly ClientIp $clientIp,
    ) {}

    /** Gibt es einen funktionierenden Weg, den Link zu verschicken? */
    public function available(): bool
    {
        return $this->mailer->isConfigured() && $this->settings->get('mail.base_url') !== '';
    }

    public function register(Router $r): void
    {
        $r->add('GET', '/passwort-vergessen', fn(Request $q): Response => $this->formular($q));
        $r->add('POST', '/passwort-vergessen', fn(Request $q): Response => $this->anfordern($q));
        $r->add('GET', '/passwort-zuruecksetzen/{token}', fn(Request $q, array $p): Response => $this->neuesPasswort($q, $p['token'], []));
        $r->add('POST', '/passwort-zuruecksetzen/{token}', fn(Request $q, array $p): Response => $this->speichern($q, $p['token']));
    }

    private function jetzt(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    private function formular(Request $q, string $hinweis = '', int $status = 200): Response
    {
        return Response::html($this->view->render('auth/vergessen', [
            'titel' => $this->t->get('reset.titel'),
            'verfuegbar' => $this->available(),
            'gesendet' => $hinweis !== '',
            'hinweis' => $hinweis,
            'csrf' => $this->csrf->token(),
        ]), $status);
    }

    private function anfordern(Request $q): Response
    {
        if (!$this->csrf->verify($q->input('_csrf')) || !$this->available()) {
            return Response::redirect($q->url('/passwort-vergessen'));
        }
        $jetzt = $this->jetzt();
        $salt = $this->salts->forTime($jetzt);
        $ip = IpAddress::normalize($this->clientIp->resolve($q)) ?? 'unbekannt';
        $email = mb_strtolower(trim($q->input('email')));
        if ($this->limiter->exceeded('reset', VisitorHasher::rateKey($salt, 'ip:' . $ip), 5, $jetzt, 3600)
            | $this->limiter->exceeded('reset', VisitorHasher::rateKey($salt, 'mail:' . $email), 3, $jetzt, 3600)) {
            return $this->formular($q, $this->t->get('reset.zuviele'), 429);
        }

        $zeile = $this->db->fetchAll('SELECT id, name FROM ' . $this->db->table('users') . ' WHERE email = ? AND disabled_at IS NULL', [$email])[0] ?? null;
        if ($zeile !== null && is_numeric($zeile['id'])) {
            $token = bin2hex(random_bytes(32));
            $this->db->run(
                'INSERT INTO ' . $this->db->table('password_resets') . ' (user_id, token_hash, expires_at) VALUES (?, ?, ?)',
                [(int) $zeile['id'], hash('sha256', $token), gmdate('Y-m-d H:i:s', $jetzt->getTimestamp() + self::VALID_SECONDS)],
            );
            $link = $this->settings->get('mail.base_url') . '/passwort-zuruecksetzen/' . $token;
            try {
                $this->mailer->send(new Message($email, $this->t->get('reset.mail_betreff'), $this->t->get('reset.mail_text', ['name' => is_string($zeile['name']) ? $zeile['name'] : '', 'link' => $link])));
            } catch (Throwable) {
                // Der Fehler darf nicht verraten, ob es die Adresse gibt. Administratoren sehen ihn beim Testversand.
            }
        }

        return $this->formular($q, $this->t->get('reset.gesendet'));
    }

    private function benutzerZuToken(string $token): ?int
    {
        if (preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
            return null;
        }
        $id = $this->db->fetchInt(
            'SELECT user_id FROM ' . $this->db->table('password_resets') . ' WHERE token_hash = ? AND used_at IS NULL AND expires_at > ?',
            [hash('sha256', $token), gmdate('Y-m-d H:i:s')],
        );

        return $id > 0 ? $id : null;
    }

    /**
     * @param array<string, string> $fehler
     */
    private function neuesPasswort(Request $q, string $token, array $fehler, int $status = 200): Response
    {
        if ($this->benutzerZuToken($token) === null) {
            return Response::html($this->view->render('auth/vergessen', [
                'titel' => $this->t->get('reset.titel'),
                'verfuegbar' => $this->available(),
                'gesendet' => false,
                'hinweis' => '',
                'abgelaufen' => true,
                'csrf' => $this->csrf->token(),
            ]), 410);
        }

        return Response::html($this->view->render('auth/neues-passwort', [
            'titel' => $this->t->get('reset.neu_titel'),
            'token' => $token,
            'fehler' => $fehler,
            'csrf' => $this->csrf->token(),
        ]), $status);
    }

    private function speichern(Request $q, string $token): Response
    {
        if (!$this->csrf->verify($q->input('_csrf'))) {
            return Response::redirect($q->url('/passwort-vergessen'));
        }
        $userId = $this->benutzerZuToken($token);
        if ($userId === null) {
            return $this->neuesPasswort($q, $token, []);
        }
        $eingabe = new AdminInput('x', 'x@example.org', $q->input('password'), $q->input('password_repeat'));
        $fehler = [];
        foreach (['password', 'password_repeat'] as $feld) {
            $k = $eingabe->validate()[$feld] ?? null;
            if ($k !== null) {
                $fehler[$feld] = $this->t->get($k);
            }
        }
        if ($fehler !== []) {
            return $this->neuesPasswort($q, $token, $fehler, 422);
        }
        $this->db->run('UPDATE ' . $this->db->table('users') . ' SET password_hash = ? WHERE id = ?', [PasswordHasher::hash($q->input('password')), $userId]);
        $this->db->run('UPDATE ' . $this->db->table('password_resets') . ' SET used_at = ? WHERE user_id = ? AND used_at IS NULL', [gmdate('Y-m-d H:i:s'), $userId]);

        return Response::redirect($q->url('/login?zurueckgesetzt=1'));
    }
}
