<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Integration\Stats;

use Pegelstand\Stats\Aggregator;
use Pegelstand\Stats\Dimension;
use Pegelstand\Tests\Integration\SiteTestCase;

final class AggregatorTest extends SiteTestCase
{
    private function aggregiere(string $tag = '2026-10-01'): void
    {
        (new Aggregator($this->db))->aggregateDay(1, 'Europe/Berlin', $tag);
    }

    /**
     * @return array<string, mixed>
     */
    private function tag(string $tag = '2026-10-01'): array
    {
        return $this->db->fetchAll('SELECT * FROM ' . $this->db->table('agg_daily') . ' WHERE site_id = 1 AND day = ?', [$tag])[0] ?? [];
    }

    /**
     * @return array<string, array<string, mixed>> Wert des Wörterbuchs => Zeile
     */
    private function dim(int $dim, string $woerterbuch, string $tag = '2026-10-01'): array
    {
        $zeilen = $this->db->fetchAll(
            'SELECT d.value AS wert, a.* FROM ' . $this->db->table('agg_daily_dim') . ' a JOIN ' . $this->db->table($woerterbuch) . ' d ON d.id = a.value_id'
            . ' WHERE a.site_id = 1 AND a.day = ? AND a.dim = ?',
            [$tag, $dim],
        );
        $ergebnis = [];
        foreach ($zeilen as $zeile) {
            $ergebnis[is_string($zeile['wert']) ? $zeile['wert'] : ''] = $zeile;
        }

        return $ergebnis;
    }

    public function testTageswerte(): void
    {
        // Besucher A: 3 Seiten, 60 Sekunden. Besucher B: Absprung. Besucher A zweite Sitzung (selber Hash).
        $this->sitzung('2026-10-01 08:00:00', ['/', '/preise', '/kontakt'], ['besucher' => 'A']);
        $this->sitzung('2026-10-01 09:00:00', ['/'], ['besucher' => 'B']);
        $this->sitzung('2026-10-01 14:00:00', ['/blog'], ['besucher' => 'A']);
        $this->aggregiere();

        $tag = $this->tag();
        self::assertEquals(2, $tag['visitors']);
        self::assertEquals(3, $tag['sessions']);
        self::assertEquals(5, $tag['pageviews']);
        self::assertEquals(2, $tag['bounces']);
        self::assertEquals(60, $tag['duration_sum']);
        self::assertEquals(0, $tag['events']);
    }

    public function testTagesgrenzeFolgtDerZeitzoneDerSite(): void
    {
        // Berlin ist im Oktober UTC+2: 30.09. 22:30 UTC ist schon der 01.10. (00:30), 01.10. 22:30 UTC ist der 02.10.
        $this->sitzung('2026-09-30 22:30:00', ['/a']);
        $this->sitzung('2026-10-01 21:59:00', ['/b']);
        $this->sitzung('2026-10-01 22:00:00', ['/c']);
        $this->aggregiere('2026-10-01');

        self::assertEquals(2, $this->tag('2026-10-01')['sessions']);
        $this->aggregiere('2026-10-02');
        self::assertEquals(1, $this->tag('2026-10-02')['sessions']);
    }

    public function testSommerzeitwechselHat25Stunden(): void
    {
        // 25.10.2026: Umstellung auf Winterzeit (03:00 -> 02:00). Der Tag dauert 25 Stunden: 24.10. 22:00 UTC bis 25.10. 23:00 UTC.
        $this->sitzung('2026-10-24 22:00:00', ['/anfang']);
        $this->sitzung('2026-10-25 22:59:00', ['/ende']);
        $this->sitzung('2026-10-25 23:00:00', ['/naechster-tag']);
        $this->aggregiere('2026-10-25');

        self::assertEquals(2, $this->tag('2026-10-25')['sessions']);
    }

