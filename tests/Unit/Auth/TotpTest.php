<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Unit\Auth;

use Pegelstand\Auth\Crypto;
use Pegelstand\Auth\Totp;
use PHPUnit\Framework\TestCase;

final class TotpTest extends TestCase
{
    /** Testwerte aus RFC 6238 (SHA-1, Schlüssel "12345678901234567890", auf 6 Stellen gekürzt). */
    public function testRfcTestvektoren(): void
    {
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

        self::assertSame('287082', Totp::code($secret, 59));
        self::assertSame('081804', Totp::code($secret, 1111111109));
        self::assertSame('050471', Totp::code($secret, 1111111111));
        self::assertSame('005924', Totp::code($secret, 1234567890));
        self::assertSame('279037', Totp::code($secret, 2000000000));
    }

    public function testPruefungMitToleranzUndEinmalnutzung(): void
    {
        $secret = Totp::generateSecret();
        $jetzt = 1_800_000_000;
        $code = Totp::code($secret, $jetzt);

        self::assertSame(intdiv($jetzt, 30), Totp::verify($secret, $code, $jetzt));
        self::assertSame(intdiv($jetzt, 30), Totp::verify($secret, substr($code, 0, 3) . ' ' . substr($code, 3), $jetzt), 'Leerzeichen sind erlaubt.');
        self::assertNotNull(Totp::verify($secret, $code, $jetzt + 30), 'Ein Fenster später ist noch gültig.');
        self::assertNull(Totp::verify($secret, $code, $jetzt + 90), 'Zwei Fenster später nicht mehr.');
        self::assertNull(Totp::verify($secret, $code, $jetzt, intdiv($jetzt, 30)), 'Ein schon benutzter Code gilt nicht noch einmal.');
        self::assertNull(Totp::verify($secret, 'abcdef', $jetzt));
        self::assertNull(Totp::verify($secret, '12345', $jetzt));
    }

    public function testSecretFormatUndUri(): void
    {
        $secret = Totp::generateSecret();

        self::assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $secret);
        self::assertNotSame($secret, Totp::generateSecret());
        self::assertSame(
            'otpauth://totp/Pegelstand%3Afrank%40beispiel.de?secret=' . $secret . '&issuer=Pegelstand&algorithm=SHA1&digits=6&period=30',
            Totp::uri($secret, 'frank@beispiel.de', 'Pegelstand'),
        );
    }

    public function testVerschluesselung(): void
    {
        $crypto = new Crypto('base64:' . base64_encode(random_bytes(32)));
        $chiffre = $crypto->encrypt('GEHEIM');

        self::assertSame('GEHEIM', $crypto->decrypt($chiffre));
        self::assertNotSame($chiffre, $crypto->encrypt('GEHEIM'), 'Jede Verschlüsselung hat einen eigenen Zufallswert.');
        self::assertStringNotContainsString('GEHEIM', $chiffre);
        self::assertNull($crypto->decrypt(substr($chiffre, 0, -1) . 'x'), 'Manipulierte Daten werden erkannt.');
        self::assertNull($crypto->decrypt('zu kurz'));
        self::assertNull((new Crypto('base64:' . base64_encode(random_bytes(32))))->decrypt($chiffre), 'Anderer Schlüssel.');
    }
}
