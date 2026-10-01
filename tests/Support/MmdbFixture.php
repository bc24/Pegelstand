<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Support;

/**
 * Erzeugt kleine MMDB-Dateien (nur IPv4, Datensätze der Form {"country": {"iso_code": "XX"}}) für Tests.
 */
final class MmdbFixture
{
    /**
     * @param array<string, string> $netze CIDR (ohne Überlappung) => Ländercode
     */
    public static function build(array $netze, int $recordSize = 24): string
    {
        $daten = '';
        $offsets = [];
        $knoten = [[null, null]];
        foreach ($netze as $cidr => $land) {
            [$ip, $laenge] = explode('/', $cidr);
            $offsets[$land] ??= strlen($daten);
            if ($offsets[$land] === strlen($daten)) {
                $daten .= self::map(['country' => self::map(['iso_code' => self::string($land)])]);
            }
            $bits = (int) $laenge;
            $gepackt = (string) inet_pton($ip);
            $index = 0;
            for ($i = 0; $i < $bits; ++$i) {
                $bit = (ord($gepackt[$i >> 3]) >> (7 - ($i & 7))) & 1;
                if ($i === $bits - 1) {
                    $knoten[$index][$bit] = ['d', $offsets[$land]];
                    break;
                }
                if (!is_int($knoten[$index][$bit])) {
                    $knoten[] = [null, null];
                    $knoten[$index][$bit] = count($knoten) - 1;
                }
                $index = $knoten[$index][$bit];
            }
        }

        $anzahl = count($knoten);
        $baum = '';
        foreach ($knoten as $paar) {
            $werte = array_map(static fn(mixed $r): int => match (true) {
                $r === null => $anzahl,
                is_int($r) => $r,
                default => $anzahl + 16 + $r[1],
            }, $paar);
            $baum .= $recordSize === 24
                ? substr(pack('N', $werte[0]), 1) . substr(pack('N', $werte[1]), 1)
                : pack('N', $werte[0]) . pack('N', $werte[1]);
        }

        $metadaten = self::map([
            'node_count' => self::uint(6, $anzahl),
            'record_size' => self::uint(5, $recordSize),
            'ip_version' => self::uint(5, 4),
            'database_type' => self::string('Test-Country'),
            'binary_format_major_version' => self::uint(5, 2),
            'binary_format_minor_version' => self::uint(5, 0),
        ]);

        return $baum . str_repeat("\0", 16) . $daten . "\xAB\xCD\xEFMaxMind.com" . $metadaten;
    }

    private static function string(string $text): string
    {
        return chr(0x40 | strlen($text)) . $text;
    }

    private static function uint(int $typ, int $wert): string
    {
        $bytes = $wert === 0 ? '' : ltrim(pack('N', $wert), "\0");

        return chr(($typ << 5) | strlen($bytes)) . $bytes;
    }

    /**
     * @param array<string, string> $felder bereits kodierte Werte
     */
    private static function map(array $felder): string
    {
        $ausgabe = chr((7 << 5) | count($felder));
        foreach ($felder as $schluessel => $wert) {
            $ausgabe .= self::string($schluessel) . $wert;
        }

        return $ausgabe;
    }
}
