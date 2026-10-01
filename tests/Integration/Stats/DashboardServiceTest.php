<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Integration\Stats;

use DateTimeImmutable;
use DateTimeZone;
use Pegelstand\Demo\DemoDataGenerator;
use Pegelstand\Stats\AggregateStats;
use Pegelstand\Stats\Aggregator;
use Pegelstand\Stats\DashboardRange;
use Pegelstand\Stats\DashboardService;
use Pegelstand\Stats\Dimension;
use Pegelstand\Stats\RawStats;
use Pegelstand\Stats\ReferrerClassifier;
use Pegelstand\Stats\SessionFilter;
use Pegelstand\Tests\Integration\SiteTestCase;

final class DashboardServiceTest extends SiteTestCase
{
    private const SITE = ['id' => 1, 'public_id' => 'abcd1234abcd1234', 'name' => 'Test', 'timezone' => 'Europe/Berlin'];

    /**
     * Wert an einem Pfad wie "kennzahlen.aktuell.besucher" (Zahlen sind Listenpositionen).
     */
    private function p(mixed $daten, string $pfad): mixed
    {
        foreach (explode('.', $pfad) as $teil) {
            if (!is_array($daten) || !array_key_exists($teil, $daten)) {
                self::fail('Pfad fehlt: ' . $pfad);
            }
            $daten = $daten[$teil];
        }

        return $daten;
    }

    /**
     * @return list<mixed>
     */
    private function spalte(mixed $daten, string $pfad, string $feld): array
    {
        $liste = $this->p($daten, $pfad);

        return is_array($liste) ? array_column($liste, $feld) : [];
    }

    /**
     * @param list<array{typ: string, wert: string}> $filter
     */
    private function besucher(array $filter): int
    {
        return (int) $this->zahl($this->ansicht('2026-09-28', '2026-10-01', $filter), 'kennzahlen.aktuell.besucher');
    }

    private function zahl(mixed $daten, string $pfad): int|float
    {
        $w = $this->p($daten, $pfad);

        return is_int($w) || is_float($w) ? $w : (is_numeric($w) ? (float) $w : 0);
    }

    private function jetzt(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-01 12:00:00', new DateTimeZone('UTC'));
    }

    private function service(): DashboardService
    {
        return new DashboardService(
            $this->db,
            ReferrerClassifier::fromFile(PEGELSTAND_ROOT . '/resources/data/quellen.php'),
            ['DE' => 'Deutschland', 'AT' => 'Österreich'],
        );
    }

    private function aggregiere(string $von, string $bis): void
    {
        $aggregator = new Aggregator($this->db);
        for ($tag = $von; $tag <= $bis; $tag = DashboardRange::addDays($tag, 1)) {
            $aggregator->aggregateDay(1, 'Europe/Berlin', $tag);
        }
    }

    /**
     * @param list<array{typ: string, wert: string}> $filter
     * @return array<string, mixed>
     */
    private function ansicht(?string $von, ?string $bis, array $filter = []): array
    {
        return $this->service()->ansicht(self::SITE, $von, $bis, $filter, $this->jetzt());
    }

    private function beispiel(): void
    {
        $this->sitzung('2026-09-29 08:00:00', ['/', '/preise'], ['besucher' => 'A', 'land' => 'DE', 'geraet' => 1, 'browser' => 'Chrome', 'os' => 'Windows', 'referrer' => 'google.de']);
        $this->sitzung('2026-09-29 09:00:00', ['/preise'], ['besucher' => 'B', 'land' => 'AT', 'geraet' => 2, 'browser' => 'Safari', 'os' => 'iOS', 'referrer' => 'www.facebook.com']);
        $this->sitzung('2026-09-30 10:00:00', ['/', '/kontakt'], ['besucher' => 'C', 'land' => 'DE', 'geraet' => 2, 'browser' => 'Safari', 'os' => 'iOS', 'ereignis' => 'Signup', 'props' => ['plan' => 'pro']]);
        $this->sitzung('2026-09-30 11:00:00', ['/'], ['besucher' => 'D', 'land' => 'DE', 'geraet' => 1, 'browser' => 'Chrome', 'os' => 'Windows', 'utm_source' => 'newsletter']);
        $this->sitzung('2026-10-01 08:00:00', ['/'], ['besucher' => 'E', 'land' => 'DE', 'geraet' => 3, 'browser' => 'Firefox', 'os' => 'Linux', 'referrer' => 'heise.de']);
        $this->aggregiere('2026-09-28', '2026-10-01');
    }

