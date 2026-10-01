<?php

declare(strict_types=1);

namespace Pegelstand\Demo;

use DateTimeImmutable;
use DateTimeZone;
use Pegelstand\Database\Database;
use Pegelstand\Ingest\Dictionary;
use Pegelstand\Ingest\UserAgentInfo;

/**
 * Erzeugt glaubwürdige Beispieldaten (Sitzungen, Seitenaufrufe, Ereignisse) direkt in den Rohdaten-Tabellen.
 * Für Demo-Modus, Tests und Lasttests. Mit gleichem Startwert (`seed`) entstehen gleiche Daten.
 * Die Daten sind frei erfunden und enthalten nichts Personenbezogenes.
 */
final class DemoDataGenerator
{
    private const BATCH = 1000;

    private const PATHS = [
        '/' => 30, '/produkte' => 12, '/produkte/alpha' => 8, '/produkte/beta' => 6, '/produkte/gamma' => 4, '/preise' => 9,
        '/blog' => 8, '/blog/datenschutz-in-der-webanalyse' => 7, '/blog/cookies-ade' => 5, '/blog/neu-in-version-2' => 3,
        '/blog/shared-hosting-tipps' => 3, '/ueber-uns' => 4, '/kontakt' => 4, '/hilfe' => 3, '/hilfe/installation' => 3,
        '/downloads' => 3, '/impressum' => 2, '/datenschutz' => 2, '/anmelden' => 3, '/warenkorb' => 2,
    ];
    private const REFERRERS = [
        '' => 45, 'google.com' => 20, 'duckduckgo.com' => 4, 'bing.com' => 3, 'ecosia.org' => 2, 'github.com' => 5,
        'news.ycombinator.com' => 3, 'linkedin.com' => 4, 'reddit.com' => 3, 'heise.de' => 3, 'golem.de' => 2, 'x.com' => 3, 'mastodon.social' => 3,
    ];
    private const COUNTRIES = ['DE' => 55, 'AT' => 8, 'CH' => 7, 'US' => 8, 'GB' => 4, 'FR' => 3, 'NL' => 3, 'PL' => 2, 'ES' => 2, 'IT' => 2, 'SE' => 1, 'CA' => 1, 'JP' => 1, 'BR' => 1];
    private const CAMPAIGNS = [
        ['newsletter', 'email', 'herbst-aktion'], ['newsletter', 'email', 'release-2'], ['mastodon', 'social', 'launch'],
        ['google', 'cpc', 'marke'], ['linkedin', 'social', 'fachartikel'],
    ];
    /** Gerätetyp, Browser, Betriebssystem, Gewicht */
    private const PROFILES = [
        [UserAgentInfo::DEVICE_DESKTOP, 'Chrome', 'Windows', 24], [UserAgentInfo::DEVICE_DESKTOP, 'Edge', 'Windows', 8],
        [UserAgentInfo::DEVICE_DESKTOP, 'Firefox', 'Windows', 6], [UserAgentInfo::DEVICE_DESKTOP, 'Firefox', 'Linux', 4],
        [UserAgentInfo::DEVICE_DESKTOP, 'Safari', 'macOS', 8], [UserAgentInfo::DEVICE_DESKTOP, 'Chrome', 'macOS', 5],
        [UserAgentInfo::DEVICE_MOBILE, 'Chrome', 'Android', 21], [UserAgentInfo::DEVICE_MOBILE, 'Safari', 'iOS', 15],
        [UserAgentInfo::DEVICE_MOBILE, 'Samsung Internet', 'Android', 3], [UserAgentInfo::DEVICE_TABLET, 'Safari', 'iOS', 5],
        [UserAgentInfo::DEVICE_TABLET, 'Chrome', 'Android', 2],
    ];
    /** Anteil des Tagesverkehrs je Stunde (UTC-nah, Mitteleuropa: 10 Uhr und 20 Uhr Spitzen) */
    private const HOURS = [1, 1, 1, 1, 1, 2, 3, 5, 7, 8, 8, 7, 6, 6, 7, 7, 6, 6, 6, 7, 8, 7, 4, 2];

    private readonly Dictionary $dictionary;

    public function __construct(private readonly Database $db)
    {
        $this->dictionary = new Dictionary($db);
    }

