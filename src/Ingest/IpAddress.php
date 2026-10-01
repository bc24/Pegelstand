<?php

declare(strict_types=1);

namespace Pegelstand\Ingest;

/**
 * Hilfen für IP-Adressen. Adressen leben nur im Arbeitsspeicher und werden nie gespeichert oder protokolliert.
 */
final class IpAddress
{
    /**
     * Gibt die Adresse in einer Form zurück, die für den Besucher-Hash taugt: IPv4 unverändert,
     * IPv6 auf das /64-Netz gekürzt (Endgeräte wechseln innerhalb davon ständig die Adresse).
     * Liefert null bei ungültiger Eingabe.
     */
    public static function normalize(string $ip): ?string
    {
        $gepackt = self::pack($ip);
        if ($gepackt === null) {
            return null;
        }
        if (strlen($gepackt) === 4) {
            return (string) inet_ntop($gepackt);
        }

        return bin2hex(substr($gepackt, 0, 8)) . '::/64';
    }

    /**
     * Prüft, ob eine Adresse in einem Bereich liegt. Der Bereich ist eine Adresse oder CIDR wie "192.0.2.0/24".
     */
    public static function inRange(string $ip, string $range): bool
    {
        $gepackt = self::pack($ip);
        if ($gepackt === null) {
            return false;
        }
        $teile = explode('/', trim($range), 2);
        $basis = self::pack($teile[0]);
        if ($basis === null || strlen($basis) !== strlen($gepackt)) {
            return false;
        }
        $bits = strlen($basis) * 8;
        if (isset($teile[1])) {
            if (!ctype_digit($teile[1]) || (int) $teile[1] > $bits) {
                return false;
            }
            $bits = (int) $teile[1];
        }
        $ganze = intdiv($bits, 8);
        if (strncmp($gepackt, $basis, $ganze) !== 0) {
            return false;
        }
        $rest = $bits % 8;
        if ($rest === 0) {
            return true;
        }
        $maske = (0xFF << (8 - $rest)) & 0xFF;

        return (ord($gepackt[$ganze]) & $maske) === (ord($basis[$ganze]) & $maske);
    }

    /** Gültige Adresse oder gültiger CIDR-Bereich. */
    public static function isValidRange(string $range): bool
    {
        $teile = explode('/', trim($range), 2);
        $basis = self::pack($teile[0]);
        if ($basis === null) {
            return false;
        }

        return !isset($teile[1]) || (ctype_digit($teile[1]) && (int) $teile[1] <= strlen($basis) * 8);
    }

    /** Binäre Form (4 oder 16 Byte). IPv4-in-IPv6 (::ffff:a.b.c.d) wird zu IPv4. */
    private static function pack(string $ip): ?string
    {
        $gepackt = @inet_pton(trim($ip));
        if ($gepackt === false) {
            return null;
        }
        if (strlen($gepackt) === 16 && str_starts_with($gepackt, str_repeat("\0", 10) . "\xFF\xFF")) {
            return substr($gepackt, 12);
        }

        return $gepackt;
    }
}
