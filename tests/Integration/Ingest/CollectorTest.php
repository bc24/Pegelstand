<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Integration\Ingest;

use DateTimeImmutable;
use DateTimeZone;
use Pegelstand\Core\Request;
use Pegelstand\Database\Database;
use Pegelstand\Database\Migrator;
use Pegelstand\Geo\CountryLookup;
use Pegelstand\Ingest\BotFilter;
use Pegelstand\Ingest\ClientIp;
use Pegelstand\Ingest\Collector;
use Pegelstand\Ingest\Dictionary;
use Pegelstand\Ingest\IngestResult;
use Pegelstand\Ingest\RateLimiter;
use Pegelstand\Ingest\SaltService;
use Pegelstand\Ingest\UrlParser;
use Pegelstand\Ingest\UserAgentParser;
use Pegelstand\Tests\Integration\DatenbankTestCase;

final class CollectorTest extends DatenbankTestCase
{
    private const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';

    private Database $db;
    private DateTimeImmutable $jetzt;
    private int $limit = 300;

    protected function setUp(): void
    {
        $this->db = $this->datenbank();
        (new Migrator($this->db, PEGELSTAND_ROOT . '/database/migrations'))->migrate();
        $this->db->run(
            'INSERT INTO ' . $this->db->table('sites') . ' (public_id, name, domain, allowed_hosts, created_at) VALUES (?, ?, ?, ?, ?)',
            ['abcd1234abcd1234', 'Test', 'beispiel.de', '*.beispiel.de', '2026-10-01 00:00:00'],
        );
        $this->jetzt = new DateTimeImmutable('2026-10-01 10:00:00', new DateTimeZone('UTC'));
    }

    private function collector(): Collector
    {
        $geo = new class implements CountryLookup {
            public function country(string $ip): ?string
            {
                return $ip === '203.0.113.5' ? 'DE' : null;
            }
        };

        return new Collector(
            $this->db,
            new SaltService($this->db, new DateTimeZone('Europe/Berlin')),
            new Dictionary($this->db),
            new UrlParser(),
            new UserAgentParser(),
            BotFilter::fromFile(PEGELSTAND_ROOT . '/resources/data/bots.php'),
            $geo,
            new RateLimiter($this->db),
            new ClientIp(),
            $this->limit,
            fn(): DateTimeImmutable => $this->jetzt,
        );
    }

    /**
     * @param array<string, mixed> $daten
     * @param array<string, string> $header
     */
    private function senden(array $daten = [], array $header = [], string $ip = '203.0.113.5'): IngestResult
    {
        $daten += ['s' => 'abcd1234abcd1234', 'u' => 'https://beispiel.de/start'];

        return $this->collector()->collect(new Request(
            'POST',
            '/api/event',
            headers: [...['user-agent' => self::UA], ...$header],
            ip: $ip,
            body: json_encode($daten, JSON_THROW_ON_ERROR),
        ));
    }

    private function woerterbuch(string $tabelle, mixed $id): mixed
    {
        return $this->db->fetchValue('SELECT value FROM ' . $this->db->table($tabelle) . ' WHERE id = ?', [is_numeric($id) ? (int) $id : 0]);
    }

    private function zaehle(string $tabelle): int
    {
        return $this->db->fetchInt('SELECT COUNT(*) FROM ' . $this->db->table($tabelle));
    }

    public function testSeitenaufrufErzeugtSitzungUndEreignis(): void
    {
        $ergebnis = $this->senden(['u' => 'https://beispiel.de/preise?utm_source=Newsletter&utm_campaign=Herbst&x=1', 'r' => 'https://www.google.com/search?q=a']);

        self::assertSame(IngestResult::Accepted, $ergebnis);
        self::assertSame(1, $this->zaehle('sessions'));
        self::assertSame(1, $this->zaehle('events'));
        $sitzung = $this->db->fetchAll('SELECT * FROM ' . $this->db->table('sessions'))[0];
        self::assertSame('DE', $sitzung['country']);
        self::assertEquals(1, $sitzung['device']);
        self::assertEquals(1, $sitzung['pageviews']);
        self::assertEquals(0, $sitzung['custom_events']);
        self::assertSame('2026-10-01 10:00:00', $sitzung['started_at']);
        self::assertSame(
            'google.com',
            $this->woerterbuch('dict_referrer', $sitzung['referrer_id']),
        );
        self::assertSame(
            'newsletter',
            $this->woerterbuch('dict_utm', $sitzung['utm_source_id']),
        );
        self::assertSame(
            '/preise',
            $this->woerterbuch('dict_path', $sitzung['entry_path_id']),
        );
        self::assertNull($sitzung['utm_medium_id']);
        self::assertSame('Chrome', $this->woerterbuch('dict_browser', $sitzung['browser_id']));
    }

