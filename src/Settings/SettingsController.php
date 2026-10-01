<?php

declare(strict_types=1);

namespace Pegelstand\Settings;

use DateTimeZone;
use Pegelstand\Auth\AuthService;
use Pegelstand\Auth\AuthUser;
use Pegelstand\Core\Csrf;
use Pegelstand\Core\Request;
use Pegelstand\Core\Response;
use Pegelstand\Core\Router;
use Pegelstand\Core\Session;
use Pegelstand\Core\Translator;
use Pegelstand\Core\View;
use Pegelstand\Install\AdminInput;

/**
 * Einstellungen: Websites und Benutzer (nur Administratoren), Konto und Passwort (alle).
 *
 * @phpstan-import-type SiteRow from SiteRepository
 */
final class SettingsController
{
    private const SITE_FELDER = ['name', 'domain', 'allowed_hosts', 'timezone', 'retention_days'];

    public function __construct(
        private readonly View $view,
        private readonly Translator $t,
        private readonly Csrf $csrf,
        private readonly Session $session,
        private readonly AuthService $auth,
        private readonly SiteRepository $sites,
        private readonly UserRepository $users,
        private readonly string $scriptPath,
        private readonly string $endpointPath,
    ) {}

    public function register(Router $r): void
    {
        $r->add('GET', '/einstellungen', fn(Request $q): Response => $this->start($q));
        $r->add('GET', '/einstellungen/websites', fn(Request $q): Response => $this->websites($q, [], []));
        $r->add('POST', '/einstellungen/websites', fn(Request $q): Response => $this->websiteAnlegen($q));
        $r->add('GET', '/einstellungen/websites/{id}', fn(Request $q, array $p): Response => $this->website($q, $p['id'], [], null));
        $r->add('POST', '/einstellungen/websites/{id}', fn(Request $q, array $p): Response => $this->websiteSpeichern($q, $p['id']));
        $r->add('POST', '/einstellungen/websites/{id}/ausschluss', fn(Request $q, array $p): Response => $this->ausschlussAnlegen($q, $p['id']));
        $r->add('POST', '/einstellungen/websites/{id}/ausschluss/{eid}/loeschen', fn(Request $q, array $p): Response => $this->ausschlussLoeschen($q, $p['id'], $p['eid']));
        $r->add('POST', '/einstellungen/websites/{id}/loeschen', fn(Request $q, array $p): Response => $this->websiteLoeschen($q, $p['id']));
        $r->add('GET', '/einstellungen/benutzer', fn(Request $q): Response => $this->benutzer($q, [], []));
        $r->add('POST', '/einstellungen/benutzer', fn(Request $q): Response => $this->benutzerAnlegen($q));
        $r->add('GET', '/einstellungen/benutzer/{id}', fn(Request $q, array $p): Response => $this->benutzerSeite($q, $p['id'], []));
        $r->add('POST', '/einstellungen/benutzer/{id}', fn(Request $q, array $p): Response => $this->benutzerSpeichern($q, $p['id']));
        $r->add('POST', '/einstellungen/benutzer/{id}/loeschen', fn(Request $q, array $p): Response => $this->benutzerLoeschen($q, $p['id']));
        $r->add('GET', '/einstellungen/konto', fn(Request $q): Response => $this->konto($q, []));
        $r->add('POST', '/einstellungen/konto', fn(Request $q): Response => $this->passwortAendern($q));
    }

    /* ---------- Rahmen ---------- */

    private function start(Request $q): Response
    {
        $u = $this->auth->user();
        if ($u === null) {
            return Response::redirect($q->url('/login'));
        }

        return Response::redirect($q->url($u->isAdmin() ? '/einstellungen/websites' : '/einstellungen/konto'));
    }

