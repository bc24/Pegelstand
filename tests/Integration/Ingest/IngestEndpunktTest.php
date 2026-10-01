<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Integration\Ingest;

use Pegelstand\Core\Application;
use Pegelstand\Core\ArraySession;
use Pegelstand\Core\Paths;
use Pegelstand\Core\Request;
use Pegelstand\Core\Response;
use Pegelstand\Database\Database;
use Pegelstand\Install\AdminInput;
use Pegelstand\Install\DatabaseInput;
use Pegelstand\Install\Installer;
use Pegelstand\Tests\Integration\DatenbankTestCase;

/**
 * Prüft Endpunkt und Script-Auslieferung durch die ganze Anwendung (Router, Konfiguration, Datenbank).
 */
final class IngestEndpunktTest extends DatenbankTestCase
{
    private const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';

    private string $temp = '';
    private Database $db;

    protected function setUp(): void
    {
        $this->temp = sys_get_temp_dir() . '/ps-ingest-' . bin2hex(random_bytes(4));
        mkdir($this->temp . '/config', 0777, true);
        mkdir($this->temp . '/storage', 0777, true);
        $z = $this->zugang();
        $praefix = $this->neuerPraefix();
        (new Installer(new Paths(PEGELSTAND_ROOT, $this->temp . '/config', $this->temp . '/storage')))->install(
            new DatabaseInput($z['host'], $z['port'], $z['name'], $z['user'], $z['password'], $praefix),
            new AdminInput('Frank', 'frank@beispiel.de', 'ein sehr langer satz', 'ein sehr langer satz'),
        );
        $this->db = Database::connect([...$z, 'prefix' => $praefix]);
        $this->db->run(
            'INSERT INTO ' . $this->db->table('sites') . ' (public_id, name, domain, created_at) VALUES (?, ?, ?, ?)',
            ['abcd1234abcd1234', 'Test', 'beispiel.de', '2026-10-01 00:00:00'],
        );
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
        return new Application(new Paths(PEGELSTAND_ROOT, $this->temp . '/config', $this->temp . '/storage'), new ArraySession());
    }

    private function senden(string $body): Response
    {
        return $this->app()->handle(new Request('POST', '/api/event', headers: ['user-agent' => self::UA, 'content-type' => 'text/plain'], ip: '203.0.113.5', body: $body));
    }

    public function testMesspunktWirdMit202AngenommenUndGespeichert(): void
    {
        $antwort = $this->senden('{"s":"abcd1234abcd1234","u":"https://beispiel.de/start"}');

        self::assertSame(202, $antwort->status);
        self::assertSame('', $antwort->body);
        self::assertSame('*', $antwort->headers['Access-Control-Allow-Origin']);
        self::assertSame(1, $this->db->fetchInt('SELECT COUNT(*) FROM ' . $this->db->table('events')));
    }

    public function testFehlerstatus(): void
    {
        self::assertSame(400, $this->senden('kaputt')->status);
        self::assertSame(404, $this->senden('{"s":"zzzzzzzzzzzzzzzz","u":"https://beispiel.de/"}')->status);
        self::assertSame(413, $this->senden(str_repeat('x', 9000))->status);
        self::assertSame(202, $this->senden('{"s":"abcd1234abcd1234","u":"https://fremd.example/"}')->status);
        self::assertSame(0, $this->db->fetchInt('SELECT COUNT(*) FROM ' . $this->db->table('events')));
    }

    public function testPreflightWirdBeantwortet(): void
    {
        $antwort = $this->app()->handle(new Request('OPTIONS', '/api/event'));

        self::assertSame(204, $antwort->status);
        self::assertSame('*', $antwort->headers['Access-Control-Allow-Origin']);
        self::assertStringContainsString('POST', $antwort->headers['Access-Control-Allow-Methods']);
        self::assertStringContainsString('Content-Type', $antwort->headers['Access-Control-Allow-Headers']);
    }

    public function testGetAufEndpunktIstMethodNotAllowed(): void
    {
        self::assertSame(405, $this->app()->handle(new Request('GET', '/api/event'))->status);
    }

    public function testScriptWirdMitCacheHeadernAusgeliefert(): void
    {
        if (!is_file(PEGELSTAND_ROOT . '/assets/p.js')) {
            self::markTestSkipped('assets/p.js ist nicht gebaut (npm run build).');
        }
        $antwort = $this->app()->handle(new Request('GET', '/p.js'));

        self::assertSame(200, $antwort->status);
        self::assertStringContainsString('javascript', $antwort->headers['Content-Type']);
        self::assertStringContainsString('max-age', $antwort->headers['Cache-Control']);
        self::assertStringContainsString('api/event', $antwort->body);

        $erneut = $this->app()->handle(new Request('GET', '/p.js', headers: ['if-none-match' => $antwort->headers['ETag']]));
        self::assertSame(304, $erneut->status);
        self::assertSame('', $erneut->body);
    }

    public function testAnderePfadeAusKonfiguration(): void
    {
        $datei = $this->temp . '/config/config.php';
        $inhalt = (string) file_get_contents($datei);
        file_put_contents($datei, str_replace('return array (', "return array (\n    'tracker' => ['script_path' => '/stats.js', 'endpoint_path' => '/stats/senden'],", $inhalt));

        $anfrage = new Request('POST', '/stats/senden', headers: ['user-agent' => self::UA], ip: '203.0.113.5', body: '{"s":"abcd1234abcd1234","u":"https://beispiel.de/"}');
        self::assertSame(202, $this->app()->handle($anfrage)->status);
        self::assertSame(1, $this->db->fetchInt('SELECT COUNT(*) FROM ' . $this->db->table('events')));
        self::assertSame(404, $this->senden('{}')->status, 'Der alte Pfad ist nicht mehr belegt.');
        self::assertSame(404, $this->app()->handle(new Request('GET', '/p.js'))->status);
    }
}
