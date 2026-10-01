<?php

declare(strict_types=1);

namespace Pegelstand\Geo;

use RuntimeException;

/**
 * Liest Dateien im MaxMind-DB-Format (.mmdb), wie sie MaxMind (GeoLite2) und DB-IP veröffentlichen.
 * Umgesetzt nach der offiziellen Spezifikation (Version 2.0). Die Datei wird nicht komplett geladen,
 * jede Abfrage liest nur die nötigen Bytes.
 */
final class MmdbReader
{
    private const METADATA_MARKER = "\xAB\xCD\xEFMaxMind.com";
    private const MAX_METADATA_BYTES = 131072;
    private const DATA_SEPARATOR = 16;
    private const MAX_DEPTH = 32;

    /** @var resource */
    private $handle;

    private int $nodeCount;
    private int $recordSize;
    private int $ipVersion;
    private int $searchTreeSize;
    private ?int $ipv4Start = null;

    /** @var array<string, mixed> */
    private array $metadata;

    /**
     * @throws RuntimeException wenn die Datei fehlt oder kein gültiges MMDB-Format hat
     */
    public function __construct(private readonly string $file)
    {
        $handle = @fopen($file, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Die GeoIP-Datenbank konnte nicht geöffnet werden.');
        }
        $this->handle = $handle;

        $groesse = (int) (fstat($handle)['size'] ?? 0);
        $ende = min($groesse, self::MAX_METADATA_BYTES);
        $ende > 0 && fseek($handle, $groesse - $ende);
        $schluss = $ende > 0 ? (string) fread($handle, $ende) : '';
        $position = strrpos($schluss, self::METADATA_MARKER);
        if ($position === false) {
            throw new RuntimeException('Die Datei ist keine gültige MMDB-Datenbank (Metadaten fehlen).');
        }
        $metadatenStart = $groesse - $ende + $position + strlen(self::METADATA_MARKER);
        [$metadaten] = $this->decode($metadatenStart, 0, 0);
        if (!is_array($metadaten)) {
            throw new RuntimeException('Die Metadaten der GeoIP-Datenbank sind ungültig.');
        }
        $this->metadata = [];
        foreach ($metadaten as $schluessel => $wert) {
            $this->metadata[(string) $schluessel] = $wert;
        }
        $this->nodeCount = self::zahl($this->metadata['node_count'] ?? null);
        $this->recordSize = self::zahl($this->metadata['record_size'] ?? null);
        $this->ipVersion = self::zahl($this->metadata['ip_version'] ?? null);
        if (!in_array($this->recordSize, [24, 28, 32], true) || !in_array($this->ipVersion, [4, 6], true) || $this->nodeCount < 1) {
            throw new RuntimeException('Dieses MMDB-Format wird nicht unterstützt.');
        }
        $this->searchTreeSize = intdiv($this->recordSize * 2, 8) * $this->nodeCount;
    }

    public function __destruct()
    {
        fclose($this->handle);
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }

    /**
     * Sucht den Datensatz zu einer IP-Adresse. Liefert null, wenn es keinen gibt oder die Adresse ungültig ist.
     */
    public function get(string $ip): mixed
    {
        $gepackt = @inet_pton($ip);
        if ($gepackt === false) {
            return null;
        }
        // IPv4-mapped-Adressen (::ffff:a.b.c.d) wie IPv4 behandeln.
        if (strlen($gepackt) === 16 && str_starts_with($gepackt, str_repeat("\0", 10) . "\xFF\xFF")) {
            $gepackt = substr($gepackt, 12);
        }
        $bits = strlen($gepackt) * 8;
        if ($bits === 128 && $this->ipVersion === 4) {
            return null;
        }

        $knoten = $bits === 32 && $this->ipVersion === 6 ? $this->ipv4Start() : 0;
        for ($i = 0; $i < $bits && $knoten < $this->nodeCount; ++$i) {
            $bit = (ord($gepackt[$i >> 3]) >> (7 - ($i & 7))) & 1;
            $knoten = $this->record($knoten, $bit);
        }
        if ($knoten === $this->nodeCount) {
            return null;
        }
        if ($knoten < $this->nodeCount) {
            throw new RuntimeException('Der Suchbaum der GeoIP-Datenbank ist beschädigt.');
        }

        $offset = $knoten - $this->nodeCount - self::DATA_SEPARATOR;
        [$wert] = $this->decode($this->searchTreeSize + self::DATA_SEPARATOR, $offset, 0);

        return $wert;
    }

    private function ipv4Start(): int
    {
        if ($this->ipv4Start === null) {
            $knoten = 0;
            for ($i = 0; $i < 96 && $knoten < $this->nodeCount; ++$i) {
                $knoten = $this->record($knoten, 0);
            }
            $this->ipv4Start = $knoten;
        }

        return $this->ipv4Start;
    }

