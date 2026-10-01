<?php

declare(strict_types=1);

namespace Pegelstand\Geo;

/** Wird genutzt, solange keine GeoIP-Datenbank hinterlegt ist: Länder bleiben unbekannt. */
final class NullCountryLookup implements CountryLookup
{
    public function country(string $ip): ?string
    {
        return null;
    }
}
