<?php

declare(strict_types=1);

namespace Pegelstand\Auth;

use RuntimeException;

/** Verschlüsselt Geheimnisse (z. B. 2FA-Schlüssel) mit AES-256-GCM. Der Schlüssel stammt aus `app_key` der Konfiguration. */
final class Crypto
{
    private readonly string $key;

    public function __construct(string $appKey)
    {
        $roh = str_starts_with($appKey, 'base64:') ? base64_decode(substr($appKey, 7), true) : $appKey;
        if ($roh === false || strlen($roh) < 16) {
            throw new RuntimeException('Der app_key in config/config.php ist ungültig.');
        }
        $this->key = hash('sha256', 'pegelstand-encryption|' . $roh, true);
    }

    public function encrypt(string $klartext): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $chiffre = openssl_encrypt($klartext, 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($chiffre === false) {
            throw new RuntimeException('Verschlüsselung fehlgeschlagen.');
        }

        return $iv . $tag . $chiffre;
    }

    public function decrypt(string $daten): ?string
    {
        if (strlen($daten) < 29) {
            return null;
        }
        $klar = openssl_decrypt(substr($daten, 28), 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, substr($daten, 0, 12), substr($daten, 12, 16));

        return $klar === false ? null : $klar;
    }
}
