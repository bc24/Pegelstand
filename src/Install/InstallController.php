<?php

declare(strict_types=1);

namespace Pegelstand\Install;

use Pegelstand\Core\Csrf;
use Pegelstand\Core\ErrorLog;
use Pegelstand\Core\Paths;
use Pegelstand\Core\Request;
use Pegelstand\Core\Response;
use Pegelstand\Core\Router;
use Pegelstand\Core\Session;
use Pegelstand\Core\Translator;
use Pegelstand\Core\View;
use Throwable;

/**
 * Der Installer in vier Schritten: Prüfung, Datenbank, Administrator, Fertig.
 * Die Zugangsdaten liegen bis zum Abschluss in der Sitzung (storage/sessions, von außen gesperrt).
 */
final class InstallController
{
    private const KEY_PRUEFUNG = 'install.pruefung';
    private const KEY_DB = 'install.db';
    private const KEY_DB_WERTE = 'install.db_werte';
    private const KEY_ADMIN_WERTE = 'install.admin_werte';

    public function __construct(
        private readonly View $view,
        private readonly Translator $translator,
        private readonly Csrf $csrf,
        private readonly Session $session,
        private readonly Installer $installer,
        private readonly Paths $paths,
        private readonly ErrorLog $log,
    ) {}

    public function register(Router $router): void
    {
        $router->add('GET', '/install', fn(Request $r): Response => $this->pruefung($r));
        $router->add('POST', '/install', fn(Request $r): Response => $this->pruefungAbschicken($r));
        $router->add('GET', '/install/datenbank', fn(Request $r): Response => $this->datenbank($r));
        $router->add('POST', '/install/datenbank', fn(Request $r): Response => $this->datenbankAbschicken($r));
        $router->add('GET', '/install/administrator', fn(Request $r): Response => $this->administrator($r));
        $router->add('POST', '/install/administrator', fn(Request $r): Response => $this->administratorAbschicken($r));
    }

    private function pruefung(Request $request): Response
    {
        $checks = Requirements::forThisServer($this->paths->configDir(), $this->paths->storageDir())->run();

        return Response::html($this->view->render('install/pruefung', [
            'titel' => $this->translator->get('install.schritte.pruefung'),
            'checks' => $checks,
            'blockiert' => Requirements::isBlocked($checks),
            'csrf' => $this->csrf->token(),
        ]));
    }

    private function pruefungAbschicken(Request $request): Response
    {
        if (!$this->csrf->verify($request->input('_csrf'))) {
            return $this->csrfFehler();
        }
        $checks = Requirements::forThisServer($this->paths->configDir(), $this->paths->storageDir())->run();
        if (Requirements::isBlocked($checks)) {
            return Response::redirect($request->url('/install'));
        }
        $this->session->set(self::KEY_PRUEFUNG, true);

        return Response::redirect($request->url('/install/datenbank'));
    }

    private function datenbank(Request $request): Response
    {
        if ($this->session->get(self::KEY_PRUEFUNG) !== true) {
            return Response::redirect($request->url('/install'));
        }
        $gespeichert = $this->session->get(self::KEY_DB_WERTE);
        $werte = is_array($gespeichert) ? $gespeichert : [];

        return $this->datenbankSeite($this->stringWerte($werte, ['host' => 'localhost', 'port' => '3306', 'name' => '', 'user' => '', 'prefix' => 'ps_']), [], '');
    }

    private function datenbankAbschicken(Request $request): Response
    {
        if (!$this->csrf->verify($request->input('_csrf'))) {
            return $this->csrfFehler();
        }
        if ($this->session->get(self::KEY_PRUEFUNG) !== true) {
            return Response::redirect($request->url('/install'));
        }
        $eingabe = DatabaseInput::fromPost($request->post);
        $werte = [
            'host' => $eingabe->host,
            'port' => $request->input('port', '3306'),
            'name' => $eingabe->name,
            'user' => $eingabe->user,
            'prefix' => $eingabe->prefix,
        ];
        $fehler = $this->uebersetzeFehler($eingabe->validate());
        if ($fehler !== []) {
            return $this->datenbankSeite($werte, $fehler, '', 422);
        }
        try {
            $this->installer->connect($eingabe);
        } catch (InstallException $e) {
            return $this->datenbankSeite($werte, [], $this->translator->get($e->messageKey, $e->params), 422);
        }
        $this->session->set(self::KEY_DB, $eingabe->toArray());
        $this->session->set(self::KEY_DB_WERTE, $werte);

        return Response::redirect($request->url('/install/administrator'));
    }

