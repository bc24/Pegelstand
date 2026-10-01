<?php

declare(strict_types=1);

namespace Pegelstand\Core;

use DateTimeZone;
use PDOException;
use Pegelstand\Auth\AuthService;
use Pegelstand\Auth\Crypto;
use Pegelstand\Auth\LoginController;
use Pegelstand\Database\Database;
use Pegelstand\Database\MigrationException;
use Pegelstand\Database\Migrator;
use Pegelstand\Geo\MmdbCountryLookup;
use Pegelstand\Ingest\BotFilter;
use Pegelstand\Ingest\ClientIp;
use Pegelstand\Ingest\Collector;
use Pegelstand\Ingest\Dictionary;
use Pegelstand\Ingest\IngestController;
use Pegelstand\Ingest\RateLimiter;
use Pegelstand\Ingest\SaltService;
use Pegelstand\Ingest\UrlParser;
use Pegelstand\Ingest\UserAgentParser;
use Pegelstand\Install\ConfigWriter;
use Pegelstand\Install\InstallController;
use Pegelstand\Install\Installer;
use Pegelstand\Jobs\AggregationJob;
use Pegelstand\Jobs\CleanupJob;
use Pegelstand\Jobs\JobRunner;
use Pegelstand\Jobs\Scheduler;
use Pegelstand\Settings\GoalRepository;
use Pegelstand\Settings\SettingsController;
use Pegelstand\Settings\SiteRepository;
use Pegelstand\Settings\UserRepository;
use Pegelstand\Stats\Aggregator;
use Pegelstand\Stats\DashboardController;
use Pegelstand\Stats\DashboardService;
use Pegelstand\Stats\ReferrerClassifier;
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

    /**
     * Datenbankverbindung der installierten Anwendung, null solange nichts installiert ist.
     * Für Kommandozeilen-Werkzeuge (bin/cron.php, bin/demo-data.php).
     */
    public function database(): ?Database
    {
        $config = Config::load($this->paths->configFile());

        return $config === null ? null : $this->connect($config);
    }

    private function connect(Config $config): Database
    {
        return Database::connect([
            'host' => $config->string('db.host', 'localhost'),
            'port' => $config->int('db.port', 3306),
            'name' => $config->string('db.name'),
            'user' => $config->string('db.user'),
            'password' => $config->string('db.password'),
            'prefix' => $config->string('db.prefix', 'ps_'),
        ]);
    }

    /** Stellt sicher, dass das Schema aktuell ist (für Kommandozeilen-Werkzeuge). */
    public function migrate(Database $db): void
    {
        (new Migrator($db, $this->paths->migrationsDir()))->migrate();
    }

    public function scheduler(Database $db): Scheduler
    {
        return new Scheduler(
            new JobRunner($db),
            [new AggregationJob($db, new Aggregator($db)), new CleanupJob($db)],
            new ErrorLog($this->paths->storageDir() . '/logs/error.log'),
        );
    }

    /**
     * Pseudo-Cron: Nach der Antwort an den Besucher laufen fällige Hintergrundjobs. So funktioniert Pegelstand auch
     * ohne Cronjob. Eine Marker-Datei verhindert, dass jede Anfrage die Datenbank fragt. Wer einen echten Cronjob
     * (`php bin/cron.php`) einrichtet, setzt `cron.mode` auf `external`.
     */
    public function afterResponse(): void
    {
        try {
            $config = Config::load($this->paths->configFile());
            if ($config === null || $config->string('cron.mode', 'pseudo') !== 'pseudo') {
                return;
            }
            $marker = $this->paths->storageDir() . '/cache/cron-last';
            if (is_file($marker) && time() - (int) filemtime($marker) < 60) {
                return;
            }
            if (!is_dir(dirname($marker))) {
                @mkdir(dirname($marker), 0750, true);
            }
            @touch($marker);
            @set_time_limit(60);
            ignore_user_abort(true);
            $db = $this->connect($config);
            if (!$db->tableExists('job_runs')) {
                return;
            }
            $this->scheduler($db)->runDue();
        } catch (Throwable $fehler) {
            (new ErrorLog($this->paths->storageDir() . '/logs/error.log'))->write($fehler);
        }
    }

    private function betrieb(Request $request, Config $config, View $view): Response
    {
        $db = $this->connect($config);

        $wartung = $this->sichereSchema($db, $request);
        if ($wartung !== null) {
            return $wartung;
        }

        $router = new Router();
        $proxy = $config->get('proxy.trusted', []);
        $salts = new SaltService($db, new DateTimeZone($config->string('rotation_timezone', 'Europe/Berlin')));
        $clientIp = new ClientIp(
            $config->string('proxy.header'),
            is_array($proxy) ? array_values(array_filter($proxy, 'is_string')) : [],
        );
        $skript = $config->string('tracker.script_path', '/p.js');
        $endpunkt = $config->string('tracker.endpoint_path', '/api/event');
        (new IngestController(
            new Collector(
                $db,
                $salts,
                new Dictionary($db),
                new UrlParser(),
                new UserAgentParser(),
                BotFilter::fromFile($this->paths->resourcesDir() . '/data/bots.php'),
                new MmdbCountryLookup($this->paths->geoIpFile()),
                new RateLimiter($db),
                $clientIp,
                $config->int('ingest.rate_limit', 300),
            ),
            $this->paths->assetsDir() . '/p.js',
            $skript,
            $endpunkt,
        ))->register($router);

        // Die Bedienoberfläche. Die Sitzung startet erst, wenn jemand sie wirklich braucht, nie für Messpunkte.
        $translator = $this->translator();
        $session = $this->session($request);
        $csrf = new Csrf($session);
        $auth = new AuthService($db, $session, new Crypto($config->string('app_key')));
        (new LoginController($view, $translator, $csrf, $auth, new RateLimiter($db), $salts, $clientIp, $config->int('login.rate_limit', 10)))->register($router);
        (new SettingsController($view, $translator, $csrf, $session, $auth, new SiteRepository($db), new UserRepository($db), new GoalRepository($db), $skript, $endpunkt))->register($router);
        (new DashboardController(
            $view,
            $translator,
            $csrf,
            $auth,
            new DashboardService(
                $db,
                ReferrerClassifier::fromFile($this->paths->resourcesDir() . '/data/quellen.php'),
                self::laender($this->paths->resourcesDir() . '/data/laender.php'),
            ),
            Version::CURRENT,
            $skript,
            $endpunkt,
        ))->register($router);

        return $router->dispatch($request) ?? $this->fehlerseite($request, '404', 404);
    }

    /**
     * @return array<string, string>
     */
    private static function laender(string $datei): array
    {
        $liste = is_file($datei) ? (static fn(): mixed => require $datei)() : [];

        $ergebnis = [];
        foreach (is_array($liste) ? $liste : [] as $code => $name) {
            if (is_string($name)) {
                $ergebnis[(string) $code] = $name;
            }
        }

        return $ergebnis;
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