    /**
     * @param int $sessionsPerDay mittlere Sitzungen pro Tag (schwankt mit Wochentag, Trend und Zufall)
     * @return array{sessions: int, events: int}
     */
    public function generate(int $siteId, DateTimeImmutable $firstDay, DateTimeImmutable $lastDay, int $sessionsPerDay, int $seed = 1): array
    {
        mt_srand($seed);
        $utc = new DateTimeZone('UTC');
        $tag = $firstDay->setTimezone($utc)->setTime(0, 0);
        $letzter = $lastDay->setTimezone($utc)->setTime(0, 0);
        $tage = max(1, (int) $tag->diff($letzter)->days + 1);

        $sitzungId = $this->naechsteId('sessions');
        $ereignisId = $this->naechsteId('events');
        $summe = ['sessions' => 0, 'events' => 0];

        $pfade = $this->ids('path', array_keys(self::PATHS));
        $profile = [];
        foreach (self::PROFILES as [$geraet, $browser, $os, $gewicht]) {
            $profile[] = [$geraet, $this->dictionary->id('browser', $browser), $this->dictionary->id('os', $os), $gewicht];
        }
        $referrer = ['' => 0] + $this->ids('referrer', array_values(array_filter(array_map('strval', array_keys(self::REFERRERS)), static fn(string $r): bool => $r !== '')));
        $kampagnen = [];
        foreach (self::CAMPAIGNS as [$quelle, $medium, $kampagne]) {
            $kampagnen[] = [$this->dictionary->id('utm', $quelle), $this->dictionary->id('utm', $medium), $this->dictionary->id('utm', $kampagne)];
        }
        $signup = $this->dictionary->id('event_name', 'Signup');
        $download = $this->dictionary->id('event_name', 'Download');
        $ausgehend = $this->dictionary->id('event_name', 'Outbound Link');
        $schluesselPlan = $this->dictionary->id('prop_key', 'plan');
        $schluesselDatei = $this->dictionary->id('prop_key', 'datei');
        $schluesselUrl = $this->dictionary->id('prop_key', 'url');
        $plaene = $this->ids('prop_value', ['free', 'pro', 'team']);
        $dateien = $this->ids('prop_value', ['handbuch.pdf', 'pegelstand.zip', 'preisliste.pdf']);
        $ziele = $this->ids('prop_value', ['https://github.com/', 'https://frank-panzer.de/', 'https://panzerit.de/']);

        $sitzungen = [];
        $ereignisse = [];
        $eigenschaften = [];
        $zaehler = 0;
        foreach (range(0, $tage - 1) as $nr) {
            $heute = $tag->modify('+' . $nr . ' days');
            $wochentag = (int) $heute->format('N');
            $faktor = ($wochentag >= 6 ? 0.6 : 1.0) * (0.8 + 0.4 * $nr / $tage) * (0.85 + mt_rand(0, 30) / 100);
            $anzahl = max(0, (int) round($sessionsPerDay * $faktor));
            $besucherPool = [];
            for ($i = 0; $i < $anzahl; ++$i) {
                $stunde = $this->waehle(self::HOURS);
                $beginn = $heute->getTimestamp() + $stunde * 3600 + mt_rand(0, 3599);
                if ($besucherPool !== [] && mt_rand(1, 100) <= 12) {
                    $besucher = $this->eins($besucherPool);
                } else {
                    $besucher = random_bytes(16);
                    $besucherPool[] = $besucher;
                }
                $profil = $this->profil($profile);
                $land = $this->waehle(self::COUNTRIES);
                $herkunft = $this->waehle(self::REFERRERS);
                $utm = [null, null, null, null, null];
                $referrerId = ($referrer[(string) $herkunft] ?? 0) ?: null;
                if (mt_rand(1, 100) <= 8) {
                    $k = $this->eins($kampagnen);
                    $utm = [$k[0], $k[1], $k[2], null, null];
                    $referrerId = null;
                }

                $seiten = 1 + $this->geometrisch(0.45, 14);
                $pfadIds = [];
                for ($s = 0; $s < $seiten; ++$s) {
                    $pfadIds[] = $pfade[$this->waehle(self::PATHS)];
                }
                $zeit = $beginn;
                $eigeneEreignisse = 0;
                $sitzungEreignisse = [];
                foreach ($pfadIds as $pfadId) {
                    $sitzungEreignisse[] = [$ereignisId, $zeit, 1, $pfadId, null];
                    ++$ereignisId;
                    $zeit += mt_rand(8, 180);
                }
                if (mt_rand(1, 100) <= 7) {
                    $art = mt_rand(1, 3);
                    $id = $ereignisId++;
                    $name = [1 => $signup, 2 => $download, 3 => $ausgehend][$art];
                    $sitzungEreignisse[] = [$id, $zeit, 2, end($pfadIds), $name];
                    $eigenschaften[] = match ($art) {
                        1 => [$id, $schluesselPlan, $this->eins($plaene)],
                        2 => [$id, $schluesselDatei, $this->eins($dateien)],
                        default => [$id, $schluesselUrl, $this->eins($ziele)],
                    };
                    ++$eigeneEreignisse;
                    $zeit += mt_rand(1, 20);
                }
                $ende = (end($sitzungEreignisse) ?: [0, 0])[1];
                $sitzungen[] = [
                    $sitzungId, $siteId, $besucher, gmdate('Y-m-d H:i:s', $beginn), gmdate('Y-m-d H:i:s', $ende), $pfadIds[0], end($pfadIds),
                    $seiten, $eigeneEreignisse, $referrerId, $utm[0], $utm[1], $utm[2], $utm[3], $utm[4], (string) $land, $profil[0], $profil[1], $profil[2],
                ];
                foreach ($sitzungEreignisse as [$id, $t, $art, $pfadId, $name]) {
                    $ereignisse[] = [$id, $siteId, $sitzungId, gmdate('Y-m-d H:i:s', $t), $art, $pfadId, $name];
                }
                ++$sitzungId;
                ++$zaehler;
                $summe['sessions']++;
                $summe['events'] += count($sitzungEreignisse);

                if ($zaehler % self::BATCH === 0) {
                    $this->schreibe($sitzungen, $ereignisse, $eigenschaften);
                    $sitzungen = $ereignisse = $eigenschaften = [];
                }
            }
        }
        $this->schreibe($sitzungen, $ereignisse, $eigenschaften);

        return $summe;
    }