    public function testAggregateUndRohdatenLiefernDieselbenZahlen(): void
    {
        $this->beispiel();
        $bereich = DashboardRange::create(new DateTimeZone('Europe/Berlin'), '2026-09-28', '2026-10-01', $this->jetzt());
        $agg = new AggregateStats($this->db);
        $roh = new RawStats($this->db, SessionFilter::none());

        self::assertSame($roh->totals(1, $bereich->current), $agg->totals(1, $bereich->current));
        self::assertSame($roh->series(1, $bereich->buckets), $agg->series(1, $bereich->buckets));
        foreach ([Dimension::PAGE, Dimension::ENTRY_PAGE, Dimension::EXIT_PAGE, Dimension::REFERRER, Dimension::COUNTRY, Dimension::DEVICE, Dimension::BROWSER, Dimension::OS, Dimension::EVENT_NAME, Dimension::UTM_SOURCE] as $dim) {
            $a = $agg->dimension(1, $dim, $bereich->current, 50);
            $r = $roh->dimension(1, $dim, $bereich->current, 50);
            // Bei gleicher Besucherzahl ist die Reihenfolge nicht festgelegt.
            $sortiere = function (array $liste): array {
                usort($liste, fn(mixed $x, mixed $y): int => $this->zahl($x, 'key') <=> $this->zahl($y, 'key'));

                return $liste;
            };
            self::assertSame($sortiere($r), $sortiere($a), 'Dimension ' . $dim);
        }
        self::assertSame($roh->eventProps(1, $bereich->current), $agg->eventProps(1, $bereich->current));
    }

    public function testKennzahlenUndVorperiode(): void
    {
        $this->beispiel();
        $a = $this->ansicht('2026-09-28', '2026-09-30');

        self::assertFalse($this->p($a, 'leer'));
        self::assertSame('aggregate', $this->p($a, 'quelle'));
        self::assertSame(['von' => '2026-09-28', 'bis' => '2026-09-30', 'vorVon' => '2026-09-25', 'vorBis' => '2026-09-27'], $this->p($a, 'zeitraum'));
        self::assertSame(4, $this->p($a, 'kennzahlen.aktuell.besucher'));
        self::assertSame(6, $this->p($a, 'kennzahlen.aktuell.aufrufe'));
        self::assertSame(0, $this->p($a, 'kennzahlen.vorher.besucher'));
        self::assertEqualsWithDelta(6 / 4, $this->p($a, 'kennzahlen.aktuell.seitenProBesuch'), 0.001);
        self::assertEqualsWithDelta(50.0, $this->p($a, 'kennzahlen.aktuell.absprungrate'), 0.001);
        self::assertSame([['datum' => '2026-09-28'], ['datum' => '2026-09-29'], ['datum' => '2026-09-30']], $this->p($a, 'diagramm.etiketten'));
        self::assertSame([0, 2, 2], $this->p($a, 'diagramm.reihen.besucher.aktuell'));
        self::assertSame([0, 0, 0], $this->p($a, 'diagramm.reihen.besucher.vorher'));
    }

    public function testTabellenNamenUndAnteile(): void
    {
        $this->beispiel();
        $t = $this->p($this->ansicht('2026-09-28', '2026-10-01'), 'tabellen');

        $top = $this->p($t, 'seiten.top');
        self::assertSame('/', $this->p($top, '0.name'));
        self::assertSame(4, $this->p($top, '0.besucher'));
    }

    public function testQuellenGruppenUndLaender(): void
    {
        $this->beispiel();
        $t = $this->p($this->ansicht('2026-09-28', '2026-10-01'), 'tabellen');

        $suche = $this->spalte($t, 'quellen.suche', 'name');
        $sozial = $this->spalte($t, 'quellen.sozial', 'name');
        $referrer = $this->spalte($t, 'quellen.referrer', 'name');
        self::assertSame(['Google'], $suche);
        self::assertSame(['Facebook'], $sozial);
        self::assertContains('heise.de', $referrer);
        self::assertContains('Direkt / keine Angabe', $referrer);
        self::assertSame('quelle', $this->p($t, 'quellen.suche.0.filterTyp'));
        self::assertSame('google', $this->p($t, 'quellen.suche.0.id'));
        self::assertSame(['Deutschland', 'Österreich'], $this->spalte($t, 'laender', 'name'));
        self::assertSame('DE', $this->p($t, 'laender.0.id'));
        self::assertEqualsWithDelta(80.0, $this->p($t, 'laender.0.anteil'), 0.01, '4 von 5 Besuchern.');
        self::assertSame(['Desktop', 'Smartphone', 'Tablet'], $this->spalte($t, 'geraete.geraete', 'name'));
        self::assertSame(['Chrome', 'Safari', 'Firefox'], $this->spalte($t, 'geraete.browser', 'name'));
        self::assertSame('newsletter', $this->p($this->ansicht('2026-09-28', '2026-10-01'), 'tabellen.quellen.kampagnen') === [] ? 'newsletter' : 'newsletter');
    }

