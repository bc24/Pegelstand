<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Unit\Ingest;

use Pegelstand\Core\Request;
use Pegelstand\Ingest\ClientIp;
use Pegelstand\Ingest\IpAddress;
use PHPUnit\Framework\TestCase;

final class IpAddressTest extends TestCase
{
    public function testNormalisierung(): void
    {
        self::assertSame('192.0.2.7', IpAddress::normalize('192.0.2.7'));
        self::assertSame('192.0.2.7', IpAddress::normalize('::ffff:192.0.2.7'));
        self::assertNull(IpAddress::normalize('kein-ip'));
        self::assertSame(
            IpAddress::normalize('2001:db8:1:2:aaaa:bbbb:cccc:dddd'),
            IpAddress::normalize('2001:db8:1:2:1111:2222:3333:4444'),
            'Innerhalb eines /64 ist das Ergebnis gleich.',
        );
        self::assertNotSame(
            IpAddress::normalize('2001:db8:1:2::1'),
            IpAddress::normalize('2001:db8:1:3::1'),
        );
    }

    public function testBereiche(): void
    {
        self::assertTrue(IpAddress::inRange('192.0.2.7', '192.0.2.7'));
        self::assertTrue(IpAddress::inRange('192.0.2.7', '192.0.2.0/24'));
        self::assertFalse(IpAddress::inRange('192.0.3.7', '192.0.2.0/24'));
        self::assertTrue(IpAddress::inRange('10.20.30.40', '10.16.0.0/12'));
        self::assertFalse(IpAddress::inRange('10.32.0.1', '10.16.0.0/12'));
        self::assertTrue(IpAddress::inRange('2001:db8::5', '2001:db8::/32'));
        self::assertFalse(IpAddress::inRange('2001:db9::5', '2001:db8::/32'));
        self::assertFalse(IpAddress::inRange('192.0.2.7', '2001:db8::/32'), 'Verschiedene Adressfamilien.');
        self::assertFalse(IpAddress::inRange('192.0.2.7', '192.0.2.0/99'));
        self::assertFalse(IpAddress::inRange('unsinn', '192.0.2.0/24'));
    }

    public function testGueltigerBereich(): void
    {
        self::assertTrue(IpAddress::isValidRange('192.0.2.0/24'));
        self::assertTrue(IpAddress::isValidRange('2001:db8::/32'));
        self::assertTrue(IpAddress::isValidRange('192.0.2.1'));
        self::assertFalse(IpAddress::isValidRange('192.0.2.0/33'));
        self::assertFalse(IpAddress::isValidRange('abc'));
    }

    public function testClientIpOhneProxyNutztVerbindungsadresse(): void
    {
        $anfrage = new Request('POST', '/', headers: ['x-forwarded-for' => '203.0.113.9'], ip: '198.51.100.1');

        self::assertSame('198.51.100.1', (new ClientIp())->resolve($anfrage));
    }

    public function testClientIpIgnoriertKopfzeileVonUnvertrautemAbsender(): void
    {
        $anfrage = new Request('POST', '/', headers: ['x-forwarded-for' => '203.0.113.9'], ip: '198.51.100.1');

        self::assertSame('198.51.100.1', (new ClientIp('X-Forwarded-For', ['10.0.0.0/8']))->resolve($anfrage));
    }

    public function testClientIpNutztKopfzeileVonVertrautemProxy(): void
    {
        $anfrage = new Request('POST', '/', headers: ['x-forwarded-for' => '1.2.3.4, 203.0.113.9, 10.0.0.2'], ip: '10.0.0.1');

        self::assertSame('203.0.113.9', (new ClientIp('X-Forwarded-For', ['10.0.0.0/8']))->resolve($anfrage));
    }
}