    /**
     * @param list<string> $werte
     * @return array<string, int>
     */
    private function ids(string $tabelle, array $werte): array
    {
        $ergebnis = [];
        foreach ($werte as $wert) {
            $ergebnis[$wert] = $this->dictionary->id($tabelle, $wert);
        }

        return $ergebnis;
    }

    private function naechsteId(string $tabelle): int
    {
        return $this->db->fetchInt('SELECT COALESCE(MAX(id), 0) + 1 FROM ' . $this->db->table($tabelle));
    }

    /**
     * @param list<array{int, int, int, int}> $profile
     * @return array{int, int, int}
     */
    private function profil(array $profile): array
    {
        $summe = array_sum(array_column($profile, 3));
        $zufall = mt_rand(1, $summe);
        foreach ($profile as $p) {
            $zufall -= $p[3];
            if ($zufall <= 0) {
                return [$p[0], $p[1], $p[2]];
            }
        }

        return [$profile[0][0], $profile[0][1], $profile[0][2]];
    }

    /**
     * Wählt einen Schlüssel nach Gewicht.
     *
     * @template K of int|string
     * @param non-empty-array<K, int> $gewichte
     * @return K
     */
    private function waehle(array $gewichte): int|string
    {
        $zufall = mt_rand(1, max(1, array_sum($gewichte)));
        foreach ($gewichte as $schluessel => $gewicht) {
            $zufall -= $gewicht;
            if ($zufall <= 0) {
                return $schluessel;
            }
        }

        return array_key_last($gewichte);
    }

    /**
     * @template T of int|string|array<mixed>
     * @param array<array-key, T> $liste
     * @return T
     */
    private function eins(array $liste): int|string|array
    {
        $werte = array_values($liste);

        return $werte[mt_rand(0, max(0, count($werte) - 1))] ?? throw new \LogicException('Leere Auswahl.');
    }

    private function geometrisch(float $p, int $max): int
    {
        $n = 0;
        while ($n < $max && mt_rand() / mt_getrandmax() > $p) {
            ++$n;
        }

        return $n;
    }

    /**
     * @param list<array<int, mixed>> $sitzungen
     * @param list<array<int, mixed>> $ereignisse
     * @param list<array<int, mixed>> $eigenschaften
     */
    private function schreibe(array $sitzungen, array $ereignisse, array $eigenschaften): void
    {
        $this->mehrfach('sessions', ['id', 'site_id', 'visitor_hash', 'started_at', 'last_seen_at', 'entry_path_id', 'exit_path_id', 'pageviews', 'custom_events', 'referrer_id', 'utm_source_id', 'utm_medium_id', 'utm_campaign_id', 'utm_term_id', 'utm_content_id', 'country', 'device', 'browser_id', 'os_id'], $sitzungen);
        $this->mehrfach('events', ['id', 'site_id', 'session_id', 'occurred_at', 'kind', 'path_id', 'name_id'], $ereignisse);
        $this->mehrfach('event_props', ['event_id', 'key_id', 'value_id'], $eigenschaften);
    }

    /**
     * @param list<string> $spalten
     * @param list<array<int, mixed>> $zeilen
     */
    private function mehrfach(string $tabelle, array $spalten, array $zeilen): void
    {
        foreach (array_chunk($zeilen, 500) as $block) {
            $zeile = '(' . implode(',', array_fill(0, count($spalten), '?')) . ')';
            $werte = [];
            foreach ($block as $z) {
                foreach ($z as $wert) {
                    $werte[] = is_scalar($wert) || $wert === null ? $wert : null;
                }
            }
            $this->db->run(
                'INSERT INTO ' . $this->db->table($tabelle) . ' (' . implode(',', $spalten) . ') VALUES ' . implode(',', array_fill(0, count($block), $zeile)),
                $werte,
            );
        }
    }
}
