<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Unit\Geo;

use Pegelstand\Geo\MmdbCountryLookup;
use Pegelstand\Geo\MmdbReader;
use Pegelstand\Tests\Support\MmdbFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MmdbReaderTest extends TestCase
{
    private string $datei = '';

    protected function tearDown(): void
    {
        if ($this->datei !== '' && is_file($this->datei)) {
            unlink($this->datei);
        }
    }

    private function fixture(int $recordSize = 24): string
    {
        $this->datei = tempnam(sys_get_temp_dir(), 'mmdb') ?: '';
        file_put_contents($this->datei, MmdbFixture::build([
            '8.8.0.0/16' => 'US',
            '91.0.0.0/8' => 'DE',
            '92.5.0.0/16' => 'AT',
            '93.0.0.0/8' => 'DE',
        ], $recordSize));

        return $this->datei;
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function recordGroessen(): iterable
    {
        yield '24 Bit' => [24];
        yield '32 Bit' => [32];
    }

    #[DataProvider('recordGroessen')]
    public function testFindetLaender(int $recordSize): void
    {
        $leser = new MmdbReader($this->fixture($recordSize));

        self::assertSame(['country' => ['iso_code' => 'US']], $leser->get('8.8.4.4'));
        self::assertSame(['country' => ['iso_code' => 'DE']], $leser->get('91.200.1.1'));
        self::assertSame(['country' => ['iso_code' => 'AT']], $leser->get('92.5.255.255'));
        self::assertSame(['country' => ['iso_code' => 'DE']], $leser->get('93.1.2.3'));
    }

    public function testUnbekannteUndUngueltigeAdressen(): void
    {
        $leser = new MmdbReader($this->fixture());

        self::assertNull($leser->get('10.0.0.1'));
        self::assertNull($leser->get('92.6.0.1'));
        self::assertNull($leser->get('kaputt'));
        self::assertNull($leser->get('2001:db8::1'), 'IPv6 in einer IPv4-Datenbank.');
    }

    public function testIpv4MappedWirdAlsIpv4Behandelt(): void
    {
        $leser = new MmdbReader($this->fixture());

        self::assertSame(['country' => ['iso_code' => 'US']], $leser->get('::ffff:8.8.8.8'));
    }

    public function testMetadaten(): void
    {
        $leser = new MmdbReader($this->fixture());

        self::assertSame('Test-Country', $leser->metadata()['database_type']);
        self::assertSame(24, $leser->metadata()['record_size']);
    }

    public function testFehlendeDateiWirftAusnahme(): void
    {
        $this->expectException(RuntimeException::class);

        new MmdbReader('/nicht/vorhanden.mmdb');
    }

    public function testKeineMmdbDateiWirftAusnahme(): void
    {
        $this->datei = tempnam(sys_get_temp_dir(), 'mmdb') ?: '';
        file_put_contents($this->datei, 'kein mmdb');

        $this->expectException(RuntimeException::class);

        new MmdbReader($this->datei);
    }

    public function testLandSuche(): void
    {
        $suche = new MmdbCountryLookup($this->fixture());

        self::assertSame('DE', $suche->country('91.1.2.3'));
        self::assertNull($suche->country('10.1.2.3'));
        self::assertNull($suche->country('unsinn'));
    }

    public function testLandSucheOhneDateiLiefertNull(): void
    {
        self::assertNull((new MmdbCountryLookup('/nicht/vorhanden.mmdb'))->country('8.8.8.8'));
    }

    public function testLandSucheMitKaputterDateiLiefertNull(): void
    {
        $this->datei = tempnam(sys_get_temp_dir(), 'mmdb') ?: '';
        file_put_contents($this->datei, 'kein mmdb');

        self::assertNull((new MmdbCountryLookup($this->datei))->country('8.8.8.8'));
    }
}
