<?php

declare(strict_types=1);

namespace Pegelstand\Core;

use PDOException;
use Pegelstand\Database\Database;
use Pegelstand\Database\MigrationException;
use Pegelstand\Database\Migrator;
use Pegelstand\Install\ConfigWriter;
use Pegelstand\Install\InstallController;
use Pegelstand\Install\Installer;
use Pegelstand\Version;
use Throwable;

/**
 * Verbindet die Bausteine zu einer Anwendung: Ohne Konfiguration läuft der Installer,
 * danach der normale Betrieb. Offene Datenbank-Migrationen laufen bei Updates automatisch.
 */
final class Application
{
    private readonly Container $container;

    public function __construct(
        private readonly Paths $paths,
        ?Session $session = null,
    ) {
        $this->container = new Container();
        if ($session !== null) {
            $this->container->set('session', static fn(): Session => $session);
        }
    }

    public function handle(Request $request): Response
    {
        $log = new ErrorLog($this->paths->storageDir() . '/logs/error.log');
        try {
            $config = Config::load($this->paths->configFile());
            $view = $this->view($request);
            $session = $this->session($request);

            if ($config === null) {
                return $this->installer($request, $view, $session, $log);
            }

            return $this->betrieb($request, $config, $view);
        } catch (Throwable $fehler) {
            $log->write($fehler);

            return $this->fehlerseite($request, $fehler instanceof PDOException ? 'datenbank' : '500', $fehler instanceof PDOException ? 503 : 500);
        }
    }

    private function installer(Request $request, View $view, Session $session, ErrorLog $log): Response
    {
        $translator = $this->translator();
        $router = new Router();
        (new InstallController(
            $view,
            $translator,
            new Csrf($session),
            $session,
            new Installer($this->paths, new ConfigWriter()),
            $this->paths,
            $log,
        ))->register($router);

        $antwort = $router->dispatch($request);
        if ($antwort !== null) {
            return $antwort;
        }
        // Alles andere führt zum Installer, solange nichts installiert ist.
        if ($request->method === 'GET') {
            return Response::redirect($request->url('/install'), 302);
        }

        return $this->fehlerseite($request, '404', 404);
    }

    private function betrieb(Request $request, Config $config, View $view): Response
    {
        $db = Database::connect([
            'host' => $config->string('db.host', 'localhost'),
            'port' => $config->int('db.port', 3306),
            'name' => $config->string('db.name'),
            'user' => $config->string('db.user'),
            'password' => $config->string('db.password'),
            'prefix' => $config->string('db.prefix', 'ps_'),
        ]);

        $wartung = $this->sichereSchema($db, $request);
        if ($wartung !== null) {
            return $wartung;
        }

        $router = new Router();
        $router->add('GET', '/', static fn(Request $r): Response => Response::html($view->render('start', [
            'titel' => $view->translate('start.titel'),
            'version' => Version::CURRENT,
        ])));

        return $router->dispatch($request) ?? $this->fehlerseite($request, '404', 404);
    }

    /**
     * Führt offene Migrationen aus. Der Zwischenspeicher vermeidet bei jedem Aufruf eine Datenbankabfrage.
     */
    private function sichereSchema(Database $db, Request $request): ?Response
    {
        $migrator = new Migrator($db, $this->paths->migrationsDir());
        $merker = $this->paths->storageDir() . '/cache/schema-version';
        $neueste = $migrator->latestVersion();
        if (@file_get_contents($merker) === $neueste) {
            return null;
        }
        try {
            $migrator->migrate();
        } catch (MigrationException $fehler) {
            if ($fehler->getCode() === MigrationException::LOCKED) {
                return $this->fehlerseite($request, 'wartung', 503, ['Retry-After' => '5'], '5');
            }
            throw $fehler;
        }
        if (!is_dir(dirname($merker))) {
            @mkdir(dirname($merker), 0750, true);
        }
        @file_put_contents($merker, $neueste, LOCK_EX);

        return null;
    }

    /**
     * @param array<string, string> $header
     */
    private function fehlerseite(Request $request, string $art, int $status, array $header = [], ?string $aktualisieren = null): Response
    {
        $view = $this->view($request);
        $antwort = Response::html($view->render('fehler/seite', [
            'titel' => $view->translate('fehlerseite.' . $art . '.titel'),
            'art' => $art,
            'aktualisieren' => $aktualisieren,
        ]), $status);
        foreach ($header as $name => $wert) {
            $antwort = $antwort->withHeader($name, $wert);
        }

        return $antwort;
    }

    private function translator(): Translator
    {
        if (!$this->container->has('translator')) {
            $this->container->set('translator', fn(): Translator => Translator::fromFile($this->paths->resourcesDir() . '/lang/de.php'));
        }

        return $this->container->get('translator', Translator::class);
    }

    private function view(Request $request): View
    {
        return new View($this->paths->resourcesDir() . '/views', $this->translator(), $request->basePath);
    }

    private function session(Request $request): Session
    {
        if (!$this->container->has('session')) {
            $this->container->set('session', fn(): Session => new NativeSession($this->paths->storageDir() . '/sessions', $request->https));
        }

        return $this->container->get('session', Session::class);
    }
}