    public function testStundenwerteInLokalzeit(): void
    {
        $this->sitzung('2026-10-01 08:10:00', ['/', '/x'], ['besucher' => 'A']);
        $this->sitzung('2026-10-01 08:50:00', ['/'], ['besucher' => 'B']);
        $this->sitzung('2026-10-01 10:00:00', ['/'], ['besucher' => 'C']);
        $this->aggregiere();

        $stunden = $this->db->fetchAll('SELECT * FROM ' . $this->db->table('agg_hourly') . ' ORDER BY hour');
        self::assertCount(2, $stunden);
        self::assertSame('2026-10-01 10:00:00', $stunden[0]['hour'], '08:xx UTC ist 10:xx in Berlin.');
        self::assertEquals(2, $stunden[0]['sessions']);
        self::assertEquals(3, $stunden[0]['pageviews']);
        self::assertEquals(1, $stunden[0]['bounces']);
        self::assertSame('2026-10-01 12:00:00', $stunden[1]['hour']);
    }

    public function testSummeDerStundenEntsprichtDemTag(): void
    {
        for ($i = 0; $i < 20; ++$i) {
            $this->sitzung(sprintf('2026-10-01 %02d:%02d:00', 5 + $i % 12, $i * 2), ['/', '/b'], ['besucher' => 'v' . $i]);
        }
        $this->aggregiere();

        $summe = $this->db->fetchAll('SELECT SUM(sessions) AS s, SUM(pageviews) AS p, SUM(visitors) AS v FROM ' . $this->db->table('agg_hourly'))[0];
        self::assertEquals($this->tag()['sessions'], $summe['s']);
        self::assertEquals($this->tag()['pageviews'], $summe['p']);
        self::assertEquals(20, $summe['v']);
    }

    public function testAufschluesselungen(): void
    {
        $this->sitzung('2026-10-01 08:00:00', ['/', '/preise'], ['besucher' => 'A', 'land' => 'DE', 'geraet' => 1, 'browser' => 'Chrome', 'os' => 'Windows', 'referrer' => 'google.com']);
        $this->sitzung('2026-10-01 09:00:00', ['/preise'], ['besucher' => 'B', 'land' => 'DE', 'geraet' => 2, 'browser' => 'Safari', 'os' => 'iOS', 'utm_source' => 'newsletter']);
        $this->sitzung('2026-10-01 10:00:00', ['/'], ['besucher' => 'C', 'land' => 'AT', 'geraet' => 2, 'browser' => 'Safari', 'os' => 'iOS', 'referrer' => 'google.com']);
        $this->sitzung('2026-10-01 11:00:00', ['/'], ['besucher' => 'D', 'geraet' => 0]);
        $this->aggregiere();

        $seiten = $this->dim(Dimension::PAGE, 'dict_path');
        self::assertEquals(3, $seiten['/']['hits']);
        self::assertEquals(3, $seiten['/']['visitors']);
        self::assertEquals(2, $seiten['/preise']['hits']);

        $einstieg = $this->dim(Dimension::ENTRY_PAGE, 'dict_path');
        self::assertEquals(3, $einstieg['/']['sessions']);
        self::assertEquals(2, $einstieg['/']['bounces']);
        self::assertEquals(1, $einstieg['/preise']['sessions']);

        $ausstieg = $this->dim(Dimension::EXIT_PAGE, 'dict_path');
        self::assertEquals(2, $ausstieg['/preise']['sessions']);

        $referrer = $this->dim(Dimension::REFERRER, 'dict_referrer');
        self::assertEquals(2, $referrer['google.com']['sessions']);
        self::assertEquals(2, $referrer['google.com']['visitors']);
        self::assertCount(1, $referrer, 'Sitzungen ohne Referrer erscheinen nicht.');

        self::assertEquals(1, $this->dim(Dimension::UTM_SOURCE, 'dict_utm')['newsletter']['sessions']);

        $laender = $this->db->fetchAll(
            'SELECT value_id, sessions FROM ' . $this->db->table('agg_daily_dim') . ' WHERE dim = ? ORDER BY sessions DESC',
            [Dimension::COUNTRY],
        );
        self::assertSame('DE', Dimension::countryCode(is_numeric($laender[0]['value_id']) ? (int) $laender[0]['value_id'] : 0));
        self::assertEquals(2, $laender[0]['sessions']);
        self::assertSame('AT', Dimension::countryCode(is_numeric($laender[1]['value_id']) ? (int) $laender[1]['value_id'] : 0));
        self::assertCount(2, $laender, 'Unbekanntes Land fehlt.');

        $geraete = $this->db->fetchAll(
            'SELECT value_id, sessions FROM ' . $this->db->table('agg_daily_dim') . ' WHERE dim = ? ORDER BY value_id',
            [Dimension::DEVICE],
        );
        self::assertEquals([['value_id' => 1, 'sessions' => 1], ['value_id' => 2, 'sessions' => 2]], $geraete, 'Gerätetyp 0 (unbekannt) fehlt.');

        $browser = $this->dim(Dimension::BROWSER, 'dict_browser');
        self::assertEquals(2, $browser['Safari']['sessions']);
        self::assertEquals(1, $browser['Chrome']['sessions']);
        self::assertEquals(2, $this->dim(Dimension::OS, 'dict_os')['iOS']['sessions']);
    }

