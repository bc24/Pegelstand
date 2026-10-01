<?php

declare(strict_types=1);

namespace Pegelstand\Ingest;

use Pegelstand\Core\Request;

/**
 * Ermittelt die Adresse des Besuchers. Hinter einem Reverse-Proxy (nginx, Cloudflare) steht sie in einer
 * Kopfzeile. Die wird nur beachtet, wenn die Verbindung von einem vertrauten Proxy kommt, sonst könnte jeder
 * Besucher seine Adresse fälschen.
 */
final class ClientIp
{
    /**
     * @param list<string> $trusted Adressen oder CIDR-Bereiche vertrauter Proxys
     */
    public function __construct(
        private readonly string $header = '',
        private readonly array $trusted = [],
    ) {}

    public function resolve(Request $request): string
    {
        $direkt = $request->ip;
        if ($this->header === '' || !$this->istVertraut($direkt)) {
            return $direkt;
        }
        $wert = $request->header($this->header);
        if ($wert === '') {
            return $direkt;
        }
        // X-Forwarded-For: von rechts den ersten Eintrag nehmen, der kein vertrauter Proxy ist.
        $eintraege = array_reverse(array_map('trim', explode(',', $wert)));
        foreach ($eintraege as $eintrag) {
            if (IpAddress::normalize($eintrag) !== null && !$this->istVertraut($eintrag)) {
                return $eintrag;
            }
        }

        return $direkt;
    }

    private function istVertraut(string $ip): bool
    {
        foreach ($this->trusted as $bereich) {
            if (IpAddress::inRange($ip, $bereich)) {
                return true;
            }
        }

        return false;
    }
}