    /** Liefert den Benutzer oder die passende Umleitung bzw. Fehlerantwort. */
    private function guard(Request $q, bool $admin, bool $post): AuthUser|Response
    {
        $u = $this->auth->user();
        if ($u === null) {
            return Response::redirect($q->url('/login'));
        }
        if ($admin && !$u->isAdmin()) {
            return new Response('', 403);
        }
        if ($post && !$this->csrf->verify($q->input('_csrf'))) {
            return Response::redirect($q->url('/einstellungen'));
        }

        return $u;
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function seite(Request $q, AuthUser $u, string $template, string $titel, array $vars, int $status = 200): Response
    {
        $flash = $this->session->get('flash');
        $this->session->remove('flash');

        return Response::html($this->view->render('einstellungen/' . $template, [
            ...$vars,
            'titel' => $titel,
            'csrf' => $this->csrf->token(),
            'admin' => $u->isAdmin(),
            'flash' => is_array($flash) ? $flash : null,
            'breit' => true,
        ]), $status);
    }

    private function merke(string $typ, string $text): void
    {
        $this->session->set('flash', ['typ' => $typ, 'text' => $text]);
    }

    /**
     * @param array<string, string> $fehlerSchluessel
     * @return array<string, string>
     */
    private function uebersetze(array $fehlerSchluessel): array
    {
        return array_map(fn(string $k): string => $this->t->get($k), $fehlerSchluessel);
    }

    /* ---------- Websites ---------- */

    /**
     * @param array<string, string> $werte
     * @param array<string, string> $fehler
     */
    private function websites(Request $q, array $werte, array $fehler, int $status = 200): Response
    {
        $u = $this->guard($q, true, false);
        if ($u instanceof Response) {
            return $u;
        }

        return $this->seite($q, $u, 'websites', $this->t->get('einst.websites.titel'), [
            'sites' => $this->sites->all(),
            'werte' => $werte + ['name' => '', 'domain' => '', 'allowed_hosts' => '', 'timezone' => 'Europe/Berlin', 'retention_days' => '730'],
            'fehler' => $fehler,
            'zeitzonen' => DateTimeZone::listIdentifiers(),
        ], $status);
    }

    private function websiteAnlegen(Request $q): Response
    {
        $u = $this->guard($q, true, true);
        if ($u instanceof Response) {
            return $u;
        }
        $werte = $this->siteWerte($q);
        $fehler = $this->sites->validate($werte);
        if ($fehler !== []) {
            return $this->websites($q, $werte, $this->uebersetze($fehler), 422);
        }
        $id = $this->sites->create($werte, $q->input('respect_dnt') === '1', $q->input('respect_gpc') === '1');
        $this->merke('success', $this->t->get('einst.websites.angelegt', ['name' => $werte['name']]));

        return Response::redirect($q->url('/einstellungen/websites/' . $id));
    }

    /**
     * @return array<string, string>
     */
    private function siteWerte(Request $q): array
    {
        $w = [];
        foreach (self::SITE_FELDER as $f) {
            $w[$f] = trim($q->input($f));
        }
        $w['domain'] = strtolower(preg_replace('#^https?://#i', '', $w['domain']) ?? '');
        $w['domain'] = rtrim(explode('/', $w['domain'])[0], '.');

        return $w;
    }

    /**
     * @param array<string, string> $fehler
     * @param array<string, string>|null $werte
     */
    private function website(Request $q, string $publicId, array $fehler, ?array $werte, int $status = 200): Response
    {
        $u = $this->guard($q, true, false);
        if ($u instanceof Response) {
            return $u;
        }
        $site = $this->sites->find($publicId);
        if ($site === null) {
            return new Response('', 404);
        }
        $werte ??= [
            'name' => $site['name'], 'domain' => $site['domain'], 'allowed_hosts' => $site['allowed_hosts'],
            'timezone' => $site['timezone'], 'retention_days' => (string) $site['retention_days'],
        ];

        return $this->seite($q, $u, 'website', $site['name'], [
            'site' => $site,
            'werte' => $werte,
            'fehler' => $fehler,
            'zeitzonen' => DateTimeZone::listIdentifiers(),
            'ausschluesse' => $this->sites->exclusions($site['id']),
            'code' => $this->trackingCode($q, $site['public_id']),
            'ip' => $q->ip,
        ], $status);
    }

    private function websiteSpeichern(Request $q, string $publicId): Response
    {
        $u = $this->guard($q, true, true);
        if ($u instanceof Response) {
            return $u;
        }
        $site = $this->sites->find($publicId);
        if ($site === null) {
            return new Response('', 404);
        }
        $werte = $this->siteWerte($q);
        $fehler = $this->sites->validate($werte, $site['id']);
        if ($fehler !== []) {
            return $this->website($q, $publicId, $this->uebersetze($fehler), $werte, 422);
        }
        $this->sites->update($site['id'], $werte, $q->input('respect_dnt') === '1', $q->input('respect_gpc') === '1');
        $this->merke('success', $this->t->get('einst.websites.gespeichert'));

        return Response::redirect($q->url('/einstellungen/websites/' . $publicId));
    }

    private function ausschlussAnlegen(Request $q, string $publicId): Response
    {
        $u = $this->guard($q, true, true);
        if ($u instanceof Response) {
            return $u;
        }
        $site = $this->sites->find($publicId);
        if ($site === null) {
            return new Response('', 404);
        }
        $ok = $this->sites->addExclusion($site['id'], $q->input('ip_range'), trim($q->input('label')));
        $ok ? $this->merke('success', $this->t->get('einst.websites.ausschluss_neu')) : $this->merke('danger', $this->t->get('einst.fehler.ip'));

        return Response::redirect($q->url('/einstellungen/websites/' . $publicId . '#ausschluesse'));
    }

    private function ausschlussLoeschen(Request $q, string $publicId, string $eid): Response
    {
        $u = $this->guard($q, true, true);
        if ($u instanceof Response) {
            return $u;
        }
        $site = $this->sites->find($publicId);
        if ($site === null) {
            return new Response('', 404);
        }
        $this->sites->removeExclusion($site['id'], (int) $eid);
        $this->merke('success', $this->t->get('einst.websites.ausschluss_weg'));

        return Response::redirect($q->url('/einstellungen/websites/' . $publicId . '#ausschluesse'));
    }

    private function websiteLoeschen(Request $q, string $publicId): Response
    {
        $u = $this->guard($q, true, true);
        if ($u instanceof Response) {
            return $u;
        }
        $site = $this->sites->find($publicId);
        if ($site === null) {
            return new Response('', 404);
        }
        if (trim($q->input('bestaetigung')) !== $site['domain']) {
            $this->merke('danger', $this->t->get('einst.fehler.bestaetigung', ['domain' => $site['domain']]));

            return Response::redirect($q->url('/einstellungen/websites/' . $publicId . '#loeschen'));
        }
        $this->sites->delete($site['id']);
        $this->merke('success', $this->t->get('einst.websites.geloescht', ['name' => $site['name']]));

        return Response::redirect($q->url('/einstellungen/websites'));
    }

    private function trackingCode(Request $q, string $siteId): string
    {
        $host = $q->header('Host');
        if (preg_match('/^[A-Za-z0-9.\-:\[\]]{1,255}$/', $host) !== 1) {
            $host = 'example.org';
        }
        $ursprung = ($q->https ? 'https' : 'http') . '://' . $host . $q->basePath;
        $code = '<script defer data-site="' . $siteId . '" src="' . $ursprung . $this->scriptPath . '"';
        if ($this->endpointPath !== '/api/event' || dirname($this->scriptPath) !== '/') {
            $code .= ' data-api="' . $ursprung . $this->endpointPath . '"';
        }

        return $code . '></script>';
    }

    /* ---------- Benutzer ---------- */

    /**
     * @param array<string, string> $werte
     * @param array<string, string> $fehler
     */
    private function benutzer(Request $q, array $werte, array $fehler, int $status = 200): Response
    {
        $u = $this->guard($q, true, false);
        if ($u instanceof Response) {
            return $u;
        }

        return $this->seite($q, $u, 'benutzer', $this->t->get('einst.benutzer.titel'), [
            'liste' => $this->users->all(),
            'sites' => $this->sites->all(),
            'werte' => $werte + ['name' => '', 'email' => '', 'role' => 'viewer'],
            'gewaehlt' => $this->siteIds($q),
            'fehler' => $fehler,
        ], $status);
    }

    /**
     * @return list<int>
     */
    private function siteIds(Request $q): array
    {
        $roh = $q->post['sites'] ?? [];
        $ids = [];
        foreach (is_array($roh) ? $roh : [] as $id) {
            if (is_string($id) && ctype_digit($id)) {
                $ids[] = (int) $id;
            }
        }

        return $ids;
    }

    /**
     * @return array<string, string>
     */
    private function benutzerFehler(Request $q, ?int $ownId, bool $passwort): array
    {
        $eingabe = new AdminInput(trim($q->input('name')), trim($q->input('email')), $q->input('password'), $q->input('password_repeat'));
        $fehler = $eingabe->validate();
        if ($ownId !== null) {
            unset($fehler['email']);
        }
        if (!$passwort) {
            unset($fehler['password'], $fehler['password_repeat']);
        }
        if ($ownId === null && !isset($fehler['email']) && $this->users->emailExists($eingabe->email)) {
            $fehler['email'] = 'einst.fehler.email_vergeben';
        }

        return $this->uebersetzeInstall($fehler);
    }

    /**
     * @param array<string, string> $fehler
     * @return array<string, string>
     */
    private function uebersetzeInstall(array $fehler): array
    {
        return array_map(fn(string $k): string => $this->t->get($k), $fehler);
    }

    private function benutzerAnlegen(Request $q): Response
    {
        $u = $this->guard($q, true, true);
        if ($u instanceof Response) {
            return $u;
        }
        $werte = ['name' => trim($q->input('name')), 'email' => trim($q->input('email')), 'role' => $q->input('role') === 'admin' ? 'admin' : 'viewer'];
        $fehler = $this->benutzerFehler($q, null, true);
        if ($fehler !== []) {
            return $this->benutzer($q, $werte, $fehler, 422);
        }
        $this->users->create($werte['name'], $werte['email'], $q->input('password'), $werte['role'], $this->siteIds($q));
        $this->merke('success', $this->t->get('einst.benutzer.angelegt', ['name' => $werte['name']]));

        return Response::redirect($q->url('/einstellungen/benutzer'));
    }

    /**
     * @param array<string, string> $fehler
     */
    private function benutzerSeite(Request $q, string $id, array $fehler, int $status = 200): Response
    {
        $u = $this->guard($q, true, false);
        if ($u instanceof Response) {
            return $u;
        }
        $ziel = $this->users->find((int) $id);
        if ($ziel === null) {
            return new Response('', 404);
        }

        return $this->seite($q, $u, 'benutzer-bearbeiten', $ziel['name'], [
            'ziel' => $ziel,
            'sites' => $this->sites->all(),
            'fehler' => $fehler,
            'selbst' => $ziel['id'] === $u->id,
        ], $status);
    }

    private function benutzerSpeichern(Request $q, string $id): Response
    {
        $u = $this->guard($q, true, true);
        if ($u instanceof Response) {
            return $u;
        }
        $ziel = $this->users->find((int) $id);
        if ($ziel === null) {
            return new Response('', 404);
        }
        $fehler = $this->benutzerFehler($q, $ziel['id'], $q->input('password') !== '');
        if ($fehler !== []) {
            return $this->benutzerSeite($q, $id, $fehler, 422);
        }
        $ok = $this->users->update(
            $ziel['id'],
            trim($q->input('name')),
            $q->input('role'),
            $q->input('disabled') === '1' && $ziel['id'] !== $u->id,
            $this->siteIds($q),
        );
        if (!$ok) {
            $this->merke('danger', $this->t->get('einst.fehler.letzter_admin'));

            return Response::redirect($q->url('/einstellungen/benutzer/' . $id));
        }
        if ($q->input('password') !== '') {
            $this->users->setPassword($ziel['id'], $q->input('password'));
        }
        $this->merke('success', $this->t->get('einst.benutzer.gespeichert'));

        return Response::redirect($q->url('/einstellungen/benutzer'));
    }

    private function benutzerLoeschen(Request $q, string $id): Response
    {
        $u = $this->guard($q, true, true);
        if ($u instanceof Response) {
            return $u;
        }
        $ziel = $this->users->find((int) $id);
        if ($ziel === null) {
            return new Response('', 404);
        }
        if ($ziel['id'] === $u->id || !$this->users->delete($ziel['id'])) {
            $this->merke('danger', $this->t->get($ziel['id'] === $u->id ? 'einst.fehler.selbst_loeschen' : 'einst.fehler.letzter_admin'));

            return Response::redirect($q->url('/einstellungen/benutzer/' . $id));
        }
        $this->merke('success', $this->t->get('einst.benutzer.geloescht'));

        return Response::redirect($q->url('/einstellungen/benutzer'));
    }

    /* ---------- Konto ---------- */

    /**
     * @param array<string, string> $fehler
     */
    private function konto(Request $q, array $fehler, int $status = 200): Response
    {
        $u = $this->guard($q, false, false);
        if ($u instanceof Response) {
            return $u;
        }

        return $this->seite($q, $u, 'konto', $this->t->get('einst.konto.titel'), ['benutzer' => $u, 'fehler' => $fehler], $status);
    }

    private function passwortAendern(Request $q): Response
    {
        $u = $this->guard($q, false, true);
        if ($u instanceof Response) {
            return $u;
        }
        $fehler = [];
        if (!password_verify($q->input('current'), $this->users->passwordHash($u->id))) {
            $fehler['current'] = $this->t->get('einst.fehler.passwort_aktuell');
        }
        $neu = new AdminInput($u->name, $u->email, $q->input('password'), $q->input('password_repeat'));
        foreach (['password', 'password_repeat'] as $feld) {
            $k = $neu->validate()[$feld] ?? null;
            if ($k !== null) {
                $fehler[$feld] = $this->t->get($k);
            }
        }
        if ($fehler !== []) {
            return $this->konto($q, $fehler, 422);
        }
        $this->users->setPassword($u->id, $q->input('password'));
        $this->session->regenerate();
        $this->merke('success', $this->t->get('einst.konto.passwort_geaendert'));

        return Response::redirect($q->url('/einstellungen/konto'));
    }
}
