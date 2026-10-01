<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Integration\Core;

use Pegelstand\Core\Application;
use Pegelstand\Core\ArraySession;
use Pegelstand\Core\Csrf;
use Pegelstand\Core\Paths;
use Pegelstand\Core\Request;
use Pegelstand\Core\Response;
use Pegelstand\Database\Database;
use Pegelstand\Install\AdminInput;
use Pegelstand\Install\DatabaseInput;
use Pegelstand\Install\Installer;
use Pegelstand\Tests\Integration\DatenbankTestCase;

/**
 * Prüft die Anwendung als Ganzes: Installer-Ablauf, Sperre nach der Installation und automatische Migration.
 */
final class ApplicationTest extends DatenbankTestCase
{
    private string $temp = '';
    private ArraySession $session;

    protected function setUp(): void
    {
        $this->temp = sys_get_temp_dir() . '/ps-app-' . bin2hex(random_bytes(4));
        mkdir($this->temp . '/config', 0777, true);
        mkdir($this->temp . '/storage', 0777, true);
        $this->session = new ArraySession();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->loesche($this->temp);
    }

    private function loesche(string $pfad): void
    {
        foreach (glob($pfad . '/{,.}[!.]*', GLOB_BRACE) ?: [] as $eintrag) {
            is_dir($eintrag) ? $this->loesche($eintrag) : @unlink($eintrag);
        }
        @rmdir($pfad);
    }

    private function app(): Application
    {
        return new Application(new Paths(PEGELSTAND_ROOT, $this->temp . '/config', $this->temp . '/storage'), $this->session);
    }

    /**
     * @param array<string, string> $post
     */
    private function anfrage(string $methode, string $pfad, array $post = []): Response
    {
        return $this->app()->handle(new Request($methode, $pfad, '', [], $post));
    }

    private function csrf(): string
    {
        return (new Csrf($this->session))->token();
    }

    private function installiere(string $praefix): void
    {
        $z = $this->zugang();
        (new Installer(new Paths(PEGELSTAND_ROOT, $this->temp . '/config', $this->temp . '/storage')))->install(
            new DatabaseInput($z['host'], $z['port'], $z['name'], $z['user'], $z['password'], $praefix),
            new AdminInput('Frank', 'frank@beispiel.de', 'ein sehr langer satz', 'ein sehr langer satz'),
        );
    }

    public function testOhneKonfigurationLeitetAllesZumInstallerUm(): void
    {
        $antwort = $this->anfrage('GET', '/irgendwas');

        self::assertSame(302, $antwort->status);
        self::assertSame('/install', $antwort->headers['Location']);
    }

    public function testInstallerSeiteMitSicherheitsHeadern(): void
    {
        $antwort = $this->anfrage('GET', '/install');

        self::assertSame(200, $antwort->status);
        self::assertStringContainsString('Willkommen bei Pegelstand', $antwort->body);
        self::assertStringContainsString('name="_csrf"', $antwort->body);
        self::assertSame('no-store', $antwort->headers['Cache-Control']);
        self::assertArrayHasKey('Content-Security-Policy', Response::SECURITY_HEADERS);
        self::assertStringContainsString("frame-ancestors 'none'", Response::SECURITY_HEADERS['Content-Security-Policy']);
    }

    public function testFormularOhneCsrfTokenWirdAbgelehnt(): void
    {
        $antwort = $this->anfrage('POST', '/install', ['_csrf' => 'falsch']);

        self::assertSame(419, $antwort->status);
        self::assertStringContainsString('Sitzung abgelaufen', $antwort->body);
    }

    public function testSchritteSindNurInDerReihenfolgeErreichbar(): void
    {
        self::assertSame('/install', $this->anfrage('GET', '/install/datenbank')->headers['Location']);
        self::assertSame('/install', $this->anfrage('GET', '/install/administrator')->headers['Location']);

        $this->anfrage('POST', '/install', ['_csrf' => $this->csrf()]);
        self::assertSame(200, $this->anfrage('GET', '/install/datenbank')->status);
        self::assertSame('/install/datenbank', $this->anfrage('GET', '/install/administrator')->headers['Location']);
    }

    public function testDatenbankSchrittZeigtFehlerMitLoesungsweg(): void
    {
        $this->anfrage('POST', '/install', ['_csrf' => $this->csrf()]);
        $z = $this->zugang();

        $antwort = $this->anfrage('POST', '/install/datenbank', [
            '_csrf' => $this->csrf(), 'host' => $z['host'], 'port' => (string) $z['port'], 'name' => $z['name'],
            'user' => $z['user'], 'password' => 'falsch-xyz', 'prefix' => 'ps_',
        ]);

        self::assertSame(422, $antwort->status);
        self::assertStringContainsString('Benutzername oder Passwort stimmen nicht', $antwort->body);
        self::assertStringContainsString('aria-invalid', $this->anfrage('POST', '/install/datenbank', ['_csrf' => $this->csrf(), 'port' => 'x'])->body);
    }

