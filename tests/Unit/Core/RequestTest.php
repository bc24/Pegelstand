<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Unit\Core;

use Pegelstand\Core\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    /**
     * @return iterable<string, array{array<string, string>, string, string}>
     */
    public static function pfade(): iterable
    {
        yield 'Wurzel' => [['SCRIPT_NAME' => '/index.php', 'REQUEST_URI' => '/install?x=1'], '/install', ''];
        yield 'Startseite' => [['SCRIPT_NAME' => '/index.php', 'REQUEST_URI' => '/'], '/', ''];
        yield 'Unterverzeichnis' => [['SCRIPT_NAME' => '/pegelstand/index.php', 'REQUEST_URI' => '/pegelstand/install/datenbank'], '/install/datenbank', '/pegelstand'];
        yield 'Unterverzeichnis Wurzel' => [['SCRIPT_NAME' => '/pegelstand/index.php', 'REQUEST_URI' => '/pegelstand/'], '/', '/pegelstand'];
        yield 'index.php im Pfad' => [['SCRIPT_NAME' => '/index.php', 'REQUEST_URI' => '/index.php'], '/', ''];
        yield 'Schrägstrich am Ende' => [['SCRIPT_NAME' => '/index.php', 'REQUEST_URI' => '/install/'], '/install', ''];
        yield 'Kodierte Zeichen' => [['SCRIPT_NAME' => '/index.php', 'REQUEST_URI' => '/a%20b'], '/a b', ''];
    }

    /**
     * @param array<string, string> $server
     */
    #[DataProvider('pfade')]
    public function testPfadUndBasisPfad(array $server, string $pfad, string $basis): void
    {
        $request = Request::fromGlobals($server, [], []);

        self::assertSame($pfad, $request->path);
        self::assertSame($basis, $request->basePath);
    }

    public function testUrlBerücksichtigtUnterverzeichnis(): void
    {
        $request = Request::fromGlobals(['SCRIPT_NAME' => '/pegelstand/index.php', 'REQUEST_URI' => '/pegelstand/'], [], []);

        self::assertSame('/pegelstand/install', $request->url('/install'));
        self::assertSame('/pegelstand/install', $request->url('install'));
    }

    public function testMethodeHttpsUndEingaben(): void
    {
        $request = Request::fromGlobals(
            ['SCRIPT_NAME' => '/index.php', 'REQUEST_URI' => '/', 'REQUEST_METHOD' => 'post', 'HTTPS' => 'on'],
            ['q' => '1', 5 => 'ignoriert'],
            ['name' => 'Frank', 'liste' => ['a']],
        );

        self::assertSame('POST', $request->method);
        self::assertTrue($request->https);
        self::assertSame(['q' => '1'], $request->query);
        self::assertSame('Frank', $request->input('name'));
        self::assertSame('', $request->input('liste'), 'Nicht-Texte werden nicht als Text geliefert.');
        self::assertSame('x', $request->input('fehlt', 'x'));
    }

    public function testHttpsAus(): void
    {
        $request = Request::fromGlobals(['HTTPS' => 'off'], [], []);

        self::assertFalse($request->https);
    }
}
