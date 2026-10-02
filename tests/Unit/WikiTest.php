<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Unit;

use Pegelstand\Tests\Support\WikiBuilder;
use PHPUnit\Framework\TestCase;

final class WikiTest extends TestCase
{
    public function testGenerierteSeitenSindAktuell(): void
    {
        $wurzel = dirname(__DIR__, 2);
        foreach ((new WikiBuilder($wurzel))->build() as $datei => $inhalt) {
            self::assertSame($inhalt, file_get_contents($wurzel . '/wiki/' . $datei), $datei . ' veraltet: php bin/build-wiki.php');
        }
    }
}