    private function record(int $knoten, int $seite): int
    {
        $knotenBytes = intdiv($this->recordSize, 4);
        $bytes = $this->lese($knoten * $knotenBytes, $knotenBytes);

        return match ($this->recordSize) {
            24 => self::ganzzahl(substr($bytes, $seite * 3, 3)),
            28 => $seite === 0
                ? ((ord($bytes[3]) & 0xF0) << 20) | self::ganzzahl(substr($bytes, 0, 3))
                : ((ord($bytes[3]) & 0x0F) << 24) | self::ganzzahl(substr($bytes, 4, 3)),
            default => self::ganzzahl(substr($bytes, $seite * 4, 4)),
        };
    }

    /**
     * Dekodiert ein Datenfeld.
     *
     * @param int $basis Dateiposition, auf die sich Offsets und Zeiger beziehen
     * @return array{0: mixed, 1: int} Wert und Offset hinter dem Feld
     */
    private function decode(int $basis, int $offset, int $tiefe): array
    {
        if ($tiefe > self::MAX_DEPTH) {
            throw new RuntimeException('Die GeoIP-Datenbank ist zu tief verschachtelt.');
        }
        $steuer = ord($this->lese($basis + $offset, 1));
        ++$offset;
        $typ = $steuer >> 5;

        if ($typ === 1) {
            $groesse = ($steuer >> 3) & 3;
            $rest = $steuer & 7;
            $bytes = $this->lese($basis + $offset, $groesse + 1);
            $offset += $groesse + 1;
            $zeiger = match ($groesse) {
                0 => ($rest << 8) | self::ganzzahl($bytes),
                1 => (($rest << 16) | self::ganzzahl($bytes)) + 2048,
                2 => (($rest << 24) | self::ganzzahl($bytes)) + 526336,
                default => self::ganzzahl($bytes),
            };
            [$wert] = $this->decode($basis, $zeiger, $tiefe + 1);

            return [$wert, $offset];
        }

        if ($typ === 0) {
            $typ = 7 + ord($this->lese($basis + $offset, 1));
            ++$offset;
        }

        $groesse = $steuer & 31;
        if ($groesse >= 29) {
            $extra = $groesse - 28;
            $zahl = self::ganzzahl($this->lese($basis + $offset, $extra));
            $offset += $extra;
            $groesse = match ($extra) {
                1 => 29 + $zahl,
                2 => 285 + $zahl,
                default => 65821 + $zahl,
            };
        }

        switch ($typ) {
            case 2:
                $text = $this->lese($basis + $offset, $groesse);

                return [$text, $offset + $groesse];
            case 4:
                return [$this->lese($basis + $offset, $groesse), $offset + $groesse];
            case 3:
                return [unpack('E', $this->lese($basis + $offset, 8))[1] ?? 0.0, $offset + $groesse];
            case 15:
                return [unpack('G', $this->lese($basis + $offset, 4))[1] ?? 0.0, $offset + $groesse];
            case 5:
            case 6:
            case 9:
                return [self::ganzzahl($this->lese($basis + $offset, $groesse)), $offset + $groesse];
            case 8:
                $wert = self::ganzzahl($this->lese($basis + $offset, $groesse));
                if ($groesse === 4 && $wert >= 0x80000000) {
                    $wert -= 0x100000000;
                }

                return [$wert, $offset + $groesse];
            case 10:
                return [bin2hex($this->lese($basis + $offset, $groesse)), $offset + $groesse];
            case 14:
                return [$groesse === 1, $offset];
            case 7:
                $karte = [];
                for ($i = 0; $i < $groesse; ++$i) {
                    [$schluessel, $offset] = $this->decode($basis, $offset, $tiefe + 1);
                    [$wert, $offset] = $this->decode($basis, $offset, $tiefe + 1);
                    if (is_string($schluessel)) {
                        $karte[$schluessel] = $wert;
                    }
                }

                return [$karte, $offset];
            case 11:
                $liste = [];
                for ($i = 0; $i < $groesse; ++$i) {
                    [$wert, $offset] = $this->decode($basis, $offset, $tiefe + 1);
                    $liste[] = $wert;
                }

                return [$liste, $offset];
            default:
                throw new RuntimeException(sprintf('Unbekannter Datentyp %d in der GeoIP-Datenbank.', $typ));
        }
    }

    private function lese(int $position, int $laenge): string
    {
        if ($laenge < 1) {
            return '';
        }
        if (fseek($this->handle, $position) !== 0) {
            throw new RuntimeException('Die GeoIP-Datenbank ist abgeschnitten.');
        }
        $daten = fread($this->handle, $laenge);
        if ($daten === false || strlen($daten) !== $laenge) {
            throw new RuntimeException('Die GeoIP-Datenbank ist abgeschnitten.');
        }

        return $daten;
    }

    private static function ganzzahl(string $bytes): int
    {
        $wert = 0;
        foreach (str_split($bytes) as $byte) {
            $wert = ($wert << 8) | ord($byte);
        }

        return $wert;
    }

    private static function zahl(mixed $wert): int
    {
        return is_int($wert) ? $wert : 0;
    }
}
