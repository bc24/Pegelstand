<?php

declare(strict_types=1);

namespace Pegelstand\Geo;

use Throwable;

/**
 * Länder aus einer lokalen MMDB-Datei (DB-IP Lite Country oder MaxMind GeoLite2 Country).
 * Fehler führen nie zu einem Abbruch der Messung, das Land bleibt dann unbekannt.
 */
final class MmdbCountryLookup implements CountryLookup
{
    private ?MmdbReader $leser = null;
    private bool $versucht = false;

    public function __construct(private readonly string $datei) {}

    public function country(string $ip): ?string
    {
        if (!$this->versucht) {
            $this->versucht = true;
            try {
                $this->leser = is_file($this->datei) ? new MmdbReader($this->datei) : null;
            } catch (Throwable) {
                $this->leser = null;
            }
        }
        if ($this->leser === null) {
            return null;
        }
        try {
            $satz = $this->leser->get($ip);
        } catch (Throwable) {
            return null;
        }

        return self::code($satz);
    }

    private static function code(mixed $satz): ?string
    {
        if (!is_array($satz)) {
            return null;
        }
        foreach (['country', 'registered_country'] as $schluessel) {
            $land = $satz[$schluessel] ?? null;
            if (is_array($land) && is_string($land['iso_code'] ?? null)) {
                return self::gueltig($land['iso_code']);
            }
        }

        return is_string($satz['country_code'] ?? null) ? self::gueltig($satz['country_code']) : null;
    }

    private static function gueltig(string $code): ?string
    {
        $code = strtoupper($code);

        return preg_match('/^[A-Z]{2}$/', $code) === 1 ? $code : null;
    }
}