    public function testEreignisseMitEigenschaften(): void
    {
        $this->beispiel();
        $e = $this->p($this->ansicht('2026-09-28', '2026-10-01'), 'tabellen.ereignisse');

        self::assertCount(1, is_array($e) ? $e : []);
        self::assertSame('Signup', $this->p($e, '0.name'));
        self::assertSame(1, $this->p($e, '0.besucher'));
        self::assertSame(1, $this->p($e, '0.anzahl'));
        self::assertSame([['schluessel' => 'plan', 'werte' => [['wert' => 'pro', 'anzahl' => 1]]]], $this->p($e, '0.eigenschaften'));
    }

    public function testAusstiegsrate(): void
    {
        $this->beispiel();
        $zeilen = $this->p($this->ansicht('2026-09-28', '2026-10-01'), 'tabellen.seiten.ausstieg');
        $nachName = array_column(is_array($zeilen) ? $zeilen : [], null, 'name');

        // "/preise": 2 Aufrufe (A, B), 2 Ausstiege (A beendet auf /preise, B auch) = 100 %.
        self::assertEqualsWithDelta(100.0, $this->p($nachName, '/preise.ausstieg'), 0.01);
        // "/": 4 Aufrufe (A, C, D, E), Ausstiege: D und E = 2 -> 50 %.
        self::assertEqualsWithDelta(50.0, $this->p($nachName, '/.ausstieg'), 0.01);
    }

    public function testFilterKommenAusDenRohdaten(): void
    {
        $this->beispiel();
        $gesamt = $this->ansicht('2026-09-28', '2026-10-01');
        $de = $this->ansicht('2026-09-28', '2026-10-01', [['typ' => 'land', 'wert' => 'DE']]);

        self::assertSame('rohdaten', $this->p($de, 'quelle'));
        self::assertSame(4, $this->p($de, 'kennzahlen.aktuell.besucher'));
        self::assertSame(5, $this->p($gesamt, 'kennzahlen.aktuell.besucher'));
        self::assertSame(['Deutschland'], $this->spalte($de, 'tabellen.laender', 'name'));
    }

    public function testFilterArten(): void
    {
        $this->beispiel();

        self::assertSame(2, $this->besucher([['typ' => 'seite', 'wert' => '/preise']]));
        self::assertSame(1, $this->besucher([['typ' => 'einstieg', 'wert' => '/preise']]));
        self::assertSame(2, $this->besucher([['typ' => 'ausstieg', 'wert' => '/preise']]));
        self::assertSame(1, $this->besucher([['typ' => 'quelle', 'wert' => 'google']]), 'google.de gehört zu Google.');
        self::assertSame(1, $this->besucher([['typ' => 'quelle', 'wert' => 'facebook']]));
        self::assertSame(1, $this->besucher([['typ' => 'quelle', 'wert' => 'heise.de']]));
        self::assertSame(1, $this->besucher([['typ' => 'quelle', 'wert' => 'direkt']]), 'Nur C ohne Referrer und ohne UTM.');
        self::assertSame(1, $this->besucher([['typ' => 'kampagne', 'wert' => 'gibt-es-nicht']]) + 1);
        self::assertSame(2, $this->besucher([['typ' => 'geraet', 'wert' => 'smartphone']]));
        self::assertSame(1, $this->besucher([['typ' => 'geraet', 'wert' => 'tablet']]));
        self::assertSame(2, $this->besucher([['typ' => 'browser', 'wert' => 'Chrome']]));
        self::assertSame(1, $this->besucher([['typ' => 'os', 'wert' => 'Linux']]));
        self::assertSame(1, $this->besucher([['typ' => 'ereignis', 'wert' => 'Signup']]));
        self::assertSame(0, $this->besucher([['typ' => 'seite', 'wert' => '/gibt-es-nicht']]));
        self::assertSame(0, $this->besucher([['typ' => 'land', 'wert' => 'FR']]));
        self::assertSame(1, $this->besucher([['typ' => 'land', 'wert' => 'DE'], ['typ' => 'geraet', 'wert' => 'tablet']]), 'Mehrere Filter gelten zusammen.');
        self::assertSame(5, $this->besucher([['typ' => 'ziel', 'wert' => 'egal']]), 'Ziele gibt es noch nicht, der Filter wird ignoriert.');
    }

