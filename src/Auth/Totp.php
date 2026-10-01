<?php

declare(strict_types=1);

namespace Pegelstand\Auth;

/** Zeitbasierte Einmalcodes nach RFC 6238 (SHA-1, 6 Stellen, 30 Sekunden), wie sie gängige Authenticator-Apps nutzen. */
final class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const STEP = 30;

    public static function generateSecret(): string
    {
        return self::base32Encode(random_bytes(20));
    }

    public static function code(string $secret, int $time): string
    {
        $schluessel = self::base32Decode($secret);
        $zaehler = pack('N2', 0, intdiv($time, self::STEP));
        $hash = hash_hmac('sha1', $zaehler, $schluessel, true);
        $offset = ord($hash[19]) & 0x0F;
        $zahl = ((ord($hash[$offset]) & 0x7F) << 24) | (ord($hash[$offset + 1]) << 16) | (ord($hash[$offset + 2]) << 8) | ord($hash[$offset + 3]);

        return str_pad((string) ($zahl % 1_000_000), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Prüft einen Code mit einer Toleranz von einem Zeitfenster davor und danach.
     *
     * @return int|null Das Zeitfenster (Zähler) des Codes, damit sich derselbe Code nicht wiederverwenden lässt; null bei falschem Code
     */
    public static function verify(string $secret, string $eingabe, int $time, int $nichtVorFenster = 0): ?int
    {
        $eingabe = preg_replace('/\s+/', '', $eingabe) ?? '';
        if (preg_match('/^\d{6}$/', $eingabe) !== 1) {
            return null;
        }
        $treffer = null;
        for ($d = -1; $d <= 1; ++$d) {
            $fenster = intdiv($time, self::STEP) + $d;
            if ($fenster > $nichtVorFenster && hash_equals(self::code($secret, $fenster * self::STEP), $eingabe)) {
                $treffer = $fenster;
            }
        }

        return $treffer;
    }

    public static function uri(string $secret, string $account, string $issuer): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $account) . '?secret=' . $secret . '&issuer=' . rawurlencode($issuer) . '&algorithm=SHA1&digits=6&period=30';
    }

    private static function base32Encode(string $daten): string
    {
        $bits = '';
        foreach (str_split($daten) as $b) {
            $bits .= str_pad(decbin(ord($b)), 8, '0', STR_PAD_LEFT);
        }
        $aus = '';
        foreach (str_split($bits, 5) as $block) {
            $aus .= self::ALPHABET[(int) bindec(str_pad($block, 5, '0'))];
        }

        return $aus;
    }

    private static function base32Decode(string $text): string
    {
        $bits = '';
        foreach (str_split(strtoupper(rtrim($text, '='))) as $z) {
            $pos = strpos(self::ALPHABET, $z);
            if ($pos !== false) {
                $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
            }
        }
        $aus = '';
        foreach (str_split($bits, 8) as $block) {
            if (strlen($block) === 8) {
                $aus .= chr((int) bindec($block));
            }
        }

        return $aus;
    }
}