    public function testZweiterAufrufGehoertZurSelbenSitzung(): void
    {
        $this->senden(['u' => 'https://beispiel.de/a']);
        $this->jetzt = $this->jetzt->modify('+10 minutes');
        $this->senden(['u' => 'https://beispiel.de/b', 'r' => 'https://beispiel.de/a']);

        self::assertSame(1, $this->zaehle('sessions'));
        self::assertSame(2, $this->zaehle('events'));
        $sitzung = $this->db->fetchAll('SELECT * FROM ' . $this->db->table('sessions'))[0];
        self::assertEquals(2, $sitzung['pageviews']);
        self::assertSame('2026-10-01 10:10:00', $sitzung['last_seen_at']);
        self::assertNotEquals($sitzung['entry_path_id'], $sitzung['exit_path_id']);
        self::assertNull($sitzung['referrer_id']);
    }

    public function testNachPauseEntstehtNeueSitzung(): void
    {
        $this->senden();
        $this->jetzt = $this->jetzt->modify('+31 minutes');
        $this->senden();

        self::assertSame(2, $this->zaehle('sessions'));
    }

    public function testVerschiedeneBesucherHabenVerschiedeneSitzungen(): void
    {
        $this->senden(ip: '203.0.113.5');
        $this->senden(ip: '203.0.113.6');
        $this->senden(header: ['user-agent' => self::UA . ' X'], ip: '203.0.113.5');

        self::assertSame(3, $this->zaehle('sessions'));
    }

    public function testEigeneReferrerWerdenIgnoriert(): void
    {
        $this->senden(['r' => 'https://shop.beispiel.de/warenkorb']);

        self::assertNull($this->db->fetchValue('SELECT referrer_id FROM ' . $this->db->table('sessions')));
        self::assertSame(0, $this->zaehle('dict_referrer'));
    }

    public function testBenutzerdefiniertesEreignisMitEigenschaften(): void
    {
        $this->senden();
        $ergebnis = $this->senden(['n' => 'Signup', 'p' => ['plan' => 'pro', 'preis' => 9.5, 'aktiv' => true, 'leer' => '', 'ungültig!' => 'x', 'liste' => [1]]]);

        self::assertSame(IngestResult::Accepted, $ergebnis);
        self::assertSame(2, $this->zaehle('events'));
        $sitzung = $this->db->fetchAll('SELECT * FROM ' . $this->db->table('sessions'))[0];
        self::assertEquals(1, $sitzung['pageviews']);
        self::assertEquals(1, $sitzung['custom_events']);
        $eigenschaften = $this->db->fetchAll(
            'SELECT k.value AS k, v.value AS v FROM ' . $this->db->table('event_props') . ' p'
            . ' JOIN ' . $this->db->table('dict_prop_key') . ' k ON k.id = p.key_id'
            . ' JOIN ' . $this->db->table('dict_prop_value') . ' v ON v.id = p.value_id ORDER BY k.value',
        );
        self::assertSame([['k' => 'aktiv', 'v' => 'true'], ['k' => 'plan', 'v' => 'pro'], ['k' => 'preis', 'v' => '9.5']], $eigenschaften);
        self::assertSame('Signup', $this->db->fetchValue('SELECT value FROM ' . $this->db->table('dict_event_name')));
    }

    public function testEreignisAlsErsterAufrufLegtSitzungOhneSeitenaufrufAn(): void
    {
        $this->senden(['n' => 'Klick']);

        $sitzung = $this->db->fetchAll('SELECT pageviews, custom_events FROM ' . $this->db->table('sessions'))[0];
        self::assertEquals(0, $sitzung['pageviews']);
        self::assertEquals(1, $sitzung['custom_events']);
    }

    public function testPageviewAlsEreignisnameIstSeitenaufruf(): void
    {
        $this->senden(['n' => 'pageview']);

        self::assertSame(0, $this->zaehle('dict_event_name'));
        self::assertEquals(1, $this->db->fetchValue('SELECT kind FROM ' . $this->db->table('events')));
    }

    public function testAntwortenBeiFehlern(): void
    {
        $collector = $this->collector();
        $anfrage = static fn(string $body): Request => new Request('POST', '/api/event', headers: ['user-agent' => self::UA], ip: '203.0.113.5', body: $body);

        self::assertSame(IngestResult::BadRequest, $collector->collect($anfrage('kein json')));
        self::assertSame(IngestResult::BadRequest, $collector->collect($anfrage('{"s":"abcd1234abcd1234"}')));
        self::assertSame(IngestResult::BadRequest, $collector->collect($anfrage('{"s":"zu kurz!","u":"https://beispiel.de/"}')));
        self::assertSame(IngestResult::TooLarge, $collector->collect($anfrage(str_repeat('x', Collector::MAX_BODY + 1))));
        self::assertSame(IngestResult::UnknownSite, $this->senden(['s' => 'zzzzzzzzzzzzzzzz']));
        self::assertSame(0, $this->zaehle('events'));
    }

    public function testFremderHostWirdIgnoriert(): void
    {
        self::assertSame(IngestResult::Ignored, $this->senden(['u' => 'https://evil.example/start']));
        self::assertSame(IngestResult::Accepted, $this->senden(['u' => 'https://www.beispiel.de/start']));
        self::assertSame(IngestResult::Accepted, $this->senden(['u' => 'https://app.beispiel.de/start']));
        self::assertSame(2, $this->zaehle('events'));
    }

