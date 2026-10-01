<?php

declare(strict_types=1);

namespace Pegelstand\Stats;

use DateTimeImmutable;
use DateTimeZone;
use Pegelstand\Database\Database;
use Pegelstand\Settings\GoalRepository;

/**
 * Baut die Daten, die das Dashboard braucht: Kennzahlen mit Vorperiode, Zeitreihe und die Tabellen.
 * Ohne Filter und bei mehr als zwei Tagen kommen die Zahlen aus den Aggregaten, sonst aus den Rohdaten.
 *
 * @phpstan-import-type Totals from StatsSource
 * @phpstan-import-type Row from StatsSource
 */
final class DashboardService
{
    private const LIMIT = 50;
    private const MAX_PROPS_PER_EVENT = 5;
    private const MAX_VALUES_PER_PROP = 8;
    public const ACTIVE_MINUTES = 5;

    public function __construct(
        private readonly Database $db,
        private readonly ReferrerClassifier $referrer,
        /** @var array<string, string> */
        private readonly array $countries,
    ) {}

    /**
     * @param array{id: int, public_id: string, name: string, timezone: string} $site
     * @param list<array{typ: string, wert: string}> $filter
     * @return array<string, mixed>
     */
    public function ansicht(array $site, ?string $von, ?string $bis, array $filter, DateTimeImmutable $now): array
    {
        $zone = new DateTimeZone($site['timezone']);
        $bereich = DashboardRange::create($zone, $von, $bis, $now);
        $siteInfo = ['id' => $site['public_id'], 'name' => $site['name']];
        $jetzt = ['datum' => $bereich->today, 'stunde' => $bereich->currentHour, 'zeit' => $bereich->nowLabel];

        if (!$this->hatDaten($site['id'])) {
            return ['leer' => true, 'site' => $siteInfo, 'jetzt' => $jetzt];
        }

        $sitzungsFilter = SessionFilter::build($this->db, $filter, $this->referrer, $site['id']);
        $quelle = $sitzungsFilter->isEmpty() && !$bereich->hourly
            ? new AggregateStats($this->db)
            : new RawStats($this->db, $sitzungsFilter);
        $id = $site['id'];

        $aktuell = $quelle->totals($id, $bereich->current);
        $vorher = $quelle->totals($id, $bereich->previous);
        $reihenAktuell = $quelle->series($id, $bereich->buckets);
        $reihenVorher = $quelle->series($id, $bereich->previousBuckets);

        $reihen = [];
        foreach (['besucher', 'aufrufe', 'seitenProBesuch', 'absprungrate', 'dauer'] as $name) {
            $reihen[$name] = [
                'aktuell' => array_map(static fn(array $t): float|int => self::metriken($t)[$name], $reihenAktuell),
                'vorher' => array_map(static fn(array $t): float|int => self::metriken($t)[$name], $reihenVorher),
            ];
        }
        $etikett = static fn(Bucket $b): array => $b->hour === null ? ['datum' => $b->day] : ['datum' => $b->day, 'stunde' => $b->hour];

        return [
            'leer' => false,
            'site' => $siteInfo,
            'jetzt' => $jetzt,
            'keineDaten' => $aktuell['besucher'] === 0 && $aktuell['aufrufe'] === 0,
            'zeitraum' => ['von' => $bereich->from, 'bis' => $bereich->to, 'vorVon' => $bereich->previousFrom, 'vorBis' => $bereich->previousTo],
            'quelle' => $quelle instanceof AggregateStats ? 'aggregate' : 'rohdaten',
            'kennzahlen' => ['aktuell' => self::metriken($aktuell), 'vorher' => self::metriken($vorher)],
            'diagramm' => [
                'stundenweise' => $bereich->hourly,
                'etiketten' => array_map($etikett, $bereich->buckets),
                'etikettenVorher' => array_map($etikett, $bereich->previousBuckets),
                'reihen' => $reihen,
            ],
            'tabellen' => $this->tabellen($quelle, $id, $bereich->current, $aktuell['besucher']),
            'aktive' => $this->aktive($id, $now),
        ];
    }

