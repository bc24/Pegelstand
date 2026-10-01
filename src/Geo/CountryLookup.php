<?php

declare(strict_types=1);

namespace Pegelstand\Geo;

/**
 * Bestimmt das Land zu einer IP-Adresse. Die Adresse wird nur im Arbeitsspeicher verwendet und nie gespeichert.
 */
interface CountryLookup
{
    /** @return string|null ISO-3166-1-Alpha-2-Code in Großbuchstaben, null wenn unbekannt */
    public function country(string $ip): ?string;
}