    public function testKompletterAblaufInstalliertUndSperrtSich(): void
    {
        $praefix = $this->neuerPraefix();
        $z = $this->zugang();
        $this->anfrage('POST', '/install', ['_csrf' => $this->csrf()]);
        $weiter = $this->anfrage('POST', '/install/datenbank', [
            '_csrf' => $this->csrf(), 'host' => $z['host'], 'port' => (string) $z['port'], 'name' => $z['name'],
            'user' => $z['user'], 'password' => $z['password'], 'prefix' => $praefix,
        ]);
        self::assertSame('/install/administrator', $weiter->headers['Location']);

        $fertig = $this->anfrage('POST', '/install/administrator', [
            '_csrf' => $this->csrf(), 'name' => 'Frank', 'email' => 'frank@beispiel.de',
            'password' => 'ein sehr langer satz', 'password_repeat' => 'ein sehr langer satz',
        ]);
        self::assertSame(200, $fertig->status);
        self::assertStringContainsString('Pegelstand ist installiert', $fertig->body);
        self::assertFileExists($this->temp . '/config/config.php');

        // Nach der Installation ist der Installer gesperrt, die Startseite läuft.
        self::assertSame(404, $this->anfrage('GET', '/install')->status);
        self::assertSame(404, $this->anfrage('POST', '/install/administrator')->status);
        $start = $this->anfrage('GET', '/');
        self::assertSame(200, $start->status);
        self::assertStringContainsString('Pegelstand ist installiert', $start->body);
    }

    public function testAdministratorSchrittZeigtFeldfehler(): void
    {
        $this->session->set('install.pruefung', true);
        $z = $this->zugang();
        $this->session->set('install.db', [...$z, 'prefix' => $this->neuerPraefix()]);

        $antwort = $this->anfrage('POST', '/install/administrator', [
            '_csrf' => $this->csrf(), 'name' => '', 'email' => 'kaputt', 'password' => 'kurz', 'password_repeat' => 'kurz',
        ]);

        self::assertSame(422, $antwort->status);
        self::assertStringContainsString('Bitte prüfe deine Angaben', $antwort->body);
        self::assertStringContainsString('href="#f-email"', $antwort->body);
        self::assertStringContainsString('Wähle mindestens 12 Zeichen', $antwort->body);
        self::assertFileDoesNotExist($this->temp . '/config/config.php');
    }

    public function testOffeneMigrationenLaufenBeimUpdateAutomatisch(): void
    {
        $praefix = $this->neuerPraefix();
        $this->installiere($praefix);
        $db = Database::connect([...$this->zugang(), 'prefix' => $praefix]);

        // Update simulieren: Tabellen der Version verschwinden, der Zwischenspeicher kennt die Version nicht mehr.
        $db->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $db->pdo->exec('DROP TABLE ' . $db->table('site_users'));
        $db->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        $db->run('DELETE FROM ' . $db->table('migrations'));
        @unlink($this->temp . '/storage/cache/schema-version');

        self::assertSame(200, $this->anfrage('GET', '/')->status);
        self::assertTrue($db->tableExists('site_users'), 'Die Migration hat die Tabelle wieder angelegt.');
        self::assertSame('0002', file_get_contents($this->temp . '/storage/cache/schema-version'));
    }

    public function testWartungsseiteWennGerademigriertWird(): void
    {
        $praefix = $this->neuerPraefix();
        $this->installiere($praefix);
        @unlink($this->temp . '/storage/cache/schema-version');
        $db = Database::connect([...$this->zugang(), 'prefix' => $praefix]);
        $sperre = $db->name . '.' . $db->prefix . 'migrate';
        $db->run('SELECT GET_LOCK(?, 0)', [$sperre]);

        $antwort = $this->anfrage('GET', '/');
        $db->run('SELECT RELEASE_LOCK(?)', [$sperre]);

        self::assertSame(503, $antwort->status);
        self::assertSame('5', $antwort->headers['Retry-After']);
        self::assertStringContainsString('Pegelstand wird aktualisiert', $antwort->body);
    }

    public function testFehlendeDatenbankZeigtHilfeseiteUndSchreibtLog(): void
    {
        $praefix = $this->neuerPraefix();
        $this->installiere($praefix);
        $konfiguration = (string) file_get_contents($this->temp . '/config/config.php');
        file_put_contents($this->temp . '/config/config.php', str_replace("'password' => '" . $this->zugang()['password'] . "'", "'password' => 'kaputt-xyz'", $konfiguration));

        $antwort = $this->anfrage('GET', '/');

        self::assertSame(503, $antwort->status);
        self::assertStringContainsString('Die Datenbank ist nicht erreichbar', $antwort->body);
        self::assertFileExists($this->temp . '/storage/logs/error.log');
        self::assertStringNotContainsString('kaputt-xyz', (string) file_get_contents($this->temp . '/storage/logs/error.log'));
    }

    public function testUnbekannteSeiteIstEine404(): void
    {
        $this->installiere($this->neuerPraefix());

        $antwort = $this->anfrage('GET', '/gibt-es-nicht');

        self::assertSame(404, $antwort->status);
        self::assertStringContainsString('Seite nicht gefunden', $antwort->body);
    }
}