    public function aktive(int $siteId, DateTimeImmutable $now): int
    {
        return $this->db->fetchInt(
            'SELECT COUNT(DISTINCT visitor_hash) FROM ' . $this->db->table('sessions') . ' WHERE site_id = ? AND last_seen_at >= ?',
            [$siteId, gmdate('Y-m-d H:i:s', $now->getTimestamp() - self::ACTIVE_MINUTES * 60)],
        );
    }

    /** Frühester Tag, für den es Zahlen gibt (Aggregate oder Rohdaten), in der Zeitzone der Site. */
    public function ersterTag(int $siteId, DateTimeZone $zone): ?string
    {
        $tage = [];
        $agg = $this->db->fetchValue('SELECT MIN(day) FROM ' . $this->db->table('agg_daily') . ' WHERE site_id = ?', [$siteId]);
        if (is_string($agg)) {
            $tage[] = $agg;
        }
        $roh = $this->db->fetchValue('SELECT MIN(started_at) FROM ' . $this->db->table('sessions') . ' WHERE site_id = ?', [$siteId]);
        if (is_string($roh)) {
            $tage[] = (new DateTimeImmutable($roh, new DateTimeZone('UTC')))->setTimezone($zone)->format('Y-m-d');
        }

        return $tage === [] ? null : min($tage);
    }

    private function hatDaten(int $siteId): bool
    {
        return $this->db->fetchInt('SELECT COUNT(*) FROM (SELECT 1 FROM ' . $this->db->table('sessions') . ' WHERE site_id = ? LIMIT 1) t', [$siteId]) > 0
            || $this->db->fetchInt('SELECT COUNT(*) FROM (SELECT 1 FROM ' . $this->db->table('agg_daily') . ' WHERE site_id = ? LIMIT 1) t', [$siteId]) > 0;
    }