    private function administrator(Request $request): Response
    {
        if (!is_array($this->session->get(self::KEY_DB))) {
            return Response::redirect($request->url($this->session->get(self::KEY_PRUEFUNG) === true ? '/install/datenbank' : '/install'));
        }
        $gespeichert = $this->session->get(self::KEY_ADMIN_WERTE);

        return $this->administratorSeite($this->stringWerte(is_array($gespeichert) ? $gespeichert : [], ['name' => '', 'email' => '']), [], '');
    }

    private function administratorAbschicken(Request $request): Response
    {
        if (!$this->csrf->verify($request->input('_csrf'))) {
            return $this->csrfFehler();
        }
        $gespeichert = $this->session->get(self::KEY_DB);
        if (!is_array($gespeichert)) {
            return Response::redirect($request->url('/install'));
        }
        $dbEingabe = DatabaseInput::fromPost($gespeichert);
        $admin = AdminInput::fromPost($request->post);
        $werte = ['name' => $admin->name, 'email' => $admin->email];
        $fehler = $this->uebersetzeFehler($admin->validate());
        if ($fehler !== []) {
            $this->session->set(self::KEY_ADMIN_WERTE, $werte);

            return $this->administratorSeite($werte, $fehler, '', 422);
        }

        try {
            $this->installer->install($dbEingabe, $admin);
        } catch (InstallException $e) {
            $this->session->set(self::KEY_ADMIN_WERTE, $werte);
            if ($e->getPrevious() !== null) {
                $this->log->write($e->getPrevious());
            }

            return $this->administratorSeite($werte, [], $this->translator->get($e->messageKey, $e->params), 422);
        } catch (Throwable $e) {
            $this->log->write($e);

            return $this->administratorSeite($werte, [], $this->translator->get('install.fehler.allgemein'), 500);
        }

        $this->session->destroy();

        return Response::html($this->view->render('install/fertig', ['titel' => $this->translator->get('install.fertig.titel')]));
    }

    /**
     * @param array<string, string> $werte
     * @param array<string, string> $fehler
     */
    private function datenbankSeite(array $werte, array $fehler, string $allgemein, int $status = 200): Response
    {
        return Response::html($this->view->render('install/datenbank', [
            'titel' => $this->translator->get('install.schritte.datenbank'),
            'werte' => $werte,
            'fehler' => $fehler,
            'allgemein' => $allgemein,
            'csrf' => $this->csrf->token(),
        ]), $status);
    }

    /**
     * @param array<string, string> $werte
     * @param array<string, string> $fehler
     */
    private function administratorSeite(array $werte, array $fehler, string $allgemein, int $status = 200): Response
    {
        return Response::html($this->view->render('install/administrator', [
            'titel' => $this->translator->get('install.schritte.admin'),
            'werte' => $werte,
            'fehler' => $fehler,
            'allgemein' => $allgemein,
            'csrf' => $this->csrf->token(),
        ]), $status);
    }

    private function csrfFehler(): Response
    {
        return Response::html($this->view->render('fehler/seite', [
            'titel' => $this->translator->get('fehlerseite.419.titel'),
            'art' => '419',
        ]), 419);
    }

    /**
     * @param array<string, string> $schluessel
     * @return array<string, string>
     */
    private function uebersetzeFehler(array $schluessel): array
    {
        return array_map(fn(string $key): string => $this->translator->get($key), $schluessel);
    }

    /**
     * @param array<mixed> $quelle
     * @param array<string, string> $standard
     * @return array<string, string>
     */
    private function stringWerte(array $quelle, array $standard): array
    {
        $ergebnis = [];
        foreach ($standard as $key => $vorgabe) {
            $wert = $quelle[$key] ?? $vorgabe;
            $ergebnis[$key] = is_scalar($wert) ? (string) $wert : $vorgabe;
        }

        return $ergebnis;
    }
}
