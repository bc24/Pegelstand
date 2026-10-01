<?php

declare(strict_types=1);

namespace Pegelstand\Stats;

/**
 * Wandelt Dashboard-Daten in CSV für deutsches Excel: UTF-8 mit BOM, Semikolon als Trenner, Dezimalkomma.
 * Zellen, die mit =, +, - oder @ beginnen, bekommen ein Hochkomma vorangestellt (Schutz vor Formel-Einschleusung).
 */
final class CsvExport
{
    /** Name im Pfad => Spaltenkopf der ersten Spalte und Pfad in den Dashboard-Daten */
    public const TABLES = [
        'zeitverlauf' => ['Zeitpunkt', ''],
        'seiten' => ['Seite', 'tabellen.seiten.top'],
        'einstiegsseiten' => ['Einstiegsseite', 'tabellen.seiten.einstieg'],
        'ausstiegsseiten' => ['Ausstiegsseite', 'tabellen.seiten.ausstieg'],
        'referrer' => ['Referrer', 'tabellen.quellen.referrer'],
        'suchmaschinen' => ['Suchmaschine', 'tabellen.quellen.suche'],
        'soziale-netzwerke' => ['Soziales Netzwerk', 'tabellen.quellen.sozial'],
        'kampagnen' => ['Kampagne', 'tabellen.quellen.kampagnen'],
        'laender' => ['Land', 'tabellen.laender'],
        'geraete' => ['Gerät', 'tabellen.geraete.geraete'],
        'browser' => ['Browser', 'tabellen.geraete.browser'],
        'betriebssysteme' => ['Betriebssystem', 'tabellen.geraete.os'],
        'ziele' => ['Ziel', 'tabellen.ziele'],
        'ereignisse' => ['Ereignis', 'tabellen.ereignisse'],
    ];

    /**
     * @param array<string, mixed> $daten Ergebnis von DashboardService::ansicht()
     */
    public static function build(string $name, array $daten): ?string
    {
        if (!isset(self::TABLES[$name])) {
            return null;
        }
        [$kopf, $pfad] = self::TABLES[$name];
        $zeilen = $name === 'zeitverlauf' ? self::zeitverlauf($daten) : self::tabelle($name, $kopf, self::liste($daten, $pfad));

        $ausgabe = "\xEF\xBB\xBF";
        foreach ($zeilen as $zeile) {
            $ausgabe .= implode(';', array_map(self::zelle(...), $zeile)) . "\r\n";
        }

        return $ausgabe;
    }

    /**
     * @param array<string, mixed> $daten
     * @return list<list<string>>
     */
    private static function zeitverlauf(array $daten): array
    {
        $d = is_array($daten['diagramm'] ?? null) ? $daten['diagramm'] : [];
        $etiketten = is_array($d['etiketten'] ?? null) ? $d['etiketten'] : [];
        $reihen = is_array($d['reihen'] ?? null) ? $d['reihen'] : [];
        $namen = ['besucher' => 'Besucher', 'aufrufe' => 'Seitenaufrufe', 'seitenProBesuch' => 'Seiten pro Besuch', 'absprungrate' => 'Absprungrate (%)', 'dauer' => 'Besuchsdauer (Sekunden)'];
        $zeilen = [['Zeitpunkt', ...array_values($namen)]];
        $i = 0;
        foreach ($etiketten as $e) {
            $e = is_array($e) ? $e : [];
            $zeit = (is_string($e['datum'] ?? null) ? self::datum($e['datum']) : '') . (isset($e['stunde']) && is_int($e['stunde']) ? sprintf(' %02d:00', $e['stunde']) : '');
            $zeile = [$zeit];
            foreach (array_keys($namen) as $schluessel) {
                $reihe = is_array($reihen[$schluessel] ?? null) ? $reihen[$schluessel] : [];
                $aktuell = is_array($reihe['aktuell'] ?? null) ? $reihe['aktuell'] : [];
                $zeile[] = self::zahl($aktuell[$i] ?? 0);
            }
            $zeilen[] = $zeile;
            ++$i;
        }

        return $zeilen;
    }

    /**
     * @param array<string, mixed> $daten
     * @return list<array<string, mixed>>
     */
    private static function liste(array $daten, string $pfad): array
    {
        $aktuell = $daten;
        foreach (explode('.', $pfad) as $teil) {
            $aktuell = is_array($aktuell) ? ($aktuell[$teil] ?? []) : [];
        }
        $ergebnis = [];
        foreach (is_array($aktuell) ? $aktuell : [] as $zeile) {
            if (is_array($zeile)) {
                $ergebnis[] = array_combine(array_map('strval', array_keys($zeile)), array_values($zeile));
            }
        }

        return $ergebnis;
    }

    /**
     * @param list<array<string, mixed>> $liste
     * @return list<list<string>>
     */
    private static function tabelle(string $name, string $kopf, array $liste): array
    {
        $ereignis = $name === 'ereignisse';
        $ziele = $name === 'ziele';
        $zeilen = [$ereignis || $ziele
            ? [$kopf, 'Besucher', 'Anzahl', 'Rate (%)']
            : [$kopf, 'Besucher', 'Anteil (%)', 'Seitenaufrufe', 'Absprungrate (%)', 'Ausstiegsrate (%)']];
        foreach ($liste as $z) {
            $name = is_string($z['name'] ?? null) ? $z['name'] : '';
            $zeilen[] = $ereignis || $ziele
                ? [$name, self::zahl($z['besucher'] ?? 0), self::zahl($z['anzahl'] ?? 0), self::zahl($z['rate'] ?? 0)]
                : [$name, self::zahl($z['besucher'] ?? 0), self::zahl($z['anteil'] ?? 0), self::zahl($z['aufrufe'] ?? 0), self::zahl($z['absprung'] ?? 0), self::zahl($z['ausstieg'] ?? 0)];
        }

        return $zeilen;
    }

    private static function datum(string $iso): string
    {
        return preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $iso, $m) === 1 ? $m[3] . '.' . $m[2] . '.' . $m[1] : $iso;
    }

    private static function zahl(mixed $wert): string
    {
        if (!is_int($wert) && !is_float($wert)) {
            return '0';
        }

        return is_int($wert) ? (string) $wert : str_replace('.', ',', rtrim(rtrim(number_format($wert, 2, '.', ''), '0'), '.'));
    }

    private static function zelle(string $wert): string
    {
        if ($wert !== '' && in_array($wert[0], ['=', '+', '-', '@', "\t", "\r"], true) && preg_match('/^-?\d+(,\d+)?$/', $wert) !== 1) {
            $wert = "'" . $wert;
        }

        return preg_match('/[;"\r\n]/', $wert) === 1 ? '"' . str_replace('"', '""', $wert) . '"' : $wert;
    }
}
