<?php

declare(strict_types=1);

namespace Pegelstand\Reports;

use DateTimeImmutable;
use Pegelstand\Mail\Message;
use Pegelstand\Stats\DashboardService;

/** Baut den Inhalt eines Berichts (Text und HTML) aus den Dashboard-Daten. */
final class ReportBuilder
{
    public function __construct(private readonly DashboardService $service) {}

    /**
     * @param array{id: int, public_id: string, name: string, timezone: string} $site
     */
    public function build(array $site, string $frequency, string $von, string $bis, string $empfaenger, string $dashboardUrl, DateTimeImmutable $now): ?Message
    {
        $d = $this->service->ansicht($site, $von, $bis, [], $now);
        if (($d['leer'] ?? true) === true) {
            return null;
        }
        $k = $this->karte($d, 'kennzahlen.aktuell');
        $v = $this->karte($d, 'kennzahlen.vorher');
        $zeitraum = self::datum($von) . ' bis ' . self::datum($bis);
        $art = $frequency === 'monthly' ? 'Monatsbericht' : 'Wochenbericht';
        $betreff = sprintf('%s für %s: %s', $art, $site['name'], $zeitraum);

        $zeilen = [
            ['Besucher', self::zahl($k['besucher'] ?? 0), self::delta($k['besucher'] ?? 0, $v['besucher'] ?? 0)],
            ['Seitenaufrufe', self::zahl($k['aufrufe'] ?? 0), self::delta($k['aufrufe'] ?? 0, $v['aufrufe'] ?? 0)],
            ['Seiten pro Besuch', self::zahl($k['seitenProBesuch'] ?? 0, 1), self::delta($k['seitenProBesuch'] ?? 0, $v['seitenProBesuch'] ?? 0)],
            ['Absprungrate', self::zahl($k['absprungrate'] ?? 0, 1) . ' %', self::delta($k['absprungrate'] ?? 0, $v['absprungrate'] ?? 0)],
            ['Besuchsdauer', self::dauer($k['dauer'] ?? 0), self::delta($k['dauer'] ?? 0, $v['dauer'] ?? 0)],
        ];
        $listen = [
            'Top-Seiten' => $this->top($d, 'tabellen.seiten.top'),
            'Wichtigste Quellen' => $this->top($d, 'tabellen.quellen.referrer', 'tabellen.quellen.suche', 'tabellen.quellen.sozial'),
            'Länder' => $this->top($d, 'tabellen.laender'),
        ];

        $text = "$art für {$site['name']}\n$zeitraum\n\n";
        foreach ($zeilen as [$name, $wert, $delta]) {
            $text .= sprintf("%-20s %12s   %s\n", $name, $wert, $delta);
        }
        foreach ($listen as $titel => $eintraege) {
            if ($eintraege === []) {
                continue;
            }
            $text .= "\n$titel\n";
            foreach ($eintraege as [$name, $besucher]) {
                $text .= sprintf("  %-40s %s\n", mb_substr($name, 0, 40), self::zahl($besucher));
            }
        }
        $text .= "\nZum Dashboard: $dashboardUrl\n\nDu bekommst diese Nachricht, weil du den $art abonniert hast. Abbestellen kannst du ihn unter Einstellungen, Mein Konto.\n";

        $h = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $html = '<div style="font-family:Arial,Helvetica,sans-serif;color:#10222b;max-width:560px">'
            . '<h1 style="font-size:20px;margin:0 0 4px">' . $h($art . ' für ' . $site['name']) . '</h1>'
            . '<p style="margin:0 0 16px;color:#52656f">' . $h($zeitraum) . '</p>'
            . '<table style="border-collapse:collapse;width:100%">';
        foreach ($zeilen as [$name, $wert, $delta]) {
            $html .= '<tr><td style="padding:6px 0;border-bottom:1px solid #dbe5e9">' . $h($name) . '</td>'
                . '<td style="padding:6px 0;border-bottom:1px solid #dbe5e9;text-align:right"><strong>' . $h($wert) . '</strong></td>'
                . '<td style="padding:6px 0 6px 12px;border-bottom:1px solid #dbe5e9;color:#52656f;white-space:nowrap">' . $h($delta) . '</td></tr>';
        }
        $html .= '</table>';
        foreach ($listen as $titel => $eintraege) {
            if ($eintraege === []) {
                continue;
            }
            $html .= '<h2 style="font-size:15px;margin:20px 0 6px">' . $h($titel) . '</h2><table style="border-collapse:collapse;width:100%">';
            foreach ($eintraege as [$name, $besucher]) {
                $html .= '<tr><td style="padding:4px 0;border-bottom:1px solid #eef3f5">' . $h($name) . '</td><td style="padding:4px 0;border-bottom:1px solid #eef3f5;text-align:right">' . $h(self::zahl($besucher)) . '</td></tr>';
            }
            $html .= '</table>';
        }
        $html .= '<p style="margin:24px 0 0"><a href="' . $h($dashboardUrl) . '">Zum Dashboard</a></p>'
            . '<p style="margin:16px 0 0;font-size:12px;color:#52656f">Du bekommst diese Nachricht, weil du den ' . $h($art) . ' abonniert hast. Abbestellen kannst du ihn unter Einstellungen, Mein Konto.</p></div>';

        return new Message($empfaenger, $betreff, $text, $html);
    }

    /**
     * @param array<string, mixed> $d
     * @return array<string, mixed>
     */
    private function karte(array $d, string $pfad): array
    {
        $a = $d;
        foreach (explode('.', $pfad) as $teil) {
            $a = is_array($a) ? ($a[$teil] ?? []) : [];
        }

        /** @var array<string, mixed> $ergebnis */
        $ergebnis = is_array($a) ? $a : [];

        return $ergebnis;
    }

    /**
     * @param array<string, mixed> $d
     * @return list<array{string, int}>
     */
    private function top(array $d, string ...$pfade): array
    {
        $alle = [];
        foreach ($pfade as $pfad) {
            $a = $d;
            foreach (explode('.', $pfad) as $teil) {
                $a = is_array($a) ? ($a[$teil] ?? []) : [];
            }
            foreach (is_array($a) ? $a : [] as $z) {
                if (is_array($z) && is_string($z['name'] ?? null) && is_int($z['besucher'] ?? null)) {
                    $alle[] = [$z['name'], $z['besucher']];
                }
            }
        }
        usort($alle, static fn(array $x, array $y): int => $y[1] <=> $x[1]);

        return array_slice($alle, 0, 5);
    }

    private static function datum(string $iso): string
    {
        return preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $iso, $m) === 1 ? $m[3] . '.' . $m[2] . '.' . $m[1] : $iso;
    }

    private static function zahl(mixed $w, int $stellen = 0): string
    {
        return number_format(is_numeric($w) ? (float) $w : 0.0, $stellen, ',', '.');
    }

    private static function dauer(mixed $w): string
    {
        $s = (int) round(is_numeric($w) ? (float) $w : 0.0);

        return intdiv($s, 60) . ':' . str_pad((string) ($s % 60), 2, '0', STR_PAD_LEFT);
    }

    private static function delta(mixed $neu, mixed $alt): string
    {
        $n = is_numeric($neu) ? (float) $neu : 0.0;
        $a = is_numeric($alt) ? (float) $alt : 0.0;
        if ($a <= 0) {
            return $n > 0 ? 'neu' : '–';
        }
        $p = round(($n - $a) / $a * 100, 1);

        return $p == 0.0 ? '± 0 %' : ($p > 0 ? '+' : '−') . number_format(abs($p), 1, ',', '.') . ' %';
    }
}
