<?php

declare(strict_types=1);

namespace Pegelstand\Ingest;

/**
 * Zerlegt Seiten- und Referrer-Adressen. Query-Strings werden verworfen, außer den UTM-Parametern.
 */
final class UrlParser
{
    private const MAX_PATH = 1024;
    private const MAX_VALUE = 255;
    private const UTM = ['source', 'medium', 'campaign', 'term', 'content'];

    public function page(string $url, bool $keepHashRoute = true): ?PageTarget
    {
        $teile = parse_url($url);
        if ($teile === false || !in_array(strtolower($teile['scheme'] ?? ''), ['http', 'https'], true) || empty($teile['host'])) {
            return null;
        }
        $pfad = $teile['path'] ?? '/';
        if ($keepHashRoute && isset($teile['fragment']) && preg_match('#^!?/#', $teile['fragment']) === 1) {
            $pfad .= '#' . ltrim($teile['fragment'], '!');
        }
        $pfad = self::cleanPath($pfad);
        if ($pfad === null) {
            return null;
        }

        return new PageTarget(strtolower($teile['host']), $pfad, self::utm($teile['query'] ?? ''));
    }

    /** Hostname des Referrers ohne "www.", oder null bei fehlendem oder ungültigem Wert. */
    public function referrerHost(string $url): ?string
    {
        $teile = parse_url($url);
        if ($teile === false || !in_array(strtolower($teile['scheme'] ?? ''), ['http', 'https'], true) || empty($teile['host'])) {
            return null;
        }
        $host = strtolower(rtrim($teile['host'], '.'));
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        return preg_match('/^[a-z0-9]([a-z0-9.\-]{0,251}[a-z0-9])?$/', $host) === 1 ? $host : null;
    }

    private static function cleanPath(string $pfad): ?string
    {
        if ($pfad === '' || $pfad[0] !== '/') {
            $pfad = '/' . $pfad;
        }
        $dekodiert = rawurldecode($pfad);
        if (preg_match('/[\x00-\x1F\x7F]/', $pfad) === 1 || !mb_check_encoding($pfad, 'UTF-8')) {
            return null;
        }
        // Lesbar speichern (Umlaute), sofern das Ergebnis gültiges UTF-8 ohne Steuerzeichen ist. Sonst bleibt der Pfad kodiert.
        if (mb_check_encoding($dekodiert, 'UTF-8') && preg_match('/[\x00-\x1F\x7F]/', $dekodiert) !== 1) {
            $pfad = $dekodiert;
        }

        return mb_substr($pfad, 0, self::MAX_PATH);
    }

    /**
     * @return array<string, string>
     */
    private static function utm(string $query): array
    {
        if ($query === '' || !str_contains($query, 'utm_')) {
            return [];
        }
        parse_str($query, $parameter);
        $ergebnis = [];
        foreach (self::UTM as $name) {
            $wert = $parameter['utm_' . $name] ?? null;
            if (!is_string($wert)) {
                continue;
            }
            $wert = trim((string) preg_replace('/[\x00-\x1F\x7F]/', '', $wert));
            if ($wert === '' || !mb_check_encoding($wert, 'UTF-8')) {
                continue;
            }
            $wert = mb_substr($wert, 0, self::MAX_VALUE);
            $ergebnis[$name] = in_array($name, ['source', 'medium'], true) ? mb_strtolower($wert) : $wert;
        }

        return $ergebnis;
    }
}