    public function testBotsUndLeereUserAgentsWerdenIgnoriert(): void
    {
        self::assertSame(IngestResult::Ignored, $this->senden(header: ['user-agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1)']));
        self::assertSame(IngestResult::Ignored, $this->senden(header: ['user-agent' => '']));
        self::assertSame(0, $this->zaehle('events'));
    }

    public function testDoNotTrackUndGpcNurWennAktiviert(): void
    {
        self::assertSame(IngestResult::Accepted, $this->senden(header: ['dnt' => '1', 'sec-gpc' => '1']));

        $this->db->run('UPDATE ' . $this->db->table('sites') . ' SET respect_dnt = 1');
        self::assertSame(IngestResult::Ignored, $this->senden(header: ['dnt' => '1']));
        self::assertSame(IngestResult::Accepted, $this->senden(header: ['sec-gpc' => '1'], ip: '203.0.113.7'));

        $this->db->run('UPDATE ' . $this->db->table('sites') . ' SET respect_gpc = 1');
        self::assertSame(IngestResult::Ignored, $this->senden(header: ['sec-gpc' => '1']));
    }

    public function testAusgeschlosseneAdresse(): void
    {
        $this->db->run(
            'INSERT INTO ' . $this->db->table('site_ip_exclusions') . ' (site_id, ip_range) VALUES (1, ?)',
            ['203.0.113.0/24'],
        );

        self::assertSame(IngestResult::Ignored, $this->senden(ip: '203.0.113.5'));
        self::assertSame(IngestResult::Accepted, $this->senden(ip: '198.51.100.5'));
        self::assertSame(1, $this->zaehle('events'));
    }

    public function testRatenbegrenzung(): void
    {
        $this->limit = 3;
        $ergebnisse = [];
        for ($i = 0; $i < 5; ++$i) {
            $ergebnisse[] = $this->senden();
        }

        self::assertSame(
            [IngestResult::Accepted, IngestResult::Accepted, IngestResult::Accepted, IngestResult::RateLimited, IngestResult::RateLimited],
            $ergebnisse,
        );

        $this->jetzt = $this->jetzt->modify('+1 minute');
        self::assertSame(IngestResult::Accepted, $this->senden(), 'Im nächsten Zeitfenster geht es weiter.');
        self::assertSame(IngestResult::Accepted, $this->senden(ip: '198.51.100.9'), 'Andere Adresse, eigener Zähler.');
    }

    public function testKeineKlartextAdresseInDerDatenbank(): void
    {
        $this->senden(ip: '203.0.113.5');

        foreach (['sessions', 'events', 'rate_limits', 'daily_salts'] as $tabelle) {
            foreach ($this->db->fetchAll('SELECT * FROM ' . $this->db->table($tabelle)) as $zeile) {
                foreach ($zeile as $wert) {
                    self::assertStringNotContainsString('203.0.113.5', is_scalar($wert) ? (string) $wert : '', $tabelle);
                    self::assertStringNotContainsString(bin2hex(inet_pton('203.0.113.5') ?: ''), is_string($wert) ? bin2hex($wert) : '', $tabelle);
                }
            }
        }
        foreach (['dict_path', 'dict_referrer', 'dict_utm', 'dict_browser', 'dict_os'] as $tabelle) {
            self::assertSame(0, $this->db->fetchInt('SELECT COUNT(*) FROM ' . $this->db->table($tabelle) . " WHERE value LIKE '%203.0.113.5%'"));
        }
    }

    public function testSaltWechseltUmMitternachtUndAltesWirdGeloescht(): void
    {
        $this->jetzt = new DateTimeImmutable('2026-10-01 21:59:00', new DateTimeZone('UTC')); // 23:59 in Berlin
        $this->senden();
        self::assertSame(1, $this->zaehle('daily_salts'));
        $erstesSalt = $this->db->fetchValue('SELECT salt FROM ' . $this->db->table('daily_salts'));

        $this->jetzt = new DateTimeImmutable('2026-10-01 22:01:00', new DateTimeZone('UTC')); // 00:01 am Folgetag
        $this->senden();

        self::assertSame(1, $this->zaehle('daily_salts'), 'Das alte Salt ist gelöscht.');
        self::assertNotSame($erstesSalt, $this->db->fetchValue('SELECT salt FROM ' . $this->db->table('daily_salts')));
        self::assertSame(2, $this->zaehle('sessions'), 'Nach dem Wechsel ist der Besucher nicht wiederzuerkennen.');
    }

    public function testWoerterbuchLegtEintraegeNurEinmalAn(): void
    {
        $this->senden(['u' => 'https://beispiel.de/a']);
        $this->senden(['u' => 'https://beispiel.de/a'], ip: '198.51.100.1');
        $this->senden(['u' => 'https://beispiel.de/b'], ip: '198.51.100.2');

        self::assertSame(2, $this->zaehle('dict_path'));
        self::assertSame(1, $this->zaehle('dict_browser'));
    }
}