    public function testEreignisseUndEigenschaften(): void
    {
        $this->sitzung('2026-10-01 08:00:00', ['/preise'], ['besucher' => 'A', 'ereignis' => 'Signup', 'props' => ['plan' => 'pro']]);
        $this->sitzung('2026-10-01 09:00:00', ['/preise'], ['besucher' => 'B', 'ereignis' => 'Signup', 'props' => ['plan' => 'pro']]);
        $this->sitzung('2026-10-01 10:00:00', ['/preise'], ['besucher' => 'C', 'ereignis' => 'Signup', 'props' => ['plan' => 'free']]);
        $this->aggregiere();

        self::assertEquals(3, $this->tag()['events']);
        $namen = $this->dim(Dimension::EVENT_NAME, 'dict_event_name');
        self::assertEquals(3, $namen['Signup']['hits']);
        self::assertEquals(3, $namen['Signup']['visitors']);

        $eigenschaften = $this->db->fetchAll(
            'SELECT v.value AS wert, p.visitors, p.events FROM ' . $this->db->table('agg_daily_prop') . ' p JOIN ' . $this->db->table('dict_prop_value')
            . ' v ON v.id = p.value_id ORDER BY v.value',
        );
        self::assertEquals([['wert' => 'free', 'visitors' => 1, 'events' => 1], ['wert' => 'pro', 'visitors' => 2, 'events' => 2]], $eigenschaften);
    }

    public function testNeuberechnungIstWiederholbarUndErsetztAlteWerte(): void
    {
        $this->sitzung('2026-10-01 08:00:00', ['/'], ['besucher' => 'A', 'land' => 'DE']);
        $this->aggregiere();
        $this->aggregiere();
        self::assertSame(1, $this->zaehle('agg_daily'));
        self::assertEquals(1, $this->tag()['sessions']);

        $this->sitzung('2026-10-01 09:00:00', ['/'], ['besucher' => 'B', 'land' => 'DE']);
        $this->aggregiere();

        self::assertEquals(2, $this->tag()['sessions']);
        self::assertSame(1, $this->zaehle('agg_hourly', "hour = '2026-10-01 10:00:00'") + $this->zaehle('agg_hourly', "hour = '2026-10-01 11:00:00'") - 1);
    }

    public function testNurDieEigeneSiteWirdBerechnet(): void
    {
        $this->db->run('INSERT INTO ' . $this->db->table('sites') . " (public_id, name, domain, created_at) VALUES ('zzzz1234zzzz1234', 'Zwei', 'zwei.de', '2026-01-01 00:00:00')");
        $this->sitzung('2026-10-01 08:00:00', ['/'], ['site' => 2]);
        $this->sitzung('2026-10-01 08:00:00', ['/'], ['site' => 1]);
        $this->aggregiere();

        self::assertEquals(1, $this->tag()['sessions']);
        self::assertSame(0, $this->zaehle('agg_daily', 'site_id = 2'));
    }

    public function testTagOhneDatenErzeugtKeineZeile(): void
    {
        $this->aggregiere('2026-10-05');

        self::assertSame(0, $this->zaehle('agg_daily'));
        self::assertSame(0, $this->zaehle('agg_hourly'));
    }

    public function testLaendercodeLaesstSichUmrechnen(): void
    {
        self::assertSame('DE', Dimension::countryCode(Dimension::countryId('DE')));
        self::assertSame(17477, Dimension::countryId('DE'));
    }
}