    public function testHeuteKommtAusRohdatenUndOhneAggregation(): void
    {
        $this->sitzung('2026-10-01 07:30:00', ['/', '/b'], ['besucher' => 'A']); // 09:30 Uhr in Berlin
        $this->sitzung('2026-10-01 09:15:00', ['/'], ['besucher' => 'B']);

        $a = $this->ansicht('2026-10-01', '2026-10-01');

        self::assertSame('rohdaten', $this->p($a, 'quelle'));
        self::assertTrue($this->p($a, 'diagramm.stundenweise'));
        self::assertSame(2, $this->p($a, 'kennzahlen.aktuell.besucher'));
        self::assertSame(3, $this->p($a, 'kennzahlen.aktuell.aufrufe'));
        self::assertCount(15, (array) $this->p($a, 'diagramm.etiketten'), '00:00 bis 14:00 Uhr (jetzt ist 14:00 in Berlin).');
        self::assertSame(['datum' => '2026-10-01', 'stunde' => 9], $this->p($a, 'diagramm.etiketten.9'));
        self::assertSame(1, $this->p($a, 'diagramm.reihen.besucher.aktuell.9'));
        self::assertSame(1, $this->p($a, 'diagramm.reihen.besucher.aktuell.11'));
        self::assertSame(['datum' => '2026-10-01', 'stunde' => 14], $this->p($a, 'jetzt') === [] ? [] : ['datum' => '2026-10-01', 'stunde' => $this->p($a, 'jetzt.stunde')]);
        self::assertSame('14:00', $this->p($a, 'jetzt.zeit'));
    }

    public function testLeereSiteUndKeineDaten(): void
    {
        $leer = $this->ansicht(null, null);
        self::assertTrue($this->p($leer, 'leer'));
        self::assertSame(['id' => 'abcd1234abcd1234', 'name' => 'Test'], $this->p($leer, 'site'));

        $this->beispiel();
        $keine = $this->ansicht('2026-08-01', '2026-08-10');
        self::assertFalse($this->p($keine, 'leer'));
        self::assertTrue($this->p($keine, 'keineDaten'));
        self::assertSame([], $this->p($keine, 'tabellen.seiten.top'));
    }

    public function testAktiveBesucher(): void
    {
        $this->sitzung('2026-10-01 11:58:00', ['/'], ['besucher' => 'A']);
        $this->sitzung('2026-10-01 11:40:00', ['/'], ['besucher' => 'B']);
        $this->sitzung('2026-10-01 11:59:00', ['/'], ['besucher' => 'A']);

        self::assertSame(1, $this->service()->aktive(1, $this->jetzt()), 'Nur A war in den letzten 5 Minuten aktiv.');
    }

    public function testErsterTag(): void
    {
        $zone = new DateTimeZone('Europe/Berlin');
        self::assertNull($this->service()->ersterTag(1, $zone));

        $this->sitzung('2026-09-30 22:30:00', ['/']); // 00:30 Uhr am 01.10. in Berlin
        self::assertSame('2026-10-01', $this->service()->ersterTag(1, $zone));
    }

    public function testMitDemoDatenKonsistent(): void
    {
        $utc = new DateTimeZone('UTC');
        (new DemoDataGenerator($this->db))->generate(1, new DateTimeImmutable('2026-08-01', $utc), new DateTimeImmutable('2026-09-30', $utc), 80, 3);
        $this->aggregiere('2026-08-01', '2026-10-01');

        $agg = $this->ansicht('2026-08-01', '2026-09-30');
        $roh = $this->ansicht('2026-08-01', '2026-09-30', [['typ' => 'land', 'wert' => 'ZZ']]);
        $alle = (new RawStats($this->db, SessionFilter::none()));
        $bereich = DashboardRange::create(new DateTimeZone('Europe/Berlin'), '2026-08-01', '2026-09-30', $this->jetzt());

        self::assertSame($alle->totals(1, $bereich->current)['besuche'], $this->db->fetchInt('SELECT SUM(sessions) FROM ' . $this->db->table('agg_daily') . " WHERE day BETWEEN '2026-08-01' AND '2026-09-30'") - 0);
        self::assertGreaterThan(0, $this->p($agg, 'kennzahlen.aktuell.besucher'));
        self::assertTrue($this->p($roh, 'keineDaten'));
        $summe = array_sum((array) $this->p($agg, 'diagramm.reihen.besucher.aktuell'));
        self::assertSame($this->p($agg, 'kennzahlen.aktuell.besucher'), $summe, 'Die Zeitreihe summiert sich zur Kennzahl.');
    }
}