    /**
     * @param Totals $t
     * @return array{besucher: int, aufrufe: int, seitenProBesuch: float|int, absprungrate: float|int, dauer: float|int}
     */
    private static function metriken(array $t): array
    {
        return [
            'besucher' => $t['besucher'],
            'aufrufe' => $t['aufrufe'],
            'seitenProBesuch' => $t['besuche'] > 0 ? $t['aufrufe'] / $t['besuche'] : 0,
            'absprungrate' => $t['besuche'] > 0 ? $t['bounces'] / $t['besuche'] * 100 : 0,
            'dauer' => $t['besuche'] > 0 ? $t['dauer'] / $t['besuche'] : 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function tabellen(StatsSource $q, int $id, Period $zeitraum, int $gesamt): array
    {
        $seiten = $q->dimension($id, Dimension::PAGE, $zeitraum, self::LIMIT);
        $einstieg = $q->dimension($id, Dimension::ENTRY_PAGE, $zeitraum, self::LIMIT);
        $ausstieg = $q->dimension($id, Dimension::EXIT_PAGE, $zeitraum, self::LIMIT);
        $aufrufeProSeite = $q->pageViews($id, $zeitraum, array_column($ausstieg, 'key'));
        $pfade = $this->namen('path', [...array_column($seiten, 'key'), ...array_column($einstieg, 'key'), ...array_column($ausstieg, 'key')]);

        $referrerRoh = $q->dimension($id, Dimension::REFERRER, $zeitraum, 200);
        $referrerNamen = $this->namen('referrer', array_column($referrerRoh, 'key'));
        $quellen = $this->quellen($referrerRoh, $referrerNamen, $gesamt);

        $kampagnenRoh = $q->dimension($id, Dimension::UTM_CAMPAIGN, $zeitraum, self::LIMIT);
        $kampagnenNamen = $this->namen('utm', array_column($kampagnenRoh, 'key'));
        $browserRoh = $q->dimension($id, Dimension::BROWSER, $zeitraum, self::LIMIT);
        $osRoh = $q->dimension($id, Dimension::OS, $zeitraum, self::LIMIT);
        $browserNamen = $this->namen('browser', array_column($browserRoh, 'key'));
        $osNamen = $this->namen('os', array_column($osRoh, 'key'));
        $ereignisRoh = $q->dimension($id, Dimension::EVENT_NAME, $zeitraum, self::LIMIT);
        $ereignisNamen = $this->namen('event_name', array_column($ereignisRoh, 'key'));

        return [
            'seiten' => [
                'top' => $this->zeilen($seiten, $pfade, 'seite', $gesamt),
                'einstieg' => $this->zeilen($einstieg, $pfade, 'einstieg', $gesamt),
                'ausstieg' => $this->zeilen($ausstieg, $pfade, 'ausstieg', $gesamt, $aufrufeProSeite),
            ],
            'quellen' => [
                'referrer' => $quellen['referrer'],
                'suche' => $quellen['suche'],
                'sozial' => $quellen['sozial'],
                'kampagnen' => $this->zeilen($kampagnenRoh, $kampagnenNamen, 'kampagne', $gesamt),
            ],
            'laender' => $this->laender($q->dimension($id, Dimension::COUNTRY, $zeitraum, self::LIMIT), $gesamt),
            'geraete' => [
                'geraete' => $this->geraete($q->dimension($id, Dimension::DEVICE, $zeitraum, 10), $gesamt),
                'browser' => $this->zeilen($browserRoh, $browserNamen, 'browser', $gesamt),
                'os' => $this->zeilen($osRoh, $osNamen, 'os', $gesamt),
            ],
            'ziele' => $this->ziele($q, $id, $zeitraum, $gesamt),
            'ereignisse' => $this->ereignisse($q, $id, $zeitraum, $ereignisRoh, $ereignisNamen, $gesamt),
        ];
    }

    /**
     * Ziele der Website mit Besuchern, Anzahl und Conversion-Rate.
     *
     * @return list<array<string, mixed>>
     */
    private function ziele(StatsSource $q, int $id, Period $zeitraum, int $gesamt): array
    {
        $ziele = (new GoalRepository($this->db))->forSite($id);
        if ($ziele === []) {
            return [];
        }
        $schluessel = ['page' => [], 'event' => []];
        foreach ($ziele as $z) {
            $tabelle = $z['kind'] === 'event' ? 'event_name' : 'path';
            $wb = $this->db->fetchInt(
                'SELECT id FROM ' . $this->db->table('dict_' . $tabelle) . ' WHERE value_hash = UNHEX(MD5(?)) AND value = ?',
                [$z['target'], $z['target']],
            );
            if ($wb > 0) {
                $schluessel[$z['kind']][$z['target']] = $wb;
            }
        }
        $seiten = $q->dimension($id, Dimension::PAGE, $zeitraum, 1000, array_values($schluessel['page']));
        $ereignisse = $q->dimension($id, Dimension::EVENT_NAME, $zeitraum, 1000, array_values($schluessel['event']));

        $ergebnis = [];
        foreach ($ziele as $z) {
            $wb = $schluessel[$z['kind']][$z['target']] ?? 0;
            // Seiten- und Ereignis-IDs stammen aus getrennten Wörterbüchern, deshalb getrennt nachschlagen.
            $liste = $z['kind'] === 'event' ? $ereignisse : $seiten;
            $zeile = null;
            foreach ($liste as $l) {
                if ($l['key'] === $wb) {
                    $zeile = $l;
                }
            }
            $besucher = $zeile['besucher'] ?? 0;
            $ergebnis[] = [
                'id' => (string) $z['id'],
                'name' => $z['name'],
                'art' => $z['kind'] === 'event' ? 'Ereignis ' . $z['target'] : 'Seite ' . $z['target'],
                'filterTyp' => 'ziel',
                'besucher' => $besucher,
                'anzahl' => $zeile['aufrufe'] ?? 0,
                'rate' => self::anteil($besucher, $gesamt),
            ];
        }
        usort($ergebnis, static fn(array $a, array $b): int => $b['besucher'] <=> $a['besucher']);

        return $ergebnis;
    }

    /**
     * @param list<Row> $zeilen
     * @param array<int, string> $namen
     * @param array<int, int>|null $seitenaufrufe nur für die Ausstiegsrate
     * @return list<array<string, mixed>>
     */
    private function zeilen(array $zeilen, array $namen, string $filterTyp, int $gesamt, ?array $seitenaufrufe = null): array
    {
        $ergebnis = [];
        foreach ($zeilen as $z) {
            $name = $namen[$z['key']] ?? null;
            if ($name === null) {
                continue;
            }
            $ausstieg = null;
            if ($seitenaufrufe !== null) {
                $aufrufe = $seitenaufrufe[$z['key']] ?? 0;
                $ausstieg = $aufrufe > 0 ? min(100, $z['besuche'] / $aufrufe * 100) : 0;
            }
            $ergebnis[] = [
                'id' => $name,
                'name' => $name,
                'filterTyp' => $filterTyp,
                'besucher' => $z['besucher'],
                'anteil' => self::anteil($z['besucher'], $gesamt),
                'aufrufe' => $z['aufrufe'],
                'absprung' => $z['besuche'] > 0 ? $z['bounces'] / $z['besuche'] * 100 : 0,
                'ausstieg' => $ausstieg ?? 0,
            ];
        }

        return $ergebnis;
    }

    /**
     * @param list<Row> $roh
     * @param array<int, string> $namen
     * @return array{referrer: list<array<string, mixed>>, suche: list<array<string, mixed>>, sozial: list<array<string, mixed>>}
     */
    private function quellen(array $roh, array $namen, int $gesamt): array
    {
        /** @var array<string, array<string, array{id: string, name: string, besucher: int, aufrufe: int}>> $gruppen */
        $gruppen = ['referrer' => [], 'suche' => [], 'sozial' => []];
        foreach ($roh as $z) {
            if ($z['key'] === 0) {
                $k = ['id' => 'direkt', 'name' => 'Direkt / keine Angabe', 'gruppe' => 'referrer'];
            } elseif (isset($namen[$z['key']])) {
                $k = $this->referrer->classify($namen[$z['key']]);
            } else {
                continue;
            }
            $eintrag = $gruppen[$k['gruppe']][$k['id']] ?? ['id' => $k['id'], 'name' => $k['name'], 'besucher' => 0, 'aufrufe' => 0];
            $eintrag['besucher'] += $z['besucher'];
            $eintrag['aufrufe'] += $z['aufrufe'];
            $gruppen[$k['gruppe']][$k['id']] = $eintrag;
        }
        $ausgabe = [];
        foreach ($gruppen as $gruppe => $eintraege) {
            usort($eintraege, static fn(array $a, array $b): int => $b['besucher'] <=> $a['besucher']);
            $ausgabe[$gruppe] = array_map(fn(array $e): array => [
                'id' => $e['id'],
                'name' => $e['name'],
                'filterTyp' => 'quelle',
                'besucher' => $e['besucher'],
                'anteil' => self::anteil($e['besucher'], $gesamt),
                'aufrufe' => $e['aufrufe'],
                'absprung' => 0,
                'ausstieg' => 0,
            ], array_slice($eintraege, 0, self::LIMIT));
        }

        return ['referrer' => $ausgabe['referrer'], 'suche' => $ausgabe['suche'], 'sozial' => $ausgabe['sozial']];
    }

    /**
     * @param list<Row> $roh
     * @return list<array<string, mixed>>
     */
    private function laender(array $roh, int $gesamt): array
    {
        $ergebnis = [];
        foreach ($roh as $z) {
            $code = Dimension::countryCode($z['key']);
            $ergebnis[] = [
                'id' => $code,
                'name' => $this->countries[$code] ?? $code,
                'filterTyp' => 'land',
                'besucher' => $z['besucher'],
                'anteil' => self::anteil($z['besucher'], $gesamt),
                'aufrufe' => $z['aufrufe'],
                'absprung' => $z['besuche'] > 0 ? $z['bounces'] / $z['besuche'] * 100 : 0,
                'ausstieg' => 0,
            ];
        }

        return $ergebnis;
    }

    /**
     * @param list<Row> $roh
     * @return list<array<string, mixed>>
     */
    private function geraete(array $roh, int $gesamt): array
    {
        $namen = [1 => ['desktop', 'Desktop'], 2 => ['smartphone', 'Smartphone'], 3 => ['tablet', 'Tablet']];
        $ergebnis = [];
        foreach ($roh as $z) {
            if (!isset($namen[$z['key']])) {
                continue;
            }
            $ergebnis[] = [
                'id' => $namen[$z['key']][0],
                'name' => $namen[$z['key']][1],
                'filterTyp' => 'geraet',
                'besucher' => $z['besucher'],
                'anteil' => self::anteil($z['besucher'], $gesamt),
                'aufrufe' => $z['aufrufe'],
                'absprung' => $z['besuche'] > 0 ? $z['bounces'] / $z['besuche'] * 100 : 0,
                'ausstieg' => 0,
            ];
        }

        return $ergebnis;
    }

    /**
     * @param list<Row> $roh
     * @param array<int, string> $namen
     * @return list<array<string, mixed>>
     */
    private function ereignisse(StatsSource $q, int $id, Period $zeitraum, array $roh, array $namen, int $gesamt): array
    {
        if ($roh === []) {
            return [];
        }
        $props = $q->eventProps($id, $zeitraum);
        $schluessel = [];
        $werte = [];
        foreach ($props as $liste) {
            foreach ($liste as $p) {
                $schluessel[] = $p['key_id'];
                $werte[] = $p['value_id'];
            }
        }
        $schluesselNamen = $this->namen('prop_key', $schluessel);
        $wertNamen = $this->namen('prop_value', $werte);

        $ergebnis = [];
        foreach ($roh as $z) {
            $name = $namen[$z['key']] ?? null;
            if ($name === null) {
                continue;
            }
            /** @var array<string, array<string, int>> $nachSchluessel */
            $nachSchluessel = [];
            foreach ($props[$z['key']] ?? [] as $p) {
                $k = $schluesselNamen[$p['key_id']] ?? null;
                $w = $wertNamen[$p['value_id']] ?? null;
                if ($k !== null && $w !== null) {
                    $nachSchluessel[$k][$w] = ($nachSchluessel[$k][$w] ?? 0) + $p['anzahl'];
                }
            }
            $eigenschaften = [];
            foreach ($nachSchluessel as $k => $liste) {
                arsort($liste);
                $eigenschaften[] = [
                    'schluessel' => $k,
                    'summe' => array_sum($liste),
                    'werte' => array_map(
                        static fn(string $wert, int $anzahl): array => ['wert' => $wert, 'anzahl' => $anzahl],
                        array_map('strval', array_keys(array_slice($liste, 0, self::MAX_VALUES_PER_PROP, true))),
                        array_slice($liste, 0, self::MAX_VALUES_PER_PROP, true),
                    ),
                ];
            }
            usort($eigenschaften, static fn(array $a, array $b): int => $b['summe'] <=> $a['summe']);
            $ergebnis[] = [
                'id' => $name,
                'name' => $name,
                'filterTyp' => 'ereignis',
                'besucher' => $z['besucher'],
                'anzahl' => $z['aufrufe'],
                'rate' => self::anteil($z['besucher'], $gesamt),
                'eigenschaften' => array_map(
                    static fn(array $e): array => ['schluessel' => $e['schluessel'], 'werte' => $e['werte']],
                    array_slice($eigenschaften, 0, self::MAX_PROPS_PER_EVENT),
                ),
            ];
        }

        return $ergebnis;
    }

    /**
     * Wörterbuch-Einträge zu IDs.
     *
     * @param list<int> $ids
     * @return array<int, string>
     */
    private function namen(string $tabelle, array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids, static fn(int $i): bool => $i > 0)));
        $ergebnis = [];
        foreach (array_chunk($ids, 500) as $block) {
            foreach ($this->db->fetchAll(
                'SELECT id, value FROM ' . $this->db->table('dict_' . $tabelle) . ' WHERE id IN (' . implode(',', $block) . ')',
            ) as $z) {
                if (is_numeric($z['id']) && is_string($z['value'])) {
                    $ergebnis[(int) $z['id']] = $z['value'];
                }
            }
        }

        return $ergebnis;
    }

    private static function anteil(int $teil, int $gesamt): float
    {
        return $gesamt > 0 ? min(100, $teil / $gesamt * 100) : 0.0;
    }
}
